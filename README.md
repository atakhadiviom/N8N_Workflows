# N8N Workflow Importer WordPress Plugin

A comprehensive WordPress plugin that automatically searches for n8n workflows on GitHub, imports them as custom posts, and displays the JSON content with a beautiful interface.

## Features

- 🤖 **AI-Powered Descriptions**: Uses OpenRouter AI (DeepSeek model) to automatically generate meaningful titles and descriptions for workflows
- 🔍 **Automatic GitHub Search**: Searches for n8n workflow JSON files across GitHub repositories
- 📥 **Smart Import System**: Automatically imports workflows with metadata extraction
- 🎨 **Beautiful Display**: Modern, responsive interface for viewing workflows
- 📱 **Mobile Responsive**: Optimized for all device sizes
- 🏷️ **Taxonomy Support**: Categories and tags for workflow organization
- 🔧 **Admin Interface**: Easy-to-use admin panel for management
- ⚡ **AJAX Powered**: Smooth user experience with AJAX functionality
- 📊 **Workflow Analytics**: Node counting, file size tracking, and statistics
- 🔗 **GitHub Integration**: Direct links to source repositories
- 📋 **JSON Viewer**: Syntax-highlighted JSON display with copy/download features

## Installation

### Method 1: Manual Installation

1. Download or clone this repository
2. Upload the entire `n8n-workflow-importer` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin settings in the admin panel

### Method 2: WordPress Admin Upload

1. Create a ZIP file of the plugin folder
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate the plugin

## Configuration

### 1. GitHub Token Setup (Recommended)

To increase API rate limits and access more repositories:

1. Go to [GitHub Settings → Developer settings → Personal access tokens](https://github.com/settings/tokens)
2. Click "Generate new token"
3. Select the "public_repo" scope for public repositories
4. Copy the token and paste it in the plugin settings

### 2. AI Integration

The plugin automatically uses OpenRouter AI to generate titles and descriptions:
- **API Key**: Pre-configured with OpenRouter API key
- **Model**: Uses DeepSeek Chat v3 (free tier)
- **Functionality**: Analyzes workflow JSON structure and node types to create meaningful descriptions
- **Fallback**: If AI fails, generates basic descriptions from workflow metadata

### 3. Plugin Settings

Navigate to **WordPress Admin → N8N Workflows → Settings** and configure:

- **GitHub Personal Access Token**: Your GitHub token for API access
- **Auto Import**: Enable automatic workflow discovery and import
- **Import Frequency**: How often to check for new workflows (hourly/daily/weekly)
- **Default Category**: Default category for imported workflows
- **Search Repositories**: List of GitHub repositories to search (one per line)

### 3. Default Repository List

The plugin comes pre-configured with these repositories:
```
Zie619/n8n-workflows
anushgr/n8n-workflows
n8n-io/n8n
```

You can add more repositories in the format: `username/repository-name`

## Usage

### Importing Workflows

#### Automatic Import
1. Enable "Auto Import" in settings
2. Set your preferred import frequency
3. The plugin will automatically discover and import new workflows

#### Manual Import
1. Go to **N8N Workflows → Import**
2. Use one of these methods:
   - **Search**: Enter keywords to search GitHub for workflows
   - **URL Import**: Paste a direct GitHub URL to a workflow JSON file
   - **Bulk Import**: Import latest workflows from configured repositories

### Viewing Workflows

#### Archive Page
- Visit `/n8n-workflows/` on your site to see all workflows
- Use filters to search by category, tags, or keywords
- Switch between grid and list views
- Sort by date, title, or number of nodes

#### Single Workflow Page
- Click any workflow to view its details
- See the complete JSON content with syntax highlighting
- Copy JSON to clipboard or download as file
- View workflow statistics and GitHub information
- Browse related workflows

### Admin Management

#### Dashboard
- Overview of total workflows and recent imports
- Quick access to import and settings
- Statistics display

#### Workflow List
- Standard WordPress post management interface
- Custom columns showing workflow-specific data
- Bulk actions for workflow management

## File Structure

```
n8n-workflow-importer/
├── n8n-workflow-importer.php          # Main plugin file
├── includes/
│   ├── class-n8n-workflow-importer.php # Main plugin class
│   ├── class-github-api.php            # GitHub API integration
│   ├── class-workflow-post-type.php    # Custom post type handler
│   └── class-admin-page.php             # Admin interface
├── admin/
│   └── class-admin-page.php             # Admin page class
├── assets/
│   ├── css/
│   │   └── n8n-workflow-importer.css    # Plugin styles
│   └── js/
│       └── n8n-workflow-importer.js     # Plugin JavaScript
├── templates/
│   ├── single-n8n_workflow.php         # Single workflow template
│   └── archive-n8n_workflow.php        # Workflow archive template
└── README.md                            # This file
```

## Custom Post Type

The plugin creates a custom post type `n8n_workflow` with:

### Custom Fields
- `_workflow_json`: Complete workflow JSON content
- `_workflow_github_url`: GitHub repository URL
- `_workflow_github_raw_url`: Direct JSON file URL
- `_workflow_repository`: Repository name
- `_workflow_nodes_count`: Number of nodes in workflow
- `_workflow_connections_count`: Number of connections
- `_workflow_file_size`: JSON file size in bytes
- `_workflow_version`: Workflow version
- `_workflow_nodes`: Array of workflow nodes

### Taxonomies
- `workflow_category`: Workflow categories
- `workflow_tag`: Workflow tags

## Hooks and Filters

### Actions
```php
// Fired after a workflow is imported
do_action('n8n_workflow_imported', $post_id, $workflow_data);

// Fired before workflow import
do_action('n8n_workflow_before_import', $workflow_data);
```

### Filters
```php
// Filter workflow data before import
apply_filters('n8n_workflow_import_data', $workflow_data);

// Filter GitHub search parameters
apply_filters('n8n_github_search_params', $params);

// Filter workflow post data
apply_filters('n8n_workflow_post_data', $post_data, $workflow_data);
```

## API Endpoints

The plugin provides AJAX endpoints for:

- `search_n8n_workflows`: Search GitHub for workflows
- `import_workflow`: Import a specific workflow
- `rate_workflow`: Rate a workflow (if rating system is enabled)
- `toggle_bookmark`: Bookmark/unbookmark workflows

## Customization

### Template Override

You can override the plugin templates by copying them to your theme:

1. Copy `templates/single-n8n_workflow.php` to your theme as `single-n8n_workflow.php`
2. Copy `templates/archive-n8n_workflow.php` to your theme as `archive-n8n_workflow.php`
3. Customize as needed

### Styling

Add custom CSS to your theme to override plugin styles:

```css
/* Custom workflow card styling */
.workflow-card {
    /* Your custom styles */
}

/* Custom JSON viewer styling */
.json-viewer {
    /* Your custom styles */
}
```

### Adding Custom Fields

```php
// Add custom meta box
add_action('add_meta_boxes', 'add_custom_workflow_meta_box');

function add_custom_workflow_meta_box() {
    add_meta_box(
        'custom_workflow_meta',
        'Custom Workflow Data',
        'custom_workflow_meta_callback',
        'n8n_workflow'
    );
}
```

## Troubleshooting

### Common Issues

1. **No workflows importing**
   - Check GitHub token is valid
   - Verify repository names are correct
   - Check WordPress cron is working

2. **API rate limit exceeded**
   - Add a GitHub Personal Access Token
   - Reduce import frequency

3. **JSON not displaying**
   - Check file permissions
   - Verify JSON is valid
   - Check for JavaScript errors

4. **Templates not loading**
   - Ensure template files are in correct location
   - Check file permissions
   - Verify post type registration

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- cURL extension enabled
- JSON extension enabled

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and questions:

1. Check the troubleshooting section above
2. Review the WordPress debug logs
3. Create an issue on the GitHub repository

## Changelog

### Version 1.0.0
- Initial release
- GitHub API integration
- Workflow import functionality
- Custom post type and taxonomies
- Admin interface
- Frontend templates
- AJAX functionality
- Mobile responsive design

---

**Note**: This plugin requires a GitHub Personal Access Token for optimal performance. Without it, you'll be limited to 60 API requests per hour.