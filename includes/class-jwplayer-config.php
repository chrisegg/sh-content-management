<?php
/**
 * JWPlayer Configuration
 *
 * Handles JWPlayer library enqueuing and configuration
 *
 * @package SH_Content_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SH_JWPlayer_Config {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Enqueue JWPlayer library on frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_jwplayer'));
        
        // Add JWPlayer configuration to page
        add_action('wp_head', array($this, 'add_jwplayer_config'));
    }
    
    /**
     * Enqueue JWPlayer library
     */
    public function enqueue_jwplayer() {
        // Get JWPlayer library key from options (if set)
        $jwplayer_key = get_option('sh_jwplayer_key', '8nHCmBK-PztY0TOOWw4JQWInV0hJd1VreEJPR0ZJVGtFMFZUUTJSSGx0TWt0NWFGZHon');
        
        if (empty($jwplayer_key)) {
            // JWPlayer key not configured - videos will use HTML5 fallback
            return;
        }
        
        // JWPlayer 8+ uses different approaches:
        // 1. Library key in URL: https://content.jwplatform.com/libraries/{key}.js
        // 2. License key set via JS: jwplayer.key = 'key' (with standard library)
        
        // Try the key in the URL first (most common method)
        // If this fails, the fallback script will try alternative methods
        $jwplayer_url = 'https://content.jwplatform.com/libraries/' . esc_attr($jwplayer_key) . '.js';
        
        wp_enqueue_script(
            'jwplayer',
            $jwplayer_url,
            array(),
            null,
            true
        );
        
        // Store key for JavaScript configuration (fallback method)
        wp_localize_script('jwplayer', 'shJWPlayerConfig', array(
            'key' => $jwplayer_key
        ));
        
        // Add error handling for JWPlayer load
        add_action('wp_footer', array($this, 'add_jwplayer_load_check'), 999);
    }
    
    /**
     * Add JWPlayer configuration
     * JWPlayer 8+ may need the key set via JavaScript
     */
    public function add_jwplayer_config() {
        $jwplayer_key = get_option('sh_jwplayer_key', '8nHCmBK-PztY0TOOWw4JQWInV0hJd1VreEJPR0ZJVGtFMFZUUTJSSGx0TWt0NWFGZHon');
        
        if (empty($jwplayer_key)) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        // Set JWPlayer key when library loads
        // JWPlayer 8+ may require the key to be set via JavaScript
        (function() {
            function setJWPlayerKey() {
                if (typeof jwplayer !== 'undefined' && typeof jwplayer.key === 'undefined') {
                    try {
                        jwplayer.key = '<?php echo esc_js($jwplayer_key); ?>';
                        console.log('JWPlayer key set via JavaScript');
                    } catch(e) {
                        console.error('Error setting JWPlayer key:', e);
                    }
                }
            }
            
            // Try immediately
            setJWPlayerKey();
            
            // Also try after DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setJWPlayerKey);
            }
            
            // Also try after a delay (in case library loads later)
            setTimeout(setJWPlayerKey, 1000);
        })();
        </script>
        <?php
    }
    
    /**
     * Add JWPlayer load check
     */
    public function add_jwplayer_load_check() {
        $jwplayer_key = get_option('sh_jwplayer_key', '8nHCmBK-PztY0TOOWw4JQWInV0hJd1VreEJPR0ZJVGtFMFZUUTJSSGx0TWt0NWFGZHon');
        ?>
        <script type="text/javascript">
        (function() {
            var jwplayerKey = '<?php echo esc_js($jwplayer_key); ?>';
            var libraryLoaded = false;
            
            // Check if JWPlayer loaded successfully
            var checkInterval = setInterval(function() {
                if (typeof jwplayer !== 'undefined') {
                    clearInterval(checkInterval);
                    libraryLoaded = true;
                    console.log('JWPlayer library loaded successfully');
                    
                    // Set key if not already set
                    if (typeof jwplayer.key === 'undefined' && jwplayerKey) {
                        try {
                            jwplayer.key = jwplayerKey;
                            console.log('JWPlayer key set:', jwplayerKey.substring(0, 10) + '...');
                        } catch(e) {
                            console.error('Error setting JWPlayer key:', e);
                        }
                    }
                    
                    // Trigger custom event for video player initialization
                    if (typeof window !== 'undefined') {
                        window.dispatchEvent(new Event('jwplayerReady'));
                    }
                }
            }, 100);
            
            // Timeout after 5 seconds
            setTimeout(function() {
                clearInterval(checkInterval);
                if (!libraryLoaded) {
                    console.warn('JWPlayer library failed to load from primary URL. Trying alternatives...');
                    
                    // Alternative 1: Try cdn.jwplayer.com
                    var script1 = document.createElement('script');
                    script1.src = 'https://cdn.jwplayer.com/libraries/' + jwplayerKey + '.js';
                    script1.onerror = function() {
                        console.log('Alternative 1 failed: cdn.jwplayer.com');
                        
                        // Alternative 2: Try standard library + set key via JS
                        var script2 = document.createElement('script');
                        // Try to find a standard JWPlayer library URL
                        // Note: This may require a different approach - check JWPlayer docs
                        script2.src = 'https://content.jwplatform.com/libraries/jwplayer.js';
                        script2.onerror = function() {
                            console.error('All JWPlayer loading methods failed.');
                            console.log('Key provided:', jwplayerKey.substring(0, 20) + '...');
                            console.log('This key may be a license key rather than a library key.');
                            console.log('Please verify the key type in your JWPlayer dashboard.');
                        };
                        script2.onload = function() {
                            console.log('JWPlayer standard library loaded, setting key...');
                            if (typeof jwplayer !== 'undefined') {
                                try {
                                    jwplayer.key = jwplayerKey;
                                    console.log('JWPlayer key set successfully');
                                    if (typeof window !== 'undefined') {
                                        window.dispatchEvent(new Event('jwplayerReady'));
                                    }
                                } catch(e) {
                                    console.error('Error setting JWPlayer key:', e);
                                }
                            }
                        };
                        document.head.appendChild(script2);
                    };
                    script1.onload = function() {
                        console.log('JWPlayer loaded from cdn.jwplayer.com');
                        if (typeof window !== 'undefined') {
                            window.dispatchEvent(new Event('jwplayerReady'));
                        }
                    };
                    document.head.appendChild(script1);
                }
            }, 5000);
        })();
        </script>
        <?php
    }
}

