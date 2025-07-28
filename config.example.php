<?php
// Example Configuration file for N8N Workflows Scraper
// Copy this file to config.php and update with your actual API keys

// OpenAI API Configuration
// Get your API key from: https://platform.openai.com/api-keys
// This is optional but highly recommended for better descriptions
define('OPENAI_API_KEY', 'sk-your-openai-api-key-here');

// GitHub API Configuration (optional - for higher rate limits)
// Get token from: https://github.com/settings/tokens
// Without token: 60 requests/hour
// With token: 5000 requests/hour
define('GITHUB_TOKEN', 'ghp_your-github-token-here');

// Application Settings
define('MAX_WORKFLOWS', 20);
define('CACHE_DURATION', 3600); // 1 hour in seconds
define('API_RATE_LIMIT_DELAY', 500000); // 0.5 seconds in microseconds

// Error reporting (set to 0 for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('UTC');

// Enable CORS for AJAX requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Function to check if OpenAI API key is configured
function isOpenAIConfigured() {
    return defined('OPENAI_API_KEY') && 
           OPENAI_API_KEY !== 'sk-your-openai-api-key-here' && 
           !empty(OPENAI_API_KEY) &&
           strpos(OPENAI_API_KEY, 'sk-') === 0;
}

// Function to check if GitHub token is configured
function isGitHubTokenConfigured() {
    return defined('GITHUB_TOKEN') && 
           GITHUB_TOKEN !== 'ghp_your-github-token-here' &&
           !empty(GITHUB_TOKEN);
}

// Function to get GitHub API headers
function getGitHubHeaders() {
    $headers = [
        'User-Agent: N8N-Workflow-Scraper/1.0',
        'Accept: application/vnd.github.v3+json'
    ];
    
    if (isGitHubTokenConfigured()) {
        $headers[] = 'Authorization: token ' . GITHUB_TOKEN;
    }
    
    return $headers;
}

// Quick setup instructions
/*
QUICK SETUP:

1. Copy this file to config.php:
   cp config.example.php config.php

2. Get OpenAI API Key (optional but recommended):
   - Visit: https://platform.openai.com/api-keys
   - Create new key
   - Replace 'sk-your-openai-api-key-here' with your actual key

3. Get GitHub Token (optional, for higher rate limits):
   - Visit: https://github.com/settings/tokens
   - Generate new token with 'public_repo' scope
   - Replace 'ghp_your-github-token-here' with your actual token

4. Start the application:
   php -S localhost:8000

5. Open in browser:
   http://localhost:8000

NOTE: The application will work without API keys, but:
- Without OpenAI key: No AI-enhanced descriptions
- Without GitHub token: Limited to 60 API requests per hour
*/
?>