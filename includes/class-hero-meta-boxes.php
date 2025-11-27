<?php
/**
 * Hero Banner Meta Boxes
 *
 * @package SH_Content_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SH_Hero_Meta_Boxes {
    
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
            'sh_hero_desktop',
            __('Desktop Hero', 'sh-content-management'),
            array($this, 'desktop_hero_meta_box'),
            'sh_hero_banner',
            'normal',
            'high'
        );
        
        add_meta_box(
            'sh_hero_mobile',
            __('Mobile Hero', 'sh-content-management'),
            array($this, 'mobile_hero_meta_box'),
            'sh_hero_banner',
            'normal',
            'high'
        );
        
        add_meta_box(
            'sh_hero_info',
            __('Hero Information', 'sh-content-management'),
            array($this, 'hero_info_meta_box'),
            'sh_hero_banner',
            'side',
            'default'
        );
    }
    
    /**
     * Desktop hero meta box
     */
    public function desktop_hero_meta_box($post) {
        wp_nonce_field('sh_hero_meta_box', 'sh_hero_meta_box_nonce');
        
        $desktop_image_url = get_post_meta($post->ID, '_desktop_image_url', true);
        $desktop_image_alt = get_post_meta($post->ID, '_desktop_image_alt', true);
        $desktop_link = get_post_meta($post->ID, '_desktop_link', true);
        $desktop_jw_media_id = get_post_meta($post->ID, '_desktop_jw_media_id', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="desktop_image_url"><?php _e('Desktop Image URL', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="url" name="desktop_image_url" id="desktop_image_url" value="<?php echo esc_url($desktop_image_url); ?>" class="regular-text" />
                    <p class="description"><?php _e('S3/CloudFront URL (1024x343 recommended)', 'sh-content-management'); ?></p>
                    <?php if ($desktop_image_url): ?>
                        <p><img src="<?php echo esc_url($desktop_image_url); ?>" style="max-width: 300px; height: auto;" /></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="desktop_image_alt"><?php _e('Desktop Image Alt Text', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="text" name="desktop_image_alt" id="desktop_image_alt" value="<?php echo esc_attr($desktop_image_alt); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="desktop_link"><?php _e('Desktop Link', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="url" name="desktop_link" id="desktop_link" value="<?php echo esc_url($desktop_link); ?>" class="regular-text" />
                    <p class="description"><?php _e('URL when user clicks on hero banner', 'sh-content-management'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="desktop_jw_media_id"><?php _e('Desktop JWPlayer Media ID', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="text" name="desktop_jw_media_id" id="desktop_jw_media_id" value="<?php echo esc_attr($desktop_jw_media_id); ?>" class="regular-text" />
                    <p class="description"><?php _e('Optional: For video heroes', 'sh-content-management'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Mobile hero meta box
     */
    public function mobile_hero_meta_box($post) {
        $mobile_image_url = get_post_meta($post->ID, '_mobile_image_url', true);
        $mobile_image_alt = get_post_meta($post->ID, '_mobile_image_alt', true);
        $mobile_link = get_post_meta($post->ID, '_mobile_link', true);
        $mobile_jw_media_id = get_post_meta($post->ID, '_mobile_jw_media_id', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="mobile_image_url"><?php _e('Mobile Image URL', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="url" name="mobile_image_url" id="mobile_image_url" value="<?php echo esc_url($mobile_image_url); ?>" class="regular-text" />
                    <p class="description"><?php _e('S3/CloudFront URL (768x458 or 768x432 for videos)', 'sh-content-management'); ?></p>
                    <?php if ($mobile_image_url): ?>
                        <p><img src="<?php echo esc_url($mobile_image_url); ?>" style="max-width: 300px; height: auto;" /></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="mobile_image_alt"><?php _e('Mobile Image Alt Text', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="text" name="mobile_image_alt" id="mobile_image_alt" value="<?php echo esc_attr($mobile_image_alt); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="mobile_link"><?php _e('Mobile Link', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="url" name="mobile_link" id="mobile_link" value="<?php echo esc_url($mobile_link); ?>" class="regular-text" />
                    <p class="description"><?php _e('URL when user clicks on hero banner', 'sh-content-management'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="mobile_jw_media_id"><?php _e('Mobile JWPlayer Media ID', 'sh-content-management'); ?></label></th>
                <td>
                    <input type="text" name="mobile_jw_media_id" id="mobile_jw_media_id" value="<?php echo esc_attr($mobile_jw_media_id); ?>" class="regular-text" />
                    <p class="description"><?php _e('Optional: For video heroes', 'sh-content-management'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Hero info meta box
     */
    public function hero_info_meta_box($post) {
        $hero_api_id = get_post_meta($post->ID, '_hero_api_id', true);
        $current_hero = SH_Hero_Logic::get_current_hero();
        $is_current = ($current_hero && $current_hero->ID === $post->ID);
        
        ?>
        <div class="hero-info-box">
            <?php if ($is_current): ?>
                <p style="color: green; font-weight: bold;">✓ <?php _e('Currently Active', 'sh-content-management'); ?></p>
            <?php elseif ($post->post_status === 'publish'): ?>
                <p style="color: orange;"><?php _e('Published but not active (another hero is active)', 'sh-content-management'); ?></p>
            <?php else: ?>
                <p><?php _e('Scheduled or Draft', 'sh-content-management'); ?></p>
            <?php endif; ?>
            
            <?php if ($hero_api_id): ?>
                <p>
                    <strong><?php _e('API ID:', 'sh-content-management'); ?></strong><br>
                    <code><?php echo esc_html($hero_api_id); ?></code>
                </p>
            <?php endif; ?>
            
            <p class="description">
                <?php _e('Only one hero banner can be active at a time. When you publish this hero, all others will be automatically unpublished.', 'sh-content-management'); ?>
            </p>
        </div>
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
        if ($post->post_type !== 'sh_hero_banner') {
            return;
        }
        
        // Check nonce
        if (!isset($_POST['sh_hero_meta_box_nonce']) || !wp_verify_nonce($_POST['sh_hero_meta_box_nonce'], 'sh_hero_meta_box')) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta fields
        $fields = array(
            'desktop_image_url',
            'desktop_image_alt',
            'desktop_link',
            'desktop_jw_media_id',
            'mobile_image_url',
            'mobile_image_alt',
            'mobile_link',
            'mobile_jw_media_id',
            'hero_api_id'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                if (strpos($field, '_url') !== false || strpos($field, '_link') !== false) {
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

