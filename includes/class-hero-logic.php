<?php
/**
 * Hero Banner Single-Active Logic
 *
 * Ensures only one hero banner is published at a time
 *
 * @package SH_Content_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SH_Hero_Logic {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('transition_post_status', array($this, 'unpublish_other_heroes'), 10, 3);
    }
    
    /**
     * Unpublish other hero banners when a new one is published
     *
     * @param string $new_status New post status
     * @param string $old_status Old post status
     * @param WP_Post $post Post object
     */
    public function unpublish_other_heroes($new_status, $old_status, $post) {
        // Only process hero banners
        if ($post->post_type !== 'sh_hero_banner') {
            return;
        }
        
        // Only when transitioning to publish
        if ($new_status !== 'publish') {
            return;
        }
        
        // Skip if already published (avoid infinite loop)
        if ($old_status === 'publish') {
            return;
        }
        
        // Get all other published hero banners
        $other_heroes = get_posts(array(
            'post_type' => 'sh_hero_banner',
            'post_status' => 'publish',
            'post__not_in' => array($post->ID),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        // Unpublish all other heroes
        if (!empty($other_heroes)) {
            foreach ($other_heroes as $hero_id) {
                wp_update_post(array(
                    'ID' => $hero_id,
                    'post_status' => 'draft'
                ));
            }
        }
    }
    
    /**
     * Get current active hero banner
     *
     * @return WP_Post|null
     */
    public static function get_current_hero() {
        $heroes = get_posts(array(
            'post_type' => 'sh_hero_banner',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        return !empty($heroes) ? $heroes[0] : null;
    }
}

