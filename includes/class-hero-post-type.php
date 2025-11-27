<?php
/**
 * Hero Banner Custom Post Type
 *
 * @package SH_Content_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SH_Hero_Post_Type {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
    }
    
    /**
     * Register Hero Banner Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Hero Banners', 'Post type general name', 'sh-content-management'),
            'singular_name'         => _x('Hero Banner', 'Post type singular name', 'sh-content-management'),
            'menu_name'             => _x('Hero Banners', 'Admin Menu text', 'sh-content-management'),
            'name_admin_bar'        => _x('Hero Banner', 'Add New on Toolbar', 'sh-content-management'),
            'add_new'               => __('Add New', 'sh-content-management'),
            'add_new_item'          => __('Add New Hero Banner', 'sh-content-management'),
            'new_item'              => __('New Hero Banner', 'sh-content-management'),
            'edit_item'             => __('Edit Hero Banner', 'sh-content-management'),
            'view_item'             => __('View Hero Banner', 'sh-content-management'),
            'all_items'             => __('All Hero Banners', 'sh-content-management'),
            'search_items'          => __('Search Hero Banners', 'sh-content-management'),
            'not_found'             => __('No hero banners found.', 'sh-content-management'),
            'not_found_in_trash'    => __('No hero banners found in Trash.', 'sh-content-management'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false, // Admin only
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-format-image',
            'supports'           => array('title', 'custom-fields'),
            'show_in_rest'       => true, // Enable REST API for frontend queries
        );

        register_post_type('sh_hero_banner', $args);
    }
}

