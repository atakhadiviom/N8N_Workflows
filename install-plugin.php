<?php
/**
 * N8N Workflows Explorer - Installation Helper
 * This script helps with plugin installation and setup
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // This is a standalone installation helper
    // Can be run independently to check requirements
}

class N8NWorkflowsInstaller {
    
    public static function check_requirements() {
        $requirements = [
            'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'wordpress_version' => function_exists('get_bloginfo') ? version_compare(get_bloginfo('version'), '5.0', '>=') : null,
            'curl_enabled' => extension_loaded('curl'),
            'json_enabled' => extension_loaded('json'),
            'openssl_enabled' => extension_loaded('openssl'),
            'wp_remote_get' => function_exists('wp_remote_get'),
            'wp_cron' => function_exists('wp_schedule_event')
        ];
        
        return $requirements;
    }
    
    public static function display_requirements_check() {
        $requirements = self::check_requirements();
        
        echo "<div class='notice notice-info'>";
        echo "<h3>N8N Workflows Explorer - System Requirements Check</h3>";
        echo "<table class='widefat'>";
        echo "<thead><tr><th>Requirement</th><th>Status</th><th>Notes</th></tr></thead>";
        echo "<tbody>";
        
        $checks = [
            'php_version' => ['PHP 7.4+', $requirements['php_version'], 'Current: ' . PHP_VERSION],
            'wordpress_version' => ['WordPress 5.0+', $requirements['wordpress_version'], function_exists('get_bloginfo') ? 'Current: ' . get_bloginfo('version') : 'WordPress not detected'],
            'curl_enabled' => ['cURL Extension', $requirements['curl_enabled'], 'Required for GitHub API calls'],
            'json_enabled' => ['JSON Extension', $requirements['json_enabled'], 'Required for API responses'],
            'openssl_enabled' => ['OpenSSL Extension', $requirements['openssl_enabled'], 'Required for HTTPS API calls'],
            'wp_remote_get' => ['WordPress HTTP API', $requirements['wp_remote_get'], 'Required for API requests'],
            'wp_cron' => ['WordPress Cron', $requirements['wp_cron'], 'Required for daily scraping']
        ];
        
        foreach ($checks as $key => $check) {
            $status = $check[1];
            $status_text = $status === true ? '✅ Pass' : ($status === false ? '❌ Fail' : '⚠️ Unknown');
            $status_class = $status === true ? 'notice-success' : ($status === false ? 'notice-error' : 'notice-warning');
            
            echo "<tr class='{$status_class}'>";
            echo "<td><strong>{$check[0]}</strong></td>";
            echo "<td>{$status_text}</td>";
            echo "<td>{$check[2]}</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        
        $all_passed = array_reduce($requirements, function($carry, $item) {
            return $carry && ($item === true || $item === null);
        }, true);
        
        if ($all_passed) {
            echo "<p class='notice-success'><strong>✅ All requirements met! You can safely install the N8N Workflows Explorer plugin.</strong></p>";
        } else {
            echo "<p class='notice-error'><strong>❌ Some requirements are not met. Please resolve the issues above before installing.</strong></p>";
        }
        
        echo "</div>";
    }
    
    public static function create_plugin_structure() {
        $plugin_dir = WP_PLUGIN_DIR . '/n8n-workflows-explorer';
        
        if (!file_exists($plugin_dir)) {
            wp_mkdir_p($plugin_dir);
            wp_mkdir_p($plugin_dir . '/assets');
        }
        
        return $plugin_dir;
    }
    
    public static function setup_default_options() {
        $defaults = [
            'n8n_max_workflows' => 10,
            'n8n_auto_scrape_enabled' => 1,
            'n8n_openai_api_key' => '',
            'n8n_github_token' => ''
        ];
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    public static function create_database_tables() {
        // This plugin uses WordPress custom posts, so no additional tables needed
        // But we can ensure proper indexes exist
        
        global $wpdb;
        
        // Ensure meta_key index exists for better performance
        $wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX meta_key_value (meta_key, meta_value(191))");
    }
    
    public static function install() {
        // Check requirements
        $requirements = self::check_requirements();
        $all_passed = array_reduce($requirements, function($carry, $item) {
            return $carry && ($item === true || $item === null);
        }, true);
        
        if (!$all_passed) {
            wp_die('N8N Workflows Explorer: System requirements not met. Please check the requirements and try again.');
        }
        
        // Create plugin structure
        self::create_plugin_structure();
        
        // Setup default options
        self::setup_default_options();
        
        // Create database optimizations
        self::create_database_tables();
        
        // Flush rewrite rules to ensure custom post type URLs work
        flush_rewrite_rules();
        
        return true;
    }
    
    public static function uninstall() {
        // Remove all plugin data
        $options_to_remove = [
            'n8n_max_workflows',
            'n8n_auto_scrape_enabled',
            'n8n_openai_api_key',
            'n8n_github_token'
        ];
        
        foreach ($options_to_remove as $option) {
            delete_option($option);
        }
        
        // Remove all workflow posts
        $workflows = get_posts([
            'post_type' => 'n8n_workflow',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($workflows as $workflow) {
            wp_delete_post($workflow->ID, true);
        }
        
        // Remove taxonomy terms
        $terms = get_terms([
            'taxonomy' => 'workflow_category',
            'hide_empty' => false
        ]);
        
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, 'workflow_category');
        }
        
        // Clear scheduled events
        wp_clear_scheduled_hook('n8n_daily_scrape');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// If running as standalone script
if (!defined('ABSPATH')) {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>N8N Workflows Explorer - Requirements Check</title>";
    echo "<style>";
    echo "body { font-family: Arial, sans-serif; margin: 40px; }";
    echo "table { border-collapse: collapse; width: 100%; margin: 20px 0; }";
    echo "th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }";
    echo "th { background-color: #f2f2f2; }";
    echo ".pass { color: green; }";
    echo ".fail { color: red; }";
    echo ".unknown { color: orange; }";
    echo "</style>";
    echo "</head><body>";
    
    echo "<h1>N8N Workflows Explorer - Requirements Check</h1>";
    
    $requirements = N8NWorkflowsInstaller::check_requirements();
    
    echo "<table>";
    echo "<tr><th>Requirement</th><th>Status</th><th>Notes</th></tr>";
    
    $checks = [
        'php_version' => ['PHP 7.4+', $requirements['php_version'], 'Current: ' . PHP_VERSION],
        'curl_enabled' => ['cURL Extension', $requirements['curl_enabled'], 'Required for GitHub API calls'],
        'json_enabled' => ['JSON Extension', $requirements['json_enabled'], 'Required for API responses'],
        'openssl_enabled' => ['OpenSSL Extension', $requirements['openssl_enabled'], 'Required for HTTPS API calls']
    ];
    
    $all_passed = true;
    
    foreach ($checks as $key => $check) {
        $status = $check[1];
        $status_text = $status === true ? '✅ Pass' : ($status === false ? '❌ Fail' : '⚠️ Unknown');
        $status_class = $status === true ? 'pass' : ($status === false ? 'fail' : 'unknown');
        
        if ($status !== true && $status !== null) {
            $all_passed = false;
        }
        
        echo "<tr>";
        echo "<td><strong>{$check[0]}</strong></td>";
        echo "<td class='{$status_class}'>{$status_text}</td>";
        echo "<td>{$check[2]}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    if ($all_passed) {
        echo "<p style='color: green; font-weight: bold;'>✅ All basic requirements met! You can proceed with WordPress plugin installation.</p>";
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Upload all plugin files to <code>/wp-content/plugins/n8n-workflows-explorer/</code></li>";
        echo "<li>Activate the plugin in WordPress Admin → Plugins</li>";
        echo "<li>Configure your OpenAI API key and GitHub token in WordPress Admin → N8N Workflows</li>";
        echo "<li>Run your first scrape to import workflows</li>";
        echo "<li>Use the <code>[n8n_workflows]</code> shortcode to display workflows</li>";
        echo "</ol>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Some requirements are not met. Please resolve the issues above before installing.</p>";
    }
    
    echo "</body></html>";
}
?>