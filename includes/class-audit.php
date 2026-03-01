<?php
/**
 * LOIQ Agent Audit Trail
 *
 * Extended audit logging for write operations. Complements the snapshot table
 * with human-readable formatting and session-level querying.
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class LOIQ_Agent_Audit {

    /**
     * Log a write action to the existing request log table with extended details.
     *
     * @param string $endpoint     The REST route
     * @param int    $status_code  HTTP response code
     * @param string $action_type  css|option|plugin|content|snippet|rollback
     * @param string $target_key   What was changed
     * @param string $session_id   Claude session identifier
     * @param bool   $dry_run      Was this a dry-run?
     */
    public static function log_write(string $endpoint, int $status_code, string $action_type = '', string $target_key = '', string $session_id = '', bool $dry_run = false): void {
        global $wpdb;
        $table = $wpdb->prefix . 'loiq_agent_log';

        // Anonymize IP
        $ip = self::get_anonymized_ip();

        // Build extended endpoint info for the log
        $log_endpoint = $endpoint;
        if ($action_type) {
            $log_endpoint .= ' [' . $action_type;
            if ($target_key) {
                $log_endpoint .= ':' . substr($target_key, 0, 50);
            }
            if ($dry_run) {
                $log_endpoint .= ' DRY-RUN';
            }
            $log_endpoint .= ']';
        }

        $wpdb->insert($table, [
            'endpoint'      => sanitize_text_field(substr($log_endpoint, 0, 100)),
            'ip_address'    => $ip,
            'response_code' => (int) $status_code,
            'created_at'    => current_time('mysql'),
        ], ['%s', '%s', '%d', '%s']);
    }

    /**
     * Get write audit entries from the snapshot table with formatted output.
     *
     * @param int    $limit
     * @param string $action_type  Optional filter by action type
     * @return array
     */
    public static function get_write_history(int $limit = 20, string $action_type = ''): array {
        global $wpdb;
        $table = $wpdb->prefix . 'loiq_agent_snapshots';

        if ($action_type) {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE action_type = %s ORDER BY created_at DESC LIMIT %d",
                $action_type,
                (int) $limit
            ));
        } else {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
                (int) $limit
            ));
        }

        $result = [];
        foreach ($rows as $row) {
            $entry = [
                'id'          => (int) $row->id,
                'action_type' => $row->action_type,
                'target_key'  => $row->target_key,
                'executed'    => (bool) $row->executed,
                'rolled_back' => (bool) $row->rolled_back,
                'session_id'  => $row->session_id,
                'ip_address'  => $row->ip_address,
                'created_at'  => $row->created_at,
                'summary'     => self::format_summary($row),
            ];

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Get audit entries for a specific session.
     *
     * @param string $session_id
     * @return array
     */
    public static function get_session_history(string $session_id): array {
        global $wpdb;
        $table = $wpdb->prefix . 'loiq_agent_snapshots';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE session_id = %s ORDER BY created_at ASC",
            sanitize_text_field($session_id)
        ));

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id'          => (int) $row->id,
                'action_type' => $row->action_type,
                'target_key'  => $row->target_key,
                'executed'    => (bool) $row->executed,
                'rolled_back' => (bool) $row->rolled_back,
                'created_at'  => $row->created_at,
                'summary'     => self::format_summary($row),
            ];
        }

        return $result;
    }

    /**
     * Generate a human-readable summary for a snapshot entry.
     *
     * @param object $row
     * @return string
     */
    private static function format_summary(object $row): string {
        $action = $row->action_type;
        $target = $row->target_key;
        $status = '';

        if ($row->rolled_back) {
            $status = ' (' . __('teruggedraaid', 'loiq-wp-agent') . ')';
        } elseif (!$row->executed) {
            $status = ' (dry-run)';
        }

        switch ($action) {
            case 'css':
                /* translators: 1: CSS target, 2: status suffix */
                return sprintf(__('CSS deploy naar %1$s%2$s', 'loiq-wp-agent'), $target, $status);
            case 'option':
                /* translators: 1: option name, 2: status suffix */
                return sprintf(__('Option \'%1$s\' bijgewerkt%2$s', 'loiq-wp-agent'), $target, $status);
            case 'plugin':
                /* translators: 1: plugin name, 2: status suffix */
                return sprintf(__('Plugin \'%1$s\' getoggled%2$s', 'loiq-wp-agent'), $target, $status);
            case 'content':
                /* translators: 1: post ID, 2: status suffix */
                return sprintf(__('Post #%1$s bijgewerkt%2$s', 'loiq-wp-agent'), $target, $status);
            case 'snippet':
                /* translators: 1: snippet name, 2: status suffix */
                return sprintf(__('Snippet \'%1$s\' gedeployed%2$s', 'loiq-wp-agent'), $target, $status);
            case 'divi':
                /* translators: 1: layout ID, 2: status suffix */
                return sprintf(__('Divi layout #%1$s bijgewerkt%2$s', 'loiq-wp-agent'), $target, $status);
            case 'theme_builder':
                /* translators: 1: template ID, 2: status suffix */
                return sprintf(__('Theme Builder template #%1$s bijgewerkt%2$s', 'loiq-wp-agent'), $target, $status);
            case 'child_theme':
                /* translators: 1: block name, 2: status suffix */
                return sprintf(__('functions.php block \'%1$s\' gewijzigd%2$s', 'loiq-wp-agent'), $target, $status);
            case 'menu':
                /* translators: 1: menu name, 2: status suffix */
                return sprintf(__('Menu \'%1$s\' gewijzigd%2$s', 'loiq-wp-agent'), $target, $status);
            case 'media':
                /* translators: 1: media ID, 2: status suffix */
                return sprintf(__('Media #%1$s geupload%2$s', 'loiq-wp-agent'), $target, $status);
            case 'form':
                /* translators: 1: form ID, 2: status suffix */
                return sprintf(__('Gravity Form #%1$s gewijzigd%2$s', 'loiq-wp-agent'), $target, $status);
            case 'facet':
                /* translators: %s: status suffix */
                return sprintf(__('FacetWP configuratie bijgewerkt%s', 'loiq-wp-agent'), $status);
            case 'taxonomy':
                /* translators: 1: term name, 2: status suffix */
                return sprintf(__('Taxonomy term \'%1$s\' gewijzigd%2$s', 'loiq-wp-agent'), $target, $status);
            default:
                /* translators: 1: action type, 2: target, 3: status suffix */
                return sprintf(__('%1$s op %2$s%3$s', 'loiq-wp-agent'), $action, $target, $status);
        }
    }

    /**
     * Get anonymized IP address (GDPR).
     * Only trusts REMOTE_ADDR — proxy headers are spoofable.
     *
     * @return string
     */
    private static function get_anonymized_ip(): string {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));

        // Only trust proxy headers when request comes from configured trusted proxy
        $trusted_proxy = get_option('loiq_agent_trusted_proxy', '');
        if (!empty($trusted_proxy) && trim($ip) === trim($trusted_proxy)) {
            $proxy_headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'];
            foreach ($proxy_headers as $header) {
                if (!empty($_SERVER[$header])) {
                    $forwarded = sanitize_text_field(wp_unslash(explode(',', $_SERVER[$header])[0]));
                    if (filter_var(trim($forwarded), FILTER_VALIDATE_IP)) {
                        $ip = $forwarded;
                    }
                    break;
                }
            }
        }

        return preg_replace('/\.\d+$/', '.0', trim($ip));
    }
}
