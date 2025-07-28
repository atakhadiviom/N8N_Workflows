/**
 * N8N Workflows Explorer - Frontend JavaScript
 * Handles search, filtering, and AJAX interactions
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initializeWorkflowsExplorer();
    });
    
    function initializeWorkflowsExplorer() {
        const $container = $('#n8n-workflows-container');
        const $searchInput = $('#n8n-search-input');
        const $searchBtn = $('#n8n-search-btn');
        const $sortFilter = $('#n8n-sort-filter');
        const $grid = $('.n8n-workflows-grid');
        const $loading = $('#n8n-loading');
        
        // Search functionality
        let searchTimeout;
        
        function performSearch() {
            const query = $searchInput.val().trim();
            const sortBy = $sortFilter.val();
            
            // Show loading
            $loading.show();
            $grid.hide();
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Debounce search
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: n8n_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'n8n_search_workflows',
                        query: query,
                        sort: sortBy,
                        nonce: n8n_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            updateWorkflowsGrid(response.data);
                        } else {
                            console.error('Search failed:', response.data);
                            showError('Search failed. Please try again.');
                        }
                    },
                    error: function() {
                        console.error('AJAX error during search');
                        showError('Connection error. Please try again.');
                    },
                    complete: function() {
                        $loading.hide();
                        $grid.show();
                    }
                });
            }, 300);
        }
        
        function updateWorkflowsGrid(workflows) {
            if (!workflows || workflows.length === 0) {
                $grid.html('<div class="n8n-no-workflows"><p>No workflows found matching your search.</p></div>');
                return;
            }
            
            let html = '';
            workflows.forEach(function(workflow) {
                html += createWorkflowCard(workflow);
            });
            
            $grid.html(html);
            
            // Add fade-in animation
            $grid.find('.n8n-workflow-card').each(function(index) {
                $(this).css('opacity', '0').delay(index * 50).animate({opacity: 1}, 300);
            });
        }
        
        function createWorkflowCard(workflow) {
            return `
                <div class="n8n-workflow-card" data-stars="${workflow.stars}" data-title="${workflow.title.toLowerCase()}">
                    <div class="n8n-workflow-header">
                        <h3 class="n8n-workflow-title">
                            <a href="${workflow.permalink}" title="${escapeHtml(workflow.title)}">
                                ${escapeHtml(workflow.title)}
                            </a>
                        </h3>
                        <div class="n8n-workflow-meta">
                            <span class="n8n-author">👤 ${escapeHtml(workflow.author)}</span>
                            <span class="n8n-stars">⭐ ${workflow.stars}</span>
                        </div>
                    </div>
                    
                    <div class="n8n-workflow-content">
                        <p class="n8n-workflow-description">
                            ${escapeHtml(workflow.description)}
                        </p>
                    </div>
                    
                    <div class="n8n-workflow-footer">
                        <div class="n8n-workflow-links">
                            <a href="${workflow.url}" target="_blank" class="n8n-github-link" title="View on GitHub">
                                📁 GitHub
                            </a>
                            <a href="${workflow.permalink}" class="n8n-details-link" title="View Details">
                                📄 Details
                            </a>
                        </div>
                        <div class="n8n-workflow-date">
                            ${workflow.date}
                        </div>
                    </div>
                </div>
            `;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showError(message) {
            $grid.html(`<div class="n8n-no-workflows"><p style="color: #d63384;">${message}</p></div>`);
        }
        
        // Event listeners
        $searchBtn.on('click', performSearch);
        
        $searchInput.on('keyup', function(e) {
            if (e.keyCode === 13) { // Enter key
                performSearch();
            } else {
                // Auto-search after typing stops
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 500);
            }
        });
        
        $sortFilter.on('change', performSearch);
        
        // Client-side filtering for immediate feedback
        function clientSideFilter() {
            const query = $searchInput.val().toLowerCase().trim();
            const sortBy = $sortFilter.val();
            
            if (!query && sortBy === 'date') {
                // Show all cards in original order
                $('.n8n-workflow-card').show();
                return;
            }
            
            let $cards = $('.n8n-workflow-card');
            
            // Filter by search query
            if (query) {
                $cards.each(function() {
                    const $card = $(this);
                    const title = $card.find('.n8n-workflow-title').text().toLowerCase();
                    const description = $card.find('.n8n-workflow-description').text().toLowerCase();
                    const author = $card.find('.n8n-author').text().toLowerCase();
                    
                    if (title.includes(query) || description.includes(query) || author.includes(query)) {
                        $card.show();
                    } else {
                        $card.hide();
                    }
                });
            }
            
            // Sort visible cards
            $cards = $('.n8n-workflow-card:visible');
            
            if (sortBy === 'stars') {
                $cards.sort(function(a, b) {
                    return parseInt($(b).data('stars')) - parseInt($(a).data('stars'));
                });
            } else if (sortBy === 'title') {
                $cards.sort(function(a, b) {
                    return $(a).data('title').localeCompare($(b).data('title'));
                });
            }
            
            // Reorder in DOM
            $cards.detach().appendTo($grid);
        }
        
        // Add immediate client-side filtering for better UX
        $searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            clientSideFilter();
            
            // Still perform server search after delay
            searchTimeout = setTimeout(performSearch, 1000);
        });
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 75) {
                e.preventDefault();
                $searchInput.focus();
            }
            
            // Escape to clear search
            if (e.keyCode === 27 && $searchInput.is(':focus')) {
                $searchInput.val('').trigger('input');
            }
        });
        
        // Add search hint
        if ($searchInput.length) {
            $searchInput.attr('title', 'Press Ctrl+K to focus, Escape to clear');
        }
        
        // Lazy loading for images (if any are added later)
        function initLazyLoading() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                
                document.querySelectorAll('img[data-src]').forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
        }
        
        // Initialize lazy loading
        initLazyLoading();
        
        // Smooth scroll to top when searching
        function scrollToTop() {
            $('html, body').animate({
                scrollTop: $container.offset().top - 20
            }, 300);
        }
        
        // Auto-scroll on search
        $searchBtn.on('click', scrollToTop);
        $searchInput.on('keydown', function(e) {
            if (e.keyCode === 13) {
                setTimeout(scrollToTop, 100);
            }
        });
    }
    
})(jQuery);

// Vanilla JS fallback for non-jQuery environments
if (typeof jQuery === 'undefined') {
    console.warn('N8N Workflows Explorer: jQuery not found. Some features may not work.');
    
    document.addEventListener('DOMContentLoaded', function() {
        // Basic search functionality without jQuery
        const searchInput = document.getElementById('n8n-search-input');
        const searchBtn = document.getElementById('n8n-search-btn');
        
        if (searchInput && searchBtn) {
            function basicSearch() {
                const query = searchInput.value.toLowerCase().trim();
                const cards = document.querySelectorAll('.n8n-workflow-card');
                
                cards.forEach(function(card) {
                    const title = card.querySelector('.n8n-workflow-title').textContent.toLowerCase();
                    const description = card.querySelector('.n8n-workflow-description').textContent.toLowerCase();
                    
                    if (!query || title.includes(query) || description.includes(query)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
            
            searchBtn.addEventListener('click', basicSearch);
            searchInput.addEventListener('keyup', function(e) {
                if (e.keyCode === 13) {
                    basicSearch();
                }
            });
        }
    });
}