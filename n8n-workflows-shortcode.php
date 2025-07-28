<?php
/**
 * N8N Workflows Explorer - Shortcode Template
 * This file handles the frontend display of workflows
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class N8NWorkflowsShortcode {
    
    public static function init() {
        add_shortcode('n8n_workflows', [self::class, 'render_workflows']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_scripts']);
    }
    
    public static function enqueue_scripts() {
        if (self::has_shortcode_on_page()) {
            wp_enqueue_script('n8n-workflows-js', plugin_dir_url(__FILE__) . 'assets/n8n-workflows.js', ['jquery'], '1.0.0', true);
            wp_enqueue_style('n8n-workflows-css', plugin_dir_url(__FILE__) . 'assets/n8n-workflows.css', [], '1.0.0');
            
            // Localize script for AJAX
            wp_localize_script('n8n-workflows-js', 'n8n_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('n8n_workflows_nonce')
            ]);
        }
    }
    
    private static function has_shortcode_on_page() {
        global $post;
        return is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'n8n_workflows');
    }
    
    public static function render_workflows($atts) {
        $atts = shortcode_atts([
            'limit' => 12,
            'show_search' => 'true',
            'show_filters' => 'true',
            'columns' => 3
        ], $atts);
        
        ob_start();
        
        // Get workflows
        $workflows = N8NWorkflowsExplorerFunctions::search_workflows();
        
        ?>
        <div id="n8n-workflows-container" class="n8n-workflows-explorer">
            <?php if ($atts['show_search'] === 'true'): ?>
            <div class="n8n-search-section">
                <div class="n8n-search-box">
                    <input type="text" id="n8n-search-input" placeholder="Search workflows..." />
                    <button type="button" id="n8n-search-btn">🔍</button>
                </div>
                <?php if ($atts['show_filters'] === 'true'): ?>
                <div class="n8n-filters">
                    <select id="n8n-sort-filter">
                        <option value="date">Sort by Date</option>
                        <option value="stars">Sort by Stars</option>
                        <option value="title">Sort by Title</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="n8n-workflows-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
                <?php if (empty($workflows)): ?>
                    <div class="n8n-no-workflows">
                        <p>No workflows found. <a href="<?php echo admin_url('admin.php?page=n8n-workflows'); ?>">Scrape some workflows</a> to get started!</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($workflows, 0, intval($atts['limit'])) as $workflow): ?>
                        <div class="n8n-workflow-card" data-stars="<?php echo esc_attr($workflow['stars']); ?>" data-title="<?php echo esc_attr(strtolower($workflow['title'])); ?>">
                            <div class="n8n-workflow-header">
                                <h3 class="n8n-workflow-title">
                                    <a href="<?php echo esc_url($workflow['permalink']); ?>" title="<?php echo esc_attr($workflow['title']); ?>">
                                        <?php echo esc_html($workflow['title']); ?>
                                    </a>
                                </h3>
                                <div class="n8n-workflow-meta">
                                    <span class="n8n-author">👤 <?php echo esc_html($workflow['author']); ?></span>
                                    <span class="n8n-stars">⭐ <?php echo esc_html($workflow['stars']); ?></span>
                                </div>
                            </div>
                            
                            <div class="n8n-workflow-content">
                                <p class="n8n-workflow-description">
                                    <?php echo esc_html($workflow['description']); ?>
                                </p>
                            </div>
                            
                            <div class="n8n-workflow-footer">
                                <div class="n8n-workflow-links">
                                    <a href="<?php echo esc_url($workflow['url']); ?>" target="_blank" class="n8n-github-link" title="View on GitHub">
                                        📁 GitHub
                                    </a>
                                    <a href="<?php echo esc_url($workflow['permalink']); ?>" class="n8n-details-link" title="View Details">
                                        📄 Details
                                    </a>
                                </div>
                                <div class="n8n-workflow-date">
                                    <?php echo esc_html($workflow['date']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div id="n8n-loading" class="n8n-loading" style="display: none;">
                <p>🔄 Searching workflows...</p>
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
}

// Initialize the shortcode
N8NWorkflowsShortcode::init();
?>