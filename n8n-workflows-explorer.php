<?php
/**
 * Plugin Name: N8N Workflows Explorer
 * Plugin URI: https://github.com/yourusername/n8n-workflows-explorer
 * Description: Automatically scrapes GitHub for N8N workflows and displays them on your WordPress site with AI-enhanced descriptions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: n8n-workflows-explorer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('N8N_WORKFLOWS_VERSION', '1.0.0');
define('N8N_WORKFLOWS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('N8N_WORKFLOWS_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include required files
require_once N8N_WORKFLOWS_PLUGIN_PATH . 'n8n-workflows-functions.php';
require_once N8N_WORKFLOWS_PLUGIN_PATH . 'n8n-workflows-shortcode.php';

// Main plugin class
class N8NWorkflowsExplorer {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_n8n_scrape_workflows', array($this, 'ajax_scrape_workflows'));
        add_action('wp_ajax_n8n_search_workflows', array($this, 'ajax_search_workflows'));
        
        // Shortcode
        add_shortcode('n8n_workflows', array($this, 'workflows_shortcode'));
        
        // Public AJAX handlers
        add_action('wp_ajax_nopriv_n8n_search_workflows', array($this, 'ajax_search_workflows'));
        
        // Cron hooks
        add_action('n8n_daily_scrape', array($this, 'daily_scrape_cron'));
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        load_plugin_textdomain('n8n-workflows-explorer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        $this->create_workflow_post_type();
        
        // Schedule daily scraping if enabled
        flush_rewrite_rules();
        
        // Schedule cron job if not already scheduled and auto-scrape is enabled
        if (get_option('n8n_auto_scrape', 1) && !wp_next_scheduled('n8n_daily_scrape')) {
            wp_schedule_event(time(), 'daily', 'n8n_daily_scrape');
        }
    }
    
    public function activate() {
        // Create custom post type
        $this->create_workflow_post_type();
        flush_rewrite_rules();
        
        // Schedule daily cron job
        if (!wp_next_scheduled('n8n_workflows_daily_scrape')) {
            wp_schedule_event(time(), 'daily', 'n8n_workflows_daily_scrape');
        }
    }
    
    public function deactivate() {
        // Clear scheduled cron job
        wp_clear_scheduled_hook('n8n_workflows_daily_scrape');
        flush_rewrite_rules();
    }
    

    
    public function create_workflow_post_type() {
        $labels = array(
            'name' => __('N8N Workflows', 'n8n-workflows-explorer'),
            'singular_name' => __('N8N Workflow', 'n8n-workflows-explorer'),
            'menu_name' => __('N8N Workflows', 'n8n-workflows-explorer'),
            'add_new' => __('Add New Workflow', 'n8n-workflows-explorer'),
            'add_new_item' => __('Add New Workflow', 'n8n-workflows-explorer'),
            'edit_item' => __('Edit Workflow', 'n8n-workflows-explorer'),
            'new_item' => __('New Workflow', 'n8n-workflows-explorer'),
            'view_item' => __('View Workflow', 'n8n-workflows-explorer'),
            'search_items' => __('Search Workflows', 'n8n-workflows-explorer'),
            'not_found' => __('No workflows found', 'n8n-workflows-explorer'),
            'not_found_in_trash' => __('No workflows found in trash', 'n8n-workflows-explorer')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'n8n-workflow'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-networking',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true
        );
        
        register_post_type('n8n_workflow', $args);
        
        // Add custom taxonomy for workflow categories
        register_taxonomy('workflow_category', 'n8n_workflow', array(
            'hierarchical' => true,
            'labels' => array(
                'name' => __('Workflow Categories', 'n8n-workflows-explorer'),
                'singular_name' => __('Workflow Category', 'n8n-workflows-explorer')
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'workflow-category'),
            'show_in_rest' => true
        ));
    }
    
    public function schedule_workflow_scraping() {
        if (!wp_next_scheduled('n8n_workflows_daily_scrape')) {
            wp_schedule_event(time(), 'daily', 'n8n_workflows_daily_scrape');
        }
        
        // Hook the cron job
        add_action('n8n_workflows_daily_scrape', array($this, 'daily_scrape_cron'));
    }
    
    public function daily_scrape_cron() {
        // Only run if auto-scrape is enabled
        if (get_option('n8n_auto_scrape', 1)) {
            $functions = new N8NWorkflowsExplorerFunctions();
            $functions->scrape_and_create_posts();
        }
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('N8N Workflows Settings', 'n8n-workflows-explorer'),
            __('N8N Workflows', 'n8n-workflows-explorer'),
            'manage_options',
            'n8n-workflows-settings',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('n8n_workflows_settings', 'n8n_openai_api_key');
        register_setting('n8n_workflows_settings', 'n8n_github_token');
        register_setting('n8n_workflows_settings', 'n8n_max_workflows');
        register_setting('n8n_workflows_settings', 'n8n_auto_scrape_enabled');
        
        add_settings_section(
            'n8n_workflows_api_section',
            __('API Configuration', 'n8n-workflows-explorer'),
            array($this, 'api_section_callback'),
            'n8n-workflows-settings'
        );
        
        add_settings_field(
            'n8n_openai_api_key',
            __('OpenAI API Key', 'n8n-workflows-explorer'),
            array($this, 'openai_api_key_callback'),
            'n8n-workflows-settings',
            'n8n_workflows_api_section'
        );
        
        add_settings_field(
            'n8n_github_token',
            __('GitHub Token', 'n8n-workflows-explorer'),
            array($this, 'github_token_callback'),
            'n8n-workflows-settings',
            'n8n_workflows_api_section'
        );
        
        add_settings_field(
            'n8n_max_workflows',
            __('Max Workflows to Scrape', 'n8n-workflows-explorer'),
            array($this, 'max_workflows_callback'),
            'n8n-workflows-settings',
            'n8n_workflows_api_section'
        );
        
        add_settings_field(
            'n8n_auto_scrape_enabled',
            __('Enable Daily Auto-Scraping', 'n8n-workflows-explorer'),
            array($this, 'auto_scrape_callback'),
            'n8n-workflows-settings',
            'n8n_workflows_api_section'
        );
    }
    
    public function api_section_callback() {
        echo '<p>' . __('Configure your API keys and scraping settings below.', 'n8n-workflows-explorer') . '</p>';
    }
    
    public function openai_api_key_callback() {
        $value = get_option('n8n_openai_api_key', '');
        echo '<input type="password" id="n8n_openai_api_key" name="n8n_openai_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Get your API key from OpenAI Platform. Required for AI-enhanced descriptions.', 'n8n-workflows-explorer') . '</p>';
    }
    
    public function github_token_callback() {
        $value = get_option('n8n_github_token', '');
        echo '<input type="password" id="n8n_github_token" name="n8n_github_token" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Optional. Increases rate limit from 60 to 5000 requests/hour.', 'n8n-workflows-explorer') . '</p>';
    }
    
    public function max_workflows_callback() {
        $value = get_option('n8n_max_workflows', 10);
        echo '<input type="number" id="n8n_max_workflows" name="n8n_max_workflows" value="' . esc_attr($value) . '" min="1" max="50" />';
        echo '<p class="description">' . __('Maximum number of workflows to scrape per session.', 'n8n-workflows-explorer') . '</p>';
    }
    
    public function auto_scrape_callback() {
        $value = get_option('n8n_auto_scrape_enabled', 1);
        echo '<input type="checkbox" id="n8n_auto_scrape_enabled" name="n8n_auto_scrape_enabled" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="n8n_auto_scrape_enabled">' . __('Automatically scrape for new workflows daily', 'n8n-workflows-explorer') . '</label>';
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p><strong><?php _e('Quick Start:', 'n8n-workflows-explorer'); ?></strong></p>
                <ol>
                    <li><?php _e('Configure your API keys below', 'n8n-workflows-explorer'); ?></li>
                    <li><?php _e('Click "Manual Scrape" to test the functionality', 'n8n-workflows-explorer'); ?></li>
                    <li><?php _e('Use shortcode [n8n_workflows] to display workflows on any page/post', 'n8n-workflows-explorer'); ?></li>
                </ol>
            </div>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('n8n_workflows_settings');
                do_settings_sections('n8n-workflows-settings');
                submit_button();
                ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Manual Actions', 'n8n-workflows-explorer'); ?></h2>
            <p>
                <button type="button" id="manual-scrape-btn" class="button button-primary">
                    <?php _e('Manual Scrape Now', 'n8n-workflows-explorer'); ?>
                </button>
                <span id="scrape-status"></span>
            </p>
            
            <h3><?php _e('Recent Workflows', 'n8n-workflows-explorer'); ?></h3>
            <?php
            $recent_workflows = get_posts(array(
                'post_type' => 'n8n_workflow',
                'posts_per_page' => 5,
                'post_status' => 'publish'
            ));
            
            if ($recent_workflows) {
                echo '<ul>';
                foreach ($recent_workflows as $workflow) {
                    $github_url = get_post_meta($workflow->ID, 'github_url', true);
                    echo '<li>';
                    echo '<strong>' . esc_html($workflow->post_title) . '</strong> - ';
                    echo '<a href="' . esc_url($github_url) . '" target="_blank">' . __('View on GitHub', 'n8n-workflows-explorer') . '</a> | ';
                    echo '<a href="' . get_edit_post_link($workflow->ID) . '">' . __('Edit', 'n8n-workflows-explorer') . '</a>';
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>' . __('No workflows found. Try running a manual scrape.', 'n8n-workflows-explorer') . '</p>';
            }
            ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#manual-scrape-btn').click(function() {
                var button = $(this);
                var status = $('#scrape-status');
                
                button.prop('disabled', true).text('<?php _e('Scraping...', 'n8n-workflows-explorer'); ?>');
                status.html('<span style="color: blue;"><?php _e('Scraping in progress...', 'n8n-workflows-explorer'); ?></span>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'n8n_manual_scrape',
                        nonce: '<?php echo wp_create_nonce('n8n_manual_scrape'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            status.html('<span style="color: green;">' + response.data.message + '</span>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            status.html('<span style="color: red;">' + response.data.message + '</span>');
                        }
                    },
                    error: function() {
                        status.html('<span style="color: red;"><?php _e('Error occurred during scraping', 'n8n-workflows-explorer'); ?></span>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php _e('Manual Scrape Now', 'n8n-workflows-explorer'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('n8n-workflows-style', N8N_WORKFLOWS_PLUGIN_URL . 'assets/style.css', array(), N8N_WORKFLOWS_VERSION);
        wp_enqueue_script('n8n-workflows-script', N8N_WORKFLOWS_PLUGIN_URL . 'assets/script.js', array('jquery'), N8N_WORKFLOWS_VERSION, true);
        
        wp_localize_script('n8n-workflows-script', 'n8n_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('n8n_workflows_nonce')
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        if ($hook === 'settings_page_n8n-workflows-settings') {
            wp_enqueue_script('jquery');
        }
    }
    
    // Manual scrape AJAX handler
    public function manual_scrape() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'n8n_manual_scrape')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $result = $this->scrape_and_create_posts();
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully created %d new workflow posts.', 'n8n-workflows-explorer'), $result)
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to scrape workflows. Check error logs for details.', 'n8n-workflows-explorer')));
        }
    }
    
    // Search workflows AJAX handler
    public function search_workflows() {
        // Verify nonce for logged-in users
        if (is_user_logged_in() && !wp_verify_nonce($_POST['nonce'], 'n8n_workflows_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'date');
        
        $args = array(
            'post_type' => 'n8n_workflow',
            'posts_per_page' => 20,
            's' => $query,
            'post_status' => 'publish'
        );
        
        if ($sort === 'title') {
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
        } else {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }
        
        $workflows = get_posts($args);
        $results = array();
        
        foreach ($workflows as $workflow) {
            $results[] = array(
                'id' => $workflow->ID,
                'title' => $workflow->post_title,
                'excerpt' => $workflow->post_excerpt,
                'url' => get_permalink($workflow->ID),
                'github_url' => get_post_meta($workflow->ID, 'github_url', true),
                'stars' => get_post_meta($workflow->ID, 'github_stars', true)
            );
        }
        
        wp_send_json_success($results);
    }
    
    // Workflows shortcode
    public function workflows_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 12,
            'category' => '',
            'search' => 'true'
        ), $atts);
        
        ob_start();
        
        // Search form
        if ($atts['search'] === 'true') {
            echo '<div class="n8n-workflows-search">';
            echo '<input type="text" id="workflow-search" placeholder="' . __('Search workflows...', 'n8n-workflows-explorer') . '" />';
            echo '<select id="workflow-sort">';
            echo '<option value="date">' . __('Sort by Date', 'n8n-workflows-explorer') . '</option>';
            echo '<option value="title">' . __('Sort by Title', 'n8n-workflows-explorer') . '</option>';
            echo '<option value="stars">' . __('Sort by Stars', 'n8n-workflows-explorer') . '</option>';
            echo '</select>';
            echo '</div>';
        }
        
        // Workflows grid
        echo '<div id="workflows-grid" class="n8n-workflows-grid">';
        
        $args = array(
            'post_type' => 'n8n_workflow',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish'
        );
        
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'workflow_category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }
        
        $workflows = get_posts($args);
        
        foreach ($workflows as $workflow) {
            $this->render_workflow_card($workflow);
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    // Render workflow card
    private function render_workflow_card($workflow) {
        $github_url = get_post_meta($workflow->ID, 'github_url', true);
        $stars = get_post_meta($workflow->ID, 'github_stars', true);
        $language = get_post_meta($workflow->ID, 'github_language', true);
        
        echo '<div class="workflow-card">';
        
        if (has_post_thumbnail($workflow->ID)) {
            echo '<div class="workflow-thumbnail">';
            echo get_the_post_thumbnail($workflow->ID, 'medium');
            echo '</div>';
        }
        
        echo '<div class="workflow-content">';
        echo '<h3><a href="' . get_permalink($workflow->ID) . '">' . esc_html($workflow->post_title) . '</a></h3>';
        
        if ($workflow->post_excerpt) {
            echo '<p class="workflow-excerpt">' . esc_html($workflow->post_excerpt) . '</p>';
        }
        
        echo '<div class="workflow-meta">';
        if ($stars) {
            echo '<span class="stars">⭐ ' . esc_html($stars) . '</span>';
        }
        if ($language) {
            echo '<span class="language">' . esc_html($language) . '</span>';
        }
        echo '</div>';
        
        echo '<div class="workflow-actions">';
        echo '<a href="' . esc_url($github_url) . '" target="_blank" class="github-link">' . __('View on GitHub', 'n8n-workflows-explorer') . '</a>';
        echo '<a href="' . get_permalink($workflow->ID) . '" class="view-details">' . __('View Details', 'n8n-workflows-explorer') . '</a>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    // Scrape and create posts method
    private function scrape_and_create_posts() {
        $github_token = get_option('n8n_github_token', '');
        $openai_key = get_option('n8n_openai_api_key', '');
        $max_workflows = get_option('n8n_max_workflows', 10);
        
        // GitHub API search for N8N workflows
        $search_url = 'https://api.github.com/search/repositories?q=n8n+workflow+language:json&sort=stars&order=desc&per_page=' . $max_workflows;
        
        $headers = array(
            'User-Agent' => 'N8N-Workflows-Explorer/1.0'
        );
        
        if (!empty($github_token)) {
            $headers['Authorization'] = 'token ' . $github_token;
        }
        
        $response = wp_remote_get($search_url, array(
            'headers' => $headers,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('GitHub API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['items'])) {
            error_log('Invalid GitHub API response');
            return false;
        }
        
        $created_count = 0;
        
        foreach ($data['items'] as $repo) {
            // Check if workflow already exists
            $existing = get_posts(array(
                'post_type' => 'n8n_workflow',
                'meta_key' => 'github_url',
                'meta_value' => $repo['html_url'],
                'posts_per_page' => 1
            ));
            
            if (!empty($existing)) {
                continue;
            }
            
            // Create new workflow post
            $post_data = array(
                'post_title' => $repo['name'],
                'post_content' => $repo['description'] ?: '',
                'post_excerpt' => wp_trim_words($repo['description'] ?: '', 20),
                'post_status' => 'publish',
                'post_type' => 'n8n_workflow'
            );
            
            $post_id = wp_insert_post($post_data);
            
            if ($post_id) {
                // Add metadata
                update_post_meta($post_id, 'github_url', $repo['html_url']);
                update_post_meta($post_id, 'github_stars', $repo['stargazers_count']);
                update_post_meta($post_id, 'github_language', $repo['language']);
                update_post_meta($post_id, 'github_updated', $repo['updated_at']);
                
                // Generate AI description if OpenAI key is available
                if (!empty($openai_key) && !empty($repo['description'])) {
                    $ai_description = $this->generate_ai_description($repo['description'], $openai_key);
                    if ($ai_description) {
                        wp_update_post(array(
                            'ID' => $post_id,
                            'post_content' => $ai_description
                        ));
                    }
                }
                
                $created_count++;
            }
        }
        
        return $created_count;
    }
    
    // Generate AI description using OpenAI
    private function generate_ai_description($description, $api_key) {
        $prompt = "Based on this N8N workflow description: '{$description}', create a detailed, user-friendly explanation of what this workflow does, its benefits, and potential use cases. Keep it under 200 words.";
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'max_tokens' => 300,
                'temperature' => 0.7
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
        
        return false;
    }
}

// Initialize the plugin
new N8NWorkflowsExplorer();
?>