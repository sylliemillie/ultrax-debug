<?php
/**
 * LOIQ WordPress Agent Uninstall
 *
 * Cleanup plugin data when deleted (not deactivated).
 * Data deletion is opt-in: only runs if loiq_agent_delete_data option is set.
 *
 * @since 3.1.0 — opt-in data deletion (audit finding)
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Opt-in check: only delete data if explicitly enabled in admin settings.
// This prevents accidental data loss (audit finding from PIC plugin lesson).
$delete_data = get_option('loiq_agent_delete_data', false);

// Always clean up: transients, cron, mu-plugin (safe, no data loss)
global $wpdb;
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_loiq_agent_%'));
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_loiq_agent_%'));

// Remove debug logging mu-plugin (always safe)
$mu_file = ABSPATH . 'wp-content/mu-plugins/loiq-agent-logging.php';
if (file_exists($mu_file)) {
    @unlink($mu_file);
}

// Clear scheduled cron events
wp_clear_scheduled_hook('loiq_agent_prune_snapshots');

if (!$delete_data) {
    // Keep snapshots, logs, options, and snippets for potential reinstall
    return;
}

// === OPT-IN DATA DELETION BELOW ===

// Delete options (v1)
delete_option('loiq_agent_token_hash');
delete_option('loiq_agent_enabled_until');
delete_option('loiq_agent_ip_whitelist');

// Delete options (v2.0+)
delete_option('loiq_agent_power_modes');
delete_option('loiq_agent_db_version');
delete_option('loiq_agent_plugin_whitelist'); // legacy cleanup
delete_option('loiq_agent_plugin_whitelist_text'); // legacy cleanup
delete_option('loiq_agent_trusted_proxy');
delete_option('loiq_agent_auth_events');
delete_option('loiq_agent_delete_data');

// Drop tables (v1 + v2)
$wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $wpdb->prefix . 'loiq_agent_log'));
$wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $wpdb->prefix . 'loiq_agent_snapshots'));

// Remove all deployed LOIQ snippets (v2.0)
$mu_dir = ABSPATH . 'wp-content/mu-plugins';
if (is_dir($mu_dir)) {
    $snippets = glob($mu_dir . '/loiq-snippet-*.php');
    if ($snippets) {
        foreach ($snippets as $snippet) {
            @unlink($snippet);
        }
    }
}
