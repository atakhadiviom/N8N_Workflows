<?php
/**
 * Custom post type for n8n workflows
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Workflow_Post_Type {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_n8n_workflow_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_n8n_workflow_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('the_content', array($this, 'add_workflow_content'));
    }
    
    /**
     * Register the n8n workflow custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('N8N Workflows', 'n8n-workflow-importer'),
            'singular_name' => __('N8N Workflow', 'n8n-workflow-importer'),
            'menu_name' => __('N8N Workflows', 'n8n-workflow-importer'),
            'add_new' => __('Add New Workflow', 'n8n-workflow-importer'),
            'add_new_item' => __('Add New N8N Workflow', 'n8n-workflow-importer'),
            'edit_item' => __('Edit N8N Workflow', 'n8n-workflow-importer'),
            'new_item' => __('New N8N Workflow', 'n8n-workflow-importer'),
            'view_item' => __('View N8N Workflow', 'n8n-workflow-importer'),
            'search_items' => __('Search N8N Workflows', 'n8n-workflow-importer'),
            'not_found' => __('No N8N workflows found', 'n8n-workflow-importer'),
            'not_found_in_trash' => __('No N8N workflows found in trash', 'n8n-workflow-importer'),
            'all_items' => __('All N8N Workflows', 'n8n-workflow-importer'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'n8n-workflow'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-networking',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments'),
            'taxonomies' => array('workflow_category', 'workflow_tag'),
        );
        
        register_post_type('n8n_workflow', $args);
        
        // Register custom taxonomies
        $this->register_taxonomies();
    }
    
    /**
     * Register custom taxonomies for workflows
     */
    private function register_taxonomies() {
        // Workflow Categories
        $category_labels = array(
            'name' => __('Workflow Categories', 'n8n-workflow-importer'),
            'singular_name' => __('Workflow Category', 'n8n-workflow-importer'),
            'menu_name' => __('Categories', 'n8n-workflow-importer'),
        );
        
        register_taxonomy('workflow_category', 'n8n_workflow', array(
            'labels' => $category_labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'workflow-category'),
        ));
        
        // Workflow Tags
        $tag_labels = array(
            'name' => __('Workflow Tags', 'n8n-workflow-importer'),
            'singular_name' => __('Workflow Tag', 'n8n-workflow-importer'),
            'menu_name' => __('Tags', 'n8n-workflow-importer'),
        );
        
        register_taxonomy('workflow_tag', 'n8n_workflow', array(
            'labels' => $tag_labels,
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'workflow-tag'),
        ));
    }
    
    /**
     * Add meta boxes for workflow data
     */
    public function add_meta_boxes() {
        add_meta_box(
            'workflow_details',
            __('Workflow Details', 'n8n-workflow-importer'),
            array($this, 'workflow_details_meta_box'),
            'n8n_workflow',
            'normal',
            'high'
        );
        
        add_meta_box(
            'workflow_json',
            __('Workflow JSON', 'n8n-workflow-importer'),
            array($this, 'workflow_json_meta_box'),
            'n8n_workflow',
            'normal',
            'high'
        );
        
        add_meta_box(
            'workflow_github',
            __('GitHub Information', 'n8n-workflow-importer'),
            array($this, 'workflow_github_meta_box'),
            'n8n_workflow',
            'side',
            'default'
        );
    }
    
    /**
     * Workflow details meta box
     */
    public function workflow_details_meta_box($post) {
        wp_nonce_field('workflow_meta_box', 'workflow_meta_box_nonce');
        
        $workflow_name = get_post_meta($post->ID, '_workflow_name', true);
        $workflow_active = get_post_meta($post->ID, '_workflow_active', true);
        $nodes_count = get_post_meta($post->ID, '_workflow_nodes_count', true);
        $connections_count = get_post_meta($post->ID, '_workflow_connections_count', true);
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="workflow_name">' . __('Workflow Name', 'n8n-workflow-importer') . '</label></th>';
        echo '<td><input type="text" id="workflow_name" name="workflow_name" value="' . esc_attr($workflow_name) . '" class="regular-text" /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th><label for="workflow_active">' . __('Active', 'n8n-workflow-importer') . '</label></th>';
        echo '<td><input type="checkbox" id="workflow_active" name="workflow_active" value="1" ' . checked($workflow_active, true, false) . ' /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>' . __('Nodes Count', 'n8n-workflow-importer') . '</th>';
        echo '<td>' . esc_html($nodes_count) . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>' . __('Connections Count', 'n8n-workflow-importer') . '</th>';
        echo '<td>' . esc_html($connections_count) . '</td>';
        echo '</tr>';
        echo '</table>';
    }
    
    /**
     * Workflow JSON meta box
     */
    public function workflow_json_meta_box($post) {
        $json_content = get_post_meta($post->ID, '_workflow_json_content', true);
        
        echo '<div class="workflow-json-container">';
        echo '<p>' . __('JSON content of the n8n workflow:', 'n8n-workflow-importer') . '</p>';
        echo '<textarea id="workflow_json_content" name="workflow_json_content" rows="20" class="large-text code">' . esc_textarea($json_content) . '</textarea>';
        echo '<p class="description">' . __('This is the raw JSON content of the n8n workflow. You can copy this and import it directly into n8n.', 'n8n-workflow-importer') . '</p>';
        
        if (!empty($json_content)) {
            echo '<div class="workflow-actions">';
            echo '<button type="button" class="button" onclick="copyJsonToClipboard()">' . __('Copy JSON', 'n8n-workflow-importer') . '</button> ';
            echo '<button type="button" class="button" onclick="downloadJson()">' . __('Download JSON', 'n8n-workflow-importer') . '</button> ';
            echo '<button type="button" class="button" onclick="validateJson()">' . __('Validate JSON', 'n8n-workflow-importer') . '</button>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Add JavaScript for JSON actions
        echo '<script>
        function copyJsonToClipboard() {
            var jsonTextarea = document.getElementById("workflow_json_content");
            jsonTextarea.select();
            document.execCommand("copy");
            alert("JSON copied to clipboard!");
        }
        
        function downloadJson() {
            var jsonContent = document.getElementById("workflow_json_content").value;
            var blob = new Blob([jsonContent], {type: "application/json"});
            var url = URL.createObjectURL(blob);
            var a = document.createElement("a");
            a.href = url;
            a.download = "workflow.json";
            a.click();
            URL.revokeObjectURL(url);
        }
        
        function validateJson() {
            var jsonContent = document.getElementById("workflow_json_content").value;
            try {
                JSON.parse(jsonContent);
                alert("JSON is valid!");
            } catch (e) {
                alert("Invalid JSON: " + e.message);
            }
        }
        </script>';
    }
    
    /**
     * GitHub information meta box
     */
    public function workflow_github_meta_box($post) {
        $github_url = get_post_meta($post->ID, '_workflow_github_url', true);
        $download_url = get_post_meta($post->ID, '_workflow_download_url', true);
        $repository = get_post_meta($post->ID, '_workflow_repository', true);
        $file_path = get_post_meta($post->ID, '_workflow_file_path', true);
        $imported_date = get_post_meta($post->ID, '_workflow_imported_date', true);
        
        echo '<div class="workflow-github-info">';
        
        if ($repository) {
            echo '<p><strong>' . __('Repository:', 'n8n-workflow-importer') . '</strong><br>' . esc_html($repository) . '</p>';
        }
        
        if ($file_path) {
            echo '<p><strong>' . __('File Path:', 'n8n-workflow-importer') . '</strong><br>' . esc_html($file_path) . '</p>';
        }
        
        if ($github_url) {
            echo '<p><a href="' . esc_url($github_url) . '" target="_blank" class="button">' . __('View on GitHub', 'n8n-workflow-importer') . '</a></p>';
        }
        
        if ($download_url) {
            echo '<p><a href="' . esc_url($download_url) . '" class="button" download>' . __('Download Original', 'n8n-workflow-importer') . '</a></p>';
        }
        
        if ($imported_date) {
            echo '<p><strong>' . __('Imported:', 'n8n-workflow-importer') . '</strong><br>' . esc_html(date_i18n(get_option('date_format'), strtotime($imported_date))) . '</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['workflow_meta_box_nonce']) || !wp_verify_nonce($_POST['workflow_meta_box_nonce'], 'workflow_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save workflow details
        if (isset($_POST['workflow_name'])) {
            update_post_meta($post_id, '_workflow_name', sanitize_text_field($_POST['workflow_name']));
        }
        
        $workflow_active = isset($_POST['workflow_active']) ? true : false;
        update_post_meta($post_id, '_workflow_active', $workflow_active);
        
        if (isset($_POST['workflow_json_content'])) {
            $json_content = wp_unslash($_POST['workflow_json_content']);
            update_post_meta($post_id, '_workflow_json_content', $json_content);
            
            // Parse JSON to update node and connection counts
            $json_data = json_decode($json_content, true);
            if ($json_data) {
                update_post_meta($post_id, '_workflow_nodes_count', count($json_data['nodes'] ?? []));
                update_post_meta($post_id, '_workflow_connections_count', count($json_data['connections'] ?? []));
            }
        }
    }
    
    /**
     * Add custom columns to workflow list
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['workflow_active'] = __('Active', 'n8n-workflow-importer');
                $new_columns['nodes_count'] = __('Nodes', 'n8n-workflow-importer');
                $new_columns['repository'] = __('Repository', 'n8n-workflow-importer');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'workflow_active':
                $active = get_post_meta($post_id, '_workflow_active', true);
                echo $active ? '<span style="color: green;">✓ Yes</span>' : '<span style="color: red;">✗ No</span>';
                break;
                
            case 'nodes_count':
                $count = get_post_meta($post_id, '_workflow_nodes_count', true);
                echo esc_html($count ?: '0');
                break;
                
            case 'repository':
                $repo = get_post_meta($post_id, '_workflow_repository', true);
                if ($repo) {
                    $github_url = get_post_meta($post_id, '_workflow_github_url', true);
                    if ($github_url) {
                        echo '<a href="' . esc_url($github_url) . '" target="_blank">' . esc_html($repo) . '</a>';
                    } else {
                        echo esc_html($repo);
                    }
                }
                break;
        }
    }
    
    /**
     * Add workflow-specific content to the post content
     */
    public function add_workflow_content($content) {
        if (!is_singular('n8n_workflow')) {
            return $content;
        }
        
        global $post;
        
        $json_content = get_post_meta($post->ID, '_workflow_json_content', true);
        $github_url = get_post_meta($post->ID, '_workflow_github_url', true);
        $download_url = get_post_meta($post->ID, '_workflow_download_url', true);
        $nodes_count = get_post_meta($post->ID, '_workflow_nodes_count', true);
        $workflow_active = get_post_meta($post->ID, '_workflow_active', true);
        
        $workflow_content = '<div class="n8n-workflow-details">';
        
        // Workflow stats
        $workflow_content .= '<div class="workflow-stats">';
        $workflow_content .= '<h3>' . __('Workflow Information', 'n8n-workflow-importer') . '</h3>';
        $workflow_content .= '<ul>';
        $workflow_content .= '<li><strong>' . __('Nodes:', 'n8n-workflow-importer') . '</strong> ' . esc_html($nodes_count ?: '0') . '</li>';
        $workflow_content .= '<li><strong>' . __('Status:', 'n8n-workflow-importer') . '</strong> ' . ($workflow_active ? __('Active', 'n8n-workflow-importer') : __('Inactive', 'n8n-workflow-importer')) . '</li>';
        $workflow_content .= '</ul>';
        $workflow_content .= '</div>';
        
        // Action buttons
        $workflow_content .= '<div class="workflow-actions">';
        if ($github_url) {
            $workflow_content .= '<a href="' . esc_url($github_url) . '" target="_blank" class="button button-primary">' . __('View on GitHub', 'n8n-workflow-importer') . '</a> ';
        }
        if ($download_url) {
            $workflow_content .= '<a href="' . esc_url($download_url) . '" class="button" download>' . __('Download JSON', 'n8n-workflow-importer') . '</a> ';
        }
        if ($json_content) {
            $workflow_content .= '<button class="button toggle-json-btn" onclick="toggleWorkflowJson()">' . __('Show/Hide JSON', 'n8n-workflow-importer') . '</button>';
        }
        $workflow_content .= '</div>';
        
        // JSON content (hidden by default)
        if ($json_content) {
            $workflow_content .= '<div class="workflow-json-content" style="display: none; margin-top: 20px;">';
            $workflow_content .= '<h3>' . __('Workflow JSON', 'n8n-workflow-importer') . '</h3>';
            $workflow_content .= '<pre class="workflow-json"><code>' . esc_html($json_content) . '</code></pre>';
            $workflow_content .= '<button class="button" onclick="copyWorkflowJson()">' . __('Copy to Clipboard', 'n8n-workflow-importer') . '</button>';
            $workflow_content .= '</div>';
        }
        
        $workflow_content .= '</div>';
        
        // Add JavaScript
        $workflow_content .= '<script>
        function toggleWorkflowJson() {
            var jsonDiv = document.querySelector(".workflow-json-content");
            if (jsonDiv.style.display === "none") {
                jsonDiv.style.display = "block";
            } else {
                jsonDiv.style.display = "none";
            }
        }
        
        function copyWorkflowJson() {
            var jsonCode = document.querySelector(".workflow-json code").textContent;
            navigator.clipboard.writeText(jsonCode).then(function() {
                alert("JSON copied to clipboard!");
            });
        }
        </script>';
        
        return $content . $workflow_content;
    }
}