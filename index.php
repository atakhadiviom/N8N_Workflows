<?php
// N8N Workflows Scraper with OpenAI Integration
// Configuration
require_once 'config.php';
require_once 'functions.php';

// Handle AJAX requests
if (isset($_GET['action'])) {
    // Ensure JSON response
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        switch ($_GET['action']) {
            case 'scrape':
                $workflows = scrapeN8NWorkflows();
                echo json_encode($workflows);
                break;
            case 'search':
                $query = $_GET['query'] ?? '';
                $workflows = searchWorkflows($query);
                echo json_encode($workflows);
                break;
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>N8N Workflows Explorer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .workflow-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .workflow-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .loading {
            display: none;
        }
        .workflow-grid {
            min-height: 400px;
        }
        .search-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="search-container">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center">
                        <h1 class="mb-4"><i class="fas fa-code-branch"></i> N8N Workflows Explorer</h1>
                        <p class="lead">Discover and explore N8N workflows from GitHub with AI-generated descriptions</p>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="input-group mb-3">
                            <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="Search workflows...">
                            <button class="btn btn-light" type="button" id="searchBtn">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <button class="btn btn-success btn-lg" id="scrapeBtn">
                            <i class="fas fa-download"></i> Scrape New Workflows
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container mt-4">
            <!-- Loading Indicator -->
            <div class="loading text-center" id="loadingIndicator">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Scraping workflows and generating descriptions...</p>
            </div>

            <!-- Workflows Grid -->
            <div class="row workflow-grid" id="workflowsGrid">
                <div class="col-12 text-center text-muted">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <h4>No workflows loaded yet</h4>
                    <p>Click "Scrape New Workflows" to get started</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let allWorkflows = [];

        // Event listeners
        document.getElementById('scrapeBtn').addEventListener('click', scrapeWorkflows);
        document.getElementById('searchBtn').addEventListener('click', searchWorkflows);
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchWorkflows();
            }
        });

        // Scrape workflows function
        async function scrapeWorkflows() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            const workflowsGrid = document.getElementById('workflowsGrid');
            const scrapeBtn = document.getElementById('scrapeBtn');
            
            loadingIndicator.style.display = 'block';
            scrapeBtn.disabled = true;
            workflowsGrid.innerHTML = '';
            
            try {
                const response = await fetch('?action=scrape');
                const workflows = await response.json();
                
                if (workflows.error) {
                    throw new Error(workflows.error);
                }
                
                allWorkflows = workflows;
                displayWorkflows(workflows);
            } catch (error) {
                console.error('Error:', error);
                workflowsGrid.innerHTML = `
                    <div class="col-12 text-center text-danger">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h4>Error occurred</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            } finally {
                loadingIndicator.style.display = 'none';
                scrapeBtn.disabled = false;
            }
        }

        // Search workflows function
        async function searchWorkflows() {
            const query = document.getElementById('searchInput').value;
            
            if (!query.trim()) {
                displayWorkflows(allWorkflows);
                return;
            }
            
            const filteredWorkflows = allWorkflows.filter(workflow => 
                workflow.title.toLowerCase().includes(query.toLowerCase()) ||
                workflow.description.toLowerCase().includes(query.toLowerCase())
            );
            
            displayWorkflows(filteredWorkflows);
        }

        // Display workflows in grid
        function displayWorkflows(workflows) {
            const workflowsGrid = document.getElementById('workflowsGrid');
            
            if (workflows.length === 0) {
                workflowsGrid.innerHTML = `
                    <div class="col-12 text-center text-muted">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <h4>No workflows found</h4>
                        <p>Try adjusting your search criteria</p>
                    </div>
                `;
                return;
            }
            
            workflowsGrid.innerHTML = workflows.map(workflow => `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card workflow-card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-code-branch text-primary"></i>
                                ${workflow.title}
                            </h5>
                            <p class="card-text">${workflow.description}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-user"></i> ${workflow.author}
                                </small>
                                <a href="${workflow.url}" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="fas fa-external-link-alt"></i> View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    </script>
</body>
</html>