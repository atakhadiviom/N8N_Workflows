<?php
/**
 * N8N Workflows Explorer - Core Functions
 * This file contains the main scraping and workflow management functions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add the core functions to the main class
add_action('init', function() {
    if (class_exists('N8NWorkflowsExplorer')) {
        // Initialize core methods if available
        if (method_exists('N8NWorkflowsExplorer', 'add_core_methods')) {
            N8NWorkflowsExplorer::add_core_methods();
        }
    }
});

class N8NWorkflowsExplorerFunctions {
    
    public static function scrape_and_create_posts() {
        // Check if WordPress functions are available
        if (!function_exists('get_option')) {
            return false;
        }
        
        // Check if auto-scraping is enabled
        if (!get_option('n8n_auto_scrape_enabled', 1)) {
            return false;
        }
        
        if (function_exists('set_time_limit')) {
            set_time_limit(300); // 5 minutes
        }
        
        try {
            $workflows = self::scrape_github_workflows();
            
            if (empty($workflows) || isset($workflows['error'])) {
                if (function_exists('error_log')) {
                    error_log('N8N Workflows: Failed to scrape workflows - ' . ($workflows['error'] ?? 'Unknown error'));
                }
                return false;
            }
            
            $created_count = 0;
            
            foreach ($workflows as $workflow) {
                if (self::create_workflow_post($workflow)) {
                    $created_count++;
                }
            }
            
            // Log the result
            if (function_exists('error_log')) {
                error_log("N8N Workflows: Created {$created_count} new workflow posts");
            }
            
            return $created_count;
            
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log('N8N Workflows Error: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    public static function scrape_github_workflows() {
        $workflows = [];
        $max_workflows = get_option('n8n_max_workflows', 10);
        
        $search_queries = [
            'n8n workflow',
            'n8n automation'
        ];
        
        foreach ($search_queries as $query) {
            $github_url = "https://api.github.com/search/repositories?q=" . urlencode($query) . "&sort=updated&per_page=" . min($max_workflows, 20);
            
            $headers = self::get_github_headers();
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers)
                ]
            ]);
            
            $response = @file_get_contents($github_url, false, $context);
            
            if ($response === false) {
                continue;
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['items'])) {
                foreach ($data['items'] as $repo) {
                    // Check if we already have this workflow
                    if (self::workflow_exists($repo['html_url'])) {
                        continue;
                    }
                    
                    $workflow = [
                        'title' => $repo['name'],
                        'description' => $repo['description'] ?? 'No description available',
                        'url' => $repo['html_url'],
                        'author' => $repo['owner']['login'],
                        'stars' => $repo['stargazers_count'],
                        'forks' => $repo['forks_count'],
                        'language' => $repo['language'],
                        'updated_at' => $repo['updated_at'],
                        'created_at' => $repo['created_at'],
                        'topics' => $repo['topics'] ?? []
                    ];
                    
                    // Generate AI description if OpenAI is configured
                    if (self::is_openai_configured()) {
                        $ai_enhanced = self::generate_ai_description($workflow);
                        if ($ai_enhanced) {
                            $workflow['ai_title'] = $ai_enhanced['title'] ?? $workflow['title'];
                            $workflow['ai_description'] = $ai_enhanced['description'] ?? $workflow['description'];
                        }
                    }
                    
                    $workflows[] = $workflow;
                    
                    if (count($workflows) >= $max_workflows) {
                        break 2;
                    }
                }
            }
            
            // Rate limiting
            usleep(200000); // 0.2 second delay
        }
        
        return $workflows;
    }
    
    public static function workflow_exists($github_url) {
        $existing = get_posts([
            'post_type' => 'n8n_workflow',
            'meta_query' => [
                [
                    'key' => 'github_url',
                    'value' => $github_url,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        
        return !empty($existing);
    }
    
    public static function create_workflow_post($workflow) {
        // Use AI-enhanced title and description if available
        $title = $workflow['ai_title'] ?? $workflow['title'];
        $description = $workflow['ai_description'] ?? $workflow['description'];
        
        // Create post content
        $content = "<div class='n8n-workflow-content'>";
        $content .= "<p><strong>Description:</strong> " . esc_html($description) . "</p>";
        $content .= "<p><strong>Author:</strong> " . esc_html($workflow['author']) . "</p>";
        $content .= "<p><strong>GitHub Repository:</strong> <a href='" . esc_url($workflow['url']) . "' target='_blank'>" . esc_html($workflow['url']) . "</a></p>";
        $content .= "<p><strong>Stars:</strong> " . intval($workflow['stars']) . " | <strong>Forks:</strong> " . intval($workflow['forks']) . "</p>";
        
        if (!empty($workflow['language'])) {
            $content .= "<p><strong>Primary Language:</strong> " . esc_html($workflow['language']) . "</p>";
        }
        
        if (!empty($workflow['topics'])) {
            $content .= "<p><strong>Topics:</strong> " . implode(', ', array_map('esc_html', $workflow['topics'])) . "</p>";
        }
        
        $content .= "</div>";
        
        // Create the post
        $post_data = [
            'post_title' => sanitize_text_field($title),
            'post_content' => $content,
            'post_excerpt' => sanitize_text_field(substr($description, 0, 150)),
            'post_status' => 'publish',
            'post_type' => 'n8n_workflow',
            'post_author' => 1, // Admin user
            'meta_input' => [
                'github_url' => esc_url_raw($workflow['url']),
                'github_author' => sanitize_text_field($workflow['author']),
                'github_stars' => intval($workflow['stars']),
                'github_forks' => intval($workflow['forks']),
                'github_language' => sanitize_text_field($workflow['language'] ?? ''),
                'github_updated' => sanitize_text_field($workflow['updated_at']),
                'github_created' => sanitize_text_field($workflow['created_at']),
                'original_description' => sanitize_textarea_field($workflow['description']),
                'ai_enhanced' => !empty($workflow['ai_title']) ? 1 : 0
            ]
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Add topics as taxonomy terms
            if (!empty($workflow['topics'])) {
                $topic_terms = [];
                foreach ($workflow['topics'] as $topic) {
                    $term = wp_insert_term($topic, 'workflow_category');
                    if (!is_wp_error($term)) {
                        $topic_terms[] = $term['term_id'];
                    } else {
                        // Term might already exist
                        $existing_term = get_term_by('name', $topic, 'workflow_category');
                        if ($existing_term) {
                            $topic_terms[] = $existing_term->term_id;
                        }
                    }
                }
                
                if (!empty($topic_terms)) {
                    wp_set_post_terms($post_id, $topic_terms, 'workflow_category');
                }
            }
            
            return $post_id;
        }
        
        return false;
    }
    
    public static function generate_ai_description($workflow) {
        // Check if WordPress functions are available
        if (!function_exists('get_option')) {
            return null;
        }
        
        $api_key = get_option('n8n_openai_api_key', '');
        
        if (empty($api_key)) {
            return null;
        }
        
        try {
            $prompt = "Based on this GitHub repository information for an N8N workflow:\n\n";
            $prompt .= "Repository Name: {$workflow['title']}\n";
            $prompt .= "Description: {$workflow['description']}\n";
            $prompt .= "Author: {$workflow['author']}\n";
            $prompt .= "Topics: " . implode(', ', $workflow['topics']) . "\n\n";
            $prompt .= "Please generate:\n";
            $prompt .= "1. A clear, descriptive title (max 60 characters)\n";
            $prompt .= "2. A detailed description (max 200 characters) explaining what this N8N workflow does\n\n";
            $prompt .= "Format your response as JSON with 'title' and 'description' keys.";
            
            $data = [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert in N8N automation workflows. Generate clear, concise titles and descriptions for N8N workflows based on repository information.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 300,
                'temperature' => 0.7
            ];
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $api_key
                    ],
                    'content' => json_encode($data)
                ]
            ]);
            
            $response = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $context);
            
            if ($response === false) {
                return null;
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['choices'][0]['message']['content'])) {
                $content = $result['choices'][0]['message']['content'];
                
                // Try to extract JSON from the response
                $json_start = strpos($content, '{');
                $json_end = strrpos($content, '}');
                
                if ($json_start !== false && $json_end !== false) {
                    $json_str = substr($content, $json_start, $json_end - $json_start + 1);
                    $ai_result = json_decode($json_str, true);
                    
                    if ($ai_result && isset($ai_result['title']) && isset($ai_result['description'])) {
                        return [
                            'title' => substr($ai_result['title'], 0, 60),
                            'description' => substr($ai_result['description'], 0, 200)
                        ];
                    }
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log('N8N Workflows AI Error: ' . $e->getMessage());
            }
            return null;
        }
    }
    
    public static function is_openai_configured() {
        $api_key = get_option('n8n_openai_api_key', '');
        return !empty($api_key) && strpos($api_key, 'sk-') === 0;
    }
    
    public static function get_github_headers() {
        $headers = [
            'User-Agent: N8N-Workflow-WordPress-Plugin/1.0',
            'Accept: application/vnd.github.v3+json'
        ];
        
        $github_token = get_option('n8n_github_token', '');
        if (!empty($github_token)) {
            $headers[] = 'Authorization: token ' . $github_token;
        }
        
        return $headers;
    }
    
    public static function search_workflows($query = '') {
        // Check if WordPress functions are available
        if (!function_exists('get_posts') || !function_exists('sanitize_text_field')) {
            return [];
        }
        
        $args = [
            'post_type' => 'n8n_workflow',
            'posts_per_page' => 20,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        if (!empty($query)) {
            $args['s'] = sanitize_text_field($query);
        }
        
        $workflows = get_posts($args);
        $results = [];
        
        foreach ($workflows as $workflow) {
            $description = $workflow->post_excerpt;
            if (empty($description) && function_exists('wp_trim_words')) {
                $description = wp_trim_words($workflow->post_content, 20);
            }
            
            $results[] = [
                'id' => $workflow->ID,
                'title' => $workflow->post_title,
                'description' => $description,
                'url' => function_exists('get_post_meta') ? get_post_meta($workflow->ID, 'github_url', true) : '',
                'author' => function_exists('get_post_meta') ? get_post_meta($workflow->ID, 'github_author', true) : '',
                'stars' => function_exists('get_post_meta') ? get_post_meta($workflow->ID, 'github_stars', true) : 0,
                'permalink' => function_exists('get_permalink') ? get_permalink($workflow->ID) : '',
                'date' => function_exists('get_the_date') ? get_the_date('Y-m-d', $workflow->ID) : ''
            ];
        }
        
        return $results;
    }
}

// Extend the main class with core methods
if (!function_exists('add_core_methods_to_n8n_class')) {
    function add_core_methods_to_n8n_class() {
        if (class_exists('N8NWorkflowsExplorer')) {
            // Initialize core functionality if available
            if (method_exists('N8NWorkflowsExplorer', 'init_core_functions')) {
                N8NWorkflowsExplorer::init_core_functions();
            }
        }
    }
    
    if (function_exists('add_action')) {
        add_action('init', 'add_core_methods_to_n8n_class');
    }
}
?>