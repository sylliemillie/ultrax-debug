<?php
/**
 * LOIQ Agent Facet Endpoints
 *
 * REST API endpoints for FacetWP management:
 * list facets/templates, create/update facets, create/update templates.
 *
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class LOIQ_Agent_Facet_Endpoints {

    /**
     * Register all v3 facet REST routes.
     *
     * @param LOIQ_WP_Agent $plugin  Reference to main plugin for permission callbacks
     */
    public static function register_routes($plugin) {
        $namespace = 'claude/v3';

        // --- READ ENDPOINTS ---

        register_rest_route($namespace, '/facet/list', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_facet_list'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [],
        ]);

        // --- WRITE ENDPOINTS ---

        register_rest_route($namespace, '/facet/create', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_facet_create'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'name'     => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_title'],
                'label'    => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'type'     => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'source'   => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'settings' => ['required' => false, 'type' => 'object', 'default' => []],
                'dry_run'  => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/facet/update', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_facet_update'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'name'    => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_title'],
                'updates' => ['required' => true, 'type' => 'object'],
                'dry_run' => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/facet/template', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_facet_template'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'name'    => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_title'],
                'label'   => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'query'   => ['required' => false, 'type' => 'object', 'default' => []],
                'display' => ['required' => false, 'type' => 'string'],
                'dry_run' => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Check if FacetWP is active.
     *
     * @return true|WP_Error
     */
    private static function check_facetwp() {
        if (!class_exists('FacetWP') && !function_exists('FWP')) {
            return new WP_Error('facetwp_not_active', 'FacetWP is niet actief. Installeer en activeer FacetWP eerst.', ['status' => 400]);
        }
        return true;
    }

    /**
     * Get the FacetWP settings array.
     *
     * @return array
     */
    private static function get_settings() {
        $settings = get_option('facetwp_settings', '');

        // FacetWP stores settings as serialized or JSON string
        if (is_string($settings) && !empty($settings)) {
            $decoded = json_decode($settings, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            $unserialized = maybe_unserialize($settings);
            if (is_array($unserialized)) {
                return $unserialized;
            }
        }

        if (is_array($settings)) {
            return $settings;
        }

        return ['facets' => [], 'templates' => []];
    }

    /**
     * Save the FacetWP settings array.
     *
     * @param array $settings
     */
    private static function save_settings($settings) {
        // FacetWP expects JSON-encoded settings
        $current = get_option('facetwp_settings', '');

        if (is_string($current) && !empty($current)) {
            // Stored as JSON string
            update_option('facetwp_settings', wp_json_encode($settings));
        } else {
            // Stored as array (serialized by WP)
            update_option('facetwp_settings', $settings);
        }
    }

    /**
     * Find a facet by name (slug) in the settings array.
     *
     * @param array  $settings
     * @param string $name
     * @return int|false  Array index or false
     */
    private static function find_facet_index($settings, $name) {
        if (empty($settings['facets']) || !is_array($settings['facets'])) {
            return false;
        }

        foreach ($settings['facets'] as $index => $facet) {
            if (($facet['name'] ?? '') === $name) {
                return $index;
            }
        }

        return false;
    }

    /**
     * Find a template by name (slug) in the settings array.
     *
     * @param array  $settings
     * @param string $name
     * @return int|false  Array index or false
     */
    private static function find_template_index($settings, $name) {
        if (empty($settings['templates']) || !is_array($settings['templates'])) {
            return false;
        }

        foreach ($settings['templates'] as $index => $template) {
            if (($template['name'] ?? '') === $name) {
                return $index;
            }
        }

        return false;
    }

    // =========================================================================
    // READ HANDLERS
    // =========================================================================

    /**
     * GET /claude/v3/facet/list
     *
     * Lists all FacetWP facets and templates with their configuration.
     */
    public static function handle_facet_list(WP_REST_Request $request) {
        $fwp_check = self::check_facetwp();
        if (is_wp_error($fwp_check)) return $fwp_check;

        $settings = self::get_settings();

        $facets = [];
        foreach (($settings['facets'] ?? []) as $facet) {
            $facets[] = [
                'name'   => $facet['name'] ?? '',
                'label'  => $facet['label'] ?? '',
                'type'   => $facet['type'] ?? '',
                'source' => $facet['source'] ?? '',
                'config' => array_diff_key($facet, array_flip(['name', 'label', 'type', 'source'])),
            ];
        }

        $templates = [];
        foreach (($settings['templates'] ?? []) as $template) {
            $templates[] = [
                'name'    => $template['name'] ?? '',
                'label'   => $template['label'] ?? '',
                'query'   => $template['query'] ?? [],
                'display' => isset($template['display']) ? substr($template['display'], 0, 500) : '',
                'config'  => array_diff_key($template, array_flip(['name', 'label', 'query', 'display'])),
            ];
        }

        return [
            'facets'    => $facets,
            'templates' => $templates,
            'total_facets'    => count($facets),
            'total_templates' => count($templates),
        ];
    }

    // =========================================================================
    // WRITE HANDLERS
    // =========================================================================

    /**
     * POST /claude/v3/facet/create
     *
     * Creates a new FacetWP facet.
     */
    public static function handle_facet_create(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('facets')) {
            return new WP_Error('power_mode_off', "Power mode voor 'facets' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $fwp_check = self::check_facetwp();
        if (is_wp_error($fwp_check)) return $fwp_check;

        $name     = $request->get_param('name');
        $label    = $request->get_param('label');
        $type     = $request->get_param('type');
        $source   = $request->get_param('source');
        $settings_param = $request->get_param('settings');
        $dry_run  = (bool) $request->get_param('dry_run');
        $session  = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Validate facet type
        $valid_types = ['dropdown', 'checkboxes', 'search', 'proximity', 'slider', 'fselect', 'radio', 'date_range', 'number_range', 'autocomplete', 'hierarchy', 'star_rating', 'pager', 'sort', 'reset'];
        if (!in_array($type, $valid_types, true)) {
            return new WP_Error('invalid_type',
                "Facet type '{$type}' is niet geldig. Beschikbaar: " . implode(', ', $valid_types),
                ['status' => 400]
            );
        }

        $settings = self::get_settings();

        // Check if facet name already exists
        if (self::find_facet_index($settings, $name) !== false) {
            return new WP_Error('facet_exists', "Facet met naam '{$name}' bestaat al", ['status' => 400]);
        }

        // Capture before state (full settings)
        $before = $settings;

        // Build facet config
        $facet = array_merge([
            'name'   => $name,
            'label'  => $label,
            'type'   => $type,
            'source' => $source,
        ], is_array($settings_param) ? $settings_param : []);

        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('facet', $name, $before, null, false, $session);
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'facet', $name, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'     => true,
                'dry_run'     => true,
                'snapshot_id' => $snapshot_id,
                'facet'       => $facet,
                'shortcode'   => '[facetwp facet="' . $name . '"]',
            ];
        }

        // Add facet to settings
        if (!isset($settings['facets']) || !is_array($settings['facets'])) {
            $settings['facets'] = [];
        }
        $settings['facets'][] = $facet;

        self::save_settings($settings);

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $settings);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'name'         => $name,
            'label'        => $label,
            'type'         => $type,
            'source'       => $source,
            'shortcode'    => '[facetwp facet="' . $name . '"]',
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    /**
     * POST /claude/v3/facet/update
     *
     * Updates an existing FacetWP facet.
     */
    public static function handle_facet_update(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('facets')) {
            return new WP_Error('power_mode_off', "Power mode voor 'facets' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $fwp_check = self::check_facetwp();
        if (is_wp_error($fwp_check)) return $fwp_check;

        $name    = $request->get_param('name');
        $updates = $request->get_param('updates');
        $dry_run = (bool) $request->get_param('dry_run');
        $session = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        if (!is_array($updates) || empty($updates)) {
            return new WP_Error('no_updates', 'Updates object is verplicht en mag niet leeg zijn', ['status' => 400]);
        }

        $settings = self::get_settings();

        // Find facet by name
        $index = self::find_facet_index($settings, $name);
        if ($index === false) {
            return new WP_Error('facet_not_found', "Facet met naam '{$name}' niet gevonden", ['status' => 404]);
        }

        // Capture before state
        $before = $settings;
        $facet_before = $settings['facets'][$index];

        // Merge updates (preserve name — cannot rename via update)
        $updated_facet = array_merge($settings['facets'][$index], $updates);
        $updated_facet['name'] = $name; // Protect name field

        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('facet', $name, $before, null, false, $session);
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'facet', $name, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'     => true,
                'dry_run'     => true,
                'snapshot_id' => $snapshot_id,
                'name'        => $name,
                'before'      => $facet_before,
                'after'       => $updated_facet,
                'changes'     => array_keys($updates),
            ];
        }

        // Apply update
        $settings['facets'][$index] = $updated_facet;

        self::save_settings($settings);

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $settings);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'name'         => $name,
            'changes'      => array_keys($updates),
            'facet'        => $updated_facet,
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    /**
     * POST /claude/v3/facet/template
     *
     * Creates or updates a FacetWP template.
     */
    public static function handle_facet_template(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('facets')) {
            return new WP_Error('power_mode_off', "Power mode voor 'facets' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $fwp_check = self::check_facetwp();
        if (is_wp_error($fwp_check)) return $fwp_check;

        $name    = $request->get_param('name');
        $label   = $request->get_param('label');
        $query   = $request->get_param('query');
        $display = $request->get_param('display');
        $dry_run = (bool) $request->get_param('dry_run');
        $session = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        $settings = self::get_settings();

        // Capture before state
        $before = $settings;

        // Check if template exists (update) or not (create)
        $index     = self::find_template_index($settings, $name);
        $is_update = ($index !== false);

        // Build template data
        if ($is_update) {
            $template = $settings['templates'][$index];
            $template['label'] = $label;
            if (!empty($query) && is_array($query)) {
                $template['query'] = array_merge($template['query'] ?? [], $query);
            }
            if ($display !== null) {
                $template['display'] = $display;
            }
        } else {
            $template = [
                'name'  => $name,
                'label' => $label,
            ];
            if (!empty($query) && is_array($query)) {
                $template['query'] = $query;
            }
            if ($display !== null) {
                $template['display'] = $display;
            }
        }

        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('facet', "template:{$name}", $before, null, false, $session);
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'facet', "template:{$name}", $session, $dry_run);

        if ($dry_run) {
            return [
                'success'     => true,
                'dry_run'     => true,
                'snapshot_id' => $snapshot_id,
                'name'        => $name,
                'is_update'   => $is_update,
                'template'    => $template,
                'shortcode'   => '[facetwp template="' . $name . '"]',
            ];
        }

        // Save template
        if (!isset($settings['templates']) || !is_array($settings['templates'])) {
            $settings['templates'] = [];
        }

        if ($is_update) {
            $settings['templates'][$index] = $template;
        } else {
            $settings['templates'][] = $template;
        }

        self::save_settings($settings);

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $settings);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'name'         => $name,
            'label'        => $label,
            'is_update'    => $is_update,
            'template'     => $template,
            'shortcode'    => '[facetwp template="' . $name . '"]',
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }
}
