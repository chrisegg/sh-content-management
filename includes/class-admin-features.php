<?php
/**
 * Admin Features
 *
 * Includes modals, buttons, settings, and admin pages
 *
 * @package SH_Content_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SH_Admin_Features {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Prevent admin menu in iframe (must be early)
        add_action('admin_init', array($this, 'prevent_admin_menu_in_iframe'), 1);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add admin pages
        add_action('admin_menu', array($this, 'add_admin_pages'));
        
        // Add Related Posts meta box
        add_action('add_meta_boxes', array($this, 'add_related_posts_meta_box'));
        
        // Add TinyMCE buttons (use admin_footer with late priority to ensure quicktags is loaded)
        add_action('admin_footer', array($this, 'add_tinymce_buttons'), 999);
        
        // Add unpublish button
        add_action('post_submitbox_misc_actions', array($this, 'add_unpublish_button'));
        
        // Handle unpublish AJAX
        add_action('wp_ajax_sh_unpublish_article', array($this, 'handle_unpublish_ajax'));
        add_action('wp_ajax_sh_get_post_titles', array($this, 'handle_get_post_titles_ajax'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Save related posts meta
        add_action('save_post', array($this, 'save_related_posts'), 10, 2);
        
        // Register JWPlayer settings
        add_action('admin_init', array($this, 'register_jwplayer_settings'));
        
        // Disable comments by default for new posts
        add_filter('wp_insert_post_data', array($this, 'disable_comments_by_default'), 10, 2);
        
        // Limit post revisions to 5
        add_filter('wp_revisions_to_keep', array($this, 'limit_post_revisions'), 10, 2);
    }
    
    /**
     * FINAL FIX: Completely kill admin UI in ThickBox — GUARANTEED
     */
    public function prevent_admin_menu_in_iframe() {
        // Must run as early as possible — before any admin template loads
        if (isset($_GET['TB_iframe']) || (isset($_GET['page']) && $_GET['page'] === 'sh-related-posts' && strpos($_SERVER['REQUEST_URI'], 'TB_iframe') !== false)) {
            
            // Define IFRAME_REQUEST early
            if (!defined('IFRAME_REQUEST')) {
                define('IFRAME_REQUEST', true);
            }
            
            // Kill admin bar at the source
            add_filter('show_admin_bar', '__return_false', 1);
            add_filter('wp_admin_bar_class', '__return_false');
            
            // Remove ALL admin header/footer actions
            remove_all_actions('admin_init');
            remove_all_actions('admin_menu');
            remove_all_actions('admin_head');
            remove_all_actions('admin_footer');
            remove_all_actions('in_admin_header');
            remove_all_actions('in_admin_footer');
            
            // Prevent any admin template from loading
            add_action('admin_page_framework', '__return_false');
            add_action('load-admin_page_sh-related-posts', '__return_false');
            
            // Final cleanup when output starts
            add_action('wp_loaded', function() {
                if (did_action('admin_init')) {
                    remove_all_actions('admin_notices');
                    remove_all_actions('all_admin_notices');
                }
            }, 1);
            
            // Body class for CSS
            add_filter('admin_body_class', function($classes) {
                return 'no-admin-bar no-sidebar iframe-mode thickbox-iframe';
            });
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Only on post edit pages
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        $screen = get_current_screen();
        if ($screen->id === 'post') {
            // Enqueue quicktags for QTags support
            wp_enqueue_script('quicktags');
            
            // Enqueue ThickBox as fallback (always available in WordPress)
            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');
            
            // Try to enqueue WordPress React components for modal
            // Note: These may not be available in all WordPress installations
            if (wp_script_is('wp-element', 'registered')) {
                wp_enqueue_script('wp-element');
            }
            if (wp_script_is('wp-components', 'registered')) {
                wp_enqueue_script('wp-components', false, array('wp-element', 'wp-i18n'), false, true);
            }
            if (wp_script_is('wp-i18n', 'registered')) {
                wp_enqueue_script('wp-i18n');
            }
            
            // Add footer script for modals
            add_action('admin_footer', array($this, 'modal_footer_script'));
        }
    }
    
    /**
     * Add admin pages
     */
    public function add_admin_pages() {
        // Related Posts selector (replaces iframe modal)
        add_submenu_page(
            null, // Hidden from menu
            __('Related Posts', 'sh-content-management'),
            __('Related Posts', 'sh-content-management'),
            'edit_posts',
            'sh-related-posts',
            array($this, 'related_posts_admin_page')
        );
        
        // Video Embed selector (replaces iframe modal)
        add_submenu_page(
            null, // Hidden from menu
            __('Embed Video', 'sh-content-management'),
            __('Embed Video', 'sh-content-management'),
            'edit_posts',
            'sh-embed-video',
            array($this, 'embed_video_admin_page')
        );
    }
    
    /**
     * Add Related Posts meta box
     */
    public function add_related_posts_meta_box() {
        add_meta_box(
            'sh-related-posts-metabox',
            __('Related Posts', 'sh-content-management'),
            array($this, 'related_posts_meta_box_callback'),
            'post',
            'side',
            'default'
        );
    }
    
    /**
     * Related Posts meta box callback
     */
    public function related_posts_meta_box_callback($post) {
        $current_post_id = $post->ID;
        $saved_ids = get_post_meta($current_post_id, '_sh_related_post_ids', true);
        ?>
        <input type="hidden" name="sh_related_post_ids" id="sh_related_post_ids" value="<?php echo esc_attr($saved_ids); ?>">
        <div id="sh-selected-posts-list"><p><em>No related posts selected.</em></p></div>
        <p><button type="button" class="button button-primary" id="sh-open-related-modal">Choose Related Articles</button></p>
        <?php
    }
    
    /**
     * Related Posts admin page (replaces iframe modal)
     */
    public function related_posts_admin_page() {
        $post_id = intval($_GET['post_id'] ?? 0);
        if (!$post_id) wp_die('Invalid post ID');



        $candidates = get_posts([

            'post_type'      => 'post',

            'post__not_in'   => [$post_id],

            'posts_per_page' => 60,

            'post_status'    => 'publish',

            'orderby'        => 'date',

            'order'          => 'DESC'

        ]);



        // Check for current_ids parameter first (unsaved changes), then fall back to post meta
        $current_ids = isset($_GET['current_ids']) ? sanitize_text_field($_GET['current_ids']) : '';
        $existing = $current_ids ? $current_ids : get_post_meta($post_id, '_sh_related_post_ids', true);

        $selected = [];
        if ($existing && trim($existing)) {
            $selected = array_map('intval', array_filter(explode(',', $existing), function($id) {
                return !empty(trim($id));
            }));
        }

        ?>

        <!DOCTYPE html>

        <html>

        <head>

            <meta charset="utf-8">

            <title>Select Related Posts</title>

            <?php wp_head(); ?>

            <style>

                body { margin:0; padding:0; background:#f0f2f5; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; width:100%; overflow-x:hidden; }

                #wpadminbar,#adminmenumain,#wpfooter,.notice,#screen-meta,.wrap h1 { display:none !important; }
                
                /* Remove WordPress admin spacing that wastes space */
                #wpcontent { margin-left:0 !important; padding:0 !important; }
                #wpbody-content { margin:0 !important; padding:0 !important; }
                #wpbody { margin:0 !important; }
                
                /* Force full width for ThickBox content */
                html, body { width:100% !important; max-width:100% !important; margin:0 !important; padding:0 !important; }

                .sh-container { width:100%; max-width:none; margin:0; padding:15px; box-sizing:border-box; }

                .sh-header { background:white; padding:15px; text-align:center; border-radius:8px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }

                .sh-header h1 { margin:0 0 6px; font-size:22px; color:#1d2327; }

                .sh-header p { margin:0; color:#646970; font-size:13px; }

                .sh-grid { 

                    display:grid; 

                    grid-template-columns:repeat(5,1fr); 

                    gap:12px; 

                    width:100%;

                }

                @media (max-width:1500px) { .sh-grid { grid-template-columns:repeat(4,1fr); } }

                @media (max-width:1200px) { .sh-grid { grid-template-columns:repeat(3,1fr); } }



                .sh-card { 

                    background:white; border-radius:8px; overflow:hidden; 

                    box-shadow:0 2px 8px rgba(0,0,0,0.08); cursor:pointer; 

                    transition:all 0.25s ease; position:relative; border:2px solid transparent;

                }

                .sh-card:hover { transform:translateY(-3px); box-shadow:0 4px 12px rgba(0,0,0,0.12); }

                .sh-card img { width:100%; height:140px; object-fit:cover; display:block; }

                .sh-card-title { padding:10px; font-weight:600; font-size:12px; line-height:1.3; color:#1d2327; }

                .sh-card.selected { border-color:#FF0099; border-width:3px; }

                .sh-check {

                    position:absolute; top:6px; right:6px; width:42px; height:42px;

                    background:#FF0099; color:white; border-radius:50%;

                    font-size:28px; line-height:42px; text-align:center; font-weight:bold;

                    opacity:0; transition:opacity 0.25s ease;

                    box-shadow:0 3px 12px rgba(255,0,153,0.6);

                }

                .sh-card.selected .sh-check { opacity:1; }

                .sh-footer { text-align:center; padding:30px 0 20px; }

                #sh-done {

                    padding:12px 36px; font-size:17px; height:auto; border-radius:8px;

                    background:#2271b1; border:none; font-weight:600;

                }

                #sh-done:hover { background:#1a5a8c; }

            </style>

        </head>

        <body>

            <div class="sh-container">

                <div class="sh-header">

                    <h1>Select Related Posts</h1>

                    <p>Click to select • Up to 8 posts • Currently selected: <strong id="count">0</strong></p>

                </div>



                <div class="sh-grid">

                    <?php foreach ($candidates as $p): 

                        $is_selected = in_array($p->ID, $selected);

                    ?>

                        <div class="sh-card <?php echo $is_selected ? 'selected' : ''; ?>" data-id="<?php echo $p->ID; ?>">

                            <?php if (has_post_thumbnail($p->ID)): ?>

                                <?php echo get_the_post_thumbnail($p->ID, 'medium', ['style'=>'height:140px;object-fit:cover;']); ?>

                            <?php else: ?>

                                <div style="height:140px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#a0aec0;font-size:32px;">No Image</div>

                            <?php endif; ?>

                            <div class="sh-card-title"><?php echo esc_html(wp_trim_words($p->post_title, 10)); ?></div>

                            <div class="sh-check">✓</div>

                        </div>

                    <?php endforeach; ?>

                </div>



                <div class="sh-footer">

                    <button id="sh-done" class="button button-primary">Done — Save Selection</button>

                </div>

            </div>



            <script>

            document.addEventListener('DOMContentLoaded', () => {

                const cards = document.querySelectorAll('.sh-card');

                const countEl = document.getElementById('count');

                const selected = new Set(<?php echo json_encode($selected); ?>);

                countEl.textContent = selected.size;



                // Mark cards as selected on page load

                cards.forEach(card => {

                    const id = parseInt(card.dataset.id);

                    if (selected.has(id)) {

                        card.classList.add('selected');

                    }

                });



                cards.forEach(card => {

                    card.addEventListener('click', () => {

                        const id = parseInt(card.dataset.id);

                        if (selected.has(id)) {

                            selected.delete(id);

                            card.classList.remove('selected');

                        } else if (selected.size < 8) {

                            selected.add(id);

                            card.classList.add('selected');

                        } else {

                            alert('Maximum 8 related posts allowed');

                            return;

                        }

                        countEl.textContent = selected.size;

                    });

                });



                document.getElementById('sh-done').addEventListener('click', () => {

                    parent.postMessage({

                        type: 'SH_RELATED_POSTS',

                        ids: Array.from(selected)

                    }, '*');

                });



                // Close on ESC key

                document.addEventListener('keydown', (e) => {

                    if (e.key === 'Escape' && typeof parent.tb_remove === 'function') {

                        parent.tb_remove();

                    }

                });

            });

            </script>

        </body>

        </html>

        <?php

        exit;

    }
    
    /**
     * Helper method to get candidate posts for selection
     */
    private function get_candidate_posts($post_id) {
        $posts = get_posts([
            'post_type' => 'post',
            'post__not_in' => [$post_id],
            'posts_per_page' => 50,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        return $posts;
    }
    
    /**
     * Embed Video admin page (replaces iframe modal)
     */
    public function embed_video_admin_page() {
        // Get videos
        $videos = get_posts(array(
            'post_type' => 'sh_video',
            'posts_per_page' => 50,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        ?>
        <div class="wrap sh-embed-video-page">
            <h1><?php _e('Select Video to Embed', 'sh-content-management'); ?></h1>
            
            <div class="sh-video-search">
                <input type="text" id="sh-video-search" placeholder="<?php esc_attr_e('Search videos...', 'sh-content-management'); ?>" class="regular-text" />
            </div>
            
            <div class="sh-videos-list">
                <?php if (empty($videos)): ?>
                    <p><?php _e('No videos found.', 'sh-content-management'); ?></p>
                <?php else: ?>
                    <ul class="sh-videos-grid">
                        <?php foreach ($videos as $video): 
                            $cover_image = get_post_meta($video->ID, '_cover_image_url', true);
                        ?>
                            <li class="sh-video-item" data-video-slug="<?php echo esc_attr($video->post_name); ?>" data-video-id="<?php echo esc_attr($video->ID); ?>">
                                <?php if ($cover_image): ?>
                                    <div class="sh-video-thumbnail">
                                        <img src="<?php echo esc_url($cover_image); ?>" alt="<?php echo esc_attr($video->post_title); ?>" />
                                    </div>
                                <?php endif; ?>
                                <div class="sh-video-info">
                                    <h3><?php echo esc_html($video->post_title); ?></h3>
                                    <p class="sh-video-slug"><?php echo esc_html($video->post_name); ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <div class="sh-actions">
                <button type="button" class="button" id="sh-cancel-video"><?php _e('Cancel', 'sh-content-management'); ?></button>
            </div>
        </div>
        
        <style>
            .sh-embed-video-page { padding: 20px; }
            .sh-video-search { margin-bottom: 20px; }
            .sh-videos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; list-style: none; padding: 0; }
            .sh-video-item { border: 1px solid #ddd; padding: 10px; cursor: pointer; }
            .sh-video-item:hover { border-color: #0073aa; }
            .sh-video-thumbnail { margin-bottom: 10px; }
            .sh-video-thumbnail img { width: 100%; height: auto; }
            .sh-video-info h3 { margin: 0 0 5px 0; font-size: 14px; }
            .sh-video-slug { font-size: 12px; color: #666; margin: 0; }
            .sh-actions { margin-top: 20px; }
        </style>
        
        <script>
        (function() {
            var searchInput = document.getElementById('sh-video-search');
            var videoItems = document.querySelectorAll('.sh-video-item');
            
            // Search functionality
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    var searchTerm = this.value.toLowerCase();
                    videoItems.forEach(function(item) {
                        var title = item.querySelector('.sh-video-info h3').textContent.toLowerCase();
                        var slug = item.querySelector('.sh-video-slug').textContent.toLowerCase();
                        if (title.indexOf(searchTerm) !== -1 || slug.indexOf(searchTerm) !== -1) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
            
            // Handle video selection
            videoItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    var videoSlug = this.getAttribute('data-video-slug');
                    var videoId = this.getAttribute('data-video-id');
                    var videoTitle = this.querySelector('.sh-video-info h3').textContent;
                    var coverImage = this.querySelector('.sh-video-thumbnail img');
                    var imageUrl = coverImage ? coverImage.src : '';
                    
                    if (window.parent && window.parent.postMessage) {
                        window.parent.postMessage({
                            type: 'VIDEO',
                            body: {
                                slug: videoSlug,
                                id: videoId,
                                title: videoTitle,
                                image: {
                                    wide: {
                                        url: imageUrl
                                    }
                                }
                            }
                        }, '*');
                    }
                    window.close();
                });
            });
            
            // Cancel button
            document.getElementById('sh-cancel-video').addEventListener('click', function() {
                window.close();
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Modal footer script - ThickBox version (proven reliable)
     */
    public function modal_footer_script() {
        global $post;

        $current_post_id = ($post && $post->ID) ? $post->ID : 0;
        if (!$current_post_id && isset($_GET['post'])) {
            $current_post_id = intval($_GET['post']);
        }

        ?>
        <style type="text/css">
        /* Force ThickBox to proper width for 5 columns */
        #TB_window {
            width: 1400px !important;
            max-width: 90vw !important;
            margin-left: -700px !important;
            left: 50% !important;
        }
        #TB_iframeContent, #TB_ajaxContent {
            width: 100% !important;
            max-width: 100% !important;
        }
        #TB_window iframe {
            width: 100% !important;
            max-width: 100% !important;
        }
        </style>
        <script type="text/javascript">
        jQuery(document).ready(function($) {

            // Meta box is now registered via WordPress add_meta_box, so we don't need to create it manually



            // Open modal with CORRECT ThickBox syntax

            $(document).on('click', '#sh-open-related-modal', function() {

                var postId = $('#post_ID').val() || <?php echo $current_post_id; ?>;

                if (!postId) {

                    alert('Please save the post first.');

                    return;

                }

                // Get current selection from hidden input (may include unsaved changes)

                var currentIds = $('#sh_related_post_ids').val() || '';

                var url = '<?php echo admin_url("admin.php?page=sh-related-posts&post_id="); ?>' + postId;

                if (currentIds) {

                    url += '&current_ids=' + encodeURIComponent(currentIds);

                }

                tb_show('Select Related Posts', url + '#TB_iframe=true&width=1400&height=900');
                
                // Force ThickBox to proper width for 5 columns
                setTimeout(function() {
                    $('#TB_window').css({
                        'width': '1400px !important',
                        'max-width': '90vw !important',
                        'margin-left': '-700px !important'
                    });
                    $('#TB_iframeContent, #TB_ajaxContent').css({
                        'width': '100% !important',
                        'max-width': '100% !important'
                    });
                }, 100);

            });



            // Receive selection from iframe

            window.addEventListener('message', function(e) {

                if (e.origin !== window.location.origin) return;

                if (e.data && e.data.type === 'SH_RELATED_POSTS') {

                    var ids = e.data.ids || [];

                    $('#sh_related_post_ids').val(ids.join(','));

                    updateSelectedList(ids);

                    tb_remove();

                }

            });



            function updateSelectedList(ids) {

                var $list = $('#sh-selected-posts-list');

                if (ids.length === 0) {

                    $list.html('<p><em>No related posts selected.</em></p>');

                    return;

                }

                $list.html('<p><strong>Loading...</strong></p>');

                $.post(ajaxurl, {

                    action: 'sh_get_post_titles',

                    post_ids: ids

                }, function(res) {

                    if (res.success && res.data.length) {

                        var html = '<ul style="margin:10px 0;">';

                        res.data.forEach(function(p) {

                            html += '<li>' + p.title + ' <a href="#" class="sh-remove" data-id="' + p.id + '">(remove)</a></li>';

                        });

                        html += '</ul>';

                        $list.html('<p><strong>Selected Posts (' + res.data.length + '):</strong></p>' + html);

                    }

                });

            }



            // Remove individual post

            $(document).on('click', '.sh-remove', function(e) {

                e.preventDefault();

                var id = $(this).data('id');

                var ids = $('#sh_related_post_ids').val().split(',').map(Number).filter(n => n && n != id);

                $('#sh_related_post_ids').val(ids.join(','));

                updateSelectedList(ids);

            });



            // Load existing on init

            var existing = $('#sh_related_post_ids').val();

            if (existing) {

                updateSelectedList(existing.split(',').map(Number).filter(Boolean));

            }

        });

        </script>

        <?php

    }
    
    /**
     * Add TinyMCE buttons
     */
    public function add_tinymce_buttons() {
        // Only on post edit pages
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'post') {
            return;
        }
        
        // Prevent multiple script outputs
        static $script_output = false;
        if ($script_output) {
            return;
        }
        $script_output = true;
        
        // Ensure quicktags is enqueued
        wp_enqueue_script('quicktags');
        ?>
        <script type="text/javascript">
        (function() {
            // Prevent multiple initializations
            if (window.shQTagsInitialized) {
                return;
            }
            window.shQTagsInitialized = true;
            
            // Wait for QTags to be available - check multiple times
            var attempts = 0;
            var maxAttempts = 50; // Try for up to 5 seconds
            
            function initQTags() {
                attempts++;
                
                if (typeof QTags !== 'undefined' && QTags.buttons && !window.shQTagsButtonsAdded) {
                    // QTags is ready, add buttons (only once)
                    window.shQTagsButtonsAdded = true;
                    
                    QTags.addButton('article_sponsor', 'Article Sponsor', function() {
                        if (typeof addEditorText !== 'undefined') {
                            addEditorText('[articlesponsor unit=""]');
                        }
                    });

                    QTags.addButton('facebook_embed', 'Facebook', function() {
                        if (typeof addEditorText !== 'undefined') {
                            addEditorText('[facebookembed height="" url=""]');
                        }
                    });

                    QTags.addButton('feauted_video', 'Featured Video', function() {
                        if (typeof addEditorText !== 'undefined') {
                            addEditorText('[featuredvideo]');
                        }
                    });

                    QTags.addButton('page-break', 'Page Break', function() {
                        if (typeof shOpenPageBreakModal !== 'undefined') {
                            shOpenPageBreakModal();
                        }
                    });

                    QTags.addButton('sh_video', 'JWPlayer', function() {
                        if (typeof addEditorText !== 'undefined') {
                            addEditorText('[shvideo video_id="" playlist_id=""]');
                        }
                    });

                    QTags.addButton('youtube_embed', 'YouTube', function() {
                        if (typeof addEditorText !== 'undefined') {
                            addEditorText('[youtubevideo id=""]');
                        }
                    });
                } else if (attempts < maxAttempts && !window.shQTagsButtonsAdded) {
                    // Retry after a short delay if QTags isn't ready yet
                    setTimeout(initQTags, 100);
                }
            }
            
            // Start initialization
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initQTags);
            } else {
                // DOM already ready, start immediately
                initQTags();
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Add Unpublish button to Edit Post page
     */
    public function add_unpublish_button($post) {
        // User must be logged in
        if (!is_user_logged_in()) {
            return;
        }

        // Show only for Administrators
        $user = wp_get_current_user();
        if (empty($user) || empty($user->roles) || !in_array('administrator', (array) $user->roles)) {
            return;
        }

        // Show only for published posts
        if (empty($post) || empty($post->ID) || $post->post_status !== 'publish' || $post->post_type !== 'post') {
            return;
        }
        ?>
        <div id="major-publishing-actions" style="overflow:hidden">
            <div style="color:#d98500;float:left;line-height:28px;text-align:left;vertical-align:middle;"><span>Admin Only:</span></div>
            <div id="publishing-action">
                <span class="spinner" id="submitbox-unpublish-spinner" style="opacity:0;"></span>
                <input type="button" value="Unpublish" class="button-primary" id="submitbox-unpublish-button" style="text-align:right;float:right;line-height:23px;background:#d98500;border-color:#c07500 #c07500 #c07500;box-shadow:0 1px 0 #c07500;text-shadow:0 -1px 1px #c07500, 1px 0 1px #c07500, 0 1px 1px #c07500, -1px 0 1px #c07500;">
            </div>
        </div>
        <script>
        (function() {
            var unpublishBtn = document.getElementById('submitbox-unpublish-button');
            var unpublishSpinner = document.getElementById('submitbox-unpublish-spinner');
            
            if (unpublishBtn) {
                unpublishBtn.onclick = function(e) {
                    e.preventDefault();
                    unpublishBtn.disabled = true;

                    if (unpublishSpinner) {
                        unpublishSpinner.style.visibility = 'visible';
                        unpublishSpinner.style.opacity = '.7';
                    }

                    var data = {
                        action: 'sh_unpublish_article',
                        post_id: <?php echo $post->ID; ?>,
                        nonce: '<?php echo wp_create_nonce('sh_unpublish_article_nonce'); ?>'
                    };

                    jQuery.post(ajaxurl, data, function(response) {
                        if (unpublishSpinner) {
                            unpublishSpinner.style.opacity = '0';
                            unpublishSpinner.style.visibility = 'hidden';
                        }
                        unpublishBtn.disabled = false;
                        location.reload();
                    });
                };
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Handle unpublish AJAX
     */
    public function handle_unpublish_ajax() {
        check_ajax_referer('sh_unpublish_article_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'sh-content-management')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'sh-content-management')));
        }
        
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'draft'
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Post unpublished successfully.', 'sh-content-management')));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('general', 'sweetyhigh_options');
        
        add_settings_field(
            'google_tag_manager_code',
            __('Google Tag Manager Code', 'sh-content-management'),
            array($this, 'google_tag_manager_code_input'),
            'general',
            'default'
        );
        
        add_settings_field(
            'mixpanel_api_code',
            __('Mixpanel API Code', 'sh-content-management'),
            array($this, 'mixpanel_api_code_input'),
            'general',
            'default'
        );
        
        add_settings_field(
            'facebook_api_code',
            __('Facebook API Code', 'sh-content-management'),
            array($this, 'facebook_api_code_input'),
            'general',
            'default'
        );
    }
    
    /**
     * Google Tag Manager code input
     */
    public function google_tag_manager_code_input() {
        $options = get_option('sweetyhigh_options');
        $value = isset($options['google_tag_manager_code']) ? $options['google_tag_manager_code'] : '';
        echo '<input id="google_tag_manager_code" name="sweetyhigh_options[google_tag_manager_code]" type="text" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * Mixpanel API code input
     */
    public function mixpanel_api_code_input() {
        $options = get_option('sweetyhigh_options');
        $value = isset($options['mixpanel_api_code']) ? $options['mixpanel_api_code'] : '';
        echo '<input id="mixpanel_api_code" name="sweetyhigh_options[mixpanel_api_code]" type="text" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * Facebook API code input
     */
    public function facebook_api_code_input() {
        $options = get_option('sweetyhigh_options');
        $value = isset($options['facebook_api_code']) ? $options['facebook_api_code'] : '';
        echo '<input id="facebook_api_code" name="sweetyhigh_options[facebook_api_code]" type="text" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * Get related posts for a post
     * 
     * First checks for manually selected posts, then falls back to
     * automatic selection based on categories AND tags.
     * 
     * @param int $post_id Post ID
     * @param int $limit Number of posts to return (default: 5)
     * @return array Array of WP_Post objects
     */
    public function get_related_posts($post_id, $limit = 8) {
        if (!$post_id) {
            return array();
        }
        
        // First, check for manually selected related posts
        $selected_ids = get_post_meta($post_id, '_sh_related_post_ids', true);
        
        if (!empty($selected_ids)) {
            // Parse comma-separated IDs
            $ids = array_map('intval', explode(',', $selected_ids));
            $ids = array_filter($ids); // Remove empty values
            
            if (!empty($ids)) {
                // Limit to 8 posts maximum
                $ids = array_slice($ids, 0, 8);
                
                $related_posts = get_posts(array(
                    'post_type' => 'post',
                    'post__in' => $ids,
                    'posts_per_page' => 8,
                    'post_status' => 'publish',
                    'orderby' => 'post__in' // Maintain manual selection order
                ));
                
                // If we got results, return them
                if (!empty($related_posts)) {
                    return $related_posts;
                }
            }
        }
        
        // Fallback: Get related posts by categories AND tags
        $tags = wp_get_post_tags($post_id, array('fields' => 'ids'));
        $categories = wp_get_post_categories($post_id);
        
        // Need at least one tag or category to find related posts
        if (empty($tags) && empty($categories)) {
            return array();
        }
        
        $args = array(
            'post_type' => 'post',
            'post__not_in' => array($post_id),
            'posts_per_page' => 8, // Always limit to 8
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Add tag and category filters (AND logic - both must match)
        if (!empty($tags)) {
            $args['tag__in'] = $tags;
        }
        if (!empty($categories)) {
            $args['category__in'] = $categories;
        }
        
        $related_posts = get_posts($args);
        
        return $related_posts;
    }
    
    /**
     * Save related posts meta
     */
    public function save_related_posts($post_id, $post) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if ($post->post_type !== 'post') {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Verify nonce for security (WordPress post save nonce)
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-post_' . $post_id)) {
            return;
        }
        
        // Save related post IDs (from our custom field or ACF)
        if (isset($_POST['sh_related_post_ids'])) {
            $related_ids = sanitize_text_field($_POST['sh_related_post_ids']);
            update_post_meta($post_id, '_sh_related_post_ids', $related_ids);
        }
        
        // Also save to ACF field if it exists
        if (isset($_POST['acf']['field_related_post_id_list'])) {
            // ACF will handle this automatically
        }
    }
    
    /**
     * AJAX handler to get post titles for selected post IDs
     */
    public function handle_get_post_titles_ajax() {
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Get post IDs from request
        $post_ids = isset($_POST['post_ids']) ? $_POST['post_ids'] : array();
        
        if (empty($post_ids) || !is_array($post_ids)) {
            wp_send_json_error(array('message' => 'No post IDs provided'));
            return;
        }
        
        // Sanitize post IDs
        $post_ids = array_map('intval', $post_ids);
        $post_ids = array_filter($post_ids);
        
        if (empty($post_ids)) {
            wp_send_json_error(array('message' => 'Invalid post IDs'));
            return;
        }
        
        // Get posts
        $posts = get_posts(array(
            'post_type' => 'post',
            'post__in' => $post_ids,
            'posts_per_page' => -1,
            'orderby' => 'post__in'
        ));
        
        // Format response
        $result = array();
        foreach ($posts as $post) {
            $result[] = array(
                'id' => $post->ID,
                'title' => esc_html($post->post_title),
                'edit_link' => get_edit_post_link($post->ID, 'raw')
            );
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Register JWPlayer settings
     */
    public function register_jwplayer_settings() {
        register_setting('general', 'sh_jwplayer_key');
        
        add_settings_field(
            'sh_jwplayer_key',
            __('JWPlayer Library Key', 'sh-content-management'),
            array($this, 'jwplayer_key_input'),
            'general',
            'default'
        );
    }
    
    /**
     * JWPlayer key input
     */
    public function jwplayer_key_input() {
        $value = get_option('sh_jwplayer_key', '8nHCmBK-PztY0TOOWw4JQWInV0hJd1VreEJPR0ZJVGtFMFZUUTJSSGx0TWt0NWFGZHon');
        echo '<input id="sh_jwplayer_key" name="sh_jwplayer_key" type="text" value="' . esc_attr($value) . '" class="regular-text" style="width: 100%; max-width: 600px;" />';
        echo '<p class="description">' . __('Enter your JWPlayer library key. Leave empty to use HTML5 fallback.', 'sh-content-management') . '</p>';
    }
    
    /**
     * Disable comments by default for new posts
     * 
     * Sets comment_status to 'closed' for new posts, but keeps meta boxes visible
     * so users can enable comments per-post if needed.
     * 
     * @param array $data An array of slashed post data.
     * @param array $postarr An array of sanitized, but otherwise unmodified post data.
     * @return array Modified post data.
     */
    public function disable_comments_by_default($data, $postarr) {
        // Only apply to new posts (not updates) and only for posts/pages
        if (!empty($postarr['ID'])) {
            return $data;
        }
        
        // Only apply to 'post' and 'page' post types
        if (!in_array($data['post_type'], array('post', 'page'))) {
            return $data;
        }
        
        // Set comment status to closed if not explicitly set
        if (!isset($postarr['comment_status']) || empty($postarr['comment_status'])) {
            $data['comment_status'] = 'closed';
        }
        
        return $data;
    }
    
    /**
     * Limit post revisions to 5 per post/page
     * 
     * WordPress will automatically delete older revisions when new ones are created
     * to maintain this limit. This is a database optimization feature.
     * 
     * @param int     $num  Number of revisions to store.
     * @param WP_Post $post Post object.
     * @return int Number of revisions to keep (5).
     */
    public function limit_post_revisions($num, $post) {
        // Limit to 5 revisions for all post types
        return 5;
    }
}

