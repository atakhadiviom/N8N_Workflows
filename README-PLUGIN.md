# N8N Workflows Explorer - WordPress Plugin

A WordPress plugin that automatically scrapes GitHub for N8N workflows and creates custom posts to display them on your WordPress website.

## Features

- **Daily Auto-Scraping**: Automatically scrapes GitHub for new N8N workflows once per day
- **Custom Post Type**: Creates `n8n_workflow` custom posts with rich metadata
- **AI-Enhanced Descriptions**: Uses OpenAI to generate better titles and descriptions
- **Frontend Display**: Beautiful shortcode to display workflows on any page/post
- **Search & Filter**: Real-time search and sorting functionality
- **Admin Interface**: Easy-to-use admin panel for configuration and manual scraping
- **Responsive Design**: Mobile-friendly workflow cards and grid layout
- **GitHub Integration**: Direct links to GitHub repositories with stars and metadata

## Installation

### Method 1: Upload Plugin Files

1. Download all plugin files:
   - `n8n-workflows-explorer.php` (main plugin file)
   - `n8n-workflows-functions.php` (core functions)
   - `n8n-workflows-shortcode.php` (shortcode handler)
   - `assets/n8n-workflows.css` (frontend styles)
   - `assets/n8n-workflows.js` (frontend JavaScript)

2. Create a new folder in your WordPress plugins directory:
   ```
   /wp-content/plugins/n8n-workflows-explorer/
   ```

3. Upload all files to this folder

4. Activate the plugin in WordPress Admin → Plugins

### Method 2: ZIP Installation

1. Create a ZIP file containing all plugin files
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate

## Configuration

### 1. Access Plugin Settings

Go to **WordPress Admin → N8N Workflows** to access the plugin settings.

### 2. Required Settings

#### OpenAI API Key (Recommended)
- Sign up at [OpenAI](https://platform.openai.com/)
- Generate an API key
- Enter it in the plugin settings
- This enables AI-enhanced workflow descriptions

#### GitHub Token (Optional but Recommended)
- Go to [GitHub Settings → Developer settings → Personal access tokens](https://github.com/settings/tokens)
- Generate a new token with `public_repo` scope
- Enter it in the plugin settings
- This increases API rate limits from 60 to 5000 requests/hour

### 3. Plugin Settings

- **Max Workflows**: Number of workflows to scrape (default: 10)
- **Auto-Scrape**: Enable/disable daily automatic scraping
- **OpenAI API Key**: For AI-enhanced descriptions
- **GitHub Token**: For higher API rate limits

## Usage

### Manual Scraping

1. Go to **WordPress Admin → N8N Workflows**
2. Click **"Scrape Workflows Now"** button
3. Wait for the process to complete
4. Check the results and any new workflow posts created

### Display Workflows on Frontend

Use the `[n8n_workflows]` shortcode on any page or post:

```
[n8n_workflows]
```

#### Shortcode Parameters

```
[n8n_workflows limit="12" show_search="true" show_filters="true" columns="3"]
```

- `limit`: Number of workflows to display (default: 12)
- `show_search`: Show search box (default: true)
- `show_filters`: Show sort filters (default: true)
- `columns`: Number of columns in grid (1-4, default: 3)

#### Examples

```
// Display 20 workflows in 2 columns with search
[n8n_workflows limit="20" columns="2"]

// Display 6 workflows without search/filters
[n8n_workflows limit="6" show_search="false" show_filters="false"]

// Single column layout for sidebar
[n8n_workflows limit="5" columns="1"]
```

### Workflow Posts

Each scraped workflow creates a custom post with:

- **Title**: Repository name (AI-enhanced if OpenAI configured)
- **Content**: Description (AI-enhanced if OpenAI configured)
- **Metadata**: GitHub URL, stars, forks, language, author, dates
- **Taxonomy**: Workflow categories based on GitHub topics
- **Permalink**: Individual page for each workflow

## Customization

### Styling

The plugin includes comprehensive CSS in `assets/n8n-workflows.css`. You can:

1. **Override styles** in your theme's CSS:
   ```css
   .n8n-workflow-card {
       /* Your custom styles */
   }
   ```

2. **Disable plugin CSS** and use your own:
   ```php
   // Add to your theme's functions.php
   add_action('wp_enqueue_scripts', function() {
       wp_dequeue_style('n8n-workflows-css');
   }, 20);
   ```

### Custom Templates

Create custom templates in your theme:

- `single-n8n_workflow.php` - Individual workflow page
- `archive-n8n_workflow.php` - Workflow archive page
- `taxonomy-workflow_category.php` - Category archive

### Hooks and Filters

The plugin provides several hooks for customization:

```php
// Modify scraped workflow data before saving
add_filter('n8n_workflow_before_save', function($workflow_data) {
    // Modify $workflow_data
    return $workflow_data;
});

// Run custom code after scraping
add_action('n8n_workflows_scraped', function($count) {
    // $count = number of workflows created
});
```

## Troubleshooting

### Common Issues

1. **No workflows appearing**
   - Check if auto-scraping is enabled
   - Try manual scraping from admin panel
   - Check WordPress error logs

2. **API rate limits**
   - Add a GitHub token to increase limits
   - Reduce max workflows setting
   - Check error logs for API responses

3. **Styling issues**
   - Check for CSS conflicts with your theme
   - Ensure plugin CSS is loading
   - Test with a default WordPress theme

4. **Search not working**
   - Ensure jQuery is loaded on your site
   - Check browser console for JavaScript errors
   - Verify AJAX endpoints are accessible

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for plugin-related errors.

## File Structure

```
n8n-workflows-explorer/
├── n8n-workflows-explorer.php     # Main plugin file
├── n8n-workflows-functions.php     # Core scraping functions
├── n8n-workflows-shortcode.php     # Shortcode implementation
├── assets/
│   ├── n8n-workflows.css          # Frontend styles
│   └── n8n-workflows.js           # Frontend JavaScript
└── README-PLUGIN.md               # This file
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- cURL extension enabled
- OpenAI API key (optional, for AI descriptions)
- GitHub token (optional, for higher rate limits)

## Security

- All user inputs are sanitized and validated
- Nonce verification for AJAX requests
- Capability checks for admin functions
- No sensitive data stored in database
- API keys stored securely in WordPress options

## Performance

- Workflows are cached as WordPress posts
- Daily scraping runs via WordPress cron
- Efficient database queries with proper indexing
- Lazy loading for frontend assets
- Debounced search to reduce server load

## License

This plugin is released under the GPL v2 or later license.

## Support

For support and bug reports:

1. Check the troubleshooting section above
2. Enable debug mode and check error logs
3. Test with default WordPress theme
4. Provide detailed error messages and steps to reproduce

## Changelog

### Version 1.0.0
- Initial release
- GitHub scraping functionality
- OpenAI integration
- Custom post type and taxonomy
- Frontend shortcode with search
- Admin interface
- Daily auto-scraping
- Responsive design

---

**Happy workflow exploring! 🚀**