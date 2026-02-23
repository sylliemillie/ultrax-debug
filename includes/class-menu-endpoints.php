<?php
/**
 * LOIQ Agent Menu Endpoints
 *
 * REST API endpoints for WordPress menu management:
 * list, create, add items, reorder, assign locations, and Max Mega Menu config.
 *
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class LOIQ_Agent_Menu_Endpoints {

    /**
     * Register all v3 menu REST routes.
     *
     * @param LOIQ_WP_Agent $plugin  Reference to main plugin for permission callbacks
     */
    public static function register_routes($plugin) {
        $namespace = 'claude/v3';

        // --- READ ENDPOINTS ---

        register_rest_route($namespace, '/menu/list', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_menu_list'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [],
        ]);

        register_rest_route($namespace, '/menu/mega-menu/read', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_mega_menu_read'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [
                'menu_item_id' => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
            ],
        ]);

        // --- WRITE ENDPOINTS ---

        register_rest_route($namespace, '/menu/create', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_menu_create'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'name'    => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'dry_run' => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/menu/items/add', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_menu_items_add'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'menu_id' => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
                'items'   => ['required' => true, 'type' => 'array'],
                'dry_run' => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/menu/items/reorder', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_menu_items_reorder'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'menu_id' => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
                'items'   => ['required' => true, 'type' => 'array'],
                'dry_run' => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/menu/assign', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_menu_assign'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'menu_id'  => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
                'location' => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'dry_run'  => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/menu/mega-menu/configure', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_mega_menu_configure'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'menu_item_id' => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
                'settings'     => ['required' => true, 'type' => 'object'],
                'dry_run'      => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);
    }

    // =========================================================================
    // READ HANDLERS
    // =========================================================================

    /**
     * GET /claude/v3/menu/list
     *
     * Returns all menus with their registered locations and item counts.
     */
    public static function handle_menu_list(WP_REST_Request $request) {
        $menus     = wp_get_nav_menus();
        $locations = get_nav_menu_locations();
        $registered_locations = get_registered_nav_menus();

        $result = [];
        foreach ($menus as $menu) {
            $items = wp_get_nav_menu_items($menu->term_id);
            $assigned_locations = [];

            foreach ($locations as $loc_slug => $menu_id) {
                if ((int) $menu_id === (int) $menu->term_id) {
                    $assigned_locations[] = [
                        'slug'  => $loc_slug,
                        'label' => $registered_locations[$loc_slug] ?? $loc_slug,
                    ];
                }
            }

            $menu_items = [];
            if ($items) {
                foreach ($items as $item) {
                    $menu_items[] = [
                        'id'        => (int) $item->ID,
                        'title'     => $item->title,
                        'url'       => $item->url,
                        'type'      => $item->type,
                        'object'    => $item->object,
                        'object_id' => (int) $item->object_id,
                        'parent'    => (int) $item->menu_item_parent,
                        'position'  => (int) $item->menu_order,
                        'classes'   => array_filter($item->classes),
                    ];
                }
            }

            $result[] = [
                'id'         => (int) $menu->term_id,
                'name'       => $menu->name,
                'slug'       => $menu->slug,
                'item_count' => $items ? count($items) : 0,
                'locations'  => $assigned_locations,
                'items'      => $menu_items,
            ];
        }

        return [
            'menus'                => $result,
            'registered_locations' => $registered_locations,
            'current_assignments'  => $locations,
        ];
    }

    /**
     * GET /claude/v3/menu/mega-menu/read
     *
     * Reads Max Mega Menu configuration for a specific menu item.
     * Returns empty config if Max Mega Menu is not active.
     */
    public static function handle_mega_menu_read(WP_REST_Request $request) {
        $menu_item_id = $request->get_param('menu_item_id');

        // Check if Max Mega Menu is active
        if (!class_exists('Mega_Menu')) {
            return [
                'menu_item_id'       => $menu_item_id,
                'mega_menu_active'   => false,
                'settings'           => [],
                'message'            => 'Max Mega Menu plugin is niet actief',
            ];
        }

        $meta = get_post_meta($menu_item_id, '_megamenu', true);

        return [
            'menu_item_id'     => $menu_item_id,
            'mega_menu_active' => true,
            'settings'         => is_array($meta) ? $meta : [],
        ];
    }

    // =========================================================================
    // WRITE HANDLERS
    // =========================================================================

    /**
     * POST /claude/v3/menu/create
     *
     * Creates a new WordPress navigation menu.
     */
    public static function handle_menu_create(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('menus')) {
            return new WP_Error('power_mode_off', "Power mode voor 'menus' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $name    = $request->get_param('name');
        $dry_run = (bool) $request->get_param('dry_run');
        $session = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Check if menu with same name already exists
        $existing = wp_get_nav_menu_object($name);
        if ($existing) {
            return new WP_Error('menu_exists', "Menu met naam '{$name}' bestaat al (ID: {$existing->term_id})", ['status' => 400]);
        }

        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('menu', $name, null, null, false, $session);
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'menu', $name, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'     => true,
                'dry_run'     => true,
                'snapshot_id' => $snapshot_id,
                'name'        => $name,
                'message'     => "Menu '{$name}' zou aangemaakt worden",
            ];
        }

        $menu_id = wp_create_nav_menu($name);
        if (is_wp_error($menu_id)) {
            return $menu_id;
        }

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, ['menu_id' => $menu_id, 'name' => $name]);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'menu_id'      => $menu_id,
            'name'         => $name,
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    /**
     * POST /claude/v3/menu/items/add
     *
     * Adds one or more items to an existing menu.
     * Item types: page, custom, category, post_type.
     */
    public static function handle_menu_items_add(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('menus')) {
            return new WP_Error('power_mode_off', "Power mode voor 'menus' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $menu_id = $request->get_param('menu_id');
        $items   = $request->get_param('items');
        $dry_run = (bool) $request->get_param('dry_run');
        $session = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Verify menu exists
        $menu = wp_get_nav_menu_object($menu_id);
        if (!$menu) {
            return new WP_Error('menu_not_found', "Menu met ID {$menu_id} niet gevonden", ['status' => 404]);
        }

        // Validate items array
        if (!is_array($items) || empty($items)) {
            return new WP_Error('invalid_items', 'Items array is verplicht en mag niet leeg zijn', ['status' => 400]);
        }

        // Capture before state
        $before_items = wp_get_nav_menu_items($menu_id);
        $before = [
            'items' => $before_items ? array_map(function ($item) {
                return [
                    'ID'               => $item->ID,
                    'title'            => $item->title,
                    'url'              => $item->url,
                    'menu_order'       => $item->menu_order,
                    'menu_item_parent' => $item->menu_item_parent,
                ];
            }, $before_items) : [],
        ];

        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('menu', (string) $menu_id, $before, null, false, $session);
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'menu', (string) $menu_id, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'      => true,
                'dry_run'      => true,
                'snapshot_id'  => $snapshot_id,
                'menu_id'      => $menu_id,
                'menu_name'    => $menu->name,
                'items_to_add' => count($items),
                'items_preview' => array_map(function ($item) {
                    return [
                        'type'  => $item['type'] ?? 'custom',
                        'title' => $item['title'] ?? '',
                    ];
                }, $items),
            ];
        }

        // Add items
        $added = [];
        foreach ($items as $item) {
            $type      = sanitize_text_field($item['type'] ?? 'custom');
            $title     = sanitize_text_field($item['title'] ?? '');
            $url       = esc_url_raw($item['url'] ?? '');
            $object_id = absint($item['object_id'] ?? 0);
            $parent_id = absint($item['parent_item_id'] ?? 0);
            $position  = isset($item['position']) ? absint($item['position']) : 0;

            $menu_item_data = [
                'menu-item-title'     => $title,
                'menu-item-position'  => $position,
                'menu-item-parent-id' => $parent_id,
                'menu-item-status'    => 'publish',
            ];

            switch ($type) {
                case 'page':
                    $menu_item_data['menu-item-type']      = 'post_type';
                    $menu_item_data['menu-item-object']     = 'page';
                    $menu_item_data['menu-item-object-id']  = $object_id;
                    break;

                case 'post_type':
                    $menu_item_data['menu-item-type']      = 'post_type';
                    $menu_item_data['menu-item-object']     = sanitize_text_field($item['object'] ?? 'post');
                    $menu_item_data['menu-item-object-id']  = $object_id;
                    break;

                case 'category':
                    $menu_item_data['menu-item-type']      = 'taxonomy';
                    $menu_item_data['menu-item-object']     = 'category';
                    $menu_item_data['menu-item-object-id']  = $object_id;
                    break;

                case 'custom':
                default:
                    $menu_item_data['menu-item-type'] = 'custom';
                    $menu_item_data['menu-item-url']  = $url;
                    break;
            }

            $new_item_id = wp_update_nav_menu_item($menu_id, 0, $menu_item_data);

            if (is_wp_error($new_item_id)) {
                $added[] = [
                    'title' => $title,
                    'error' => $new_item_id->get_error_message(),
                ];
            } else {
                $added[] = [
                    'id'    => $new_item_id,
                    'title' => $title,
                    'type'  => $type,
                ];
            }
        }

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, ['added' => $added]);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'menu_id'      => $menu_id,
            'menu_name'    => $menu->name,
            'items_added'  => $added,
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    /**
     * POST /claude/v3/menu/items/reorder
     *
     * Reorders menu items by updating menu_order and parent for each item.
     */
    public static function handle_menu_items_reorder(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('menus')) {
            return new WP_Error('power_mode_off', "Power mode voor 'menus' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $menu_id = $request->get_param('menu_id');
        $items   = $request->get_param('items');
        $dry_run = (bool) $request->get_param('dry_run');
        $session = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Verify menu exists
        $menu = wp_get_nav_menu_object($menu_id);
        if (!$menu) {
            return new WP_Error('menu_not_found', "Menu met ID {$menu_id} niet gevonden", ['status' => 404]);
        }

        if (!is_array($items) || empty($items)) {
            return new WP_Error('invalid_items', 'Items array is verplicht en mag niet leeg zijn', ['status' => 400]);
        }

        // Capture before state
        $before_items = wp_get_nav_menu_items($menu_id);
        $before = [
            'items' => $before_items ? array_map(function ($item) {
                return [
                    'ID'               => $item->ID,
                    'menu_order'       => $item->menu_order,
                    'menu_item_parent' => $item->menu_item_parent,
                ];
            }, $before_items) : [],
        ];

        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('menu', (string) $menu_id, $before, null, false, $session);
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'menu', (string) $menu_id, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'       => true,
                'dry_run'       => true,
                'snapshot_id'   => $snapshot_id,
                'menu_id'       => $menu_id,
                'menu_name'     => $menu->name,
                'items_to_reorder' => count($items),
            ];
        }

        // Reorder items
        $updated = [];
        foreach ($items as $item) {
            $item_id  = absint($item['id'] ?? 0);
            $position = absint($item['position'] ?? 0);
            $parent   = absint($item['parent'] ?? 0);

            if ($item_id <= 0) continue;

            // Update menu_order via direct post update
            wp_update_post([
                'ID'         => $item_id,
                'menu_order' => $position,
            ]);

            // Update parent
            update_post_meta($item_id, '_menu_item_menu_item_parent', $parent);

            $updated[] = [
                'id'       => $item_id,
                'position' => $position,
                'parent'   => $parent,
            ];
        }

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, ['updated' => $updated]);

        return [
            'success'       => true,
            'snapshot_id'   => $snapshot_id,
            'menu_id'       => $menu_id,
            'menu_name'     => $menu->name,
            'items_updated' => $updated,
            'rollback_url'  => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    /**
     * POST /claude/v3/menu/assign
     *
     * Assigns a menu to a theme location.
     */
    public static function handle_menu_assign(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('menus')) {
            return new WP_Error('power_mode_off', "Power mode voor 'menus' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $menu_id  = $request->get_param('menu_id');
        $location = $request->get_param('location');
        $dry_run  = (bool) $request->get_param('dry_run');
        $session  = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Verify menu exists
        $menu = wp_get_nav_menu_object($menu_id);
        if (!$menu) {
            return new WP_Error('menu_not_found', "Menu met ID {$menu_id} niet gevonden", ['status' => 404]);
        }

        // Verify location is registered
        $registered = get_registered_nav_menus();
        if (!isset($registered[$location])) {
            return new WP_Error('invalid_location',
                "Menu locatie '{$location}' bestaat niet. Beschikbaar: " . implode(', ', array_keys($registered)),
                ['status' => 400]
            );
        }

        // Capture before state
        $before_locations = get_nav_menu_locations();

        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('menu', "assign:{$location}", ['locations' => $before_locations], null, false, $session);
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'menu', "assign:{$location}", $session, $dry_run);

        if ($dry_run) {
            $previous_menu = isset($before_locations[$location]) ? (int) $before_locations[$location] : null;
            return [
                'success'       => true,
                'dry_run'       => true,
                'snapshot_id'   => $snapshot_id,
                'menu_id'       => $menu_id,
                'menu_name'     => $menu->name,
                'location'      => $location,
                'location_label'=> $registered[$location],
                'previous_menu' => $previous_menu,
            ];
        }

        // Assign menu to location
        $locations = get_nav_menu_locations();
        $locations[$location] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, ['locations' => $locations]);

        return [
            'success'        => true,
            'snapshot_id'    => $snapshot_id,
            'menu_id'        => $menu_id,
            'menu_name'      => $menu->name,
            'location'       => $location,
            'location_label' => $registered[$location],
            'rollback_url'   => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    /**
     * POST /claude/v3/menu/mega-menu/configure
     *
     * Configures Max Mega Menu settings for a specific menu item.
     */
    public static function handle_mega_menu_configure(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('menus')) {
            return new WP_Error('power_mode_off', "Power mode voor 'menus' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        // Check if Max Mega Menu is active
        if (!class_exists('Mega_Menu')) {
            return new WP_Error('mega_menu_not_active', 'Max Mega Menu plugin is niet actief', ['status' => 400]);
        }

        $menu_item_id = $request->get_param('menu_item_id');
        $settings     = $request->get_param('settings');
        $dry_run      = (bool) $request->get_param('dry_run');
        $session      = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Verify menu item exists
        $menu_item = get_post($menu_item_id);
        if (!$menu_item || $menu_item->post_type !== 'nav_menu_item') {
            return new WP_Error('menu_item_not_found', "Menu item met ID {$menu_item_id} niet gevonden", ['status' => 404]);
        }

        // Capture before state
        $before = get_post_meta($menu_item_id, '_megamenu', true);
        if (!is_array($before)) {
            $before = [];
        }

        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('menu', "megamenu:{$menu_item_id}", $before, null, false, $session);
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'menu', "megamenu:{$menu_item_id}", $session, $dry_run);

        // Merge settings
        $after = array_merge($before, $settings);

        if ($dry_run) {
            return [
                'success'      => true,
                'dry_run'      => true,
                'snapshot_id'  => $snapshot_id,
                'menu_item_id' => $menu_item_id,
                'before'       => $before,
                'after'        => $after,
            ];
        }

        // Save mega menu settings
        update_post_meta($menu_item_id, '_megamenu', $after);

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $after);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'menu_item_id' => $menu_item_id,
            'settings'     => $after,
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }
}
