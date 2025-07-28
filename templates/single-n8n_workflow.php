<?php
/**
 * Single N8N Workflow Template
 * 
 * This template displays a single n8n workflow with its JSON content,
 * metadata, and related information.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="n8n-workflow-container">
    <?php while (have_posts()) : the_post(); ?>
        
        <!-- Workflow Header -->
        <div class="workflow-header">
            <h1 class="workflow-title"><?php the_title(); ?></h1>
            
            <?php if (get_the_content()) : ?>
                <div class="workflow-description-header">
                    <?php echo wp_trim_words(get_the_content(), 30); ?>
                </div>
            <?php endif; ?>
            
            <div class="workflow-meta">
                <div class="workflow-meta-item">
                    <i class="dashicons dashicons-calendar-alt"></i>
                    <span><?php echo get_the_date(); ?></span>
                </div>
                
                <?php 
                $repository = get_post_meta(get_the_ID(), '_workflow_repository', true);
                if ($repository) : ?>
                <div class="workflow-meta-item">
                    <i class="dashicons dashicons-admin-site"></i>
                    <span><?php echo esc_html($repository); ?></span>
                </div>
                <?php endif; ?>
                
                <?php 
                $nodes_count = get_post_meta(get_the_ID(), '_workflow_nodes_count', true);
                if ($nodes_count) : ?>
                <div class="workflow-meta-item">
                    <i class="dashicons dashicons-networking"></i>
                    <span><?php echo esc_html($nodes_count); ?> <?php _e('nodes', 'n8n-workflow-importer'); ?></span>
                </div>
                <?php endif; ?>
                
                <?php 
                $file_size = get_post_meta(get_the_ID(), '_workflow_file_size', true);
                if ($file_size) : ?>
                <div class="workflow-meta-item">
                    <i class="dashicons dashicons-media-document"></i>
                    <span><?php echo esc_html(size_format($file_size)); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content Grid -->
        <div class="workflow-content">
            
            <!-- Main Content -->
            <div class="workflow-main">
                
                <!-- Description -->
                <?php if (get_the_content()) : ?>
                <div class="workflow-description">
                    <?php the_content(); ?>
                </div>
                <?php endif; ?>
                
                <!-- JSON Viewer -->
                <?php 
                $workflow_json = get_post_meta(get_the_ID(), '_workflow_json', true);
                if ($workflow_json) : ?>
                <div class="workflow-json">
                    <div class="json-header">
                        <h3><?php _e('Workflow JSON', 'n8n-workflow-importer'); ?></h3>
                        <div class="json-actions">
                            <button class="json-btn copy" title="<?php _e('Copy to clipboard', 'n8n-workflow-importer'); ?>">
                                <i class="dashicons dashicons-admin-page"></i>
                                <?php _e('Copy', 'n8n-workflow-importer'); ?>
                            </button>
                            <button class="json-btn download" title="<?php _e('Download JSON file', 'n8n-workflow-importer'); ?>">
                                <i class="dashicons dashicons-download"></i>
                                <?php _e('Download', 'n8n-workflow-importer'); ?>
                            </button>
                            <button class="json-btn toggle" title="<?php _e('Toggle JSON view', 'n8n-workflow-importer'); ?>">
                                <i class="dashicons dashicons-visibility"></i>
                                <?php _e('Toggle', 'n8n-workflow-importer'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="json-content">
                        <textarea class="json-viewer" readonly><?php echo esc_textarea($workflow_json); ?></textarea>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Sidebar -->
            <div class="workflow-sidebar">
                
                <!-- Workflow Stats -->
                <div class="sidebar-widget">
                    <div class="widget-header">
                        <h3><?php _e('Workflow Stats', 'n8n-workflow-importer'); ?></h3>
                    </div>
                    <div class="widget-content">
                        <div class="workflow-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($nodes_count ?: '0'); ?></span>
                                <span class="stat-label"><?php _e('Nodes', 'n8n-workflow-importer'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html(get_post_meta(get_the_ID(), '_workflow_connections_count', true) ?: '0'); ?></span>
                                <span class="stat-label"><?php _e('Connections', 'n8n-workflow-importer'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($file_size ? size_format($file_size) : '0 KB'); ?></span>
                                <span class="stat-label"><?php _e('File Size', 'n8n-workflow-importer'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html(get_post_meta(get_the_ID(), '_workflow_version', true) ?: '1.0'); ?></span>
                                <span class="stat-label"><?php _e('Version', 'n8n-workflow-importer'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- GitHub Information -->
                <?php 
                $github_url = get_post_meta(get_the_ID(), '_workflow_github_url', true);
                $github_raw_url = get_post_meta(get_the_ID(), '_workflow_github_raw_url', true);
                if ($github_url || $github_raw_url) : ?>
                <div class="sidebar-widget">
                    <div class="widget-header">
                        <h3><?php _e('GitHub Information', 'n8n-workflow-importer'); ?></h3>
                    </div>
                    <div class="widget-content">
                        <div class="github-info">
                            <?php if ($github_url) : ?>
                            <a href="<?php echo esc_url($github_url); ?>" target="_blank" class="github-link">
                                <i class="dashicons dashicons-admin-site"></i>
                                <?php _e('View on GitHub', 'n8n-workflow-importer'); ?>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($github_raw_url) : ?>
                            <a href="<?php echo esc_url($github_raw_url); ?>" target="_blank" class="github-link">
                                <i class="dashicons dashicons-media-code"></i>
                                <?php _e('Raw JSON File', 'n8n-workflow-importer'); ?>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($repository) : ?>
                            <div class="github-repo-info">
                                <strong><?php _e('Repository:', 'n8n-workflow-importer'); ?></strong><br>
                                <?php echo esc_html($repository); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Categories -->
                <?php 
                $categories = get_the_terms(get_the_ID(), 'workflow_category');
                if ($categories && !is_wp_error($categories)) : ?>
                <div class="sidebar-widget">
                    <div class="widget-header">
                        <h3><?php _e('Categories', 'n8n-workflow-importer'); ?></h3>
                    </div>
                    <div class="widget-content">
                        <div class="workflow-categories">
                            <?php foreach ($categories as $category) : ?>
                            <a href="<?php echo get_term_link($category); ?>" class="workflow-category">
                                <?php echo esc_html($category->name); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Tags -->
                <?php 
                $tags = get_the_terms(get_the_ID(), 'workflow_tag');
                if ($tags && !is_wp_error($tags)) : ?>
                <div class="sidebar-widget">
                    <div class="widget-header">
                        <h3><?php _e('Tags', 'n8n-workflow-importer'); ?></h3>
                    </div>
                    <div class="widget-content">
                        <div class="workflow-tags">
                            <?php foreach ($tags as $tag) : ?>
                            <a href="<?php echo get_term_link($tag); ?>" class="workflow-tag">
                                <?php echo esc_html($tag->name); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Workflow Nodes -->
                <?php 
                $workflow_nodes = get_post_meta(get_the_ID(), '_workflow_nodes', true);
                if ($workflow_nodes && is_array($workflow_nodes)) : ?>
                <div class="sidebar-widget">
                    <div class="widget-header">
                        <h3><?php _e('Workflow Nodes', 'n8n-workflow-importer'); ?></h3>
                    </div>
                    <div class="widget-content">
                        <div class="workflow-nodes">
                            <?php foreach ($workflow_nodes as $index => $node) : ?>
                            <div class="workflow-node">
                                <div class="node-icon">
                                    <?php echo esc_html(substr($node['type'], 0, 2)); ?>
                                </div>
                                <div class="node-info">
                                    <div class="node-name"><?php echo esc_html($node['name'] ?: $node['type']); ?></div>
                                    <div class="node-type"><?php echo esc_html($node['type']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Share & Actions -->
                <div class="sidebar-widget">
                    <div class="widget-header">
                        <h3><?php _e('Actions', 'n8n-workflow-importer'); ?></h3>
                    </div>
                    <div class="widget-content">
                        <div class="workflow-actions">
                            <button class="share-workflow-btn button button-primary" style="width: 100%; margin-bottom: 10px;">
                                <i class="dashicons dashicons-share"></i>
                                <?php _e('Share Workflow', 'n8n-workflow-importer'); ?>
                            </button>
                            
                            <?php if ($github_raw_url) : ?>
                            <a href="<?php echo esc_url($github_raw_url); ?>" class="button" style="width: 100%; margin-bottom: 10px; text-align: center; text-decoration: none;" download>
                                <i class="dashicons dashicons-download"></i>
                                <?php _e('Download JSON', 'n8n-workflow-importer'); ?>
                            </a>
                            <?php endif; ?>
                            
                            <button class="bookmark-btn button" data-workflow-id="<?php echo get_the_ID(); ?>" style="width: 100%;">
                                <i class="dashicons dashicons-heart"></i>
                                <?php _e('Bookmark', 'n8n-workflow-importer'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
            </div>
            
        </div>
        
        <!-- Related Workflows -->
        <?php
        // Get related workflows based on categories or tags
        $related_args = array(
            'post_type' => 'n8n_workflow',
            'posts_per_page' => 3,
            'post__not_in' => array(get_the_ID()),
            'orderby' => 'rand'
        );
        
        // Try to get related by category first
        if ($categories && !is_wp_error($categories)) {
            $category_ids = wp_list_pluck($categories, 'term_id');
            $related_args['tax_query'] = array(
                array(
                    'taxonomy' => 'workflow_category',
                    'field' => 'term_id',
                    'terms' => $category_ids
                )
            );
        }
        
        $related_workflows = new WP_Query($related_args);
        
        if ($related_workflows->have_posts()) : ?>
        <div class="related-workflows">
            <h3><?php _e('Related Workflows', 'n8n-workflow-importer'); ?></h3>
            <div class="related-workflow-grid">
                <?php while ($related_workflows->have_posts()) : $related_workflows->the_post(); ?>
                <div class="related-workflow-item">
                    <h4 class="related-workflow-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h4>
                    <div class="related-workflow-excerpt">
                        <?php echo wp_trim_words(get_the_content(), 15); ?>
                    </div>
                    <div class="related-workflow-meta">
                        <span><?php echo get_the_date(); ?></span>
                        <?php 
                        $related_nodes = get_post_meta(get_the_ID(), '_workflow_nodes_count', true);
                        if ($related_nodes) : ?>
                        <span><?php echo esc_html($related_nodes); ?> <?php _e('nodes', 'n8n-workflow-importer'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php 
        wp_reset_postdata();
        endif; ?>
        
    <?php endwhile; ?>
</div>

<!-- Mobile Menu Toggle for Sidebar -->
<button class="mobile-menu-toggle" style="display: none;">
    <i class="dashicons dashicons-menu"></i>
    <?php _e('Menu', 'n8n-workflow-importer'); ?>
</button>

<style>
/* Mobile responsive styles */
@media (max-width: 768px) {
    .mobile-menu-toggle {
        display: block !important;
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        background: #667eea;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    
    .workflow-sidebar {
        position: fixed;
        top: 0;
        right: -100%;
        width: 80%;
        max-width: 400px;
        height: 100vh;
        background: white;
        z-index: 999;
        overflow-y: auto;
        transition: right 0.3s ease;
        box-shadow: -2px 0 10px rgba(0,0,0,0.3);
        padding: 80px 20px 20px;
    }
    
    .workflow-sidebar.mobile-open {
        right: 0;
    }
    
    .workflow-sidebar::before {
        content: '';
        position: fixed;
        top: 0;
        left: -100vw;
        width: 100vw;
        height: 100vh;
        background: rgba(0,0,0,0.5);
        z-index: -1;
    }
    
    .workflow-sidebar.mobile-open::before {
        left: 0;
    }
}
</style>

<script>
// Localize script for AJAX
var n8nWorkflowAjax = {
    ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('n8n_workflow_nonce'); ?>'
};
</script>

<?php get_footer(); ?>