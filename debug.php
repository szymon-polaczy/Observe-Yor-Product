<?php
/**
 * Debug file to check plugin activation status
 * 
 * Add this to your WordPress installation to debug the plugin
 */

// Check if WordPress is loaded
if (!defined('ABSPATH')) {
    die('WordPress not loaded');
}

echo "<h2>Plugin Debug Information</h2>";

// Check if plugin file exists
$plugin_file = WP_PLUGIN_DIR . '/observe-yor-product/observe-yor-product.php';
echo "<p><strong>Plugin file exists:</strong> " . (file_exists($plugin_file) ? 'YES' : 'NO') . "</p>";

// Check if WooCommerce is active
echo "<p><strong>WooCommerce active:</strong> " . (class_exists('WooCommerce') ? 'YES' : 'NO') . "</p>";

// Check PHP version
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>PHP 8.1+ compatible:</strong> " . (version_compare(PHP_VERSION, '8.1', '>=') ? 'YES' : 'NO') . "</p>";

// Check WordPress version
echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
echo "<p><strong>WordPress 6.3+ compatible:</strong> " . (version_compare(get_bloginfo('version'), '6.3', '>=') ? 'YES' : 'NO') . "</p>";

// Check if plugin is in active plugins list
$active_plugins = get_option('active_plugins', array());
$plugin_basename = 'observe-yor-product/observe-yor-product.php';
echo "<p><strong>Plugin in active list:</strong> " . (in_array($plugin_basename, $active_plugins) ? 'YES' : 'NO') . "</p>";

// Check if our main class exists
echo "<p><strong>Main class loaded:</strong> " . (class_exists('Observe_Yor_Product') ? 'YES' : 'NO') . "</p>";

// Check current user capabilities
$user = wp_get_current_user();
echo "<p><strong>Current user roles:</strong> " . implode(', ', $user->roles) . "</p>";
echo "<p><strong>Can manage_3d_models:</strong> " . (current_user_can('manage_3d_models') ? 'YES' : 'NO') . "</p>";

// List all active plugins
echo "<h3>Active Plugins:</h3>";
foreach($active_plugins as $plugin) {
    echo "<p>- $plugin</p>";
}

// Check for any plugin errors
$plugin_errors = get_option('plugin_activation_errors', array());
if (!empty($plugin_errors)) {
    echo "<h3>Plugin Activation Errors:</h3>";
    foreach($plugin_errors as $plugin => $error) {
        echo "<p><strong>$plugin:</strong> $error</p>";
    }
}
?>