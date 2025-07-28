<?php
/**
 * Archive N8N Workflow Template
 * 
 * This template displays a list/grid of n8n workflows with filtering
 * and search capabilities.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="n8n-workflow-archive-container">
    
    <!-- Archive Header -->
    <div class="archive-header">
        <h1 class="archive-title">
            <?php 
            if (is_tax()) {
                $term = get_queried_object();
                printf(__('%s Workflows', 'n8n-workflow-importer'), esc_html($term->name));
            } else {
                _e('N8N Workflows', 'n8n-workflow-importer');
            }
            ?>
        </h1>
        
        <?php if (is_tax() && $term->description) : ?>
        <div class="archive-description">
            <?php echo wpautop(esc_html($term->description)); ?>
        </div>
        <?php endif; ?>
        
        <div class="archive-stats">
            <?php 
            global $wp_query;
            $total_workflows = $wp_query->found_posts;
            printf(_n('%d workflow found', '%d workflows found', $total_workflows, 'n8n-workflow-importer'), $total_workflows);
            ?>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="workflow-filters">
        <div class="filter-row">
            
            <!-- Search -->
            <div class="search-box">
                <form role="search" method="get" class="workflow-search-form">
                    <input type="search" 
                           name="s" 
                           value="<?php echo get_search_query(); ?>" 
                           placeholder="<?php _e('Search workflows...', 'n8n-workflow-importer'); ?>"
                           class="workflow-search-input" />
                    <input type="hidden" name="post_type" value="n8n_workflow" />
                    <button type="submit" class="workflow-search-submit">
                        <i class="dashicons dashicons-search"></i>
                        <span class="screen-reader-text"><?php _e('Search', 'n8n-workflow-importer'); ?></span>
                    </button>
                </form>
            </div>
            
            <!-- Category Filter -->
            <div class="filter-dropdown">
                <select id="category-filter" class="filter-select">
                    <option value=""><?php _e('All Categories', 'n8n-workflow-importer'); ?></option>
                    <?php 
                    $categories = get_terms(array(
                        'taxonomy' => 'workflow_category',
                        'hide_empty' => true
                    ));
                    
                    foreach ($categories as $category) :
                        $selected = (is_tax('workflow_category', $category->slug)) ? 'selected' : '';
                    ?>
                    <option value="<?php echo esc_attr($category->slug); ?>" <?php echo $selected; ?>>
                        <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Tag Filter -->
            <div class="filter-dropdown">
                <select id="tag-filter" class="filter-select">
                    <option value=""><?php _e('All Tags', 'n8n-workflow-importer'); ?></option>
                    <?php 
                    $tags = get_terms(array(
                        'taxonomy' => 'workflow_tag',
                        'hide_empty' => true,
                        'number' => 20
                    ));
                    
                    foreach ($tags as $tag) :
                        $selected = (is_tax('workflow_tag', $tag->slug)) ? 'selected' : '';
                    ?>
                    <option value="<?php echo esc_attr($tag->slug); ?>" <?php echo $selected; ?>>
                        <?php echo esc_html($tag->name); ?> (<?php echo $tag->count; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Sort Options -->
            <div class="filter-dropdown">
                <select id="sort-filter" class="filter-select">
                    <option value="date-desc" <?php selected(get_query_var('orderby'), 'date'); ?>>
                        <?php _e('Newest First', 'n8n-workflow-importer'); ?>
                    </option>
                    <option value="date-asc">
                        <?php _e('Oldest First', 'n8n-workflow-importer'); ?>
                    </option>
                    <option value="title-asc">
                        <?php _e('Title A-Z', 'n8n-workflow-importer'); ?>
                    </option>
                    <option value="title-desc">
                        <?php _e('Title Z-A', 'n8n-workflow-importer'); ?>
                    </option>
                    <option value="nodes-desc">
                        <?php _e('Most Nodes', 'n8n-workflow-importer'); ?>
                    </option>
                    <option value="nodes-asc">
                        <?php _e('Least Nodes', 'n8n-workflow-importer'); ?>
                    </option>
                </select>
            </div>
            
            <!-- View Toggle -->
            <div class="view-toggle">
                <button class="view-btn grid-view active" data-view="grid" title="<?php _e('Grid View', 'n8n-workflow-importer'); ?>">
                    <i class="dashicons dashicons-grid-view"></i>
                </button>
                <button class="view-btn list-view" data-view="list" title="<?php _e('List View', 'n8n-workflow-importer'); ?>">
                    <i class="dashicons dashicons-list-view"></i>
                </button>
            </div>
            
        </div>
        
        <!-- Active Filters Display -->
        <div class="active-filters" style="display: none;">
            <span class="filter-label"><?php _e('Active filters:', 'n8n-workflow-importer'); ?></span>
            <div class="filter-tags"></div>
            <button class="clear-all-filters"><?php _e('Clear All', 'n8n-workflow-importer'); ?></button>
        </div>
        
    </div>
    
    <!-- Results Info -->
    <div class="results-info">
        <div class="results-count">
            <?php 
            $paged = get_query_var('paged') ? get_query_var('paged') : 1;
            $posts_per_page = get_query_var('posts_per_page');
            $start = ($paged - 1) * $posts_per_page + 1;
            $end = min($paged * $posts_per_page, $total_workflows);
            
            if ($total_workflows > 0) {
                printf(__('Showing %d-%d of %d workflows', 'n8n-workflow-importer'), $start, $end, $total_workflows);
            }
            ?>
        </div>
        
        <div class="results-actions">
            <button class="bulk-import-btn button" style="display: none;">
                <?php _e('Import Selected', 'n8n-workflow-importer'); ?>
            </button>
        </div>
    </div>
    
    <!-- Workflow Grid/List -->
    <div class="workflow-archive grid-view" id="workflow-archive">
        
        <?php if (have_posts()) : ?>
            
            <?php while (have_posts()) : the_post(); ?>
                
                <article class="workflow-card" data-workflow-id="<?php echo get_the_ID(); ?>">
                    
                    <!-- Card Header -->
                    <div class="workflow-card-header">
                        <h2 class="workflow-card-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        
                        <div class="workflow-card-actions">
                            <button class="bookmark-btn" data-workflow-id="<?php echo get_the_ID(); ?>" title="<?php _e('Bookmark', 'n8n-workflow-importer'); ?>">
                                <i class="dashicons dashicons-heart"></i>
                            </button>
                            
                            <?php 
                            $github_url = get_post_meta(get_the_ID(), '_workflow_github_url', true);
                            if ($github_url) : ?>
                            <a href="<?php echo esc_url($github_url); ?>" target="_blank" class="github-btn" title="<?php _e('View on GitHub', 'n8n-workflow-importer'); ?>">
                                <i class="dashicons dashicons-admin-site"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Card Content -->
                    <div class="workflow-card-content">
                        
                        <!-- Description -->
                        <div class="workflow-card-description">
                            <?php 
                            $content = get_the_content();
                            if ($content) {
                                echo wp_trim_words($content, 25);
                            } else {
                                _e('No description available.', 'n8n-workflow-importer');
                            }
                            ?>
                        </div>
                        
                        <!-- Categories and Tags -->
                        <div class="workflow-card-terms">
                            <?php 
                            $categories = get_the_terms(get_the_ID(), 'workflow_category');
                            if ($categories && !is_wp_error($categories)) : ?>
                            <div class="workflow-categories">
                                <?php foreach (array_slice($categories, 0, 2) as $category) : ?>
                                <a href="<?php echo get_term_link($category); ?>" class="workflow-category" data-category="<?php echo esc_attr($category->slug); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </a>
                                <?php endforeach; ?>
                                <?php if (count($categories) > 2) : ?>
                                <span class="more-terms">+<?php echo count($categories) - 2; ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php 
                            $tags = get_the_terms(get_the_ID(), 'workflow_tag');
                            if ($tags && !is_wp_error($tags)) : ?>
                            <div class="workflow-tags">
                                <?php foreach (array_slice($tags, 0, 3) as $tag) : ?>
                                <a href="<?php echo get_term_link($tag); ?>" class="workflow-tag" data-tag="<?php echo esc_attr($tag->slug); ?>">
                                    <?php echo esc_html($tag->name); ?>
                                </a>
                                <?php endforeach; ?>
                                <?php if (count($tags) > 3) : ?>
                                <span class="more-terms">+<?php echo count($tags) - 3; ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="workflow-card-footer">
                        
                        <div class="workflow-card-meta">
                            <div class="workflow-card-stats">
                                <?php 
                                $nodes_count = get_post_meta(get_the_ID(), '_workflow_nodes_count', true);
                                $file_size = get_post_meta(get_the_ID(), '_workflow_file_size', true);
                                $repository = get_post_meta(get_the_ID(), '_workflow_repository', true);
                                ?>
                                
                                <?php if ($nodes_count) : ?>
                                <div class="workflow-card-stat" title="<?php _e('Number of nodes', 'n8n-workflow-importer'); ?>">
                                    <i class="dashicons dashicons-networking"></i>
                                    <span class="node-count"><?php echo esc_html($nodes_count); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($file_size) : ?>
                                <div class="workflow-card-stat" title="<?php _e('File size', 'n8n-workflow-importer'); ?>">
                                    <i class="dashicons dashicons-media-document"></i>
                                    <span><?php echo esc_html(size_format($file_size)); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($repository) : ?>
                                <div class="workflow-card-stat" title="<?php echo esc_attr($repository); ?>">
                                    <i class="dashicons dashicons-admin-site"></i>
                                    <span><?php echo esc_html(wp_trim_words($repository, 2, '')); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="workflow-card-date">
                                <time datetime="<?php echo get_the_date('c'); ?>" title="<?php echo get_the_date(); ?>">
                                    <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ' . __('ago', 'n8n-workflow-importer'); ?>
                                </time>
                            </div>
                        </div>
                        
                        <div class="workflow-card-actions-footer">
                            <a href="<?php the_permalink(); ?>" class="view-workflow-btn button button-primary">
                                <?php _e('View Workflow', 'n8n-workflow-importer'); ?>
                            </a>
                            
                            <?php 
                            $github_raw_url = get_post_meta(get_the_ID(), '_workflow_github_raw_url', true);
                            if ($github_raw_url) : ?>
                            <a href="<?php echo esc_url($github_raw_url); ?>" class="download-json-btn button" download title="<?php _e('Download JSON', 'n8n-workflow-importer'); ?>">
                                <i class="dashicons dashicons-download"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    
                </article>
                
            <?php endwhile; ?>
            
        <?php else : ?>
            
            <!-- No Results -->
            <div class="no-workflows-found">
                <div class="no-results-icon">
                    <i class="dashicons dashicons-search"></i>
                </div>
                <h3><?php _e('No workflows found', 'n8n-workflow-importer'); ?></h3>
                <p><?php _e('Try adjusting your search criteria or browse all workflows.', 'n8n-workflow-importer'); ?></p>
                
                <div class="no-results-actions">
                    <a href="<?php echo get_post_type_archive_link('n8n_workflow'); ?>" class="button button-primary">
                        <?php _e('View All Workflows', 'n8n-workflow-importer'); ?>
                    </a>
                    
                    <?php if (current_user_can('manage_options')) : ?>
                    <a href="<?php echo admin_url('admin.php?page=n8n-workflow-import'); ?>" class="button">
                        <?php _e('Import Workflows', 'n8n-workflow-importer'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php endif; ?>
        
    </div>
    
    <!-- Pagination -->
    <?php if (have_posts()) : ?>
    <div class="workflow-pagination">
        <?php 
        echo paginate_links(array(
            'total' => $wp_query->max_num_pages,
            'current' => $paged,
            'format' => '?paged=%#%',
            'show_all' => false,
            'end_size' => 1,
            'mid_size' => 2,
            'prev_next' => true,
            'prev_text' => '<i class="dashicons dashicons-arrow-left-alt2"></i> ' . __('Previous', 'n8n-workflow-importer'),
            'next_text' => __('Next', 'n8n-workflow-importer') . ' <i class="dashicons dashicons-arrow-right-alt2"></i>',
            'type' => 'list'
        ));
        ?>
    </div>
    <?php endif; ?>
    
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" style="display: none;">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p><?php _e('Loading workflows...', 'n8n-workflow-importer'); ?></p>
    </div>
</div>

<style>
/* Archive-specific styles */
.n8n-workflow-archive-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.archive-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
}

.archive-title {
    font-size: 3em;
    margin: 0 0 15px 0;
    font-weight: 300;
}

.archive-description {
    font-size: 1.2em;
    margin-bottom: 20px;
    opacity: 0.9;
}

.archive-stats {
    font-size: 1.1em;
    opacity: 0.8;
}

/* Filters */
.workflow-filters {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.filter-row {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 250px;
}

.workflow-search-form {
    display: flex;
    position: relative;
}

.workflow-search-input {
    flex: 1;
    padding: 12px 50px 12px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 25px;
    font-size: 16px;
    outline: none;
    transition: border-color 0.3s;
}

.workflow-search-input:focus {
    border-color: #667eea;
}

.workflow-search-submit {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    background: #667eea;
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.filter-dropdown {
    min-width: 150px;
}

.filter-select {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 6px;
    background: white;
    font-size: 14px;
    cursor: pointer;
}

.view-toggle {
    display: flex;
    border: 2px solid #e1e5e9;
    border-radius: 6px;
    overflow: hidden;
}

.view-btn {
    background: white;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
    transition: all 0.3s;
}

.view-btn.active {
    background: #667eea;
    color: white;
}

.active-filters {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e1e5e9;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.filter-tag {
    background: #667eea;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.filter-tag .remove {
    cursor: pointer;
    font-weight: bold;
}

/* Results Info */
.results-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px 0;
    border-bottom: 1px solid #e1e5e9;
}

/* Grid/List Views */
.workflow-archive.grid-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}

.workflow-archive.list-view {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.workflow-archive.list-view .workflow-card {
    display: flex;
    align-items: center;
    padding: 20px;
}

.workflow-archive.list-view .workflow-card-header {
    flex: 1;
    background: none;
    color: inherit;
    padding: 0;
    margin-right: 20px;
}

.workflow-archive.list-view .workflow-card-content {
    flex: 2;
    padding: 0;
    margin-right: 20px;
}

.workflow-archive.list-view .workflow-card-footer {
    flex: 1;
    padding: 0;
}

/* No Results */
.no-workflows-found {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.no-results-icon {
    font-size: 4em;
    color: #ccc;
    margin-bottom: 20px;
}

.no-results-actions {
    margin-top: 30px;
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.9);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-spinner {
    text-align: center;
}

.loading-spinner .spinner {
    width: 50px;
    height: 50px;
    margin: 0 auto 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        min-width: auto;
    }
    
    .workflow-archive.grid-view {
        grid-template-columns: 1fr;
    }
    
    .workflow-archive.list-view .workflow-card {
        flex-direction: column;
        align-items: stretch;
    }
    
    .workflow-archive.list-view .workflow-card-header,
    .workflow-archive.list-view .workflow-card-content,
    .workflow-archive.list-view .workflow-card-footer {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .results-info {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
}
</style>

<script>
// Archive page JavaScript
jQuery(document).ready(function($) {
    
    // View toggle
    $('.view-btn').on('click', function() {
        var view = $(this).data('view');
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        
        $('#workflow-archive').removeClass('grid-view list-view').addClass(view + '-view');
        
        // Save preference
        localStorage.setItem('workflow_view_preference', view);
    });
    
    // Load saved view preference
    var savedView = localStorage.getItem('workflow_view_preference');
    if (savedView) {
        $('.view-btn[data-view="' + savedView + '"]').click();
    }
    
    // Filter changes
    $('.filter-select').on('change', function() {
        updateFilters();
    });
    
    function updateFilters() {
        var category = $('#category-filter').val();
        var tag = $('#tag-filter').val();
        var sort = $('#sort-filter').val();
        
        var url = new URL(window.location);
        
        // Update URL parameters
        if (category) {
            url.searchParams.set('workflow_category', category);
        } else {
            url.searchParams.delete('workflow_category');
        }
        
        if (tag) {
            url.searchParams.set('workflow_tag', tag);
        } else {
            url.searchParams.delete('workflow_tag');
        }
        
        if (sort && sort !== 'date-desc') {
            url.searchParams.set('orderby', sort);
        } else {
            url.searchParams.delete('orderby');
        }
        
        // Reset to first page
        url.searchParams.delete('paged');
        
        // Reload page with new filters
        window.location.href = url.toString();
    }
    
    // Clear all filters
    $('.clear-all-filters').on('click', function() {
        $('.filter-select').val('');
        var url = new URL(window.location);
        url.searchParams.delete('workflow_category');
        url.searchParams.delete('workflow_tag');
        url.searchParams.delete('orderby');
        url.searchParams.delete('paged');
        window.location.href = url.toString();
    });
    
    // Show active filters
    function showActiveFilters() {
        var activeFilters = [];
        
        $('.filter-select').each(function() {
            var $select = $(this);
            var value = $select.val();
            var label = $select.find('option:selected').text();
            
            if (value) {
                activeFilters.push({
                    type: $select.attr('id'),
                    value: value,
                    label: label
                });
            }
        });
        
        if (activeFilters.length > 0) {
            var $filterTags = $('.filter-tags');
            $filterTags.empty();
            
            activeFilters.forEach(function(filter) {
                var $tag = $('<span class="filter-tag">' + filter.label + ' <span class="remove" data-filter="' + filter.type + '">×</span></span>');
                $filterTags.append($tag);
            });
            
            $('.active-filters').show();
        } else {
            $('.active-filters').hide();
        }
    }
    
    // Remove individual filter
    $(document).on('click', '.filter-tag .remove', function() {
        var filterType = $(this).data('filter');
        $('#' + filterType).val('').trigger('change');
    });
    
    // Initialize active filters display
    showActiveFilters();
    
});
</script>

<?php get_footer(); ?>