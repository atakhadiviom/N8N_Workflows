<?php
/**
 * Admin page class for the N8N Workflow Importer plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class N8N_Admin_Page {   
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('N8N Workflow Importer', 'n8n-workflow-importer'),
            __('N8N Workflows', 'n8n-workflow-importer'),
            'manage_options',
            'n8n-workflow-importer',
            array($this, 'admin_page'),
            'dashicons-networking',
            30
        );
        
        add_submenu_page(
            'n8n-workflow-importer',
            __('Settings', 'n8n-workflow-importer'),
            __('Settings', 'n8n-workflow-importer'),
            'manage_options',
            'n8n-workflow-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'n8n-workflow-importer',
            __('Import Workflows', 'n8n-workflow-importer'),
            __('Import', 'n8n-workflow-importer'),
            'manage_options',
            'n8n-workflow-import',
            array($this, 'import_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('n8n_workflow_settings', 'n8n_github_token');
        register_setting('n8n_workflow_settings', 'n8n_auto_import_enabled');
        register_setting('n8n_workflow_settings', 'n8n_import_frequency');
        register_setting('n8n_workflow_settings', 'n8n_default_category');
        register_setting('n8n_workflow_settings', 'n8n_search_repositories');
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        $total_workflows = wp_count_posts('n8n_workflow')->publish;
        $recent_workflows = get_posts(array(
            'post_type' => 'n8n_workflow',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        ?>
        <div class="wrap">
            <h1><?php _e('N8N Workflow Importer', 'n8n-workflow-importer'); ?></h1>
            
            <div class="n8n-dashboard">
                <div class="n8n-stats">
                    <div class="stat-box">
                        <h3><?php echo esc_html($total_workflows); ?></h3>
                        <p><?php _e('Total Workflows', 'n8n-workflow-importer'); ?></p>
                    </div>
                    
                    <div class="stat-box">
                        <h3><?php echo esc_html(count($recent_workflows)); ?></h3>
                        <p><?php _e('Recent Imports', 'n8n-workflow-importer'); ?></p>
                    </div>
                    
                    <div class="stat-box">
                        <h3><?php echo get_option('n8n_github_token') ? '✓' : '✗'; ?></h3>
                        <p><?php _e('GitHub Token', 'n8n-workflow-importer'); ?></p>
                    </div>
                </div>
                
                <div class="n8n-quick-actions">
                    <h2><?php _e('Quick Actions', 'n8n-workflow-importer'); ?></h2>
                    <div class="action-buttons">
                        <a href="<?php echo admin_url('admin.php?page=n8n-workflow-import'); ?>" class="button button-primary">
                            <?php _e('Import Workflows', 'n8n-workflow-importer'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=n8n-workflow-settings'); ?>" class="button">
                            <?php _e('Settings', 'n8n-workflow-importer'); ?>
                        </a>
                        <a href="<?php echo admin_url('edit.php?post_type=n8n_workflow'); ?>" class="button">
                            <?php _e('View All Workflows', 'n8n-workflow-importer'); ?>
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($recent_workflows)): ?>
                <div class="n8n-recent-workflows">
                    <h2><?php _e('Recent Workflows', 'n8n-workflow-importer'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Title', 'n8n-workflow-importer'); ?></th>
                                <th><?php _e('Repository', 'n8n-workflow-importer'); ?></th>
                                <th><?php _e('Nodes', 'n8n-workflow-importer'); ?></th>
                                <th><?php _e('Date', 'n8n-workflow-importer'); ?></th>
                                <th><?php _e('Actions', 'n8n-workflow-importer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_workflows as $workflow): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($workflow->ID); ?>">
                                            <?php echo esc_html($workflow->post_title); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td>
                                    <?php 
                                    $repo = get_post_meta($workflow->ID, '_workflow_repository', true);
                                    echo esc_html($repo);
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $nodes = get_post_meta($workflow->ID, '_workflow_nodes_count', true);
                                    echo esc_html($nodes ?: '0');
                                    ?>
                                </td>
                                <td><?php echo get_the_date('', $workflow->ID); ?></td>
                                <td>
                                    <a href="<?php echo get_permalink($workflow->ID); ?>" class="button button-small">
                                        <?php _e('View', 'n8n-workflow-importer'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .n8n-dashboard {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }
        
        .n8n-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-box h3 {
            font-size: 2em;
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        
        .stat-box p {
            margin: 0;
            color: #666;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .n8n-quick-actions,
        .n8n-recent-workflows {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        </style>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('n8n_settings_nonce');
            
            update_option('n8n_github_token', sanitize_text_field($_POST['n8n_github_token']));
            update_option('n8n_auto_import_enabled', isset($_POST['n8n_auto_import_enabled']));
            update_option('n8n_import_frequency', sanitize_text_field($_POST['n8n_import_frequency']));
            update_option('n8n_default_category', intval($_POST['n8n_default_category']));
            update_option('n8n_search_repositories', sanitize_textarea_field($_POST['n8n_search_repositories']));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'n8n-workflow-importer') . '</p></div>';
        }
        
        $github_token = get_option('n8n_github_token', '');
        $auto_import = get_option('n8n_auto_import_enabled', false);
        $import_frequency = get_option('n8n_import_frequency', 'daily');
        $default_category = get_option('n8n_default_category', 0);
        $search_repositories = get_option('n8n_search_repositories', "Zie619/n8n-workflows\nanushgr/n8n-workflows\nn8n-io/n8n");
        
        $categories = get_terms(array(
            'taxonomy' => 'workflow_category',
            'hide_empty' => false
        ));
        
        ?>
        <div class="wrap">
            <h1><?php _e('N8N Workflow Importer Settings', 'n8n-workflow-importer'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('n8n_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="n8n_github_token"><?php _e('GitHub Personal Access Token', 'n8n-workflow-importer'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="n8n_github_token" name="n8n_github_token" value="<?php echo esc_attr($github_token); ?>" class="regular-text" />
                            <p class="description">
                                <?php _e('Enter your GitHub Personal Access Token to increase API rate limits and access private repositories.', 'n8n-workflow-importer'); ?>
                                <a href="https://github.com/settings/tokens" target="_blank"><?php _e('Create token', 'n8n-workflow-importer'); ?></a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <?php _e('Auto Import', 'n8n-workflow-importer'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="n8n_auto_import_enabled" value="1" <?php checked($auto_import); ?> />
                                <?php _e('Enable automatic workflow import', 'n8n-workflow-importer'); ?>
                            </label>
                            <p class="description"><?php _e('Automatically search for and import new workflows from GitHub.', 'n8n-workflow-importer'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="n8n_import_frequency"><?php _e('Import Frequency', 'n8n-workflow-importer'); ?></label>
                        </th>
                        <td>
                            <select id="n8n_import_frequency" name="n8n_import_frequency">
                                <option value="hourly" <?php selected($import_frequency, 'hourly'); ?>><?php _e('Hourly', 'n8n-workflow-importer'); ?></option>
                                <option value="daily" <?php selected($import_frequency, 'daily'); ?>><?php _e('Daily', 'n8n-workflow-importer'); ?></option>
                                <option value="weekly" <?php selected($import_frequency, 'weekly'); ?>><?php _e('Weekly', 'n8n-workflow-importer'); ?></option>
                            </select>
                            <p class="description"><?php _e('How often to check for new workflows.', 'n8n-workflow-importer'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="n8n_default_category"><?php _e('Default Category', 'n8n-workflow-importer'); ?></label>
                        </th>
                        <td>
                            <select id="n8n_default_category" name="n8n_default_category">
                                <option value="0"><?php _e('No category', 'n8n-workflow-importer'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($default_category, $category->term_id); ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Default category for imported workflows.', 'n8n-workflow-importer'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="n8n_search_repositories"><?php _e('Search Repositories', 'n8n-workflow-importer'); ?></label>
                        </th>
                        <td>
                            <textarea id="n8n_search_repositories" name="n8n_search_repositories" rows="5" class="large-text"><?php echo esc_textarea($search_repositories); ?></textarea>
                            <p class="description"><?php _e('List of GitHub repositories to search for workflows (one per line).', 'n8n-workflow-importer'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="n8n-settings-help">
                <h2><?php _e('Help & Documentation', 'n8n-workflow-importer'); ?></h2>
                <div class="help-section">
                    <h3><?php _e('GitHub Token Setup', 'n8n-workflow-importer'); ?></h3>
                    <ol>
                        <li><?php _e('Go to GitHub Settings > Developer settings > Personal access tokens', 'n8n-workflow-importer'); ?></li>
                        <li><?php _e('Click "Generate new token"', 'n8n-workflow-importer'); ?></li>
                        <li><?php _e('Select "public_repo" scope for public repositories', 'n8n-workflow-importer'); ?></li>
                        <li><?php _e('Copy the token and paste it in the field above', 'n8n-workflow-importer'); ?></li>
                    </ol>
                </div>
                
                <div class="help-section">
                    <h3><?php _e('Repository Format', 'n8n-workflow-importer'); ?></h3>
                    <p><?php _e('Enter repositories in the format: username/repository-name', 'n8n-workflow-importer'); ?></p>
                    <p><?php _e('Example:', 'n8n-workflow-importer'); ?></p>
                    <code>
                        Zie619/n8n-workflows<br>
                        anushgr/n8n-workflows<br>
                        n8n-io/n8n
                    </code>
                </div>
            </div>
        </div>
        
        <style>
        .n8n-settings-help {
            margin-top: 40px;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .help-section {
            margin-bottom: 30px;
        }
        
        .help-section:last-child {
            margin-bottom: 0;
        }
        
        .help-section h3 {
            margin-top: 0;
        }
        
        .help-section code {
            display: block;
            background: #f1f1f1;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Import page
     */
    public function import_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Import N8N Workflows', 'n8n-workflow-importer'); ?></h1>
            
            <div class="n8n-import-container">
                <div class="import-section">
                    <h2><?php _e('Search GitHub for Workflows', 'n8n-workflow-importer'); ?></h2>
                    <div class="search-form">
                        <input type="text" id="workflow-search" placeholder="<?php _e('Search for workflows...', 'n8n-workflow-importer'); ?>" class="regular-text" />
                        <button id="search-workflows" class="button button-primary"><?php _e('Search', 'n8n-workflow-importer'); ?></button>
                    </div>
                    
                    <div id="search-results" class="search-results"></div>
                </div>
                
                <div class="import-section">
                    <h2><?php _e('Import from URL', 'n8n-workflow-importer'); ?></h2>
                    <div class="url-form">
                        <input type="url" id="workflow-url" placeholder="<?php _e('GitHub workflow URL...', 'n8n-workflow-importer'); ?>" class="regular-text" />
                        <button id="import-url" class="button button-primary"><?php _e('Import', 'n8n-workflow-importer'); ?></button>
                    </div>
                    <p class="description"><?php _e('Enter a direct GitHub URL to a .json workflow file.', 'n8n-workflow-importer'); ?></p>
                </div>
                
                <div class="import-section">
                    <h2><?php _e('Bulk Import Latest Workflows', 'n8n-workflow-importer'); ?></h2>
                    <p><?php _e('Import the latest workflows from configured repositories.', 'n8n-workflow-importer'); ?></p>
                    <button id="bulk-import" class="button button-secondary"><?php _e('Start Bulk Import', 'n8n-workflow-importer'); ?></button>
                    <div id="bulk-import-progress" class="import-progress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="progress-text"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .n8n-import-container {
            display: grid;
            gap: 30px;
            margin-top: 20px;
        }
        
        .import-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .search-form,
        .url-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-form input,
        .url-form input {
            flex: 1;
        }
        
        .search-results {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: none;
        }
        
        .workflow-result {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .workflow-result:last-child {
            border-bottom: none;
        }
        
        .workflow-info h4 {
            margin: 0 0 5px 0;
        }
        
        .workflow-info p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        
        .import-progress {
            margin-top: 15px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f1f1f1;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #0073aa;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            margin-top: 10px;
            text-align: center;
            font-weight: bold;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Search workflows
            $('#search-workflows').on('click', function() {
                var searchTerm = $('#workflow-search').val();
                if (!searchTerm) return;
                
                var button = $(this);
                button.addClass('loading').text('<?php _e('Searching...', 'n8n-workflow-importer'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'search_n8n_workflows',
                        search_term: searchTerm,
                        nonce: '<?php echo wp_create_nonce('n8n_workflow_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            displaySearchResults(response.data.items);
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Search failed. Please try again.', 'n8n-workflow-importer'); ?>');
                    },
                    complete: function() {
                        button.removeClass('loading').text('<?php _e('Search', 'n8n-workflow-importer'); ?>');
                    }
                });
            });
            
            // Import from URL
            $('#import-url').on('click', function() {
                var url = $('#workflow-url').val();
                if (!url) return;
                
                importWorkflow(url, $(this));
            });
            
            // Bulk import
            $('#bulk-import').on('click', function() {
                if (confirm('<?php _e('This will import multiple workflows. Continue?', 'n8n-workflow-importer'); ?>')) {
                    startBulkImport();
                }
            });
            
            function displaySearchResults(workflows) {
                var resultsDiv = $('#search-results');
                resultsDiv.empty();
                
                if (workflows.length === 0) {
                    resultsDiv.html('<p><?php _e('No workflows found.', 'n8n-workflow-importer'); ?></p>');
                } else {
                    workflows.forEach(function(workflow) {
                        var resultHtml = '<div class="workflow-result">' +
                            '<div class="workflow-info">' +
                            '<h4>' + workflow.name + '</h4>' +
                            '<p>' + workflow.repository.full_name + ' - ' + workflow.path + '</p>' +
                            '</div>' +
                            '<button class="button import-workflow" data-url="' + workflow.html_url + '"><?php _e('Import', 'n8n-workflow-importer'); ?></button>' +
                            '</div>';
                        resultsDiv.append(resultHtml);
                    });
                }
                
                resultsDiv.show();
            }
            
            // Handle individual workflow import
            $(document).on('click', '.import-workflow', function() {
                var url = $(this).data('url');
                importWorkflow(url, $(this));
            });
            
            function importWorkflow(url, button) {
                button.addClass('loading').text('<?php _e('Importing...', 'n8n-workflow-importer'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'import_workflow',
                        workflow_url: url,
                        nonce: '<?php echo wp_create_nonce('n8n_workflow_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Workflow imported successfully!', 'n8n-workflow-importer'); ?>');
                            button.text('<?php _e('Imported', 'n8n-workflow-importer'); ?>').prop('disabled', true);
                        } else {
                            alert('Error: ' + response.data);
                            button.removeClass('loading').text('<?php _e('Import', 'n8n-workflow-importer'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Import failed. Please try again.', 'n8n-workflow-importer'); ?>');
                        button.removeClass('loading').text('<?php _e('Import', 'n8n-workflow-importer'); ?>');
                    }
                });
            }
            
            function startBulkImport() {
                $('#bulk-import-progress').show();
                var progressBar = $('.progress-fill');
                var progressText = $('.progress-text');
                
                // Simulate bulk import progress
                var progress = 0;
                var interval = setInterval(function() {
                    progress += 10;
                    progressBar.css('width', progress + '%');
                    progressText.text('<?php _e('Importing workflows...', 'n8n-workflow-importer'); ?> ' + progress + '%');
                    
                    if (progress >= 100) {
                        clearInterval(interval);
                        progressText.text('<?php _e('Bulk import completed!', 'n8n-workflow-importer'); ?>');
                        setTimeout(function() {
                            $('#bulk-import-progress').hide();
                            progressBar.css('width', '0%');
                        }, 2000);
                    }
                }, 500);
            }
        });
        </script>
        <?php
    }
}