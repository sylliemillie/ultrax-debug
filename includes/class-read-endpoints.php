<?php
/**
 * LOIQ Agent Read Endpoints
 *
 * All v1 read endpoint handlers: status, errors, plugins, theme,
 * database, code-context, styles, forms, page.
 *
 * @since 3.1.0 — extracted from main plugin file
 */

if (!defined('ABSPATH')) exit;

class LOIQ_Agent_Read_Endpoints {

    /**
     * Register all v1 read routes.
     *
     * @param LOIQ_WP_Agent $plugin Main plugin instance (for permission callbacks).
     */
    public static function register_routes($plugin) {
        $namespace = 'claude/v1';

        register_rest_route($namespace, '/status', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_status'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [],
        ]);

        register_rest_route($namespace, '/errors', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_errors'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [
                'lines' => [
                    'required'          => false,
                    'type'              => 'integer',
                    'default'           => 50,
                    'minimum'           => 1,
                    'maximum'           => 500,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route($namespace, '/plugins', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_plugins'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [],
        ]);

        register_rest_route($namespace, '/theme', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_theme'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [],
        ]);

        register_rest_route($namespace, '/database', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_database'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [],
        ]);

        register_rest_route($namespace, '/code-context', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_code_context'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [
                'topic' => [
                    'required'          => false,
                    'type'              => 'string',
                    'default'           => 'general',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        $allowed = ['gravity-forms', 'woocommerce', 'divi', 'acf', 'custom-post-types', 'rest-api', 'cron', 'general'];
                        return in_array($param, $allowed, true);
                    },
                ],
            ],
        ]);

        register_rest_route($namespace, '/styles', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_styles'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [],
        ]);

        register_rest_route($namespace, '/forms', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_forms_detail'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [
                'id' => [
                    'required'          => false,
                    'type'              => 'integer',
                    'default'           => 0,
                    'minimum'           => 0,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route($namespace, '/page', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_page_context'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [
                'url' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'id' => [
                    'required'          => false,
                    'type'              => 'integer',
                    'default'           => 0,
                    'minimum'           => 0,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
    }

    // =========================================================================
    // ENDPOINT HANDLERS
    // =========================================================================

    /**
     * GET /claude/v1/status — Site health info
     */
    public static function get_status() {
        global $wpdb;

        return rest_ensure_response([
            'site_url'       => get_site_url(),
            'wp_version'     => get_bloginfo('version'),
            'php_version'    => PHP_VERSION,
            'mysql_version'  => $wpdb->db_version(),
            'memory_limit'   => WP_MEMORY_LIMIT,
            'debug_mode'     => WP_DEBUG,
            'debug_log'      => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'multisite'      => is_multisite(),
            'ssl'            => is_ssl(),
            'timezone'       => wp_timezone_string(),
            'active_plugins' => count(get_option('active_plugins', [])),
            'post_count'     => wp_count_posts()->publish ?? 0,
            'page_count'     => wp_count_posts('page')->publish ?? 0,
            'user_count'     => count_users()['total_users'] ?? 0,
        ]);
    }

    /**
     * GET /claude/v1/errors — Last N lines from debug.log
     */
    public static function get_errors(WP_REST_Request $request) {
        $lines = (int) $request->get_param('lines') ?: 50;
        $lines = min($lines, 500);

        $log_file = WP_CONTENT_DIR . '/debug.log';

        if (!file_exists($log_file)) {
            return rest_ensure_response([
                'exists'  => false,
                'message' => 'debug.log niet gevonden. Zet WP_DEBUG_LOG aan in wp-config.php',
            ]);
        }

        $file = new SplFileObject($log_file, 'r');
        $file->seek(PHP_INT_MAX);
        $total_lines = $file->key();

        $start = max(0, $total_lines - $lines);
        $file->seek($start);

        $log_lines = [];
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $log_lines[] = $line;
            }
            $file->next();
        }

        return rest_ensure_response([
            'exists'      => true,
            'total_lines' => $total_lines,
            'returned'    => count($log_lines),
            'lines'       => $log_lines,
        ]);
    }

    /**
     * GET /claude/v1/plugins — Plugin list with update status
     */
    public static function get_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);

        $updates = get_site_transient('update_plugins');
        $update_list = $updates->response ?? [];

        $result = [];
        foreach ($all_plugins as $file => $data) {
            $result[] = [
                'file'        => $file,
                'name'        => $data['Name'],
                'version'     => $data['Version'],
                'active'      => in_array($file, $active_plugins),
                'update'      => isset($update_list[$file]) ? $update_list[$file]->new_version : null,
                'author'      => strip_tags($data['Author']),
                'requires_wp' => $data['RequiresWP'] ?? null,
                'requires_php'=> $data['RequiresPHP'] ?? null,
            ];
        }

        return rest_ensure_response([
            'total'  => count($result),
            'active' => count($active_plugins),
            'updates'=> count($update_list),
            'plugins'=> $result,
        ]);
    }

    /**
     * GET /claude/v1/theme — Active theme info
     */
    public static function get_theme() {
        $theme = wp_get_theme();
        $parent = $theme->parent();

        $updates = get_site_transient('update_themes');
        $update_available = isset($updates->response[$theme->get_stylesheet()]);

        return rest_ensure_response([
            'name'        => $theme->get('Name'),
            'version'     => $theme->get('Version'),
            'author'      => $theme->get('Author'),
            'template'    => $theme->get_template(),
            'stylesheet'  => $theme->get_stylesheet(),
            'is_child'    => $theme->parent() !== false,
            'parent'      => $parent ? [
                'name'    => $parent->get('Name'),
                'version' => $parent->get('Version'),
            ] : null,
            'update'      => $update_available ? $updates->response[$theme->get_stylesheet()]['new_version'] : null,
        ]);
    }

    /**
     * GET /claude/v1/database — Database schema info (NO data!)
     */
    public static function get_database() {
        global $wpdb;

        $tables = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME LIKE %s",
                DB_NAME,
                $wpdb->prefix . '%'
            )
        );

        $result = [];
        $total_size = 0;

        foreach ($tables as $table) {
            $size = (int) $table->DATA_LENGTH + (int) $table->INDEX_LENGTH;
            $total_size += $size;

            $result[] = [
                'name'  => $table->TABLE_NAME,
                'rows'  => (int) $table->TABLE_ROWS,
                'size'  => self::format_bytes($size),
                'bytes' => $size,
            ];
        }

        return rest_ensure_response([
            'prefix'      => $wpdb->prefix,
            'total_tables'=> count($result),
            'total_size'  => self::format_bytes($total_size),
            'total_bytes' => $total_size,
            'tables'      => $result,
        ]);
    }

    /**
     * GET /claude/v1/code-context — Code context for specific topics
     */
    public static function get_code_context(WP_REST_Request $request) {
        $topic = sanitize_text_field($request->get_param('topic') ?: 'general');

        $topics = [
            'gravity-forms' => [
                'patterns' => ['gform_', 'GF_', 'gravityforms'],
                'plugin'   => 'gravityforms/gravityforms.php',
                'hooks'    => ['gform_after_submission', 'gform_pre_render', 'gform_field_validation', 'gform_pre_submission'],
            ],
            'woocommerce' => [
                'patterns' => ['wc_', 'woocommerce_', 'WC_'],
                'plugin'   => 'woocommerce/woocommerce.php',
                'hooks'    => ['woocommerce_checkout_process', 'woocommerce_payment_complete', 'woocommerce_add_to_cart'],
            ],
            'divi' => [
                'patterns' => ['et_', 'divi_', 'ET_Builder'],
                'theme'    => 'Divi',
                'hooks'    => ['et_after_main_content', 'et_before_main_content', 'et_pb_module_shortcode_attributes'],
            ],
            'acf' => [
                'patterns' => ['acf/', 'acf_', 'get_field', 'the_field'],
                'plugin'   => 'advanced-custom-fields-pro/acf.php',
                'hooks'    => ['acf/save_post', 'acf/load_field', 'acf/update_value'],
            ],
            'custom-post-types' => [
                'patterns' => ['register_post_type', 'register_taxonomy'],
                'hooks'    => [],
            ],
            'rest-api' => [
                'patterns' => ['register_rest_route', 'rest_api_init'],
                'hooks'    => ['rest_api_init', 'rest_pre_dispatch'],
            ],
            'cron' => [
                'patterns' => ['wp_schedule_event', 'wp_cron'],
                'hooks'    => [],
            ],
            'general' => [
                'patterns' => ['add_action', 'add_filter', 'do_action', 'apply_filters'],
                'hooks'    => [],
            ],
        ];

        $config = $topics[$topic] ?? $topics['general'];
        $result = [
            'topic'     => $topic,
            'available_topics' => array_keys($topics),
        ];

        // Check if related plugin is active
        if (!empty($config['plugin'])) {
            if (!function_exists('is_plugin_active')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $result['plugin_active'] = is_plugin_active($config['plugin']);

            if ($result['plugin_active']) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $config['plugin']);
                $result['plugin_version'] = $plugin_data['Version'] ?? 'unknown';
            }
        }

        // Check if related theme is active
        if (!empty($config['theme'])) {
            $theme = wp_get_theme();
            $result['theme_match'] = (
                stripos($theme->get('Name'), $config['theme']) !== false ||
                stripos($theme->get_template(), strtolower($config['theme'])) !== false
            );
        }

        // Scan functions.php for hooks
        $theme_dir = get_stylesheet_directory();
        $functions_file = $theme_dir . '/functions.php';
        $result['theme_hooks'] = [];

        if (file_exists($functions_file)) {
            $result['theme_hooks'] = self::scan_file_for_patterns($functions_file, $config['patterns']);
        }

        // Also check child theme if exists
        $parent_dir = get_template_directory();
        if ($parent_dir !== $theme_dir) {
            $parent_functions = $parent_dir . '/functions.php';
            if (file_exists($parent_functions)) {
                $parent_hooks = self::scan_file_for_patterns($parent_functions, $config['patterns']);
                foreach ($parent_hooks as $hook) {
                    $hook['source'] = 'parent_theme';
                    $result['theme_hooks'][] = $hook;
                }
            }
        }

        // Get custom functions related to topic
        $result['custom_functions'] = self::extract_function_names($result['theme_hooks']);

        // Topic-specific data
        if ($topic === 'gravity-forms' && class_exists('GFAPI')) {
            $forms = GFAPI::get_forms();
            $result['forms_count'] = count($forms);
            $result['forms_summary'] = array_map(function($f) {
                return [
                    'id'           => $f['id'],
                    'title'        => $f['title'],
                    'is_active'    => (bool) ($f['is_active'] ?? false),
                    'fields_count' => count($f['fields'] ?? []),
                    'css_class'    => $f['cssClass'] ?? '',
                ];
            }, array_slice($forms, 0, 10));

            if (method_exists('GFAPI', 'get_feeds')) {
                $feeds = GFAPI::get_feeds();
                $feed_summary = [];
                foreach (($feeds ?: []) as $feed) {
                    if (!empty($feed['is_active'])) {
                        $feed_summary[] = [
                            'addon_slug' => $feed['addon_slug'] ?? 'unknown',
                            'form_id'    => $feed['form_id'] ?? 0,
                        ];
                    }
                }
                $result['feeds'] = array_slice($feed_summary, 0, 20);
            }
        }

        if ($topic === 'woocommerce' && function_exists('wc_get_product')) {
            $result['product_count'] = wp_count_posts('product')->publish ?? 0;
            $result['order_count'] = wp_count_posts('shop_order')->publish ?? 0;
        }

        if ($topic === 'acf' && function_exists('acf_get_field_groups')) {
            $groups = acf_get_field_groups();
            $result['field_groups'] = count($groups);
            $result['field_group_titles'] = array_map(function($g) {
                return $g['title'];
            }, array_slice($groups, 0, 10));
        }

        if ($topic === 'divi') {
            $divi_options = get_option('et_divi', []);
            if (is_array($divi_options) && !empty($divi_options['custom_css'])) {
                $result['custom_css_length'] = strlen($divi_options['custom_css']);
            }
            $library_count = wp_count_posts('et_pb_layout');
            $result['builder_layouts'] = (int) ($library_count->publish ?? 0);
        }

        if ($topic === 'custom-post-types') {
            $post_types = get_post_types(['_builtin' => false], 'objects');
            $result['custom_post_types'] = [];
            foreach ($post_types as $pt) {
                $result['custom_post_types'][] = [
                    'name'   => $pt->name,
                    'label'  => $pt->label,
                    'count'  => wp_count_posts($pt->name)->publish ?? 0,
                ];
            }
        }

        $docs = [
            'gravity-forms' => 'https://docs.gravityforms.com/category/developers/',
            'woocommerce'   => 'https://woocommerce.github.io/code-reference/',
            'divi'          => 'https://www.elegantthemes.com/documentation/developers/',
            'acf'           => 'https://www.advancedcustomfields.com/resources/',
        ];
        $result['docs_url'] = $docs[$topic] ?? null;

        return rest_ensure_response($result);
    }

    /**
     * GET /claude/v1/styles — CSS environment context
     */
    public static function get_styles() {
        $result = [
            'stylesheets'    => [],
            'customizer_css' => '',
            'child_theme_css'=> '',
            'divi_custom_css'=> null,
        ];

        global $wp_styles;
        if ($wp_styles instanceof WP_Styles) {
            foreach ($wp_styles->registered as $handle => $style) {
                $result['stylesheets'][] = [
                    'handle'  => $handle,
                    'src'     => $style->src ?: null,
                    'deps'    => $style->deps,
                    'version' => $style->ver,
                ];
            }
        }

        $result['customizer_css'] = wp_get_custom_css();

        $child_css_path = get_stylesheet_directory() . '/style.css';
        if (file_exists($child_css_path)) {
            $lines = file($child_css_path, FILE_IGNORE_NEW_LINES);
            $result['child_theme_css'] = implode("\n", array_slice($lines, 0, 200));
        }

        $divi_options = get_option('et_divi', []);
        if (is_array($divi_options) && !empty($divi_options['custom_css'])) {
            $result['divi_custom_css'] = $divi_options['custom_css'];
        }

        return rest_ensure_response($result);
    }

    /**
     * GET /claude/v1/forms — Gravity Forms detail
     */
    public static function get_forms_detail(WP_REST_Request $request) {
        if (!class_exists('GFAPI')) {
            return new WP_Error('gf_not_active', 'Gravity Forms is niet actief', ['status' => 404]);
        }

        $form_id = (int) $request->get_param('id');

        if ($form_id > 0) {
            $form = GFAPI::get_form($form_id);
            if (!$form) {
                return new WP_Error('form_not_found', 'Formulier niet gevonden', ['status' => 404]);
            }

            $fields = [];
            foreach ($form['fields'] ?? [] as $field) {
                $field_data = [
                    'id'         => $field->id,
                    'type'       => $field->type,
                    'label'      => $field->label,
                    'cssClass'   => $field->cssClass,
                    'isRequired' => (bool) $field->isRequired,
                ];
                if (!empty($field->choices)) {
                    $field_data['choices'] = array_map(function($c) {
                        return ['text' => $c['text'], 'value' => $c['value']];
                    }, $field->choices);
                }
                $fields[] = $field_data;
            }

            return rest_ensure_response([
                'id'             => $form['id'],
                'title'          => $form['title'],
                'css_class'      => $form['cssClass'] ?? '',
                'is_active'      => (bool) ($form['is_active'] ?? false),
                'fields'         => $fields,
                'confirmations'  => array_values(array_map(function($c) {
                    return [
                        'type'    => $c['type'] ?? 'message',
                        'message' => isset($c['message']) ? wp_strip_all_tags(substr($c['message'], 0, 300)) : '',
                    ];
                }, $form['confirmations'] ?? [])),
                'notifications'  => array_values(array_map(function($n) {
                    return [
                        'name'    => $n['name'] ?? '',
                        'event'   => $n['event'] ?? '',
                        'to'      => $n['toType'] ?? 'email',
                        'subject' => $n['subject'] ?? '',
                        'is_active' => (bool) ($n['isActive'] ?? true),
                    ];
                }, $form['notifications'] ?? [])),
            ]);
        }

        // All forms summary
        $forms = GFAPI::get_forms();
        $summary = [];
        foreach ($forms as $form) {
            $summary[] = [
                'id'           => $form['id'],
                'title'        => $form['title'],
                'is_active'    => (bool) ($form['is_active'] ?? false),
                'fields_count' => count($form['fields'] ?? []),
                'entries_count'=> (int) GFAPI::count_entries($form['id']),
            ];
        }

        return rest_ensure_response([
            'total' => count($summary),
            'forms' => $summary,
        ]);
    }

    /**
     * GET /claude/v1/page — Page context by URL or ID
     */
    public static function get_page_context(WP_REST_Request $request) {
        $url = $request->get_param('url');
        $post_id = (int) $request->get_param('id');

        if (!empty($url) && $post_id === 0) {
            $full_url = home_url($url);
            $post_id = url_to_postid($full_url);
        }

        if ($post_id <= 0) {
            return new WP_Error('page_not_found', 'Pagina niet gevonden. Gebruik ?url=/pad of ?id=42', ['status' => 404]);
        }

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('page_not_found', 'Pagina niet gevonden', ['status' => 404]);
        }

        $result = [
            'id'             => $post->ID,
            'title'          => $post->post_title,
            'slug'           => $post->post_name,
            'status'         => $post->post_status,
            'type'           => $post->post_type,
            'template'       => get_page_template_slug($post->ID) ?: 'default',
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'full') ?: null,
            'content_preview'=> wp_strip_all_tags(substr($post->post_content, 0, 500)),
        ];

        // Meta keys (filtered for sensitive data)
        $sensitive_keys = ['password', 'secret', 'token', 'key', 'hash', 'nonce', 'auth'];
        $all_meta = get_post_meta($post->ID);
        $safe_meta_keys = [];
        foreach (array_keys($all_meta) as $meta_key) {
            $is_sensitive = false;
            foreach ($sensitive_keys as $sensitive) {
                if (stripos($meta_key, $sensitive) !== false) {
                    $is_sensitive = true;
                    break;
                }
            }
            if (!$is_sensitive) {
                $safe_meta_keys[] = $meta_key;
            }
        }
        $result['meta_keys'] = $safe_meta_keys;

        // Divi modules (if Divi builder content)
        if (strpos($post->post_content, '[et_pb_') !== false) {
            preg_match_all('/\[et_pb_(\w+)[\s\]]/', $post->post_content, $matches);
            $result['divi_modules'] = array_values(array_unique($matches[1] ?? []));
        }

        // Yoast SEO data
        $yoast_title = get_post_meta($post->ID, '_yoast_wpseo_title', true);
        $yoast_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
        if ($yoast_title || $yoast_desc) {
            $result['yoast'] = [
                'title'       => $yoast_title ?: null,
                'description' => $yoast_desc ?: null,
            ];
        }

        return rest_ensure_response($result);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Scan a file for patterns and return matches with context.
     */
    private static function scan_file_for_patterns($file, $patterns) {
        $matches = [];
        $lines = file($file, FILE_IGNORE_NEW_LINES);

        foreach ($lines as $line_num => $line) {
            foreach ($patterns as $pattern) {
                if (stripos($line, $pattern) !== false) {
                    if (self::contains_sensitive_data($line)) {
                        continue;
                    }

                    $hook_name = self::extract_hook_name($line);

                    $matches[] = [
                        'file'    => basename($file),
                        'line'    => $line_num + 1,
                        'hook'    => $hook_name,
                        'snippet' => self::sanitize_snippet($line),
                        'source'  => 'child_theme',
                    ];
                    break;
                }
            }
        }

        return array_slice($matches, 0, 50);
    }

    /**
     * Check if line contains sensitive data.
     */
    private static function contains_sensitive_data($line) {
        $sensitive_patterns = [
            '/api[_-]?key/i',
            '/secret/i',
            '/password/i',
            '/credential/i',
            '/token\s*=/i',
            '/auth/i',
            '/private[_-]?key/i',
            '/DB_PASSWORD/i',
            '/DB_USER/i',
        ];

        foreach ($sensitive_patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract hook name from add_action/add_filter call.
     */
    private static function extract_hook_name($line) {
        if (preg_match('/add_(action|filter)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            return $matches[2];
        }
        return null;
    }

    /**
     * Sanitize snippet for safe output.
     */
    private static function sanitize_snippet($line) {
        $line = trim($line);
        if (strlen($line) > 150) {
            $line = substr($line, 0, 147) . '...';
        }
        return $line;
    }

    /**
     * Extract function names from hook matches.
     */
    private static function extract_function_names($hooks) {
        $functions = [];
        foreach ($hooks as $hook) {
            if (preg_match('/[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]/', $hook['snippet'], $matches)) {
                $func = $matches[1];
                if (!in_array($func, ['__return_true', '__return_false', '__return_empty_array'])) {
                    $functions[] = $func . '()';
                }
            }
        }
        return array_unique($functions);
    }

    /**
     * Format bytes to human readable.
     */
    private static function format_bytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
