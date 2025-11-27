<?php
/**
 * Plugin Name: Sweety High Content Management
 * Plugin URI: https://sweetyhigh.com
 * Description: Complete content management system for SweetyHigh. Includes video management, hero banners, shortcodes, and admin features. Do not deactivate or delete.
 * Version: 1.0.0
 * Author: WP Mantis
 * Author URI: https://wpmantis.com
 * License: GPL v2 or later
 * Text Domain: sh-content-management
 * Requires at least: 5.0
 * Requires PHP: 7.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SH_CONTENT_MANAGEMENT_VERSION', '1.0.0');
define('SH_CONTENT_MANAGEMENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SH_CONTENT_MANAGEMENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SH_CONTENT_MANAGEMENT_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class SH_Content_Management {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once SH_CONTENT_MANAGEMENT_PLUGIN_DIR . 'includes/class-video-post-type.php';
        require_once SH_CONTENT_MANAGEMENT_PLUGIN_DIR . 'includes/class-hero-post-type.php';
        require_once SH_CONTENT_MANAGEMENT_PLUGIN_DIR . 'includes/class-video-meta-boxes.php';
        require_once SH_CONTENT_MANAGEMENT_PLUGIN_DIR . 'includes/class-hero-meta-boxes.php';
        require_once SH_CONTENT_MANAGEMENT_PLUGIN_DIR . 'includes/class-hero-logic.php';
        require_once SH_CONTENT_MANAGEMENT_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once SH_CONTENT_MANAGEMENT_PLUGIN_DIR . 'includes/class-admin-features.php';
        require_once SH_CONTENT_MANAGEMENT_PLUGIN_DIR . 'includes/class-utilities.php';
        require_once SH_CONTENT_MANAGEMENT_PLUGIN_DIR . 'includes/class-jwplayer-config.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize classes
        SH_Video_Post_Type::get_instance();
        SH_Hero_Post_Type::get_instance();
        SH_Video_Meta_Boxes::get_instance();
        SH_Hero_Meta_Boxes::get_instance();
        SH_Hero_Logic::get_instance();
        SH_Shortcodes::get_instance();
        SH_Admin_Features::get_instance();
        SH_Utilities::get_instance();
        SH_JWPlayer_Config::get_instance();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Register post types to ensure they exist
        SH_Video_Post_Type::get_instance();
        SH_Hero_Post_Type::get_instance();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize plugin
SH_Content_Management::get_instance();

