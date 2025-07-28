<?php
/**
 * N8N Workflows Explorer - Plugin ZIP Creator
 * Creates a distributable ZIP file of the WordPress plugin
 */

class N8NPluginZipCreator {
    
    private $plugin_files = [
        'n8n-workflows-explorer.php',
        'n8n-workflows-functions.php', 
        'n8n-workflows-shortcode.php',
        'install-plugin.php',
        'assets/n8n-workflows.css',
        'assets/n8n-workflows.js',
        'README-PLUGIN.md'
    ];
    
    private $base_dir;
    private $plugin_name = 'n8n-workflows-explorer';
    
    public function __construct($base_dir = null) {
        $this->base_dir = $base_dir ?: __DIR__;
    }
    
    public function create_zip($output_path = null) {
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive class not found. Please install php-zip extension.');
        }
        
        $output_path = $output_path ?: $this->base_dir . '/' . $this->plugin_name . '.zip';
        
        // Remove existing ZIP if it exists
        if (file_exists($output_path)) {
            unlink($output_path);
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($output_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result !== TRUE) {
            throw new Exception("Cannot create ZIP file: {$output_path} (Error code: {$result})");
        }
        
        $added_files = 0;
        $missing_files = [];
        
        foreach ($this->plugin_files as $file) {
            $file_path = $this->base_dir . '/' . $file;
            
            if (file_exists($file_path)) {
                $zip_path = $this->plugin_name . '/' . $file;
                $zip->addFile($file_path, $zip_path);
                $added_files++;
                echo "✅ Added: {$file}\n";
            } else {
                $missing_files[] = $file;
                echo "❌ Missing: {$file}\n";
            }
        }
        
        // Add plugin header comment to the ZIP
        $plugin_info = $this->get_plugin_info();
        $zip->setArchiveComment($plugin_info);
        
        $zip->close();
        
        echo "\n📦 ZIP file created: {$output_path}\n";
        echo "📁 Files added: {$added_files}\n";
        
        if (!empty($missing_files)) {
            echo "⚠️  Missing files: " . implode(', ', $missing_files) . "\n";
            echo "\n⚠️  Warning: Some files are missing. The plugin may not work correctly.\n";
        } else {
            echo "✅ All files included successfully!\n";
        }
        
        echo "\n📋 Installation Instructions:\n";
        echo "1. Upload {$this->plugin_name}.zip to WordPress Admin → Plugins → Add New → Upload Plugin\n";
        echo "2. Activate the plugin\n";
        echo "3. Configure settings in WordPress Admin → N8N Workflows\n";
        echo "4. Add your OpenAI API key and GitHub token\n";
        echo "5. Run your first scrape\n";
        echo "6. Use [n8n_workflows] shortcode to display workflows\n";
        
        return $output_path;
    }
    
    public function validate_files() {
        $validation_results = [];
        
        foreach ($this->plugin_files as $file) {
            $file_path = $this->base_dir . '/' . $file;
            $result = [
                'file' => $file,
                'exists' => file_exists($file_path),
                'size' => file_exists($file_path) ? filesize($file_path) : 0,
                'readable' => file_exists($file_path) ? is_readable($file_path) : false
            ];
            
            // Additional validation for PHP files
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $result['exists']) {
                $content = file_get_contents($file_path);
                $result['has_php_tags'] = strpos($content, '<?php') !== false;
                $result['syntax_valid'] = $this->check_php_syntax($file_path);
            }
            
            $validation_results[] = $result;
        }
        
        return $validation_results;
    }
    
    private function check_php_syntax($file_path) {
        $output = [];
        $return_var = 0;
        exec("php -l {$file_path} 2>&1", $output, $return_var);
        return $return_var === 0;
    }
    
    private function get_plugin_info() {
        return "N8N Workflows Explorer WordPress Plugin\n" .
               "Version: 1.0.0\n" .
               "Description: Automatically scrapes GitHub for N8N workflows and displays them on your WordPress site\n" .
               "Author: N8N Workflows Explorer\n" .
               "Created: " . date('Y-m-d H:i:s') . "\n" .
               "\n" .
               "Installation:\n" .
               "1. Upload to WordPress Admin → Plugins → Add New → Upload Plugin\n" .
               "2. Activate the plugin\n" .
               "3. Configure in WordPress Admin → N8N Workflows\n" .
               "4. Use [n8n_workflows] shortcode to display workflows";
    }
    
    public function display_validation_report() {
        $results = $this->validate_files();
        
        echo "\n📋 Plugin Files Validation Report\n";
        echo str_repeat('=', 50) . "\n";
        
        $all_valid = true;
        
        foreach ($results as $result) {
            $status = $result['exists'] && $result['readable'] ? '✅' : '❌';
            echo "{$status} {$result['file']}";
            
            if ($result['exists']) {
                echo " (" . $this->format_bytes($result['size']) . ")";
                
                if (isset($result['syntax_valid'])) {
                    echo $result['syntax_valid'] ? ' [Syntax OK]' : ' [Syntax Error]';
                    if (!$result['syntax_valid']) {
                        $all_valid = false;
                    }
                }
            } else {
                echo " [Missing]";
                $all_valid = false;
            }
            
            echo "\n";
        }
        
        echo str_repeat('=', 50) . "\n";
        
        if ($all_valid) {
            echo "✅ All files are valid and ready for packaging!\n";
        } else {
            echo "❌ Some files have issues. Please fix them before creating the ZIP.\n";
        }
        
        return $all_valid;
    }
    
    private function format_bytes($size, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

// Command line usage
if (php_sapi_name() === 'cli') {
    echo "N8N Workflows Explorer - Plugin ZIP Creator\n";
    echo str_repeat('=', 50) . "\n";
    
    $creator = new N8NPluginZipCreator();
    
    // Validate files first
    if ($creator->display_validation_report()) {
        echo "\n🚀 Creating plugin ZIP file...\n";
        
        try {
            $zip_path = $creator->create_zip();
            echo "\n🎉 Plugin ZIP created successfully!\n";
            echo "📁 Location: {$zip_path}\n";
            echo "📊 File size: " . (file_exists($zip_path) ? number_format(filesize($zip_path)) . ' bytes' : 'Unknown') . "\n";
        } catch (Exception $e) {
            echo "\n❌ Error creating ZIP: " . $e->getMessage() . "\n";
            exit(1);
        }
    } else {
        echo "\n❌ Cannot create ZIP due to validation errors.\n";
        exit(1);
    }
} else {
    // Web interface
    if (isset($_GET['action'])) {
        header('Content-Type: text/plain');
        
        $creator = new N8NPluginZipCreator();
        
        if ($_GET['action'] === 'validate') {
            $creator->display_validation_report();
        } elseif ($_GET['action'] === 'create') {
            if ($creator->display_validation_report()) {
                echo "\n🚀 Creating plugin ZIP file...\n";
                try {
                    $zip_path = $creator->create_zip();
                    echo "\n🎉 Plugin ZIP created successfully!\n";
                    echo "📁 Location: {$zip_path}\n";
                } catch (Exception $e) {
                    echo "\n❌ Error creating ZIP: " . $e->getMessage() . "\n";
                }
            }
        }
    } else {
        // Simple web interface
        echo "<!DOCTYPE html>";
        echo "<html><head><title>N8N Workflows Explorer - Plugin Creator</title></head>";
        echo "<body style='font-family: Arial, sans-serif; margin: 40px;'>";
        echo "<h1>N8N Workflows Explorer - Plugin ZIP Creator</h1>";
        echo "<p><a href='?action=validate'>Validate Plugin Files</a></p>";
        echo "<p><a href='?action=create'>Create Plugin ZIP</a></p>";
        echo "</body></html>";
    }
}
?>