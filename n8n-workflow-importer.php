<?php
/**
 * Plugin Name: N8N Workflow Importer
 * Plugin URI: https://github.com/your-username/n8n-workflow-importer
 * Description: A WordPress plugin that searches for n8n workflows on GitHub and creates custom posts with titles, descriptions, and JSON file content.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: n8n-workflow-importer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('N8N_WORKFLOW_IMPORTER_VERSION', '1.0.0');
define('N8N_WORKFLOW_IMPORTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('N8N_WORKFLOW_IMPORTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once N8N_WORKFLOW_IMPORTER_PLUGIN_DIR . 'includes/class-n8n-workflow-importer.php';
require_once N8N_WORKFLOW_IMPORTER_PLUGIN_DIR . 'includes/class-github-api.php';
require_once N8N_WORKFLOW_IMPORTER_PLUGIN_DIR . 'includes/class-workflow-post-type.php';
require_once N8N_WORKFLOW_IMPORTER_PLUGIN_DIR . 'admin/class-admin-page.php';

// Initialize the plugin
function n8n_workflow_importer_init() {
    $plugin = new N8N_Workflow_Importer();
    $plugin->run();
}
add_action('plugins_loaded', 'n8n_workflow_importer_init');

// Activation hook
register_activation_hook(__FILE__, 'n8n_workflow_importer_activate');
function n8n_workflow_importer_activate() {
    // Create custom post type
    $workflow_post_type = new Workflow_Post_Type();
    $workflow_post_type->register_post_type();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'n8n_workflow_importer_deactivate');
function n8n_workflow_importer_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Add settings link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'n8n_workflow_importer_settings_link');
function n8n_workflow_importer_settings_link($links) {
    $settings_link = '<a href="admin.php?page=n8n-workflow-importer">' . __('Settings', 'n8n-workflow-importer') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}