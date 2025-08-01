<?php
/**
 * Main plugin class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class N8N_Workflow_Importer {
    
    private $github_api;
    private $workflow_post_type;
    private $admin_page;
    
    public function __construct() {
        $this->github_api = new GitHub_API();
        $this->workflow_post_type = new Workflow_Post_Type();
        $this->admin_page = new N8N_Admin_Page();
    }
    
    public function run() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_search_n8n_workflows', array($this, 'ajax_search_workflows'));
        add_action('wp_ajax_import_workflow', array($this, 'ajax_import_workflow'));
        
        // Cron hooks
        add_action('n8n_workflow_import_cron', array($this, 'scheduled_import'));
        
        // Schedule cron if not already scheduled
        if (!wp_next_scheduled('n8n_workflow_import_cron')) {
            wp_schedule_event(time(), 'daily', 'n8n_workflow_import_cron');
        }
    }
    
    public function init() {
        $this->workflow_post_type->register_post_type();
        $this->admin_page->init();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script(
            'n8n-workflow-importer-frontend',
            N8N_WORKFLOW_IMPORTER_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            N8N_WORKFLOW_IMPORTER_VERSION,
            true
        );
        
        wp_enqueue_style(
            'n8n-workflow-importer-frontend',
            N8N_WORKFLOW_IMPORTER_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            N8N_WORKFLOW_IMPORTER_VERSION
        );
        
        wp_localize_script('n8n-workflow-importer-frontend', 'n8nWorkflowAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('n8n_workflow_frontend_nonce')
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        // Delegate to admin page enqueue method
        $this->admin_page->enqueue_admin_scripts($hook);
        
        // Additional admin scripts if needed
        if ($hook !== 'toplevel_page_n8n-workflow-importer') {
            return;
        }
        
        wp_enqueue_script(
            'n8n-workflow-importer-admin',
            N8N_WORKFLOW_IMPORTER_PLUGIN_URL . 'assets/js/n8n-workflow-importer.js',
            array('jquery'),
            N8N_WORKFLOW_IMPORTER_VERSION,
            true
        );
        
        wp_enqueue_style(
            'n8n-workflow-importer-admin',
            N8N_WORKFLOW_IMPORTER_PLUGIN_URL . 'assets/css/n8n-workflow-importer.css',
            array(),
            N8N_WORKFLOW_IMPORTER_VERSION
        );
        
        wp_localize_script('n8n-workflow-importer-admin', 'n8nWorkflowAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('n8n_workflow_admin_nonce')
        ));
    }
    
    public function ajax_search_workflows() {
        check_ajax_referer('n8n_workflow_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        
        try {
            $workflows = $this->github_api->search_workflows($search_term, $page);
            wp_send_json_success($workflows);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function ajax_import_workflow() {
        check_ajax_referer('n8n_workflow_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $workflow_url = sanitize_url($_POST['workflow_url'] ?? '');
        
        try {
            // Get the raw JSON content
            $workflow_content = $this->github_api->get_workflow_content($workflow_url);
            
            // Validate that it's valid JSON
            $json_data = json_decode($workflow_content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON content: ' . json_last_error_msg());
            }
            
            // Check if it looks like an n8n workflow
            if (!isset($json_data['nodes']) || !is_array($json_data['nodes'])) {
                throw new Exception('This does not appear to be a valid n8n workflow (missing nodes array)');
            }
            
            // Parse the URL to extract repository info
            if (preg_match('/github\.com\/([^\/]+\/[^\/]+)\/blob\/[^\/]+\/(.+)/', $workflow_url, $matches)) {
                $repo_name = $matches[1];
                $file_path = $matches[2];
            } else {
                throw new Exception('Could not parse GitHub URL');
            }
            
            // Structure the workflow data as expected by create_workflow_post
            $workflow_data = array(
                'content' => $workflow_content,
                'html_url' => $workflow_url,
                'download_url' => $workflow_url, // For manual imports, use the same URL
                'path' => $file_path,
                'repository' => array(
                    'full_name' => $repo_name
                )
            );
            
            $post_id = $this->create_workflow_post($workflow_data);
            
            wp_send_json_success(array(
                'post_id' => $post_id,
                'message' => 'Workflow imported successfully'
            ));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function scheduled_import() {
        $auto_import = get_option('n8n_auto_import_enabled', false);
        
        if (!$auto_import) {
            return;
        }
        
        try {
            $workflows = $this->github_api->get_latest_workflows();
            
            foreach ($workflows as $workflow) {
                // Check if workflow already exists
                $existing_post = get_posts(array(
                    'post_type' => 'n8n_workflow',
                    'meta_query' => array(
                        array(
                            'key' => '_workflow_github_url',
                            'value' => $workflow['html_url'],
                            'compare' => '='
                        )
                    ),
                    'posts_per_page' => 1
                ));
                
                if (empty($existing_post)) {
                    $this->create_workflow_post($workflow);
                }
            }
        } catch (Exception $e) {
            error_log('N8N Workflow Importer: ' . $e->getMessage());
        }
    }
    
    private function create_workflow_post($workflow_data) {
        // Generate AI-powered title and description
        $ai_metadata = $this->github_api->generate_workflow_metadata($workflow_data['content']);
        
        $title = $ai_metadata['title'];
        $ai_description = $ai_metadata['description'];
        
        // Create full description with AI description and workflow details
        $description = $this->generate_full_description($workflow_data, $ai_description);
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => 'n8n_workflow',
            'post_author' => 1
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            throw new Exception('Failed to create post: ' . $post_id->get_error_message());
        }
        
        // Save workflow metadata
        update_post_meta($post_id, '_workflow_json_content', $workflow_data['content']);
        update_post_meta($post_id, '_workflow_github_url', $workflow_data['html_url']);
        update_post_meta($post_id, '_workflow_download_url', $workflow_data['download_url']);
        update_post_meta($post_id, '_workflow_repository', $workflow_data['repository']['full_name']);
        update_post_meta($post_id, '_workflow_file_path', $workflow_data['path']);
        update_post_meta($post_id, '_workflow_imported_date', current_time('mysql'));
        
        // Parse JSON to extract additional metadata
        $json_data = json_decode($workflow_data['content'], true);
        if ($json_data) {
            update_post_meta($post_id, '_workflow_name', $json_data['name'] ?? '');
            update_post_meta($post_id, '_workflow_active', $json_data['active'] ?? false);
            update_post_meta($post_id, '_workflow_nodes_count', count($json_data['nodes'] ?? []));
            update_post_meta($post_id, '_workflow_connections_count', count($json_data['connections'] ?? []));
        }
        
        return $post_id;
    }
    
    private function generate_title($workflow_data) {
        $filename = basename($workflow_data['path'], '.json');
        
        // Convert filename to readable title
        $title = str_replace(array('_', '-'), ' ', $filename);
        $title = ucwords($title);
        
        // If JSON contains a name, use that instead
        $json_data = json_decode($workflow_data['content'], true);
        if ($json_data && !empty($json_data['name'])) {
            $title = $json_data['name'];
        }
        
        return $title;
    }
    
    private function generate_full_description($workflow_data, $ai_description) {
        $json_data = json_decode($workflow_data['content'], true);
        
        $description = '<div class="n8n-workflow-description">';
        
        // Add AI-generated description at the top
        $description .= '<div class="ai-description">';
        $description .= '<h3>Description</h3>';
        $description .= '<p>' . esc_html($ai_description) . '</p>';
        $description .= '</div>';
        
        $description .= '<h3>Workflow Details</h3>';
        
        if ($json_data) {
            $description .= '<p><strong>Repository:</strong> ' . esc_html($workflow_data['repository']['full_name']) . '</p>';
            $description .= '<p><strong>File Path:</strong> ' . esc_html($workflow_data['path']) . '</p>';
            $description .= '<p><strong>Nodes Count:</strong> ' . count($json_data['nodes'] ?? []) . '</p>';
            $description .= '<p><strong>Active:</strong> ' . ($json_data['active'] ? 'Yes' : 'No') . '</p>';
            
            if (!empty($json_data['nodes'])) {
                $node_types = array_unique(array_column($json_data['nodes'], 'type'));
                $description .= '<p><strong>Node Types:</strong> ' . implode(', ', $node_types) . '</p>';
            }
        }
        
        $description .= '<h3>JSON Content</h3>';
        $description .= '<div class="n8n-workflow-json">';
        $description .= '<button class="toggle-json" onclick="toggleJsonContent(this)">Show/Hide JSON</button>';
        $description .= '<pre class="json-content" style="display:none;"><code>' . esc_html($workflow_data['content']) . '</code></pre>';
        $description .= '</div>';
        
        $description .= '<div class="n8n-workflow-actions">';
        $description .= '<a href="' . esc_url($workflow_data['html_url']) . '" target="_blank" class="button">View on GitHub</a> ';
        $description .= '<a href="' . esc_url($workflow_data['download_url']) . '" class="button" download>Download JSON</a>';
        $description .= '</div>';
        
        $description .= '</div>';
        
        return $description;
    }
}