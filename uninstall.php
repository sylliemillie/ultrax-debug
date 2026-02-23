<?php
/**
 * LOIQ WordPress Agent Uninstall
 *
 * Cleanup all plugin data when deleted (not deactivated).
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options (v1)
delete_option('loiq_agent_token_hash');
delete_option('loiq_agent_enabled_until');
delete_option('loiq_agent_ip_whitelist');

// Delete options (v2.0)
delete_option('loiq_agent_power_modes');
delete_option('loiq_agent_db_version');
delete_option('loiq_agent_plugin_whitelist'); // legacy cleanup
delete_option('loiq_agent_plugin_whitelist_text'); // legacy cleanup
delete_option('loiq_agent_trusted_proxy');

// Delete transients (pattern matching for rate limits + lockouts — v1 + v2)
global $wpdb;
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_loiq_agent_%'));
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_loiq_agent_%'));

// Drop tables (v1 + v2)
$wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $wpdb->prefix . 'loiq_agent_log'));
$wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $wpdb->prefix . 'loiq_agent_snapshots'));

// Remove debug logging mu-plugin
$mu_file = ABSPATH . 'wp-content/mu-plugins/loiq-agent-logging.php';
if (file_exists($mu_file)) {
    @unlink($mu_file);
}

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
