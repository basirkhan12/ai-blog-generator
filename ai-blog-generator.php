<?php
/**
 * Plugin Name: AI Blog Generator Pro
 * Plugin URI: https://unclewebsite.com/ai-blog-generator
 * Description: Automatically generate SEO-optimized blog posts with AI-powered content and featured images
 * Version: 1.0.0
 * Author: Basir uddin
 * Author URI: https://unclewebsite.com
 * License: GPL v2 or later
 * Text Domain: ai-blog-generator
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AIBG_VERSION', '1.0.0');
define('AIBG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIBG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AIBG_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'AIBG\\';
    $base_dir = AIBG_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Main plugin class
class AI_Blog_Generator {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init']);
    }
    
    private function load_dependencies() {
        require_once AIBG_PLUGIN_DIR . "lib/parsedown.php";
        require_once AIBG_PLUGIN_DIR . 'includes/Admin/Settings.php';
        require_once AIBG_PLUGIN_DIR . 'includes/Admin/Dashboard.php';
        require_once AIBG_PLUGIN_DIR . 'includes/Admin/PostManager.php';
        require_once AIBG_PLUGIN_DIR . 'includes/Core/Generator.php';
        require_once AIBG_PLUGIN_DIR . 'includes/Core/Scheduler.php';
        require_once AIBG_PLUGIN_DIR . 'includes/API/DeepseekAPI.php';
        require_once AIBG_PLUGIN_DIR . 'includes/API/UnsplashAPI.php';
        require_once AIBG_PLUGIN_DIR . 'includes/Utils/SEO.php';
        require_once AIBG_PLUGIN_DIR . 'includes/Utils/Analytics.php';
        
    }
    
    public function init() {
        new AIBG\Admin\Settings();
        new AIBG\Admin\Dashboard();
        new AIBG\Admin\PostManager();
        new AIBG\Core\Scheduler();
        
        // Register REST API routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    public function register_rest_routes() {
        register_rest_route('aibg/v1', '/generate', [
            'methods' => 'POST',
            'callback' => [new AIBG\Core\Generator(), 'generate_post_endpoint'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        register_rest_route('aibg/v1', '/posts', [
            'methods' => 'GET',
            'callback' => [new AIBG\Admin\PostManager(), 'get_posts_endpoint'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        register_rest_route('aibg/v1', '/analytics', [
            'methods' => 'GET',
            'callback' => [new AIBG\Utils\Analytics(), 'get_analytics_endpoint'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'aibg_posts';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            topic varchar(255) NOT NULL,
            status varchar(20) NOT NULL,
            featured_image_url text,
            generation_date datetime DEFAULT CURRENT_TIMESTAMP,
            publish_date datetime,
            metadata longtext,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Set default options
        $defaults = [
            'aibg_deepseek_api_key' => '',
            'aibg_unsplash_api_key' => '',
            'aibg_auto_publish' => 'draft',
            'aibg_post_frequency' => 'daily',
            'aibg_seo_enabled' => '1',
            'aibg_internal_links' => '3',
            'aibg_external_links' => '2',
            'aibg_post_length' => 'medium',
            'aibg_categories' => '',
            'aibg_image_orientation' => 'landscape',
            'aibg_image_resolution' => 'regular'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!get_option($key)) {
                add_option($key, $value);
            }
        }
        
        // Schedule cron job
        if (!wp_next_scheduled('aibg_generate_post')) {
            wp_schedule_event(time(), 'daily', 'aibg_generate_post');
        }
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('aibg_generate_post');
        flush_rewrite_rules();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('ai-blog-generator', false, dirname(AIBG_PLUGIN_BASENAME) . '/languages');
    }
}

// Initialize plugin
function aibg_init() {
    return AI_Blog_Generator::get_instance();
}

aibg_init();