<?php
/**
 * LOIQ Agent Forms Endpoints
 *
 * REST API endpoints for Gravity Forms management:
 * create, update, delete forms, and generate embed shortcodes.
 *
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class LOIQ_Agent_Forms_Endpoints {

    /**
     * Register all v3 forms REST routes.
     *
     * @param LOIQ_WP_Agent $plugin  Reference to main plugin for permission callbacks
     */
    public static function register_routes($plugin) {
        $namespace = 'claude/v3';

        // --- WRITE ENDPOINTS ---

        register_rest_route($namespace, '/forms/create', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_forms_create'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'title'          => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'fields'         => ['required' => true, 'type' => 'array'],
                'description'    => ['required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'],
                'confirmations'  => ['required' => false, 'type' => 'object'],
                'notifications'  => ['required' => false, 'type' => 'object'],
                'dry_run'        => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/forms/update', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_forms_update'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'form_id'        => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
                'title'          => ['required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'description'    => ['required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'],
                'fields'         => ['required' => false, 'type' => 'array'],
                'confirmations'  => ['required' => false, 'type' => 'object'],
                'notifications'  => ['required' => false, 'type' => 'object'],
                'dry_run'        => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        register_rest_route($namespace, '/forms/delete', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_forms_delete'],
            'permission_callback' => [$plugin, 'check_write_permission'],
            'args'                => [
                'form_id' => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
                'dry_run' => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);

        // --- READ ENDPOINTS ---

        register_rest_route($namespace, '/forms/embed', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_forms_embed'],
            'permission_callback' => [$plugin, 'check_permission'],
            'args'                => [
                'form_id'     => ['required' => true, 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint'],
                'title'       => ['required' => false, 'type' => 'boolean', 'default' => true],
                'description' => ['required' => false, 'type' => 'boolean', 'default' => true],
                'ajax'        => ['required' => false, 'type' => 'boolean', 'default' => false],
            ],
        ]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Check if Gravity Forms is active.
     *
     * @return true|WP_Error
     */
    private static function check_gravity_forms() {
        if (!class_exists('GFAPI')) {
            return new WP_Error('gf_not_active', 'Gravity Forms is niet actief. Installeer en activeer Gravity Forms eerst.', ['status' => 400]);
        }
        return true;
    }

    // =========================================================================
    // WRITE HANDLERS
    // =========================================================================

    /**
     * POST /claude/v3/forms/create
     *
     * Creates a new Gravity Forms form.
     */
    public static function handle_forms_create(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('forms')) {
            return new WP_Error('power_mode_off', "Power mode voor 'forms' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $gf_check = self::check_gravity_forms();
        if (is_wp_error($gf_check)) return $gf_check;

        $title          = $request->get_param('title');
        $fields         = $request->get_param('fields');
        $description    = $request->get_param('description');
        $confirmations  = $request->get_param('confirmations');
        $notifications  = $request->get_param('notifications');
        $dry_run        = (bool) $request->get_param('dry_run');
        $session        = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Validate fields
        if (!is_array($fields) || empty($fields)) {
            return new WP_Error('invalid_fields', 'Fields array is verplicht en mag niet leeg zijn', ['status' => 400]);
        }

        // Build form array
        $form = [
            'title'  => $title,
            'fields' => [],
        ];

        if (!empty($description)) {
            $form['description'] = $description;
        }

        // Build field objects
        $field_id = 1;
        foreach ($fields as $field_data) {
            if (empty($field_data['type'])) {
                return new WP_Error('field_missing_type', "Veld #{$field_id} mist verplicht 'type'", ['status' => 400]);
            }

            $field = GF_Fields::create($field_data);
            if (!$field) {
                // Fallback: create basic field
                $field_data['id'] = $field_id;
                $form['fields'][] = $field_data;
            } else {
                $field->id = $field_id;
                $form['fields'][] = $field;
            }

            $field_id++;
        }

        // Add confirmations if provided
        if (!empty($confirmations) && is_array($confirmations)) {
            $form['confirmations'] = $confirmations;
        }

        // Add notifications if provided
        if (!empty($notifications) && is_array($notifications)) {
            $form['notifications'] = $notifications;
        }

        // Snapshot: before = null (new form)
        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('form', $title, null, null, false, $session);
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'form', $title, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'      => true,
                'dry_run'      => true,
                'snapshot_id'  => $snapshot_id,
                'title'        => $title,
                'field_count'  => count($form['fields']),
                'fields_preview' => array_map(function ($f) {
                    $data = is_object($f) ? ['type' => $f->type, 'label' => $f->label ?? ''] : ['type' => $f['type'] ?? '', 'label' => $f['label'] ?? ''];
                    return $data;
                }, $form['fields']),
            ];
        }

        // Create the form
        $result = GFAPI::add_form($form);

        if (is_wp_error($result)) {
            return new WP_Error('form_create_failed', 'Form aanmaken mislukt: ' . $result->get_error_message(), ['status' => 500]);
        }

        $form_id = $result;

        // Update snapshot target_key with form ID
        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, ['form_id' => $form_id, 'title' => $title]);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'form_id'      => $form_id,
            'title'        => $title,
            'field_count'  => count($form['fields']),
            'embed'        => '[gravityform id="' . $form_id . '" title="true" description="true"]',
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    /**
     * POST /claude/v3/forms/update
     *
     * Updates an existing Gravity Forms form.
     */
    public static function handle_forms_update(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('forms')) {
            return new WP_Error('power_mode_off', "Power mode voor 'forms' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $gf_check = self::check_gravity_forms();
        if (is_wp_error($gf_check)) return $gf_check;

        $form_id        = $request->get_param('form_id');
        $dry_run        = (bool) $request->get_param('dry_run');
        $session        = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Get current form
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return new WP_Error('form_not_found', "Formulier met ID {$form_id} niet gevonden", ['status' => 404]);
        }

        // Capture before state (full form data)
        $before = $form;

        // Apply updates
        $title         = $request->get_param('title');
        $description   = $request->get_param('description');
        $fields        = $request->get_param('fields');
        $confirmations = $request->get_param('confirmations');
        $notifications = $request->get_param('notifications');

        $changes = [];

        if ($title !== null) {
            $form['title'] = $title;
            $changes[] = 'title';
        }

        if ($description !== null) {
            $form['description'] = $description;
            $changes[] = 'description';
        }

        if ($fields !== null && is_array($fields)) {
            // Rebuild fields with proper IDs
            $new_fields = [];
            $field_id = 1;
            foreach ($fields as $field_data) {
                if (empty($field_data['type'])) {
                    return new WP_Error('field_missing_type', "Veld #{$field_id} mist verplicht 'type'", ['status' => 400]);
                }

                $field = GF_Fields::create($field_data);
                if (!$field) {
                    if (!isset($field_data['id'])) {
                        $field_data['id'] = $field_id;
                    }
                    $new_fields[] = $field_data;
                } else {
                    if (!isset($field_data['id'])) {
                        $field->id = $field_id;
                    }
                    $new_fields[] = $field;
                }

                $field_id++;
            }
            $form['fields'] = $new_fields;
            $changes[] = 'fields';
        }

        if ($confirmations !== null && is_array($confirmations)) {
            $form['confirmations'] = $confirmations;
            $changes[] = 'confirmations';
        }

        if ($notifications !== null && is_array($notifications)) {
            $form['notifications'] = $notifications;
            $changes[] = 'notifications';
        }

        if (empty($changes)) {
            return new WP_Error('no_changes', 'Geen wijzigingen opgegeven', ['status' => 400]);
        }

        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('form', (string) $form_id, $before, null, false, $session);
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'form', (string) $form_id, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'      => true,
                'dry_run'      => true,
                'snapshot_id'  => $snapshot_id,
                'form_id'      => $form_id,
                'title'        => $form['title'],
                'changes'      => $changes,
            ];
        }

        // Update the form
        $result = GFAPI::update_form($form, $form_id);

        if (is_wp_error($result)) {
            return new WP_Error('form_update_failed', 'Form bijwerken mislukt: ' . $result->get_error_message(), ['status' => 500]);
        }

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, $form);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'form_id'      => $form_id,
            'title'        => $form['title'],
            'changes'      => $changes,
            'field_count'  => count($form['fields']),
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    /**
     * POST /claude/v3/forms/delete
     *
     * Deletes a Gravity Forms form.
     */
    public static function handle_forms_delete(WP_REST_Request $request) {
        if (!LOIQ_Agent_Safeguards::is_enabled('forms')) {
            return new WP_Error('power_mode_off', "Power mode voor 'forms' is uitgeschakeld. Enable via wp-admin.", ['status' => 403]);
        }

        $gf_check = self::check_gravity_forms();
        if (is_wp_error($gf_check)) return $gf_check;

        $form_id = $request->get_param('form_id');
        $dry_run = (bool) $request->get_param('dry_run');
        $session = sanitize_text_field($request->get_header('X-Claude-Session') ?? '');

        // Get current form (before state)
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return new WP_Error('form_not_found', "Formulier met ID {$form_id} niet gevonden", ['status' => 404]);
        }

        // Capture full form data for potential rollback
        $before = $form;
        $entry_count = (int) GFAPI::count_entries($form_id);

        $snapshot_id = LOIQ_Agent_Safeguards::create_snapshot('form', (string) $form_id, $before, null, false, $session);
        LOIQ_Agent_Audit::log_write($request->get_route(), 200, 'form', (string) $form_id, $session, $dry_run);

        if ($dry_run) {
            return [
                'success'      => true,
                'dry_run'      => true,
                'snapshot_id'  => $snapshot_id,
                'form_id'      => $form_id,
                'title'        => $form['title'],
                'field_count'  => count($form['fields'] ?? []),
                'entry_count'  => $entry_count,
                'warning'      => $entry_count > 0 ? "Dit formulier heeft {$entry_count} entries die NIET verwijderd worden" : null,
            ];
        }

        // Delete the form
        $result = GFAPI::delete_form($form_id);

        if (is_wp_error($result)) {
            return new WP_Error('form_delete_failed', 'Form verwijderen mislukt: ' . $result->get_error_message(), ['status' => 500]);
        }

        LOIQ_Agent_Safeguards::mark_executed($snapshot_id, null);

        return [
            'success'      => true,
            'snapshot_id'  => $snapshot_id,
            'form_id'      => $form_id,
            'title'        => $form['title'],
            'deleted'      => true,
            'rollback_url' => rest_url('claude/v2/rollback?snapshot_id=' . $snapshot_id),
        ];
    }

    // =========================================================================
    // READ HANDLERS
    // =========================================================================

    /**
     * GET /claude/v3/forms/embed
     *
     * Returns the Gravity Forms embed shortcode for a form.
     */
    public static function handle_forms_embed(WP_REST_Request $request) {
        if (!class_exists('GFAPI')) {
            return new WP_Error('gf_not_active', 'Gravity Forms is niet actief', ['status' => 400]);
        }

        $form_id     = $request->get_param('form_id');
        $title       = $request->get_param('title');
        $description = $request->get_param('description');
        $ajax        = $request->get_param('ajax');

        // Verify form exists
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return new WP_Error('form_not_found', "Formulier met ID {$form_id} niet gevonden", ['status' => 404]);
        }

        $shortcode = sprintf(
            '[gravityform id="%d" title="%s" description="%s"%s]',
            $form_id,
            $title ? 'true' : 'false',
            $description ? 'true' : 'false',
            $ajax ? ' ajax="true"' : ''
        );

        return [
            'form_id'    => $form_id,
            'title'      => $form['title'],
            'shortcode'  => $shortcode,
            'block'      => '<!-- wp:gravityforms/form {"formId":"' . $form_id . '"} /-->',
        ];
    }
}
