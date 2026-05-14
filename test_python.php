<?php
/**
 * Test Python Availability - Run this in your browser
 * Visit: yourdomain.com/test_python.php
 * 
 * This will check if Python is available and show you the path
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Python Availability Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h1>🐍 Python Availability Test</h1>
    <p>This page checks if Python is available on your server for the blog automation script.</p>
    
    <?php
    echo "<h2>1. Checking Python Installation</h2>";
    
    $python_paths = [
        '/usr/local/bin/python3',
        '/usr/bin/python3',
        '/opt/cpanel/ea-python38/bin/python3',
        '/opt/cpanel/ea-python39/bin/python3',
        '/opt/cpanel/ea-python310/bin/python3',
        'python3',
        'python'
    ];
    
    $found_python = null;
    $python_version = null;
    
    foreach ($python_paths as $path) {
        $output = [];
        $return_var = 0;
        $command = "which $path 2>&1";
        exec($command, $output, $return_var);
        
        if ($return_var === 0 && !empty($output)) {
            $full_path = trim($output[0]);
            
            // Test if it actually works
            $version_output = [];
            exec("$full_path --version 2>&1", $version_output, $version_return);
            if ($version_return === 0) {
                $found_python = $full_path;
                $python_version = implode(' ', $version_output);
                break;
            }
        }
    }
    
    if ($found_python) {
        echo "<div class='success'>";
        echo "✅ <strong>Python Found!</strong><br>";
        echo "Path: <code>$found_python</code><br>";
        echo "Version: <code>$python_version</code>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "❌ <strong>Python Not Found</strong><br>";
        echo "Python 3 is not accessible via standard paths. You may need to contact your hosting provider.";
        echo "</div>";
    }
    
    echo "<h2>2. Checking Python Packages</h2>";
    
    if ($found_python) {
        $packages = ['requests', 'bs4', 'lxml'];
        $missing_packages = [];
        
        foreach ($packages as $package) {
            $output = [];
            exec("$found_python -c 'import $package' 2>&1", $output, $return_var);
            if ($return_var !== 0) {
                $missing_packages[] = $package;
            }
        }
        
        if (empty($missing_packages)) {
            echo "<div class='success'>";
            echo "✅ <strong>All Required Packages Are Installed!</strong><br>";
            echo "Your server is ready to run the blog automation script.";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>Missing Packages:</strong><br>";
            echo "<ul>";
            foreach ($missing_packages as $pkg) {
                echo "<li>$pkg</li>";
            }
            echo "</ul>";
            echo "<p><strong>Solution:</strong> Contact your hosting provider and ask them to install these Python packages:</p>";
            echo "<pre>pip3 install requests beautifulsoup4 lxml Pillow</pre>";
            echo "</div>";
        }
    } else {
        echo "<div class='info'>";
        echo "⚠️ Cannot check packages - Python not found.";
        echo "</div>";
    }
    
    echo "<h2>3. Testing Blog Automation Script</h2>";
    
    $script_path = __DIR__ . '/blog_automation.py';
    if (file_exists($script_path)) {
        echo "<div class='success'>";
        echo "✅ <strong>blog_automation.py found</strong><br>";
        echo "Path: <code>$script_path</code>";
        echo "</div>";
        
        if ($found_python) {
            echo "<div class='info'>";
            echo "<strong>You can test the script now:</strong><br>";
            echo "<form method='post' style='margin-top: 10px;'>";
            echo "<input type='submit' name='test_script' value='Run Test (Dry Run)' style='padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
            echo "</form>";
            echo "</div>";
            
            if (isset($_POST['test_script'])) {
                echo "<h3>Test Results:</h3>";
                echo "<pre>";
                $output = [];
                exec("cd " . escapeshellarg(__DIR__) . " && $found_python blog_automation.py 2>&1", $output, $return_var);
                echo htmlspecialchars(implode("\n", $output));
                echo "</pre>";
                
                if ($return_var === 0) {
                    echo "<div class='success'>✅ Script executed successfully!</div>";
                } else {
                    echo "<div class='error'>❌ Script encountered errors. Check the output above.</div>";
                }
            }
        }
    } else {
        echo "<div class='error'>";
        echo "❌ <strong>blog_automation.py not found</strong><br>";
        echo "Make sure you've uploaded all files to your public_html directory.";
        echo "</div>";
    }
    
    echo "<h2>4. Cron Job Command</h2>";
    
    if ($found_python) {
        $username = get_current_user();
        $cron_command = "cd /home/$username/public_html && $found_python blog_automation.py >> blog_automation.log 2>&1";
        
        echo "<div class='info'>";
        echo "<strong>Use this command in your cPanel Cron Job:</strong><br>";
        echo "<pre style='background: #fff; border: 2px solid #4CAF50; padding: 15px;'>";
        echo htmlspecialchars($cron_command);
        echo "</pre>";
        echo "<p><strong>Or use PHP wrapper:</strong></p>";
        echo "<pre style='background: #fff; border: 2px solid #4CAF50; padding: 15px;'>";
        echo "php " . __DIR__ . "/run_blog_automation.php >> blog_automation.log 2>&1";
        echo "</pre>";
        echo "</div>";
    } else {
        echo "<div class='info'>";
        echo "<strong>Use PHP wrapper in Cron Job:</strong><br>";
        echo "<pre style='background: #fff; border: 2px solid #4CAF50; padding: 15px;'>";
        $username = get_current_user();
        echo "php /home/$username/public_html/run_blog_automation.php >> /home/$username/public_html/blog_automation.log 2>&1";
        echo "</pre>";
        echo "</div>";
    }
    ?>
    
    <hr>
    <p><small>After testing, you can delete this file (test_python.php) for security.</small></p>
</body>
</html>

