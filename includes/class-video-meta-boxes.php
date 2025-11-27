<?php
/**
 * Video Meta Boxes
 *
 * @package SH_Content_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SH_Video_Meta_Boxes {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta'), 10, 2);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'sh_video_urls',
            __('Video URLs (S3 + CloudFront)', 'sh-content-management'),
            array($this, 'video_urls_meta_box'),
            'sh_video',
            'normal',
            'high'
        );
        
        add_meta_box(
            'sh_video_metadata',
            __('Video Metadata', 'sh-content-management'),
            array($this, 'video_metadata_meta_box'),
            'sh_video',
            'normal',
            'default'
        );
    }
    
    /**
     * Video URLs meta box
     */
    public function video_urls_meta_box($post) {
        wp_nonce_field('sh_video_meta_box', 'sh_video_meta_box_nonce');
        
        $video_url = get_post_meta($post->ID, '_video_url', true);
        $playlist_m3u8 = get_post_meta($post->ID, '_playlist_m3u8', true);
        $jw_media_id = get_post_meta($post->ID, '_jw_media_id', true);
        $cover_image_url = get_post_meta($post->ID, '_cover_image_url', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="video_url"><?php _e('Video URL (S3/CloudFront)', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="url" name="video_url" id="video_url" value="<?php echo esc_url($video_url); ?>" class="regular-text" />
                    <p class="description"><?php _e('Direct URL to video file (.mp4, .mov)', 'sh-content-management'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="playlist_m3u8"><?php _e('Playlist M3U8', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="url" name="playlist_m3u8" id="playlist_m3u8" value="<?php echo esc_url($playlist_m3u8); ?>" class="regular-text" />
                    <p class="description"><?php _e('HLS playlist URL for transcoded video', 'sh-content-management'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="jw_media_id"><?php _e('JWPlayer Media ID', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="text" name="jw_media_id" id="jw_media_id" value="<?php echo esc_attr($jw_media_id); ?>" class="regular-text" />
                    <p class="description"><?php _e('Optional JWPlayer Media ID', 'sh-content-management'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cover_image_url"><?php _e('Cover Image URL', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="url" name="cover_image_url" id="cover_image_url" value="<?php echo esc_url($cover_image_url); ?>" class="regular-text" />
                    <p class="description"><?php _e('S3/CloudFront URL to cover image (1920x1080 recommended)', 'sh-content-management'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Video metadata meta box
     */
    public function video_metadata_meta_box($post) {
        $cassandra_id = get_post_meta($post->ID, '_cassandra_id', true);
        $position = get_post_meta($post->ID, '_position', true);
        $show_category_slug = get_post_meta($post->ID, '_show_category_slug', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="cassandra_id"><?php _e('Cassandra ID', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="text" name="cassandra_id" id="cassandra_id" value="<?php echo esc_attr($cassandra_id); ?>" class="regular-text" readonly />
                    <p class="description"><?php _e('Original Cassandra UUID (for reference)', 'sh-content-management'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="position"><?php _e('Position', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="number" name="position" id="position" value="<?php echo esc_attr($position); ?>" class="small-text" />
                    <p class="description"><?php _e('Display order within category', 'sh-content-management'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="show_category_slug"><?php _e('Show Category Slug', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="text" name="show_category_slug" id="show_category_slug" value="<?php echo esc_attr($show_category_slug); ?>" class="regular-text" />
                    <p class="description"><?php _e('Show category slug for Shows Page', 'sh-content-management'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Video Slug', 'sh-content-management'); ?></label></th>
                <td>
                    <code><?php echo esc_html($post->post_name); ?></code>
                    <p class="description"><?php _e('Used in shortcodes: [webvideo slug="' . esc_html($post->post_name) . '"]', 'sh-content-management'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save meta data
     */
    public function save_meta($post_id, $post) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if ($post->post_type !== 'sh_video') {
            return;
        }
        
        // Check nonce
        if (!isset($_POST['sh_video_meta_box_nonce']) || !wp_verify_nonce($_POST['sh_video_meta_box_nonce'], 'sh_video_meta_box')) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta fields
        $fields = array(
            'video_url',
            'playlist_m3u8',
            'jw_media_id',
            'cover_image_url',
            'cassandra_id',
            'position',
            'show_category_slug'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'video_url' || $field === 'playlist_m3u8' || $field === 'cover_image_url') {
                    update_post_meta($post_id, '_' . $field, esc_url_raw($_POST[$field]));
                } else {
                    update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
                }
            } else {
                delete_post_meta($post_id, '_' . $field);
            }
        }
    }
}

