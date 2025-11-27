<?php
/**
 * Utilities
 *
 * Excerpt length, comment IP, cache clearing, TinyMCE config, widgets
 *
 * @package SH_Content_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SH_Utilities {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Custom excerpt length
        add_filter('excerpt_length', array($this, 'custom_excerpt_length'), 999);
        
        // Comment IP address display
        add_action('add_meta_boxes_comment', array($this, 'add_comment_ip_meta_box'));
        
        // Cache clearing on comment actions
        add_action('wp_set_comment_status', array($this, 'clear_post_comment_cache'), 10, 2);
        
        // TinyMCE configuration
        add_filter('tiny_mce_before_init', array($this, 'edit_tinymce_config'));
        
        // Hero banner widgets (legacy support - may be replaced by CPT)
        add_action('widgets_init', array($this, 'register_hero_widgets'));
    }
    
    /**
     * Set custom excerpt length for social sharing
     */
    public function custom_excerpt_length($length) {
        return 100;
    }
    
    /**
     * Display comment user IP address
     */
    public function add_comment_ip_meta_box() {
        add_meta_box(
            'comment_user_IP',
            __('Real User IP', 'sh-content-management'),
            array($this, 'comment_ip_meta_box_callback'),
            'comment',
            'normal',
            'high'
        );
    }
    
    /**
     * Comment IP meta box callback
     */
    public function comment_ip_meta_box_callback($comment) {
        $ip = get_comment_meta($comment->comment_ID, 'comment_user_IP', true);
        if (empty($ip)) {
            $ip = 'Unavailable';
        }
        ?>
        <p>
            <input type="text" name="comment_user_IP" value="<?php echo esc_attr($ip); ?>" class="widefat" readonly />
        </p>
        <?php
    }
    
    /**
     * Clear post cache after comment actions
     */
    public function clear_post_comment_cache($comment_id, $comment_status) {
        try {
            wp_cache_flush();
        } catch (Exception $e) {
            error_log('Exception occurred attempting to clear post cache after comment action: ' . $e->getMessage());
        }
    }
    
    /**
     * Prevent the TinyMCE editor from stripping useful HTML
     */
    public function edit_tinymce_config($init) {
        // Prevent html tags from being stripped
        $opts = '*[*]';
        $init['valid_elements'] = $opts;
        $init['extended_valid_elements'] = $opts;

        // Prevent &nbsp; from being stripped
        $init['entities'] = '160,nbsp,38,amp,60,lt,62,gt';

        return $init;
    }
    
    /**
     * Register the Sweety High hero banner widgets
     * Legacy support - may be replaced by CPT-based solution
     */
    public function register_hero_widgets() {
        register_sidebar(array(
            'name'          => __('Hero Banner Desktop', 'sh-content-management'),
            'id'            => 'hero-banner-desktop',
            'description'   => __('Banner ad area in hero (Desktop).', 'sh-content-management'),
            'before_widget' => '<div id="%1$s" class="hero-banner">',
            'after_widget'  => '</div>',
            'before_title'  => '<h1 class="hero-banner-title">',
            'after_title'   => '</h1>',
        ));
        
        register_sidebar(array(
            'name'          => __('Hero Banner Mobile', 'sh-content-management'),
            'id'            => 'hero-banner-mobile',
            'description'   => __('Banner ad area in hero (mobile).', 'sh-content-management'),
            'before_widget' => '<div id="%1$s" class="hero-banner">',
            'after_widget'  => '</div>',
            'before_title'  => '<h1 class="hero-banner-title">',
            'after_title'   => '</h1>',
        ));
    }
}

