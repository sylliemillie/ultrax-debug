<?php
/**
 * LOIQ Agent Taxonomy Endpoints
 *
 * List taxonomies and terms, create terms, and assign terms to posts.
 *
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class LOIQ_Agent_Taxonomy_Endpoints {

    /** Maximum terms to return per taxonomy in list */
    const MAX_TERMS_PER_TAXONOMY = 50;

    /**
     * Register all v3 taxonomy REST routes.
     * Called from main plugin class via rest_api_init.
     *
     * @param LOIQ_WP_Agent $plugin  Reference to main plugin for permission callbacks
     */
    public static function register_routes($plugin) {
        $namespace = 'claude/v3';

        // --- TAXONOMY ENDPOINTS ---

        register_rest_route($namespace, '/taxonomy/list', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_taxonomy_list'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [],
        ]);

        register_rest_route($namespace, '/taxonomy/create-term', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_create_term'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'taxonomy'    => ['required' => true,  'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'name'        => ['required' => true,  'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'slug'        => ['required' => false, 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_title'],
                'parent'      => ['required' => false, 'type' => 'integer', 'default' => 0, 'minimum' => 0, 'sanitize_callback' => 'absint'],
                'description' => ['required' => false, 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field'],
                'dry_run'     => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/taxonomy/assign', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_assign_terms'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'post_id'  => ['required' => true,  'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
                'taxonomy' => ['required' => true,  'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'terms'    => ['required' => true,  'type' => 'array'],
                'append'   => ['required' => false, 'type' => 'boolean', 'default' => true],
                'dry_run'  => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);
    }

    // =========================================================================
    // HANDLERS
    // =========================================================================

    /**
     * GET /claude/v3/taxonomy/list
     *
     * List all taxonomies with their terms.
     */
    public static function handle_taxonomy_list(WP_REST_Request $request) {
        $taxonomies = get_taxonomies([], 'objects');
        $result = [];

        foreach ($taxonomies as $taxonomy) {
            $tax_data = [
                'name'        => $taxonomy->name,
                'label'       => $taxonomy->label,
                'public'      => (bool) $taxonomy->public,
                'hierarchical'=> (bool) $taxonomy->hierarchical,
                'post_types'  => (array) $taxonomy->object_type,
                'count'       => (int) wp_count_terms(['taxonomy' => $taxonomy->name, 'hide_empty' => false]),
                'terms'       => [],
            ];

            // Get first N terms
            $terms = get_terms([
                'taxonomy'   => $taxonomy->name,
                'number'     => self::MAX_TERMS_PER_TAXONOMY,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    $tax_data['terms'][] = [
                        'id'     => (int) $term->term_id,
                        'name'   => $term->name,
                        'slug'   => $term->slug,
                        'count'  => (int) $term->count,
                        'parent' => (int) $term->parent,
                    ];
                }
            }

            $result[] = $tax_data;
        }

        return [
            'taxonomies' => $result,
            'total'      => count($result),
        ];
    }

    /**
     * POST /claude/v3/taxonomy/create-term
     *
     * Create a new term in a taxonomy.
     */
    public static function handle_create_term(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('content')) {
            return new WP_Error('power_mode_off', "Power mode voor 'content' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $taxonomy    = $request->get_param('taxonomy');
        $name        = $request->get_param('name');
        $slug        = $request->get_param('slug');
        $parent      = (int) $request->get_param('parent');
        $description = $request->get_param('description');
        $dry_run     = (bool) $request->get_param('dry_run');
        $session     = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Validate taxonomy exists
        if (!taxonomy_exists($taxonomy)) {
            return new WP_Error('taxonomy_not_found',
                "Taxonomy '{$taxonomy}' bestaat niet",
                ['status' => 404]
            );
        }

        // Check if term already exists
        $existing = term_exists($name, $taxonomy);
        if ($existing) {
            $existing_term = get_term($existing['term_id'], $taxonomy);
            return new WP_Error('term_exists',
                "Term '{$name}' bestaat al in taxonomy '{$taxonomy}' (ID: {$existing['term_id']}, slug: {$existing_term->slug})",
                ['status' => 409]
            );
        }

        // Validate parent if provided
        if ($parent > 0) {
            $tax_obj = get_taxonomy($taxonomy);
            if (!$tax_obj->hierarchical) {
                return new WP_Error('not_hierarchical',
                    "Taxonomy '{$taxonomy}' is niet hierarchisch, parent is niet ondersteund",
                    ['status' => 400]
                );
            }
            $parent_term = get_term($parent, $taxonomy);
            if (!$parent_term || is_wp_error($parent_term)) {
                return new WP_Error('parent_not_found',
                    "Parent term #{$parent} niet gevonden in taxonomy '{$taxonomy}'",
                    ['status' => 404]
                );
            }
        }

        // Build term args
        $term_args = [];
        if (!empty($slug)) {
            $term_args['slug'] = $slug;
        }
        if ($parent > 0) {
            $term_args['parent'] = $parent;
        }
        if (!empty($description)) {
            $term_args['description'] = $description;
        }

        // Create snapshot (before_value = null for new terms)
        $target_key = $taxonomy . ':new';
        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('taxonomy', $target_key, null, null, false, $session);

        // Audit log
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'taxonomy', $taxonomy . ':' . $name, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'     => true,
                'dry_run'     => true,
                'snapshot_id' => $snapshot_id,
                'taxonomy'    => $taxonomy,
                'name'        => $name,
                'slug'        => $slug ?: sanitize_title($name),
                'parent'      => $parent,
                'description' => $description,
            ];
        }

        // Insert term
        $result = wp_insert_term($name, $taxonomy, $term_args);
        if (is_wp_error($result)) {
            return $result;
        }

        $term_id = $result['term_id'];
        $term = get_term($term_id, $taxonomy);

        // Update snapshot target_key with actual term ID
        self::update_snapshot_target($snapshot_id, $taxonomy . ':' . $term_id);

        // Mark snapshot as executed
        $after = [
            'term_id'  => $term_id,
            'taxonomy' => $taxonomy,
            'name'     => $term->name,
            'slug'     => $term->slug,
        ];
        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $after);

        return [
            'success'      => true,
            'term_id'      => $term_id,
            'taxonomy'     => $taxonomy,
            'name'         => $term->name,
            'slug'         => $term->slug,
            'parent'       => (int) $term->parent,
            'description'  => $term->description,
            'snapshot_id'  => $snapshot_id,
            'rollback_url' => rest_url('claude/v3/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    /**
     * POST /claude/v3/taxonomy/assign
     *
     * Assign terms to a post.
     */
    public static function handle_assign_terms(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('content')) {
            return new WP_Error('power_mode_off', "Power mode voor 'content' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $post_id  = (int) $request->get_param('post_id');
        $taxonomy = $request->get_param('taxonomy');
        $terms    = $request->get_param('terms');
        $append   = (bool) $request->get_param('append');
        $dry_run  = (bool) $request->get_param('dry_run');
        $session  = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Validate post exists
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('post_not_found', "Post #{$post_id} niet gevonden", ['status' => 404]);
        }

        // Validate taxonomy exists
        if (!taxonomy_exists($taxonomy)) {
            return new WP_Error('taxonomy_not_found',
                "Taxonomy '{$taxonomy}' bestaat niet",
                ['status' => 404]
            );
        }

        // Validate taxonomy is registered for this post type
        $tax_obj = get_taxonomy($taxonomy);
        if (!in_array($post->post_type, (array) $tax_obj->object_type, true)) {
            return new WP_Error('taxonomy_not_for_post_type',
                "Taxonomy '{$taxonomy}' is niet geregistreerd voor post type '{$post->post_type}'",
                ['status' => 400]
            );
        }

        // Sanitize terms array — can be term IDs (int) or names (string)
        $sanitized_terms = self::sanitize_terms_input($terms, $taxonomy);
        if (is_wp_error($sanitized_terms)) {
            return $sanitized_terms;
        }

        // Capture current terms (before state)
        $current_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'all']);
        $before = [];
        if (!is_wp_error($current_terms)) {
            foreach ($current_terms as $ct) {
                $before[] = [
                    'term_id' => (int) $ct->term_id,
                    'name'    => $ct->name,
                    'slug'    => $ct->slug,
                ];
            }
        }

        // Create snapshot
        $target_key = $post_id . ':' . $taxonomy;
        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('taxonomy', $target_key, $before, null, false, $session);

        // Audit log
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'taxonomy', $target_key, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'       => true,
                'dry_run'       => true,
                'snapshot_id'   => $snapshot_id,
                'post_id'       => $post_id,
                'taxonomy'      => $taxonomy,
                'current_terms' => $before,
                'new_terms'     => $sanitized_terms,
                'append'        => $append,
            ];
        }

        // Assign terms
        $result = wp_set_object_terms($post_id, $sanitized_terms, $taxonomy, $append);
        if (is_wp_error($result)) {
            return $result;
        }

        // Capture after state
        $after_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'all']);
        $after = [];
        if (!is_wp_error($after_terms)) {
            foreach ($after_terms as $at) {
                $after[] = [
                    'term_id' => (int) $at->term_id,
                    'name'    => $at->name,
                    'slug'    => $at->slug,
                ];
            }
        }

        // Mark snapshot as executed
        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $after);

        return [
            'success'       => true,
            'post_id'       => $post_id,
            'taxonomy'      => $taxonomy,
            'before_terms'  => $before,
            'after_terms'   => $after,
            'append'        => $append,
            'snapshot_id'   => $snapshot_id,
            'rollback_url'  => rest_url('claude/v3/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Sanitize terms input array. Accepts mixed term IDs (integers) and
     * term names (strings). Validates that referenced IDs exist.
     *
     * @param array  $terms
     * @param string $taxonomy
     * @return array|WP_Error  Sanitized array of term IDs and/or names, or error
     */
    private static function sanitize_terms_input($terms, $taxonomy) {
        if (!is_array($terms) || empty($terms)) {
            return new WP_Error('invalid_terms', 'Terms moet een niet-lege array zijn', ['status' => 400]);
        }

        $sanitized = [];
        foreach ($terms as $term) {
            if (is_int($term) || (is_string($term) && ctype_digit($term))) {
                // Numeric: treat as term ID
                $term_id = (int) $term;
                $existing = get_term($term_id, $taxonomy);
                if (!$existing || is_wp_error($existing)) {
                    return new WP_Error('term_not_found',
                        "Term ID #{$term_id} niet gevonden in taxonomy '{$taxonomy}'",
                        ['status' => 404]
                    );
                }
                $sanitized[] = $term_id;
            } elseif (is_string($term)) {
                // String: treat as term name (wp_set_object_terms will create if needed for non-hierarchical)
                $sanitized[] = sanitize_text_field($term);
            } else {
                return new WP_Error('invalid_term_value',
                    'Ongeldige term waarde: verwacht integer (ID) of string (naam)',
                    ['status' => 400]
                );
            }
        }

        return $sanitized;
    }

    /**
     * Update snapshot target_key after term creation.
     *
     * @param int    $snapshot_id
     * @param string $target_key
     */
    private static function update_snapshot_target($snapshot_id, $target_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'loiq_agent_snapshots';

        $wpdb->update(
            $table,
            ['target_key' => sanitize_text_field(substr($target_key, 0, 255))],
            ['id' => (int) $snapshot_id],
            ['%s'],
            ['%d']
        );
    }
}
