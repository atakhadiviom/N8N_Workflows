/**
 * N8N Workflow Importer JavaScript
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initWorkflowViewer();
        initJsonViewer();
        initSearchFunctionality();
        initWorkflowActions();
    });
    
    /**
     * Initialize workflow viewer functionality
     */
    function initWorkflowViewer() {
        // Add smooth scrolling to anchor links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            var target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 1000);
            }
        });
        
        // Initialize tooltips if available
        if (typeof $.fn.tooltip === 'function') {
            $('[data-toggle="tooltip"]').tooltip();
        }
        
        // Lazy load workflow nodes if there are many
        lazyLoadNodes();
    }
    
    /**
     * Initialize JSON viewer functionality
     */
    function initJsonViewer() {
        // Copy JSON to clipboard
        $('.json-btn.copy').on('click', function() {
            var jsonContent = $('.json-viewer').text();
            copyToClipboard(jsonContent);
            showMessage('JSON copied to clipboard!', 'success');
        });
        
        // Download JSON file
        $('.json-btn.download').on('click', function() {
            var jsonContent = $('.json-viewer').text();
            var workflowTitle = $('.workflow-title').text() || 'workflow';
            downloadJson(jsonContent, workflowTitle + '.json');
        });
        
        // Toggle JSON viewer
        $('.json-btn.toggle').on('click', function() {
            var $jsonContent = $('.json-content');
            var $button = $(this);
            
            if ($jsonContent.is(':visible')) {
                $jsonContent.slideUp();
                $button.text('Show JSON');
            } else {
                $jsonContent.slideDown();
                $button.text('Hide JSON');
            }
        });
        
        // Format JSON with syntax highlighting
        formatJsonSyntax();
        
        // Add line numbers to JSON
        addJsonLineNumbers();
    }
    
    /**
     * Initialize search functionality
     */
    function initSearchFunctionality() {
        // Live search in workflow content
        var searchTimeout;
        $('#workflow-search').on('input', function() {
            clearTimeout(searchTimeout);
            var searchTerm = $(this).val();
            
            searchTimeout = setTimeout(function() {
                if (searchTerm.length > 2) {
                    highlightSearchTerm(searchTerm);
                } else {
                    clearHighlights();
                }
            }, 300);
        });
        
        // Filter workflows by category/tag
        $('.filter-btn').on('click', function() {
            var filterType = $(this).data('filter-type');
            var filterValue = $(this).data('filter-value');
            
            if (filterType && filterValue) {
                filterWorkflows(filterType, filterValue);
            }
        });
        
        // Clear filters
        $('.clear-filters').on('click', function() {
            clearFilters();
        });
    }
    
    /**
     * Initialize workflow actions
     */
    function initWorkflowActions() {
        // Import workflow from URL
        $('.import-workflow-btn').on('click', function() {
            var workflowUrl = $(this).data('url');
            if (workflowUrl) {
                importWorkflowFromUrl(workflowUrl);
            }
        });
        
        // Share workflow
        $('.share-workflow-btn').on('click', function() {
            var workflowUrl = window.location.href;
            copyToClipboard(workflowUrl);
            showMessage('Workflow URL copied to clipboard!', 'success');
        });
        
        // Rate workflow
        $('.rating-star').on('click', function() {
            var rating = $(this).data('rating');
            var workflowId = $(this).closest('.workflow-rating').data('workflow-id');
            rateWorkflow(workflowId, rating);
        });
        
        // Bookmark workflow
        $('.bookmark-btn').on('click', function() {
            var workflowId = $(this).data('workflow-id');
            toggleBookmark(workflowId);
        });
    }
    
    /**
     * Copy text to clipboard
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
            } catch (err) {
                console.error('Failed to copy text: ', err);
            }
            
            document.body.removeChild(textArea);
        }
    }
    
    /**
     * Download JSON file
     */
    function downloadJson(jsonContent, filename) {
        try {
            // Validate and format JSON
            var jsonObj = JSON.parse(jsonContent);
            var formattedJson = JSON.stringify(jsonObj, null, 2);
            
            var blob = new Blob([formattedJson], { type: 'application/json' });
            var url = window.URL.createObjectURL(blob);
            
            var a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            
            document.body.appendChild(a);
            a.click();
            
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showMessage('JSON file downloaded successfully!', 'success');
        } catch (error) {
            showMessage('Error downloading JSON file: ' + error.message, 'error');
        }
    }
    
    /**
     * Format JSON with syntax highlighting
     */
    function formatJsonSyntax() {
        $('.json-viewer').each(function() {
            var $viewer = $(this);
            var jsonText = $viewer.text();
            
            try {
                var jsonObj = JSON.parse(jsonText);
                var formattedJson = JSON.stringify(jsonObj, null, 2);
                
                // Apply syntax highlighting
                var highlightedJson = syntaxHighlight(formattedJson);
                $viewer.html(highlightedJson);
            } catch (error) {
                console.warn('Invalid JSON for syntax highlighting:', error);
            }
        });
    }
    
    /**
     * Apply syntax highlighting to JSON
     */
    function syntaxHighlight(json) {
        json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        
        return json.replace(/(\\\\"|\\\\[^"\\\\]|[^"\\\\])*\\\\"/g, function(match) {
            return '<span class="json-string">' + match + '</span>';
        }).replace(/\\\\b(true|false)\\\\b/g, function(match) {
            return '<span class="json-boolean">' + match + '</span>';
        }).replace(/\\\\b(null)\\\\b/g, function(match) {
            return '<span class="json-null">' + match + '</span>';
        }).replace(/\\\\b(-?\\\\d+(?:\\\\.\\\\d+)?)\\\\b/g, function(match) {
            return '<span class="json-number">' + match + '</span>';
        }).replace(/\"([^"\\\\]*(\\\\.[^"\\\\]*)*)\"\\\\s*:/g, function(match, key) {
            return '"<span class="json-key">' + key + '</span>":';
        });
    }
    
    /**
     * Add line numbers to JSON viewer
     */
    function addJsonLineNumbers() {
        $('.json-viewer').each(function() {
            var $viewer = $(this);
            var lines = $viewer.html().split('\n');
            var numberedLines = lines.map(function(line, index) {
                return '<span class="line-number">' + (index + 1) + '</span>' + line;
            });
            
            $viewer.html(numberedLines.join('\n'));
        });
    }
    
    /**
     * Highlight search term in content
     */
    function highlightSearchTerm(searchTerm) {
        clearHighlights();
        
        var regex = new RegExp('(' + escapeRegex(searchTerm) + ')', 'gi');
        
        $('.workflow-description, .workflow-node').each(function() {
            var $element = $(this);
            var html = $element.html();
            var highlightedHtml = html.replace(regex, '<mark class="search-highlight">$1</mark>');
            $element.html(highlightedHtml);
        });
        
        // Scroll to first highlight
        var $firstHighlight = $('.search-highlight').first();
        if ($firstHighlight.length) {
            $('html, body').animate({
                scrollTop: $firstHighlight.offset().top - 100
            }, 500);
        }
    }
    
    /**
     * Clear search highlights
     */
    function clearHighlights() {
        $('.search-highlight').each(function() {
            var $highlight = $(this);
            $highlight.replaceWith($highlight.text());
        });
    }
    
    /**
     * Escape regex special characters
     */
    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    /**
     * Filter workflows by type and value
     */
    function filterWorkflows(filterType, filterValue) {
        $('.workflow-card').each(function() {
            var $card = $(this);
            var shouldShow = false;
            
            if (filterType === 'category') {
                shouldShow = $card.find('.workflow-category[data-category="' + filterValue + '"]').length > 0;
            } else if (filterType === 'tag') {
                shouldShow = $card.find('.workflow-tag[data-tag="' + filterValue + '"]').length > 0;
            } else if (filterType === 'nodes') {
                var nodeCount = parseInt($card.find('.node-count').text()) || 0;
                shouldShow = nodeCount >= parseInt(filterValue);
            }
            
            if (shouldShow) {
                $card.show();
            } else {
                $card.hide();
            }
        });
        
        updateFilterStatus(filterType, filterValue);
    }
    
    /**
     * Clear all filters
     */
    function clearFilters() {
        $('.workflow-card').show();
        $('.filter-btn').removeClass('active');
        $('.filter-status').hide();
    }
    
    /**
     * Update filter status display
     */
    function updateFilterStatus(filterType, filterValue) {
        var visibleCount = $('.workflow-card:visible').length;
        var totalCount = $('.workflow-card').length;
        
        $('.filter-status').html(
            'Showing ' + visibleCount + ' of ' + totalCount + ' workflows ' +
            '(filtered by ' + filterType + ': ' + filterValue + ')'
        ).show();
    }
    
    /**
     * Import workflow from URL
     */
    function importWorkflowFromUrl(url) {
        showMessage('Importing workflow...', 'info');
        
        $.ajax({
            url: n8nWorkflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'import_workflow',
                workflow_url: url,
                nonce: n8nWorkflowAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Workflow imported successfully!', 'success');
                    // Optionally redirect to the new workflow
                    if (response.data.workflow_url) {
                        setTimeout(function() {
                            window.location.href = response.data.workflow_url;
                        }, 2000);
                    }
                } else {
                    showMessage('Error importing workflow: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Failed to import workflow. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Rate a workflow
     */
    function rateWorkflow(workflowId, rating) {
        $.ajax({
            url: n8nWorkflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'rate_workflow',
                workflow_id: workflowId,
                rating: rating,
                nonce: n8nWorkflowAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateRatingDisplay(workflowId, rating, response.data.average_rating);
                    showMessage('Rating saved!', 'success');
                } else {
                    showMessage('Error saving rating: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Failed to save rating. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Update rating display
     */
    function updateRatingDisplay(workflowId, userRating, averageRating) {
        var $ratingContainer = $('.workflow-rating[data-workflow-id="' + workflowId + '"]');
        
        // Update user rating stars
        $ratingContainer.find('.rating-star').each(function(index) {
            var $star = $(this);
            if (index < userRating) {
                $star.addClass('active');
            } else {
                $star.removeClass('active');
            }
        });
        
        // Update average rating display
        if (averageRating) {
            $ratingContainer.find('.average-rating').text(averageRating.toFixed(1));
        }
    }
    
    /**
     * Toggle bookmark status
     */
    function toggleBookmark(workflowId) {
        var $bookmarkBtn = $('.bookmark-btn[data-workflow-id="' + workflowId + '"]');
        var isBookmarked = $bookmarkBtn.hasClass('bookmarked');
        
        $.ajax({
            url: n8nWorkflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_bookmark',
                workflow_id: workflowId,
                bookmarked: !isBookmarked,
                nonce: n8nWorkflowAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (isBookmarked) {
                        $bookmarkBtn.removeClass('bookmarked').text('Bookmark');
                        showMessage('Bookmark removed!', 'success');
                    } else {
                        $bookmarkBtn.addClass('bookmarked').text('Bookmarked');
                        showMessage('Workflow bookmarked!', 'success');
                    }
                } else {
                    showMessage('Error updating bookmark: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Failed to update bookmark. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Lazy load workflow nodes
     */
    function lazyLoadNodes() {
        var $nodeContainer = $('.workflow-nodes');
        var $nodes = $nodeContainer.find('.workflow-node');
        
        if ($nodes.length > 20) {
            // Hide nodes beyond the first 20
            $nodes.slice(20).hide();
            
            // Add "Show More" button
            var $showMoreBtn = $('<button class="show-more-nodes button">Show More Nodes (' + ($nodes.length - 20) + ')</button>');
            $nodeContainer.after($showMoreBtn);
            
            $showMoreBtn.on('click', function() {
                $nodes.slice(20).slideDown();
                $(this).remove();
            });
        }
    }
    
    /**
     * Show message to user
     */
    function showMessage(message, type) {
        var $messageContainer = $('.message-container');
        
        if (!$messageContainer.length) {
            $messageContainer = $('<div class="message-container"></div>');
            $('body').prepend($messageContainer);
        }
        
        var $message = $('<div class="message ' + type + '">' + message + '</div>');
        $messageContainer.append($message);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Allow manual close
        $message.on('click', function() {
            $(this).fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Initialize responsive behavior
     */
    function initResponsiveBehavior() {
        // Handle mobile menu toggle
        $('.mobile-menu-toggle').on('click', function() {
            $('.workflow-sidebar').toggleClass('mobile-open');
        });
        
        // Close mobile menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.workflow-sidebar, .mobile-menu-toggle').length) {
                $('.workflow-sidebar').removeClass('mobile-open');
            }
        });
        
        // Handle window resize
        $(window).on('resize', function() {
            if ($(window).width() > 768) {
                $('.workflow-sidebar').removeClass('mobile-open');
            }
        });
    }
    
    // Initialize responsive behavior
    initResponsiveBehavior();
    
    // Expose some functions globally for external use
    window.N8NWorkflowImporter = {
        copyToClipboard: copyToClipboard,
        downloadJson: downloadJson,
        showMessage: showMessage,
        importWorkflowFromUrl: importWorkflowFromUrl
    };
    
})(jQuery);

/**
 * Additional utility functions
 */

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Format date
function formatDate(dateString) {
    var date = new Date(dateString);
    var options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return date.toLocaleDateString('en-US', options);
}

// Debounce function
function debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

// Throttle function
function throttle(func, limit) {
    var inThrottle;
    return function() {
        var args = arguments;
        var context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(function() {
                inThrottle = false;
            }, limit);
        }
    };
}