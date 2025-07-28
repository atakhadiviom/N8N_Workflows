<?php
/**
 * GitHub API handler class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GitHub_API {
    
    private $api_base_url = 'https://api.github.com';
    private $github_token;
    
    public function __construct() {
        $this->github_token = get_option('n8n_github_token', '');
    }
    
    /**
     * Search for n8n workflows on GitHub
     */
    public function search_workflows($search_term = '', $page = 1, $per_page = 30) {
        $query = 'filename:.json';
        
        if (!empty($search_term)) {
            $query .= ' ' . $search_term;
        }
        
        // Add n8n specific search terms
        $query .= ' (n8n OR workflow OR automation)';
        
        $url = $this->api_base_url . '/search/code';
        $params = array(
            'q' => $query,
            'page' => $page,
            'per_page' => $per_page,
            'sort' => 'updated',
            'order' => 'desc'
        );
        
        $response = $this->make_request($url, $params);
        
        if (is_wp_error($response)) {
            throw new Exception('GitHub API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from GitHub API');
        }
        
        if (isset($data['message'])) {
            throw new Exception('GitHub API Error: ' . $data['message']);
        }
        
        return $this->filter_n8n_workflows($data);
    }
    
    /**
     * Get specific workflow content from GitHub
     */
    public function get_workflow_content($file_url) {
        // Extract repository and path from GitHub URL
        if (preg_match('/github\.com\/([^\/]+\/[^\/]+)\/blob\/[^\/]+\/(.+)/', $file_url, $matches)) {
            $repo = $matches[1];
            $path = $matches[2];
        } else {
            throw new Exception('Invalid GitHub URL format');
        }
        
        $url = $this->api_base_url . '/repos/' . $repo . '/contents/' . $path;
        
        $response = $this->make_request($url);
        
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch workflow content: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from GitHub API');
        }
        
        if (isset($data['message'])) {
            throw new Exception('GitHub API Error: ' . $data['message']);
        }
        
        // Decode base64 content
        if (isset($data['content'])) {
            $data['content'] = base64_decode($data['content']);
        }
        
        // Add repository information
        $repo_info = $this->get_repository_info($repo);
        $data['repository'] = $repo_info;
        
        return $data;
    }
    
    /**
     * Get latest n8n workflows from popular repositories
     */
    public function get_latest_workflows($limit = 50) {
        $popular_repos = array(
            'Zie619/n8n-workflows',
            'anushgr/n8n-workflows',
            'n8n-io/n8n',
            'n8n-community/n8n-workflows'
        );
        
        $all_workflows = array();
        
        foreach ($popular_repos as $repo) {
            try {
                $workflows = $this->search_workflows_in_repo($repo, $limit / count($popular_repos));
                $all_workflows = array_merge($all_workflows, $workflows);
            } catch (Exception $e) {
                error_log('Failed to fetch workflows from ' . $repo . ': ' . $e->getMessage());
                continue;
            }
        }
        
        // Sort by updated date
        usort($all_workflows, function($a, $b) {
            return strtotime($b['repository']['updated_at']) - strtotime($a['repository']['updated_at']);
        });
        
        return array_slice($all_workflows, 0, $limit);
    }
    
    /**
     * Search workflows in a specific repository
     */
    private function search_workflows_in_repo($repo, $limit = 10) {
        $query = 'filename:.json repo:' . $repo;
        
        $url = $this->api_base_url . '/search/code';
        $params = array(
            'q' => $query,
            'per_page' => $limit,
            'sort' => 'updated',
            'order' => 'desc'
        );
        
        $response = $this->make_request($url, $params);
        
        if (is_wp_error($response)) {
            throw new Exception('GitHub API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from GitHub API');
        }
        
        if (isset($data['message'])) {
            throw new Exception('GitHub API Error: ' . $data['message']);
        }
        
        return $this->filter_n8n_workflows($data);
    }
    
    /**
     * Get repository information
     */
    private function get_repository_info($repo) {
        $url = $this->api_base_url . '/repos/' . $repo;
        
        $response = $this->make_request($url);
        
        if (is_wp_error($response)) {
            return array('full_name' => $repo);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array('full_name' => $repo);
        }
        
        return $data;
    }
    
    /**
     * Filter results to only include likely n8n workflows
     */
    private function filter_n8n_workflows($data) {
        if (!isset($data['items'])) {
            return array('items' => array(), 'total_count' => 0);
        }
        
        $filtered_items = array();
        
        foreach ($data['items'] as $item) {
            // Check if file is likely an n8n workflow
            if ($this->is_likely_n8n_workflow($item)) {
                // Get file content to verify
                try {
                    $content = $this->get_file_content($item['url']);
                    $json_data = json_decode($content, true);
                    
                    // Verify it's an n8n workflow by checking for n8n-specific properties
                    if ($this->is_n8n_workflow_json($json_data)) {
                        $item['content'] = $content;
                        $item['json_data'] = $json_data;
                        $filtered_items[] = $item;
                    }
                } catch (Exception $e) {
                    // Skip files that can't be read
                    continue;
                }
            }
        }
        
        return array(
            'items' => $filtered_items,
            'total_count' => count($filtered_items),
            'original_count' => $data['total_count']
        );
    }
    
    /**
     * Check if file is likely an n8n workflow based on filename and path
     */
    private function is_likely_n8n_workflow($item) {
        $filename = strtolower($item['name']);
        $path = strtolower($item['path']);
        
        // Must be a JSON file
        if (!str_ends_with($filename, '.json')) {
            return false;
        }
        
        // Check for n8n-related keywords in filename or path
        $n8n_keywords = array('n8n', 'workflow', 'automation', 'nodemation');
        
        foreach ($n8n_keywords as $keyword) {
            if (strpos($filename, $keyword) !== false || strpos($path, $keyword) !== false) {
                return true;
            }
        }
        
        // Check repository name
        $repo_name = strtolower($item['repository']['full_name']);
        foreach ($n8n_keywords as $keyword) {
            if (strpos($repo_name, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verify if JSON content is an n8n workflow
     */
    private function is_n8n_workflow_json($json_data) {
        if (!is_array($json_data)) {
            return false;
        }
        
        // Check for n8n workflow structure
        $required_fields = array('nodes', 'connections');
        foreach ($required_fields as $field) {
            if (!isset($json_data[$field])) {
                return false;
            }
        }
        
        // Check if nodes have n8n-specific structure
        if (isset($json_data['nodes']) && is_array($json_data['nodes'])) {
            foreach ($json_data['nodes'] as $node) {
                if (isset($node['type']) && isset($node['position'])) {
                    return true; // Found at least one valid n8n node
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get file content from GitHub API
     */
    private function get_file_content($api_url) {
        $response = $this->make_request($api_url);
        
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch file content');
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response');
        }
        
        if (isset($data['content'])) {
            return base64_decode($data['content']);
        }
        
        throw new Exception('No content found in response');
    }
    
    /**
     * Make HTTP request to GitHub API
     */
    private function make_request($url, $params = array()) {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $headers = array(
            'User-Agent' => 'N8N-Workflow-Importer/1.0.0',
            'Accept' => 'application/vnd.github.v3+json'
        );
        
        // Add authentication if token is available
        if (!empty($this->github_token)) {
            $headers['Authorization'] = 'token ' . $this->github_token;
        }
        
        $args = array(
            'headers' => $headers,
            'timeout' => 30
        );
        
        return wp_remote_get($url, $args);
    }
}