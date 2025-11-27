<?php
/**
 * Shortcodes
 *
 * All existing shortcodes plus updated webvideo shortcode
 *
 * @package SH_Content_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SH_Shortcodes {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Register shortcodes on init hook (WordPress best practice)
        add_action('init', array($this, 'register_shortcodes'));
        
        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }
    
    /**
     * Enqueue frontend scripts for video rendering
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_script(
            'sh-video-player',
            SH_CONTENT_MANAGEMENT_PLUGIN_URL . 'public/js/video-player.js',
            array(),
            SH_CONTENT_MANAGEMENT_VERSION,
            true
        );
        
        // Enqueue related posts styles
        wp_enqueue_style(
            'sh-related-posts',
            SH_CONTENT_MANAGEMENT_PLUGIN_URL . 'public/css/related-posts.css',
            array(),
            SH_CONTENT_MANAGEMENT_VERSION
        );
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        // Remove old webvideo shortcode if it exists (from old plugin)
        remove_shortcode('webvideo');
        
        // Register all shortcodes
        add_shortcode('articlesponsor', array($this, 'article_sponsor'));
        add_shortcode('embedjs', array($this, 'embed_js'));
        add_shortcode('facebookembed', array($this, 'facebook_embed'));
        add_shortcode('featuredvideo', array($this, 'featured_video'));
        add_shortcode('pagebreak', array($this, 'page_break'));
        add_shortcode('relatedposts', array($this, 'related_posts'));
        add_shortcode('shvideo', array($this, 'sh_video'));
        add_shortcode('youtubevideo', array($this, 'youtube_video'));
        add_shortcode('webvideo', array($this, 'webvideo')); // Updated to query WordPress
    }
    
    /**
     * Shortcode for embedding an article sponsor into a post
     * Usage: [articlesponsor unit="{unit}"]
     */
    public function article_sponsor($atts) {
        $a = shortcode_atts(array(
            'unit' => ''
        ), $atts);
        
        return '<div class="content-sponsor-1x1" data-aaad="true" data-aa-adunit="/22181265/' . esc_attr($a['unit']) . '"></div>';
    }
    
    /**
     * Shortcode for embedding a custom script into a post
     * Usage: [embedjs name="{scriptName}"]
     */
    public function embed_js($atts) {
        $a = shortcode_atts(array(
            'name' => ''
        ), $atts);
        
        if (empty($a['name'])) {
            return '';
        }
        
        // Use theme directory or plugin directory
        $script_path = '/wp-content/themes/sweetyhigh/scripts/embeds/' . esc_attr($a['name']) . '.js';
        
        return '<script type="text/javascript" src="' . esc_url($script_path) . '"></script>' .
                '<script type="text/javascript"><!--' . "\n" .
                  'try { ' . esc_js($a['name']) . '(); }' . "\n" .
                  'catch(e) { console.log("Unable to execute ' . esc_js($a['name']) . '()", e); }' . "\n" .
                '//--></script>';
    }
    
    /**
     * Shortcode for embedding Facebook content into a post
     * Usage: [facebookembed height="{height}" url="{url}"]
     */
    public function facebook_embed($atts) {
        $a = shortcode_atts(array(
            'height' => '',
            'url' => ''
        ), $atts);
        
        if (empty($a['url'])) {
            return '';
        }
        
        return '<div>' . "\n" .
                '<iframe style="border:none;overflow:hidden;" src="' . esc_url($a['url']) . '" width="100%" height="' . esc_attr($a['height']) . '" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>' . "\n" .
                '</div>';
    }
    
    /**
     * Shortcode for embedding a featured video playlist into a post
     * Usage: [featuredvideo] - works without attributes, fetches featured videos for today
     */
    public function featured_video($atts) {
        $a = shortcode_atts(array(
            'playlist_id' => '',
            'tags' => '',
            'trending' => '',
            'video_id' => ''
        ), $atts);

        // Output the same HTML structure as the old plugin
        $fv = '<span class="web-video-embed" id="blog-featured-video"';

        if (!empty($a['playlist_id'])) {
            $fv .= ' data-playlist-id="' . esc_attr($a['playlist_id']) . '"';
        }

        if (!empty($a['tags'])) {
            $fv .= ' data-tags="' . esc_attr($a['tags']) . '"';
        }

        if (!empty($a['trending'])) {
            $fv .= ' data-trending="' . esc_attr($a['trending']) . '"';
        }

        if (!empty($a['video_id'])) {
            $fv .= ' data-video-id="' . esc_attr($a['video_id']) . '"';
        }

        $fv .= '></span>';

        return $fv;
    }
    
    /**
     * Shortcode for embedding a page break into a post
     * Usage: [pagebreak slug="{slug}"]
     */
    public function page_break($atts) {
        $a = shortcode_atts(array(
            'slug' => ''
        ), $atts);
        
        if (empty($a['slug'])) {
            return '';
        }
        
        return '<div class="page-break-wrapper">' .
                '<div class="page-break-ads"></div>' .
                '<a class="page-break-anchor" name="' . esc_attr($a['slug']) . '"></a>' .
                '</div>';
    }
    
    /**
     * Shortcode for embedding a web video into a post (JWPlayer)
     * Usage: [shvideo video_id="{videoId}" playlist_id="{playlistId}"]
     */
    public function sh_video($atts) {
        $a = shortcode_atts(array(
            'video_id' => '',
            'playlist_id' => ''
        ), $atts);
        
        $html = '<div class="web-video-embed"';
        
        if (!empty($a['video_id'])) {
            $html .= ' data-video-id="' . esc_attr($a['video_id']) . '"';
        }
        
        if (!empty($a['playlist_id'])) {
            $html .= ' data-playlist-id="' . esc_attr($a['playlist_id']) . '"';
        }
        
        $html .= '></div>';
        
        return $html;
    }
    
    /**
     * Shortcode for embedding a YouTube video in a post
     * Usage: [youtubevideo id="{videoId}"]
     */
    public function youtube_video($atts) {
        $a = shortcode_atts(array(
            'autoplay' => false,
            'id' => '',
            'mute' => false
        ), $atts);
        
        if (empty($a['id'])) {
            return '';
        }
        
        $yt_html = '<div class="video-container">';
        $yt_html .= '<div class="youtube-player"';
        $yt_html .= ' id="youtube-video-' . esc_attr($a['id']) . '"';
        $yt_html .= ' data-video-id="' . esc_attr($a['id']) . '"';
        
        if ($a['autoplay']) {
            $yt_html .= ' data-autoplay="true"';
        }
        
        if ($a['mute']) {
            $yt_html .= ' data-mute="true"';
        }
        
        $yt_html .= '></div></div>';
        
        return $yt_html;
    }
    
    /**
     * Shortcode for embedding a web video into a post
     * UPDATED: Now queries WordPress CPTs instead of external API
     * Usage: [webvideo slug="{slug}"]
     */
    public function webvideo($atts) {
        $a = shortcode_atts(array(
            'slug' => ''
        ), $atts);
        
        if (empty($a['slug'])) {
            return '';
        }
        
        // Query WordPress for video by slug
        $video = get_posts(array(
            'post_type' => 'sh_video',
            'name' => $a['slug'],
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        if (empty($video)) {
            return '';
        }
        
        $video_id = $video[0]->ID;
        $video_url = get_post_meta($video_id, '_video_url', true);
        $playlist_m3u8 = get_post_meta($video_id, '_playlist_m3u8', true);
        $jw_media_id = get_post_meta($video_id, '_jw_media_id', true);
        $image_url = get_post_meta($video_id, '_cover_image_url', true);
        
        // Build HTML with data attributes (same format as before for frontend compatibility)
        $html = '<div class="web-video-embed"';
        $html .= ' data-video-id="' . esc_attr($video_id) . '"';
        
        if ($video_url) {
            $html .= ' data-video-url="' . esc_url($video_url) . '"';
        }
        
        if ($playlist_m3u8) {
            $html .= ' data-playlist-m3u8="' . esc_url($playlist_m3u8) . '"';
        }
        
        if ($jw_media_id) {
            $html .= ' data-jw-media-id="' . esc_attr($jw_media_id) . '"';
        }
        
        if ($image_url) {
            $html .= ' data-image-url="' . esc_url($image_url) . '"';
        }
        
        $html .= '></div>';
        
        return $html;
    }
    
    /**
     * Shortcode for displaying related posts
     * 
     * First checks for manually selected posts, then falls back to
     * automatic selection based on categories AND tags.
     * 
     * Usage: [relatedposts limit="5" post_id="123"]
     * 
     * Attributes:
     * - limit: Number of posts to display (default: 5)
     * - post_id: Post ID to get related posts for (default: current post)
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function related_posts($atts) {
        $a = shortcode_atts(array(
            'limit' => 8,
            'post_id' => 0
        ), $atts);
        
        // Get post ID - use provided ID or current post
        $post_id = intval($a['post_id']);
        if (!$post_id) {
            global $post;
            if ($post && $post->ID) {
                $post_id = $post->ID;
            } else {
                return ''; // No post context available
            }
        }
        
        // Get limit - always cap at 8
        $limit = intval($a['limit']);
        if ($limit < 1 || $limit > 8) {
            $limit = 8;
        }
        
        // Get related posts using helper function
        $admin_features = SH_Admin_Features::get_instance();
        $related_posts = $admin_features->get_related_posts($post_id, $limit);
        
        if (empty($related_posts)) {
            return ''; // No related posts found
        }
        
        // Build HTML output
        $output = '<div class="sh-related-posts">';
        $output .= '<ul class="sh-related-posts-list">';
        
        foreach ($related_posts as $related_post) {
            $permalink = get_permalink($related_post->ID);
            $title = get_the_title($related_post->ID);
            $date = get_the_date('', $related_post->ID);
            $thumbnail = '';
            
            if (has_post_thumbnail($related_post->ID)) {
                $thumbnail = '<div class="sh-related-post-thumbnail">' .
                    '<a href="' . esc_url($permalink) . '">' .
                    get_the_post_thumbnail($related_post->ID, 'thumbnail') .
                    '</a></div>';
            }
            
            $output .= '<li class="sh-related-post-item">';
            $output .= $thumbnail;
            $output .= '<div class="sh-related-post-content">';
            $output .= '<h3 class="sh-related-post-title">' .
                '<a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a>' .
                '</h3>';
            $output .= '<p class="sh-related-post-date">' . esc_html($date) . '</p>';
            $output .= '</div>';
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }
}

