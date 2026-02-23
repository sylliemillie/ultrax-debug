<?php
/**
 * LOIQ Agent Child Theme Endpoints
 *
 * Read, append, remove, and list tagged code blocks in the child theme's
 * functions.php. Uses tagged block format for safe management.
 *
 * Tagged block format:
 *   // === LOIQ-AGENT:{name} START ===
 *   // Deployed: {date} | Session: {session_id}
 *   {code}
 *   // === LOIQ-AGENT:{name} END ===
 *
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class LOIQ_Agent_Child_Theme {

    /** Maximum functions.php file size after append (100KB) */
    const MAX_FILE_SIZE = 102400;

    /** Maximum lines to return for full file read */
    const MAX_READ_LINES = 5000;

    /**
     * Register all v3 child-theme REST routes.
     * Called from main plugin class via rest_api_init.
     *
     * @param LOIQ_WP_Agent $plugin  Reference to main plugin for permission callbacks
     */
    public static function register_routes($plugin) {
        $namespace = 'claude/v3';

        // --- CHILD THEME ENDPOINTS ---

        register_rest_route($namespace, '/child-theme/functions/read', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_functions_read'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [
                'block' => ['required' => false, 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);

        register_rest_route($namespace, '/child-theme/functions/append', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_functions_append'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'name'    => ['required' => true,  'type' => 'string', 'sanitize_callback' => 'sanitize_file_name'],
                'code'    => ['required' => true,  'type' => 'string'],
                'dry_run' => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/child-theme/functions/remove', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_functions_remove'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'name'    => ['required' => true,  'type' => 'string', 'sanitize_callback' => 'sanitize_file_name'],
                'dry_run' => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/child-theme/functions/list', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_functions_list'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [],
        ]);
    }

    // =========================================================================
    // HANDLERS
    // =========================================================================

    /**
     * GET /claude/v3/child-theme/functions/read
     *
     * Read functions.php content, optionally filtered to a specific tagged block.
     */
    public static function handle_functions_read(WP_REST_Request $request) {
        $block_name = $request->get_param('block');

        $functions_file = self::get_functions_path();
        if (is_wp_error($functions_file)) {
            return $functions_file;
        }

        $content = file_get_contents($functions_file);
        if ($content === false) {
            return new WP_Error('read_failed', 'Kan functions.php niet lezen', ['status' => 500]);
        }

        // If specific block requested, extract only that block
        if (!empty($block_name)) {
            $block = self::extract_block($content, $block_name);
            if ($block === null) {
                return new WP_Error('block_not_found',
                    "Block '{$block_name}' niet gevonden in functions.php",
                    ['status' => 404]
                );
            }

            return [
                'success'    => true,
                'block_name' => $block_name,
                'content'    => $block['content'],
                'line_start' => $block['line_start'],
                'line_end'   => $block['line_end'],
            ];
        }

        // Return full file, capped at MAX_READ_LINES
        $lines = explode("\n", $content);
        $total_lines = count($lines);
        $capped = $total_lines > self::MAX_READ_LINES;

        if ($capped) {
            $content = implode("\n", array_slice($lines, 0, self::MAX_READ_LINES));
        }

        return [
            'success'     => true,
            'file'        => 'functions.php',
            'content'     => $content,
            'total_lines' => $total_lines,
            'capped'      => $capped,
            'file_size'   => strlen($content),
        ];
    }

    /**
     * POST /claude/v3/child-theme/functions/append
     *
     * Append a tagged code block to functions.php.
     */
    public static function handle_functions_append(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('child_theme')) {
            return new WP_Error('power_mode_off', "Power mode voor 'child_theme' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $name    = $request->get_param('name');
        $code    = $request->get_param('code');
        $dry_run = (bool) $request->get_param('dry_run');
        $session = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        $functions_file = self::get_functions_path();
        if (is_wp_error($functions_file)) {
            return $functions_file;
        }

        // Read current content (before state)
        $before = file_get_contents($functions_file);
        if ($before === false) {
            return new WP_Error('read_failed', 'Kan functions.php niet lezen', ['status' => 500]);
        }

        // Check if block name already exists
        $existing = self::extract_block($before, $name);
        if ($existing !== null) {
            return new WP_Error('block_exists',
                "Block '{$name}' bestaat al in functions.php (regel {$existing['line_start']}-{$existing['line_end']}). Verwijder eerst met /functions/remove.",
                ['status' => 409]
            );
        }

        // PHP syntax check: write temp file and run php -l
        $syntax_check = self::check_php_syntax($code);
        if (is_wp_error($syntax_check)) {
            return $syntax_check;
        }

        // Scan for dangerous patterns (reuse snippet scanner approach)
        $scan_result = LOIQ_Agent_Safeguards::scan_snippet($code);
        if (is_wp_error($scan_result)) {
            return $scan_result;
        }

        // Build tagged block
        $deployed_date = current_time('mysql');
        $tagged_block = self::build_tagged_block($name, $code, $deployed_date, $session);

        // Calculate new content
        $after = rtrim($before) . "\n\n" . $tagged_block . "\n";

        // Max file size check
        if (strlen($after) > self::MAX_FILE_SIZE) {
            return new WP_Error('file_too_large',
                'functions.php zou ' . size_format(strlen($after)) . ' worden na append (max ' . size_format(self::MAX_FILE_SIZE) . ')',
                ['status' => 400]
            );
        }

        // Create snapshot
        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('child_theme', $name, $before, null, false, $session);

        // Audit log
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'child_theme', $name, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'       => true,
                'dry_run'       => true,
                'snapshot_id'   => $snapshot_id,
                'block_name'    => $name,
                'code_lines'    => substr_count($code, "\n") + 1,
                'before_size'   => strlen($before),
                'after_size'    => strlen($after),
                'syntax_check'  => 'passed',
                'safety_scan'   => 'passed',
            ];
        }

        // Check writability
        if (!is_writable($functions_file)) {
            return new WP_Error('not_writable', 'functions.php is niet schrijfbaar', ['status' => 500]);
        }

        // Write new content
        if (file_put_contents($functions_file, $after) === false) {
            return new WP_Error('write_failed', 'Kan niet schrijven naar functions.php', ['status' => 500]);
        }

        // Mark snapshot as executed
        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $after);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'block_name'   => $name,
            'code_lines'   => substr_count($code, "\n") + 1,
            'before_size'  => strlen($before),
            'after_size'   => strlen($after),
            'syntax_check' => 'passed',
            'safety_scan'  => 'passed',
            'rollback_url' => rest_url('claude/v3/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    /**
     * POST /claude/v3/child-theme/functions/remove
     *
     * Remove a tagged code block from functions.php.
     */
    public static function handle_functions_remove(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('child_theme')) {
            return new WP_Error('power_mode_off', "Power mode voor 'child_theme' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $name    = $request->get_param('name');
        $dry_run = (bool) $request->get_param('dry_run');
        $session = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        $functions_file = self::get_functions_path();
        if (is_wp_error($functions_file)) {
            return $functions_file;
        }

        // Read current content
        $before = file_get_contents($functions_file);
        if ($before === false) {
            return new WP_Error('read_failed', 'Kan functions.php niet lezen', ['status' => 500]);
        }

        // Find the block
        $block = self::extract_block($before, $name);
        if ($block === null) {
            return new WP_Error('block_not_found',
                "Block '{$name}' niet gevonden in functions.php",
                ['status' => 404]
            );
        }

        // Remove the block (including surrounding blank lines)
        $after = self::remove_block($before, $name);

        // Create snapshot
        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('child_theme', $name, $before, null, false, $session);

        // Audit log
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'child_theme', $name, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'      => true,
                'dry_run'      => true,
                'snapshot_id'  => $snapshot_id,
                'block_name'   => $name,
                'removed_lines'=> $block['line_end'] - $block['line_start'] + 1,
                'before_size'  => strlen($before),
                'after_size'   => strlen($after),
            ];
        }

        // Check writability
        if (!is_writable($functions_file)) {
            return new WP_Error('not_writable', 'functions.php is niet schrijfbaar', ['status' => 500]);
        }

        // Write new content
        if (file_put_contents($functions_file, $after) === false) {
            return new WP_Error('write_failed', 'Kan niet schrijven naar functions.php', ['status' => 500]);
        }

        // Mark snapshot as executed
        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $after);

        return [
            'success'       => true,
            'snapshot_id'   => $snapshot_id,
            'block_name'    => $name,
            'removed_lines' => $block['line_end'] - $block['line_start'] + 1,
            'before_size'   => strlen($before),
            'after_size'    => strlen($after),
            'rollback_url'  => rest_url('claude/v3/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    /**
     * GET /claude/v3/child-theme/functions/list
     *
     * List all LOIQ-AGENT tagged blocks in functions.php.
     */
    public static function handle_functions_list(WP_REST_Request $request) {
        $functions_file = self::get_functions_path();
        if (is_wp_error($functions_file)) {
            return $functions_file;
        }

        $content = file_get_contents($functions_file);
        if ($content === false) {
            return new WP_Error('read_failed', 'Kan functions.php niet lezen', ['status' => 500]);
        }

        $blocks = self::parse_all_blocks($content);

        return [
            'success' => true,
            'file'    => 'functions.php',
            'blocks'  => $blocks,
            'total'   => count($blocks),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Get the path to the child theme's functions.php.
     *
     * @return string|WP_Error
     */
    private static function get_functions_path() {
        $functions_file = get_stylesheet_directory() . '/functions.php';

        if (!file_exists($functions_file)) {
            return new WP_Error('no_functions_php',
                'Child theme functions.php niet gevonden: ' . get_stylesheet_directory(),
                ['status' => 404]
            );
        }

        return $functions_file;
    }

    /**
     * Build a tagged block string.
     *
     * @param string $name
     * @param string $code
     * @param string $deployed_date
     * @param string $session_id
     * @return string
     */
    private static function build_tagged_block($name, $code, $deployed_date, $session_id) {
        $lines = [];
        $lines[] = '// === LOIQ-AGENT:' . $name . ' START ===';
        $lines[] = '// Deployed: ' . $deployed_date . ' | Session: ' . $session_id;
        $lines[] = $code;
        $lines[] = '// === LOIQ-AGENT:' . $name . ' END ===';

        return implode("\n", $lines);
    }

    /**
     * Extract a specific tagged block from content.
     *
     * @param string $content
     * @param string $name
     * @return array|null  Array with 'content', 'line_start', 'line_end' or null if not found
     */
    private static function extract_block($content, $name) {
        $escaped_name = preg_quote($name, '/');
        $pattern = '/^\/\/ === LOIQ-AGENT:' . $escaped_name . ' START ===$/m';

        if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $start_offset = $matches[0][1];
        $start_tag = '// === LOIQ-AGENT:' . $name . ' START ===';
        $end_tag   = '// === LOIQ-AGENT:' . $name . ' END ===';

        $end_pos = strpos($content, $end_tag, $start_offset);
        if ($end_pos === false) {
            return null;
        }

        // Calculate line numbers
        $before_start = substr($content, 0, $start_offset);
        $line_start = substr_count($before_start, "\n") + 1;

        $block_end_offset = $end_pos + strlen($end_tag);
        $block_content = substr($content, $start_offset, $block_end_offset - $start_offset);
        $line_end = $line_start + substr_count($block_content, "\n");

        // Extract the code between the tags (skip START line and metadata line)
        $block_lines = explode("\n", $block_content);
        // Remove first line (START tag), second line (metadata), and last line (END tag)
        $code_lines = array_slice($block_lines, 2, -1);
        $code_content = implode("\n", $code_lines);

        return [
            'content'    => $code_content,
            'full_block' => $block_content,
            'line_start' => $line_start,
            'line_end'   => $line_end,
        ];
    }

    /**
     * Remove a tagged block from content.
     *
     * @param string $content
     * @param string $name
     * @return string  Content with block removed
     */
    private static function remove_block($content, $name) {
        $escaped_name = preg_quote($name, '/');
        // Match the full block including optional surrounding blank lines
        $pattern = '/\n?\n?\/\/ === LOIQ-AGENT:' . $escaped_name . ' START ===\n.*?\/\/ === LOIQ-AGENT:' . $escaped_name . ' END ===\n?/s';

        $result = preg_replace($pattern, "\n", $content);

        // Clean up multiple consecutive blank lines
        $result = preg_replace('/\n{3,}/', "\n\n", $result);

        return $result;
    }

    /**
     * Parse all tagged blocks from functions.php content.
     *
     * @param string $content
     * @return array  Array of block info objects
     */
    private static function parse_all_blocks($content) {
        $blocks = [];
        $lines = explode("\n", $content);
        $total_lines = count($lines);

        for ($i = 0; $i < $total_lines; $i++) {
            $line = $lines[$i];

            // Match START tag
            if (preg_match('/^\/\/ === LOIQ-AGENT:(.+?) START ===$/', $line, $matches)) {
                $block_name = $matches[1];
                $line_start = $i + 1; // 1-indexed

                // Parse metadata from next line
                $deployed_date = '';
                $session_id = '';
                if (isset($lines[$i + 1]) && preg_match('/^\/\/ Deployed: (.+?) \| Session: (.*)$/', $lines[$i + 1], $meta_matches)) {
                    $deployed_date = $meta_matches[1];
                    $session_id = $meta_matches[2];
                }

                // Find END tag
                $line_end = $line_start;
                for ($j = $i + 1; $j < $total_lines; $j++) {
                    if (preg_match('/^\/\/ === LOIQ-AGENT:' . preg_quote($block_name, '/') . ' END ===$/', $lines[$j])) {
                        $line_end = $j + 1; // 1-indexed
                        break;
                    }
                }

                $blocks[] = [
                    'name'          => $block_name,
                    'line_start'    => $line_start,
                    'line_end'      => $line_end,
                    'deployed_date' => $deployed_date,
                    'session_id'    => $session_id,
                ];

                // Skip past this block
                $i = $line_end - 1; // Will be incremented by for loop
            }
        }

        return $blocks;
    }

    /**
     * Check PHP syntax by writing a temp file and running php -l.
     *
     * @param string $code  The PHP code to check (without <?php tag)
     * @return true|WP_Error
     */
    private static function check_php_syntax($code) {
        $full = "<?php\nif (!defined('ABSPATH')) exit;\n" . $code;
        $tmp = tempnam(sys_get_temp_dir(), 'loiq_ct_');

        if (!$tmp) {
            return true; // Skip if temp file fails -- don't block on infra issues
        }

        file_put_contents($tmp, $full);
        $output = [];
        $return_code = 0;
        exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $output, $return_code);
        @unlink($tmp);

        if ($return_code !== 0) {
            $error_msg = implode(' ', $output);
            // Strip temp file path from error
            $error_msg = str_replace($tmp, 'functions.php', $error_msg);
            return new WP_Error('syntax_error',
                'PHP syntax fout: ' . $error_msg,
                ['status' => 400]
            );
        }

        return true;
    }
}
