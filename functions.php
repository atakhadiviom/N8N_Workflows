<?php
// Functions for N8N Workflows Scraper

/**
 * Scrape N8N workflows from GitHub
 */
function scrapeN8NWorkflows() {
    // Increase execution time limit
    set_time_limit(120); // 2 minutes
    
    try {
        $workflows = [];
        
        // GitHub API endpoint for searching N8N workflows
        $searchQueries = [
            'n8n workflow',
            'n8n automation'
        ];
        
        foreach ($searchQueries as $query) {
            $githubUrl = "https://api.github.com/search/repositories?q=" . urlencode($query) . "&sort=updated&per_page=10";
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: N8N-Workflow-Scraper/1.0',
                        'Accept: application/vnd.github.v3+json'
                    ]
                ]
            ]);
            
            $response = @file_get_contents($githubUrl, false, $context);
            
            if ($response === false) {
                continue;
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['items'])) {
                foreach ($data['items'] as $repo) {
                    // Check if we already have this workflow
                    $exists = false;
                    foreach ($workflows as $existing) {
                        if ($existing['url'] === $repo['html_url']) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if (!$exists) {
                        $workflow = [
                            'title' => $repo['name'],
                            'description' => $repo['description'] ?? 'No description available',
                            'url' => $repo['html_url'],
                            'author' => $repo['owner']['login'],
                            'stars' => $repo['stargazers_count'],
                            'updated_at' => $repo['updated_at'],
                            'raw_data' => $repo
                        ];
                        
                        // Generate AI description and title (only if OpenAI is configured)
                        if (isOpenAIConfigured()) {
                            $aiEnhanced = generateAIDescription($workflow);
                            if ($aiEnhanced) {
                                $workflow['title'] = $aiEnhanced['title'] ?? $workflow['title'];
                                $workflow['description'] = $aiEnhanced['description'] ?? $workflow['description'];
                            }
                        }
                        
                        $workflows[] = $workflow;
                    }
                }
            }
            
            // Rate limiting - be nice to GitHub API
            usleep(200000); // 0.2 second delay
        }
        
        // Sort by stars and updated date
        usort($workflows, function($a, $b) {
            if ($a['stars'] === $b['stars']) {
                return strtotime($b['updated_at']) - strtotime($a['updated_at']);
            }
            return $b['stars'] - $a['stars'];
        });
        
        // Limit to top 10 workflows to avoid timeout
        $workflows = array_slice($workflows, 0, 10);
        
        // Save to cache file
        file_put_contents('workflows_cache.json', json_encode($workflows, JSON_PRETTY_PRINT));
        
        return $workflows;
        
    } catch (Exception $e) {
        error_log('Error scraping workflows: ' . $e->getMessage());
        // Return JSON error instead of HTML
        header('Content-Type: application/json');
        return ['error' => 'Failed to scrape workflows: ' . $e->getMessage()];
    }
}

/**
 * Generate AI-enhanced title and description using OpenAI API
 */
function generateAIDescription($workflow) {
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        return null;
    }
    
    try {
        $prompt = "Based on this GitHub repository information for an N8N workflow:\n\n";
        $prompt .= "Repository Name: {$workflow['title']}\n";
        $prompt .= "Description: {$workflow['description']}\n";
        $prompt .= "Author: {$workflow['author']}\n\n";
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
                    'Authorization: Bearer ' . OPENAI_API_KEY
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
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                $aiResult = json_decode($jsonStr, true);
                
                if ($aiResult && isset($aiResult['title']) && isset($aiResult['description'])) {
                    return [
                        'title' => substr($aiResult['title'], 0, 60),
                        'description' => substr($aiResult['description'], 0, 200)
                    ];
                }
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log('Error generating AI description: ' . $e->getMessage());
        return null;
    }
}

/**
 * Search workflows from cache
 */
function searchWorkflows($query) {
    try {
        // Load from cache if exists
        if (file_exists('workflows_cache.json')) {
            $workflows = json_decode(file_get_contents('workflows_cache.json'), true);
            
            if (empty($query)) {
                return $workflows;
            }
            
            $filtered = array_filter($workflows, function($workflow) use ($query) {
                return stripos($workflow['title'], $query) !== false ||
                       stripos($workflow['description'], $query) !== false ||
                       stripos($workflow['author'], $query) !== false;
            });
            
            return array_values($filtered);
        }
        
        return [];
        
    } catch (Exception $e) {
        error_log('Error searching workflows: ' . $e->getMessage());
        return ['error' => 'Failed to search workflows: ' . $e->getMessage()];
    }
}

/**
 * Get workflow details from GitHub API
 */
function getWorkflowDetails($repoUrl) {
    try {
        // Extract owner and repo from URL
        $parts = parse_url($repoUrl);
        $pathParts = explode('/', trim($parts['path'], '/'));
        
        if (count($pathParts) < 2) {
            return null;
        }
        
        $owner = $pathParts[0];
        $repo = $pathParts[1];
        
        $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}/contents";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: N8N-Workflow-Scraper/1.0',
                    'Accept: application/vnd.github.v3+json'
                ]
            ]
        ]);
        
        $response = @file_get_contents($apiUrl, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        $files = json_decode($response, true);
        
        // Look for .n8n files or workflow-related files
        $workflowFiles = [];
        foreach ($files as $file) {
            if (isset($file['name']) && 
                (strpos($file['name'], '.n8n') !== false || 
                 strpos(strtolower($file['name']), 'workflow') !== false ||
                 strpos(strtolower($file['name']), 'automation') !== false)) {
                $workflowFiles[] = $file;
            }
        }
        
        return $workflowFiles;
        
    } catch (Exception $e) {
        error_log('Error getting workflow details: ' . $e->getMessage());
        return null;
    }
}
?>