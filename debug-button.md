# Debugging the "Fetch New Workflows" Button

The button has been properly implemented in the code. If you can't see it, here are the troubleshooting steps:

## 1. Check Plugin Activation
Make sure the N8N Workflow Importer plugin is activated in your WordPress admin:
- Go to **Plugins** → **Installed Plugins**
- Ensure "N8N Workflow Importer" is **Active**

## 2. Check Admin Page Access
The button appears on the main plugin page:
- Go to **N8N Workflows** in your WordPress admin menu
- The button should be in the "Quick Actions" section

## 3. Check for JavaScript Errors
- Open your browser's Developer Tools (F12)
- Go to the **Console** tab
- Look for any JavaScript errors that might prevent the button from working

## 4. Check CSS Loading
- In Developer Tools, go to the **Network** tab
- Refresh the page
- Look for `n8n-workflow-importer.css` - it should load successfully

## 5. Check WordPress Hooks
The button functionality depends on these WordPress hooks being properly registered:
- `admin_enqueue_scripts` - for loading CSS and JavaScript
- `wp_ajax_fetch_new_workflows` - for handling the AJAX request

## 6. Manual Verification
If the button still doesn't appear, check the HTML source:
- Right-click on the page and select "View Page Source"
- Search for `fetch-new-workflows` - you should find the button HTML

## Button Location in Code
The button is implemented in:
- **File**: `admin/class-admin-page.php`
- **Line**: Around line 109
- **Method**: `admin_page()`
- **JavaScript**: Lines 640-675 (embedded in the same method)

## Expected Button HTML
```html
<button id="fetch-new-workflows" class="button button-secondary" type="button">
    <span class="dashicons dashicons-download"></span>
    Fetch New Workflows
</button>
```

## If Button Appears But Doesn't Work
1. Check browser console for AJAX errors
2. Verify GitHub API token is configured in Settings
3. Check WordPress error logs
4. Ensure proper nonce verification

## Next Steps
If the button still doesn't appear after these checks:
1. Deactivate and reactivate the plugin
2. Clear any caching plugins
3. Check for theme conflicts by switching to a default WordPress theme temporarily