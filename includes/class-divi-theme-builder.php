<?php
/**
 * LOIQ Agent Divi Theme Builder Endpoints
 *
 * REST API endpoints for managing Divi Theme Builder templates and
 * Divi Library items. Supports listing, reading, creating, updating,
 * and assigning templates, plus saving to the Divi Library.
 *
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class LOIQ_Agent_Divi_Theme_Builder {

    /**
     * Register all Divi Theme Builder REST routes.
     * Called from main plugin class via rest_api_init.
     *
     * @param LOIQ_WP_Agent $plugin  Reference to main plugin for permission callbacks
     */
    public static function register_routes(LOIQ_WP_Agent $plugin): void {
        $namespace = 'claude/v3';

        // --- THEME BUILDER READ ENDPOINTS ---

        register_rest_route($namespace, '/theme-builder/list', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_list'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [],
        ]);

        register_rest_route($namespace, '/theme-builder/read', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_read'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [
                'template_id' => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
            ],
        ]);

        // --- THEME BUILDER WRITE ENDPOINTS ---

        register_rest_route($namespace, '/theme-builder/create', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_create'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'title'          => ['required' => true,  'type' => 'string',  'sanitize_callback' => 'sanitize_text_field'],
                'use_on'         => ['required' => false, 'type' => 'array',   'default' => []],
                'exclude_from'   => ['required' => false, 'type' => 'array',   'default' => []],
                'header_content' => ['required' => false, 'type' => 'string',  'default' => ''],
                'body_content'   => ['required' => false, 'type' => 'string',  'default' => ''],
                'footer_content' => ['required' => false, 'type' => 'string',  'default' => ''],
                'dry_run'        => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/theme-builder/update', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_update'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'template_id'    => ['required' => true,  'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
                'header_content' => ['required' => false, 'type' => 'string'],
                'body_content'   => ['required' => false, 'type' => 'string'],
                'footer_content' => ['required' => false, 'type' => 'string'],
                'dry_run'        => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/theme-builder/assign', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_assign'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'template_id'  => ['required' => true,  'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
                'use_on'       => ['required' => false, 'type' => 'array'],
                'exclude_from' => ['required' => false, 'type' => 'array'],
                'dry_run'      => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        // --- DIVI LIBRARY ENDPOINTS ---

        register_rest_route($namespace, '/divi/library/list', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_library_list'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [],
        ]);

        register_rest_route($namespace, '/divi/library/save', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_library_save'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'title'       => ['required' => true,  'type' => 'string',  'sanitize_callback' => 'sanitize_text_field'],
                'content'     => ['required' => true,  'type' => 'string'],
                'layout_type' => ['required' => true,  'type' => 'string',  'enum' => ['module', 'row', 'section', 'full']],
                'category'    => ['required' => false, 'type' => 'string',  'default' => '', 'sanitize_callback' => 'sanitize_text_field'],
                'dry_run'     => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);
    }

    // =========================================================================
    // PREREQUISITE CHECK
    // =========================================================================

    /**
     * Verify that Divi Theme Builder is available.
     *
     * Checks for the Divi theme (or child of Divi) being active and the
     * et_template post type being registered.
     *
     * @return true|WP_Error
     */
    private static function require_divi() {
        // Check if the et_template post type is registered (Divi Theme Builder)
        if (post_type_exists('et_template')) {
            return true;
        }

        // Fallback: check if Divi theme is active
        $theme = wp_get_theme();
        $template = $theme->get_template();
        if ($template === 'Divi' || $template === 'Extra') {
            return true;
        }

        // Check if Divi Builder plugin is active
        if (defined('ET_BUILDER_VERSION')) {
            return true;
        }

        return new WP_Error(
            'divi_not_active',
            'Divi Theme Builder is niet beschikbaar. Divi thema of Divi Builder plugin is niet actief.',
            ['status' => 424]
        );
    }

    /**
     * Verify that the divi_builder power mode is enabled.
     *
     * @return true|WP_Error
     */
    private static function require_power_mode() {
        if (!LOIQ_Agent_Safeguards::is_enabled('divi_builder')) {
            return new WP_Error(
                'power_mode_off',
                "Power mode voor 'divi_builder' is uitgeschakeld. Enable via wp-admin.",
                ['status' => 403]
            );
        }
        return true;
    }

    // =========================================================================
    // THEME BUILDER: LIST
    // =========================================================================

    /**
     * GET /claude/v3/theme-builder/list
     *
     * List all Theme Builder templates with their conditions and layout status.
     */
    public static function handle_list(WP_REST_Request $request) {
        $divi_check = self::require_divi();
        if (is_wp_error($divi_check)) return $divi_check;

        $templates = get_posts([
            'post_type'      => 'et_template',
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'draft'],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $result = [];
        foreach ($templates as $template) {
            $use_on       = get_post_meta($template->ID, '_et_use_on', true);
            $exclude_from = get_post_meta($template->ID, '_et_exclude_from', true);

            $result[] = [
                'id'           => $template->ID,
                'title'        => $template->post_title,
                'status'       => $template->post_status,
                'conditions'   => [
                    'use_on'       => is_array($use_on) ? $use_on : [],
                    'exclude_from' => is_array($exclude_from) ? $exclude_from : [],
                ],
                'has_header'   => self::template_has_layout($template->ID, 'et_header_layout'),
                'has_body'     => self::template_has_layout($template->ID, 'et_body_layout'),
                'has_footer'   => self::template_has_layout($template->ID, 'et_footer_layout'),
            ];
        }

        // Get default template info from the master option
        $theme_builder_option = get_option('et_theme_builder', []);
        $default_template = null;
        if (!empty($theme_builder_option) && is_array($theme_builder_option)) {
            $default_id = $theme_builder_option['template_id_default'] ?? null;
            if ($default_id) {
                $default_post = get_post($default_id);
                if ($default_post && $default_post->post_type === 'et_template') {
                    $default_template = [
                        'id'         => $default_post->ID,
                        'title'      => $default_post->post_title,
                        'status'     => $default_post->post_status,
                        'has_header' => self::template_has_layout($default_post->ID, 'et_header_layout'),
                        'has_body'   => self::template_has_layout($default_post->ID, 'et_body_layout'),
                        'has_footer' => self::template_has_layout($default_post->ID, 'et_footer_layout'),
                    ];
                }
            }
        }

        return [
            'total'            => count($result),
            'templates'        => $result,
            'default_template' => $default_template,
        ];
    }

    // =========================================================================
    // THEME BUILDER: READ
    // =========================================================================

    /**
     * GET /claude/v3/theme-builder/read
     *
     * Read a single template with full layout contents.
     */
    public static function handle_read(WP_REST_Request $request) {
        $divi_check = self::require_divi();
        if (is_wp_error($divi_check)) return $divi_check;

        $template_id = $request->get_param('template_id');
        $template = get_post($template_id);

        if (!$template || $template->post_type !== 'et_template') {
            return new WP_Error('template_not_found', "Template #{$template_id} niet gevonden of is geen et_template", ['status' => 404]);
        }

        $use_on       = get_post_meta($template_id, '_et_use_on', true);
        $exclude_from = get_post_meta($template_id, '_et_exclude_from', true);

        // Get linked layout posts
        $header_layout  = self::get_template_layout($template_id, 'et_header_layout');
        $body_layout    = self::get_template_layout($template_id, 'et_body_layout');
        $footer_layout  = self::get_template_layout($template_id, 'et_footer_layout');

        return [
            'id'             => $template->ID,
            'title'          => $template->post_title,
            'status'         => $template->post_status,
            'conditions'     => [
                'use_on'       => is_array($use_on) ? $use_on : [],
                'exclude_from' => is_array($exclude_from) ? $exclude_from : [],
            ],
            'header'         => $header_layout ? [
                'layout_id' => $header_layout->ID,
                'content'   => $header_layout->post_content,
            ] : null,
            'body'           => $body_layout ? [
                'layout_id' => $body_layout->ID,
                'content'   => $body_layout->post_content,
            ] : null,
            'footer'         => $footer_layout ? [
                'layout_id' => $footer_layout->ID,
                'content'   => $footer_layout->post_content,
            ] : null,
        ];
    }

    // =========================================================================
    // THEME BUILDER: CREATE
    // =========================================================================

    /**
     * POST /claude/v3/theme-builder/create
     *
     * Create a new Theme Builder template with optional layout content.
     */
    public static function handle_create(WP_REST_Request $request) {
        $divi_check = self::require_divi();
        if (is_wp_error($divi_check)) return $divi_check;

        $power_check = self::require_power_mode();
        if (is_wp_error($power_check)) return $power_check;

        $title          = $request->get_param('title');
        $use_on         = $request->get_param('use_on');
        $exclude_from   = $request->get_param('exclude_from');
        $header_content = $request->get_param('header_content');
        $body_content   = $request->get_param('body_content');
        $footer_content = $request->get_param('footer_content');
        $dry_run        = (bool) $request->get_param('dry_run');
        $session        = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Sanitize condition arrays
        $use_on       = is_array($use_on) ? array_map('sanitize_text_field', $use_on) : [];
        $exclude_from = is_array($exclude_from) ? array_map('sanitize_text_field', $exclude_from) : [];

        // Create snapshot (before = null for new template)
        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('theme_builder', 'new_template', null, null, false, $session);

        // Audit log
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'theme_builder', $title, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'        => true,
                'dry_run'        => true,
                'snapshot_id'    => $snapshot_id,
                'title'          => $title,
                'use_on'         => $use_on,
                'exclude_from'   => $exclude_from,
                'would_create'   => [
                    'template'       => true,
                    'header_layout'  => !empty($header_content),
                    'body_layout'    => !empty($body_content),
                    'footer_layout'  => !empty($footer_content),
                ],
            ];
        }

        // Create the et_template post
        $template_id = wp_insert_post([
            'post_type'   => 'et_template',
            'post_title'  => $title,
            'post_status' => 'publish',
        ], true);

        if (is_wp_error($template_id)) {
            return new WP_Error('create_failed', 'Kan template niet aanmaken: ' . $template_id->get_error_message(), ['status' => 500]);
        }

        // Set conditions
        if (!empty($use_on)) {
            update_post_meta($template_id, '_et_use_on', $use_on);
        }
        if (!empty($exclude_from)) {
            update_post_meta($template_id, '_et_exclude_from', $exclude_from);
        }

        // Create layout posts if content provided
        $header_id = null;
        $body_id   = null;
        $footer_id = null;

        if (!empty($header_content)) {
            $header_id = self::create_layout_post($template_id, 'et_header_layout', $header_content, $title . ' Header');
            if (is_wp_error($header_id)) return $header_id;
        }

        if (!empty($body_content)) {
            $body_id = self::create_layout_post($template_id, 'et_body_layout', $body_content, $title . ' Body');
            if (is_wp_error($body_id)) return $body_id;
        }

        if (!empty($footer_content)) {
            $footer_id = self::create_layout_post($template_id, 'et_footer_layout', $footer_content, $title . ' Footer');
            if (is_wp_error($footer_id)) return $footer_id;
        }

        // Capture after state
        $after = [
            'template_id' => $template_id,
            'title'        => $title,
            'use_on'       => $use_on,
            'exclude_from' => $exclude_from,
            'header_id'    => $header_id,
            'body_id'      => $body_id,
            'footer_id'    => $footer_id,
        ];

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $after);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'template_id'  => $template_id,
            'title'        => $title,
            'use_on'       => $use_on,
            'exclude_from' => $exclude_from,
            'header_id'    => $header_id,
            'body_id'      => $body_id,
            'footer_id'    => $footer_id,
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    // =========================================================================
    // THEME BUILDER: UPDATE
    // =========================================================================

    /**
     * POST /claude/v3/theme-builder/update
     *
     * Update layout contents for an existing template.
     */
    public static function handle_update(WP_REST_Request $request) {
        $divi_check = self::require_divi();
        if (is_wp_error($divi_check)) return $divi_check;

        $power_check = self::require_power_mode();
        if (is_wp_error($power_check)) return $power_check;

        $template_id    = $request->get_param('template_id');
        $header_content = $request->get_param('header_content');
        $body_content   = $request->get_param('body_content');
        $footer_content = $request->get_param('footer_content');
        $dry_run        = (bool) $request->get_param('dry_run');
        $session        = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Verify template exists
        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'et_template') {
            return new WP_Error('template_not_found', "Template #{$template_id} niet gevonden of is geen et_template", ['status' => 404]);
        }

        // Check that at least one layout content was provided
        if ($header_content === null && $body_content === null && $footer_content === null) {
            return new WP_Error('no_content', 'Minimaal header_content, body_content of footer_content is vereist', ['status' => 400]);
        }

        // Capture before state
        $before = self::capture_template_layouts($template_id);

        // Create snapshot
        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('theme_builder', (string) $template_id, $before, null, false, $session);

        // Audit log
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'theme_builder', (string) $template_id, $session, $dry_run);

        if ($dry_run) {
            $changes = [];
            if ($header_content !== null) {
                $changes['header'] = [
                    'before_length' => isset($before['header_content']) ? strlen($before['header_content']) : 0,
                    'after_length'  => strlen($header_content),
                    'has_existing'  => !empty($before['header_id']),
                ];
            }
            if ($body_content !== null) {
                $changes['body'] = [
                    'before_length' => isset($before['body_content']) ? strlen($before['body_content']) : 0,
                    'after_length'  => strlen($body_content),
                    'has_existing'  => !empty($before['body_id']),
                ];
            }
            if ($footer_content !== null) {
                $changes['footer'] = [
                    'before_length' => isset($before['footer_content']) ? strlen($before['footer_content']) : 0,
                    'after_length'  => strlen($footer_content),
                    'has_existing'  => !empty($before['footer_id']),
                ];
            }

            return [
                'success'      => true,
                'dry_run'      => true,
                'snapshot_id'  => $snapshot_id,
                'template_id'  => $template_id,
                'template_title' => $template->post_title,
                'changes'      => $changes,
            ];
        }

        // Update or create each layout
        $updated = [];

        if ($header_content !== null) {
            $result = self::update_or_create_layout($template_id, 'et_header_layout', $header_content, $template->post_title . ' Header');
            if (is_wp_error($result)) return $result;
            $updated['header_id'] = $result;
        }

        if ($body_content !== null) {
            $result = self::update_or_create_layout($template_id, 'et_body_layout', $body_content, $template->post_title . ' Body');
            if (is_wp_error($result)) return $result;
            $updated['body_id'] = $result;
        }

        if ($footer_content !== null) {
            $result = self::update_or_create_layout($template_id, 'et_footer_layout', $footer_content, $template->post_title . ' Footer');
            if (is_wp_error($result)) return $result;
            $updated['footer_id'] = $result;
        }

        // Capture after state
        $after = self::capture_template_layouts($template_id);
        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $after);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'template_id'  => $template_id,
            'template_title' => $template->post_title,
            'updated'      => $updated,
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    // =========================================================================
    // THEME BUILDER: ASSIGN
    // =========================================================================

    /**
     * POST /claude/v3/theme-builder/assign
     *
     * Assign or update conditions for a template.
     */
    public static function handle_assign(WP_REST_Request $request) {
        $divi_check = self::require_divi();
        if (is_wp_error($divi_check)) return $divi_check;

        $power_check = self::require_power_mode();
        if (is_wp_error($power_check)) return $power_check;

        $template_id  = $request->get_param('template_id');
        $use_on       = $request->get_param('use_on');
        $exclude_from = $request->get_param('exclude_from');
        $dry_run      = (bool) $request->get_param('dry_run');
        $session      = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Verify template exists
        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'et_template') {
            return new WP_Error('template_not_found', "Template #{$template_id} niet gevonden of is geen et_template", ['status' => 404]);
        }

        // Check that at least one condition array was provided
        if ($use_on === null && $exclude_from === null) {
            return new WP_Error('no_conditions', 'Minimaal use_on of exclude_from is vereist', ['status' => 400]);
        }

        // Capture before state
        $current_use_on       = get_post_meta($template_id, '_et_use_on', true);
        $current_exclude_from = get_post_meta($template_id, '_et_exclude_from', true);

        $before = [
            'use_on'       => is_array($current_use_on) ? $current_use_on : [],
            'exclude_from' => is_array($current_exclude_from) ? $current_exclude_from : [],
        ];

        // Sanitize new conditions
        $new_use_on       = $use_on !== null ? array_map('sanitize_text_field', (array) $use_on) : null;
        $new_exclude_from = $exclude_from !== null ? array_map('sanitize_text_field', (array) $exclude_from) : null;

        // Create snapshot
        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('theme_builder', (string) $template_id, $before, null, false, $session);

        // Audit log
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'theme_builder', (string) $template_id, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'      => true,
                'dry_run'      => true,
                'snapshot_id'  => $snapshot_id,
                'template_id'  => $template_id,
                'template_title' => $template->post_title,
                'before'       => $before,
                'after'        => [
                    'use_on'       => $new_use_on !== null ? $new_use_on : $before['use_on'],
                    'exclude_from' => $new_exclude_from !== null ? $new_exclude_from : $before['exclude_from'],
                ],
            ];
        }

        // Update conditions
        if ($new_use_on !== null) {
            update_post_meta($template_id, '_et_use_on', $new_use_on);
        }
        if ($new_exclude_from !== null) {
            update_post_meta($template_id, '_et_exclude_from', $new_exclude_from);
        }

        // Capture after state
        $after_use_on       = get_post_meta($template_id, '_et_use_on', true);
        $after_exclude_from = get_post_meta($template_id, '_et_exclude_from', true);

        $after = [
            'use_on'       => is_array($after_use_on) ? $after_use_on : [],
            'exclude_from' => is_array($after_exclude_from) ? $after_exclude_from : [],
        ];

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $after);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'template_id'  => $template_id,
            'template_title' => $template->post_title,
            'before'       => $before,
            'after'        => $after,
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    // =========================================================================
    // DIVI LIBRARY: LIST
    // =========================================================================

    /**
     * GET /claude/v3/divi/library/list
     *
     * List all Divi Library items (et_pb_layout post type).
     */
    public static function handle_library_list(WP_REST_Request $request) {
        $divi_check = self::require_divi();
        if (is_wp_error($divi_check)) return $divi_check;

        $layouts = get_posts([
            'post_type'      => 'et_pb_layout',
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'draft'],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $result = [];
        foreach ($layouts as $layout) {
            // Get layout type from taxonomy
            $layout_type_terms = wp_get_post_terms($layout->ID, 'layout_type', ['fields' => 'names']);
            $layout_type = !is_wp_error($layout_type_terms) && !empty($layout_type_terms) ? $layout_type_terms[0] : 'unknown';

            // Get layout category from taxonomy
            $category_terms = wp_get_post_terms($layout->ID, 'layout_category', ['fields' => 'names']);
            $category = !is_wp_error($category_terms) && !empty($category_terms) ? implode(', ', $category_terms) : '';

            $result[] = [
                'id'          => $layout->ID,
                'title'       => $layout->post_title,
                'layout_type' => $layout_type,
                'category'    => $category,
                'date'        => $layout->post_date,
            ];
        }

        return [
            'total'   => count($result),
            'layouts' => $result,
        ];
    }

    // =========================================================================
    // DIVI LIBRARY: SAVE
    // =========================================================================

    /**
     * POST /claude/v3/divi/library/save
     *
     * Save a new item to the Divi Library.
     */
    public static function handle_library_save(WP_REST_Request $request) {
        $divi_check = self::require_divi();
        if (is_wp_error($divi_check)) return $divi_check;

        $power_check = self::require_power_mode();
        if (is_wp_error($power_check)) return $power_check;

        $title       = $request->get_param('title');
        $content     = $request->get_param('content');
        $layout_type = $request->get_param('layout_type');
        $category    = $request->get_param('category');
        $dry_run     = (bool) $request->get_param('dry_run');
        $session     = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Create snapshot (before = null for new library item)
        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('divi', 'library_new', null, null, false, $session);

        // Audit log
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'divi', $title, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'      => true,
                'dry_run'      => true,
                'snapshot_id'  => $snapshot_id,
                'title'        => $title,
                'layout_type'  => $layout_type,
                'category'     => $category,
                'content_length' => strlen($content),
            ];
        }

        // Create the et_pb_layout post
        $layout_id = wp_insert_post([
            'post_type'    => 'et_pb_layout',
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
        ], true);

        if (is_wp_error($layout_id)) {
            return new WP_Error('create_failed', 'Kan library item niet aanmaken: ' . $layout_id->get_error_message(), ['status' => 500]);
        }

        // Set the layout_type taxonomy term
        $type_map = [
            'module'  => 'module',
            'row'     => 'row',
            'section' => 'section',
            'full'    => 'layout',
        ];
        $type_term = $type_map[$layout_type] ?? 'layout';
        wp_set_object_terms($layout_id, $type_term, 'layout_type');

        // Set category taxonomy term if provided
        if (!empty($category)) {
            wp_set_object_terms($layout_id, $category, 'layout_category');
        }

        // Mark Divi builder as active on this post
        update_post_meta($layout_id, '_et_pb_use_builder', 'on');

        // Capture after state
        $after = [
            'layout_id'   => $layout_id,
            'title'       => $title,
            'layout_type' => $layout_type,
            'category'    => $category,
        ];

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $after);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'layout_id'    => $layout_id,
            'title'        => $title,
            'layout_type'  => $layout_type,
            'category'     => $category,
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    // =========================================================================
    // HELPER: TEMPLATE LAYOUT OPERATIONS
    // =========================================================================

    /**
     * Check if a template has a linked layout of a given type.
     *
     * @param int    $template_id
     * @param string $layout_post_type  et_header_layout | et_body_layout | et_footer_layout
     * @return bool
     */
    private static function template_has_layout(int $template_id, string $layout_post_type): bool {
        $layout = self::get_template_layout($template_id, $layout_post_type);
        return $layout !== null;
    }

    /**
     * Get the layout post linked to a template.
     *
     * Divi links layouts to templates via post_parent on the layout post,
     * or via template meta. We check both approaches.
     *
     * @param int    $template_id
     * @param string $layout_post_type
     * @return WP_Post|null
     */
    private static function get_template_layout(int $template_id, string $layout_post_type) {
        // Primary: layout posts with this template as parent
        $layouts = get_posts([
            'post_type'      => $layout_post_type,
            'post_parent'    => $template_id,
            'posts_per_page' => 1,
            'post_status'    => ['publish', 'draft'],
        ]);

        if (!empty($layouts)) {
            return $layouts[0];
        }

        // Fallback: check template meta for layout ID reference
        $meta_key_map = [
            'et_header_layout' => '_et_header_layout_id',
            'et_body_layout'   => '_et_body_layout_id',
            'et_footer_layout' => '_et_footer_layout_id',
        ];

        $meta_key = $meta_key_map[$layout_post_type] ?? null;
        if ($meta_key) {
            $layout_id = get_post_meta($template_id, $meta_key, true);
            if ($layout_id) {
                $layout = get_post((int) $layout_id);
                if ($layout && $layout->post_type === $layout_post_type) {
                    return $layout;
                }
            }
        }

        return null;
    }

    /**
     * Create a layout post and link it to a template.
     *
     * @param int    $template_id
     * @param string $layout_post_type
     * @param string $content           Divi shortcode content
     * @param string $title             Layout title
     * @return int|WP_Error             Layout post ID on success
     */
    private static function create_layout_post(int $template_id, string $layout_post_type, string $content, string $title) {
        $layout_id = wp_insert_post([
            'post_type'    => $layout_post_type,
            'post_parent'  => $template_id,
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
        ], true);

        if (is_wp_error($layout_id)) {
            return new WP_Error('layout_create_failed', "Kan {$layout_post_type} niet aanmaken: " . $layout_id->get_error_message(), ['status' => 500]);
        }

        // Store the layout ID reference on the template
        $meta_key_map = [
            'et_header_layout' => '_et_header_layout_id',
            'et_body_layout'   => '_et_body_layout_id',
            'et_footer_layout' => '_et_footer_layout_id',
        ];

        $meta_key = $meta_key_map[$layout_post_type] ?? null;
        if ($meta_key) {
            update_post_meta($template_id, $meta_key, $layout_id);
        }

        // Enable Divi Builder on the layout
        update_post_meta($layout_id, '_et_pb_use_builder', 'on');

        return $layout_id;
    }

    /**
     * Update an existing layout or create it if it doesn't exist yet.
     *
     * @param int    $template_id
     * @param string $layout_post_type
     * @param string $content
     * @param string $title
     * @return int|WP_Error  Layout post ID
     */
    private static function update_or_create_layout(int $template_id, string $layout_post_type, string $content, string $title) {
        $existing = self::get_template_layout($template_id, $layout_post_type);

        if ($existing) {
            // Update existing layout post
            $result = wp_update_post([
                'ID'           => $existing->ID,
                'post_content' => $content,
            ], true);

            if (is_wp_error($result)) {
                return new WP_Error('layout_update_failed', "Kan {$layout_post_type} #{$existing->ID} niet bijwerken: " . $result->get_error_message(), ['status' => 500]);
            }

            return $existing->ID;
        }

        // No existing layout — create a new one
        return self::create_layout_post($template_id, $layout_post_type, $content, $title);
    }

    /**
     * Capture the current layout contents for a template (for snapshot before state).
     *
     * @param int $template_id
     * @return array
     */
    private static function capture_template_layouts(int $template_id): array {
        $header = self::get_template_layout($template_id, 'et_header_layout');
        $body   = self::get_template_layout($template_id, 'et_body_layout');
        $footer = self::get_template_layout($template_id, 'et_footer_layout');

        return [
            'header_id'      => $header ? $header->ID : null,
            'header_content' => $header ? $header->post_content : null,
            'body_id'        => $body ? $body->ID : null,
            'body_content'   => $body ? $body->post_content : null,
            'footer_id'      => $footer ? $footer->ID : null,
            'footer_content' => $footer ? $footer->post_content : null,
        ];
    }
}
