<?php
/**
 * Video Custom Post Type
 *
 * @package SH_Content_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SH_Video_Post_Type {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        
        // Expose meta fields in REST API
        add_action('rest_api_init', array($this, 'register_rest_fields'));
    }
    
    /**
     * Register REST API fields for videos
     */
    public function register_rest_fields() {
        register_rest_field('sh_video', 'meta', array(
            'get_callback' => array($this, 'get_video_meta'),
            'schema' => null,
        ));
        
        // Register custom endpoint for featured videos
        register_rest_route('sh-api/v1', '/videos/featured', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_featured_videos'),
            'permission_callback' => '__return_true',
        ));
        
        // Register endpoint for featured items (for blog-featured-video)
        register_rest_route('sh-api/v1', '/featured-items/locked', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_featured_items'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Get featured items for today (mimics old API endpoint)
     */
    public function get_featured_items($request) {
        $featured_date = isset($request['featuredDate']) ? intval($request['featuredDate']) : time() * 1000;
        $date = new DateTime();
        $date->setTimestamp($featured_date / 1000);
        $date->setTime(0, 0, 0);
        
        // Query featured videos from admin_media_featured equivalent
        // For now, we'll query sh_video posts with featured meta
        $args = array(
            'post_type' => 'sh_video',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_featured_date',
                    'value' => $date->format('Y-m-d'),
                    'compare' => '='
                )
            ),
            'orderby' => 'meta_value_num',
            'meta_key' => '_featured_position',
            'order' => 'ASC'
        );
        
        $videos = get_posts($args);
        $items = array();
        
        foreach ($videos as $video) {
            $featured_start = get_post_meta($video->ID, '_featured_start_date', true);
            $featured_end = get_post_meta($video->ID, '_featured_end_date', true);
            
            // Check if video is active for this date
            if ($featured_start && $featured_end) {
                $start_ts = strtotime($featured_start);
                $end_ts = strtotime($featured_end);
                $current_ts = $date->getTimestamp();
                
                if ($current_ts < $start_ts || $current_ts > $end_ts) {
                    continue;
                }
            }
            
            $playlist_id = get_post_meta($video->ID, '_playlist_id', true);
            
            $items[] = array(
                'featuredDate' => $featured_date,
                'id' => $video->ID,
                'position' => intval(get_post_meta($video->ID, '_featured_position', true)) ?: 0,
                'type' => 'media',
                'value' => array(
                    'id' => strval($video->ID),
                    'playlistId' => $playlist_id ?: '',
                    'title' => $video->post_title,
                    'description' => $video->post_content,
                    'type' => 'video',
                    'image' => array(
                        'banner' => array(
                            'url' => get_post_meta($video->ID, '_cover_image_url', true) ?: '',
                            'width' => 640,
                            'height' => 290
                        )
                    )
                ),
                'startDate' => $featured_start ? strtotime($featured_start) * 1000 : $featured_date,
                'endDate' => $featured_end ? strtotime($featured_end) * 1000 : $featured_date
            );
        }
        
        return $items;
    }
    
    /**
     * Get featured videos by tags/trending
     */
    public function get_featured_videos($request) {
        $tags = isset($request['tags']) ? sanitize_text_field($request['tags']) : '';
        $trending = isset($request['trending']) ? sanitize_text_field($request['trending']) : '';
        $limit = isset($request['limit']) ? intval($request['limit']) : 10;
        
        $args = array(
            'post_type' => 'sh_video',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Filter by tags if provided
        if (!empty($tags)) {
            $tag_array = explode(',', $tags);
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'video_tag',
                    'field' => 'slug',
                    'terms' => $tag_array
                )
            );
        }
        
        // TODO: Implement trending logic (might need custom meta field or calculation)
        if (!empty($trending)) {
            // For now, just return recent videos
            // Trending could be based on view count, engagement, etc.
        }
        
        $videos = get_posts($args);
        $result = array();
        
        foreach ($videos as $video) {
            $video_id = $video->ID;
            $result[] = array(
                'id' => $video_id,
                'title' => $video->post_title,
                'slug' => $video->post_name,
                'meta' => $this->get_video_meta(array('id' => $video_id))
            );
        }
        
        return $result;
    }
    
    /**
     * Get video meta for REST API
     */
    public function get_video_meta($object) {
        $post_id = $object['id'];
        
        return array(
            '_video_url' => get_post_meta($post_id, '_video_url', true),
            '_playlist_m3u8' => get_post_meta($post_id, '_playlist_m3u8', true),
            '_jw_media_id' => get_post_meta($post_id, '_jw_media_id', true),
            '_cover_image_url' => get_post_meta($post_id, '_cover_image_url', true),
            '_position' => get_post_meta($post_id, '_position', true),
            '_show_category_slug' => get_post_meta($post_id, '_show_category_slug', true),
        );
    }
    
    /**
     * Register Video Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Videos', 'Post type general name', 'sh-content-management'),
            'singular_name'         => _x('Video', 'Post type singular name', 'sh-content-management'),
            'menu_name'             => _x('Videos', 'Admin Menu text', 'sh-content-management'),
            'name_admin_bar'        => _x('Video', 'Add New on Toolbar', 'sh-content-management'),
            'add_new'               => __('Add New', 'sh-content-management'),
            'add_new_item'          => __('Add New Video', 'sh-content-management'),
            'new_item'              => __('New Video', 'sh-content-management'),
            'edit_item'             => __('Edit Video', 'sh-content-management'),
            'view_item'             => __('View Video', 'sh-content-management'),
            'all_items'             => __('All Videos', 'sh-content-management'),
            'search_items'          => __('Search Videos', 'sh-content-management'),
            'not_found'             => __('No videos found.', 'sh-content-management'),
            'not_found_in_trash'    => __('No videos found in Trash.', 'sh-content-management'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'videos'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-video-alt3',
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'show_in_rest'       => true,
        );

        register_post_type('sh_video', $args);
    }
    
    /**
     * Register Video Taxonomies
     */
    public function register_taxonomies() {
        // Video Categories (hierarchical)
        register_taxonomy('video_category', 'sh_video', array(
            'hierarchical' => true,
            'labels' => array(
                'name' => _x('Video Categories', 'taxonomy general name', 'sh-content-management'),
                'singular_name' => _x('Video Category', 'taxonomy singular name', 'sh-content-management'),
                'menu_name' => __('Categories', 'sh-content-management'),
                'all_items' => __('All Categories', 'sh-content-management'),
                'edit_item' => __('Edit Category', 'sh-content-management'),
                'view_item' => __('View Category', 'sh-content-management'),
                'update_item' => __('Update Category', 'sh-content-management'),
                'add_new_item' => __('Add New Category', 'sh-content-management'),
                'new_item_name' => __('New Category Name', 'sh-content-management'),
                'search_items' => __('Search Categories', 'sh-content-management'),
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'video-category'),
            'show_in_rest' => true,
        ));

        // Video Tags (non-hierarchical)
        register_taxonomy('video_tag', 'sh_video', array(
            'hierarchical' => false,
            'labels' => array(
                'name' => _x('Video Tags', 'taxonomy general name', 'sh-content-management'),
                'singular_name' => _x('Video Tag', 'taxonomy singular name', 'sh-content-management'),
                'menu_name' => __('Tags', 'sh-content-management'),
                'all_items' => __('All Tags', 'sh-content-management'),
                'edit_item' => __('Edit Tag', 'sh-content-management'),
                'view_item' => __('View Tag', 'sh-content-management'),
                'update_item' => __('Update Tag', 'sh-content-management'),
                'add_new_item' => __('Add New Tag', 'sh-content-management'),
                'new_item_name' => __('New Tag Name', 'sh-content-management'),
                'search_items' => __('Search Tags', 'sh-content-management'),
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'video-tag'),
            'show_in_rest' => true,
        ));
    }
}

