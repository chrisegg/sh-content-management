/**
 * Video Player Initialization
 * 
 * Handles rendering of YouTube videos and web videos from shortcodes
 * 
 * @package SH_Content_Management
 */

(function() {
    'use strict';

    /**
     * Initialize YouTube Players
     */
    function initYouTubePlayers() {
        var youtubePlayers = document.querySelectorAll('.youtube-player');
        
        if (youtubePlayers.length === 0) {
            return;
        }

        // Check if YouTube iframe API is loaded
        if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
            // Load YouTube iframe API
            var tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            var firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

            // Wait for API to load
            window.onYouTubeIframeAPIReady = function() {
                createYouTubePlayers();
            };
        } else {
            createYouTubePlayers();
        }

        function createYouTubePlayers() {
            youtubePlayers.forEach(function(playerElement) {
                var videoId = playerElement.getAttribute('data-video-id');
                var autoplay = playerElement.getAttribute('data-autoplay') === 'true';
                var mute = playerElement.getAttribute('data-mute') === 'true';

                if (!videoId) {
                    return;
                }

                // Create iframe embed as fallback if API not available
                if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
                    var iframe = document.createElement('iframe');
                    iframe.src = 'https://www.youtube.com/embed/' + videoId + 
                        (autoplay ? '?autoplay=1' : '') + 
                        (mute ? '&mute=1' : '');
                    iframe.width = '100%';
                    iframe.height = '315';
                    iframe.frameBorder = '0';
                    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
                    iframe.allowFullscreen = true;
                    playerElement.appendChild(iframe);
                    return;
                }

                // Use YouTube iframe API
                try {
                    new YT.Player(playerElement, {
                        videoId: videoId,
                        playerVars: {
                            autoplay: autoplay ? 1 : 0,
                            mute: mute ? 1 : 0,
                            rel: 0,
                            modestbranding: 1
                        },
                        events: {
                            onReady: function(event) {
                                // Player is ready
                            },
                            onError: function(event) {
                                console.error('YouTube player error:', event.data);
                            }
                        }
                    });
                } catch (e) {
                    console.error('Error creating YouTube player:', e);
                    // Fallback to iframe
                    var iframe = document.createElement('iframe');
                    iframe.src = 'https://www.youtube.com/embed/' + videoId;
                    iframe.width = '100%';
                    iframe.height = '315';
                    iframe.frameBorder = '0';
                    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
                    iframe.allowFullscreen = true;
                    playerElement.appendChild(iframe);
                }
            });
        }
    }

    /**
     * Initialize Web Video Players (JWPlayer, HTML5, etc.)
     */
    function initWebVideoPlayers() {
        var webVideoEmbeds = document.querySelectorAll('.web-video-embed');
        
        if (webVideoEmbeds.length === 0) {
            return;
        }

        webVideoEmbeds.forEach(function(embedElement) {
            var videoId = embedElement.getAttribute('data-video-id');
            var videoUrl = embedElement.getAttribute('data-video-url');
            var playlistM3u8 = embedElement.getAttribute('data-playlist-m3u8');
            var jwMediaId = embedElement.getAttribute('data-jw-media-id');
            var imageUrl = embedElement.getAttribute('data-image-url');

            // JWPlayer - check if library is loaded
            if (typeof jwplayer === 'undefined') {
                console.warn('JWPlayer library not loaded. Web videos may not render properly.');
                // Fall through to HTML5 fallback
            } else {
                // JWPlayer is available
                if (jwMediaId) {
                    try {
                        jwplayer(embedElement).setup({
                            mediaid: jwMediaId,
                            image: imageUrl || '',
                            width: '100%',
                            aspectratio: '16:9',
                            autostart: false
                        });
                        return;
                    } catch (e) {
                        console.error('Error initializing JWPlayer with media ID:', e);
                    }
                } else if (playlistM3u8 || videoUrl) {
                    try {
                        var playerConfig = {
                            file: playlistM3u8 || videoUrl,
                            image: imageUrl || '',
                            width: '100%',
                            aspectratio: '16:9',
                            autostart: false
                        };
                        
                        if (playlistM3u8) {
                            playerConfig.type = 'hls';
                        }
                        
                        jwplayer(embedElement).setup(playerConfig);
                        return;
                    } catch (e) {
                        console.error('Error initializing JWPlayer:', e);
                    }
                }
            }

            // HTML5 Video Player (fallback)
            if (videoUrl || playlistM3u8) {
                var video = document.createElement('video');
                video.controls = true;
                video.preload = 'metadata';
                video.style.width = '100%';
                video.style.height = 'auto';

                if (imageUrl) {
                    video.poster = imageUrl;
                }

                // Add source elements
                if (playlistM3u8) {
                    var sourceM3u8 = document.createElement('source');
                    sourceM3u8.src = playlistM3u8;
                    sourceM3u8.type = 'application/x-mpegURL';
                    video.appendChild(sourceM3u8);
                }

                if (videoUrl) {
                    var source = document.createElement('source');
                    source.src = videoUrl;
                    source.type = 'video/mp4';
                    video.appendChild(source);
                }

                embedElement.appendChild(video);
            }
        });
    }

    /**
     * Initialize Featured Video Players
     */
    function initFeaturedVideoPlayers() {
        var featuredVideos = document.querySelectorAll('#blog-featured-video, .web-video-embed[data-playlist-id], .web-video-embed[data-tags], .web-video-embed[data-trending]');
        
        if (featuredVideos.length === 0) {
            return;
        }

        featuredVideos.forEach(function(featuredElement) {
            var playlistId = featuredElement.getAttribute('data-playlist-id');
            var tags = featuredElement.getAttribute('data-tags');
            var trending = featuredElement.getAttribute('data-trending');
            var videoId = featuredElement.getAttribute('data-video-id');
            var isBlogFeatured = featuredElement.id === 'blog-featured-video';

            // If this is blog-featured-video with no attributes, fetch featured videos for today
            if (isBlogFeatured && !playlistId && !tags && !trending && !videoId) {
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                var featuredDate = today.getTime();
                
                console.log('Fetching featured videos for date:', featuredDate);
                
                fetch('/wp-json/sh-api/v1/featured-items/locked?featuredDate=' + featuredDate)
                    .then(function(response) {
                        console.log('Featured videos response status:', response.status);
                        if (!response.ok) {
                            throw new Error('HTTP error! status: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function(items) {
                        console.log('Featured videos received:', items);
                        
                        // Filter for type='media' and get the first one (or by position)
                        var featuredVideos = items.filter(function(item) {
                            return item.type === 'media';
                        }).sort(function(a, b) {
                            return (a.position || 0) - (b.position || 0);
                        });
                        
                        console.log('Filtered featured videos:', featuredVideos);
                        
                        if (featuredVideos.length === 0) {
                            console.warn('No featured videos found for today');
                            return;
                        }
                        
                        // Wait for JWPlayer to be available
                        function initJWPlayer() {
                            if (typeof jwplayer === 'undefined') {
                                console.log('JWPlayer not yet loaded, waiting...');
                                setTimeout(initJWPlayer, 100);
                                return;
                            }
                            
                            var featured = featuredVideos[0];
                            var playlistId = featured.value && featured.value.playlistId ? featured.value.playlistId : null;
                            
                            console.log('Initializing JWPlayer with playlist ID:', playlistId);
                            
                            if (playlistId) {
                                try {
                                    // Use JWPlayer playlist
                                    jwplayer(featuredElement).setup({
                                        playlist: playlistId,
                                        width: '100%',
                                        aspectratio: '16:9',
                                        autostart: false
                                    });
                                    console.log('JWPlayer initialized successfully');
                                } catch (e) {
                                    console.error('Error initializing JWPlayer:', e);
                                }
                            } else {
                                console.warn('Featured video has no playlist ID. Featured data:', featured);
                            }
                        }
                        
                        initJWPlayer();
                    })
                    .catch(function(error) {
                        console.error('Error fetching featured videos:', error);
                    });
                return;
            }

            // JWPlayer initialization
            if (typeof jwplayer === 'undefined') {
                console.warn('JWPlayer library not loaded. Featured videos require JWPlayer.');
                return;
            }

            // If we have a playlist ID, use JWPlayer playlist
            if (playlistId) {
                try {
                    jwplayer(featuredElement).setup({
                        playlist: playlistId,
                        width: '100%',
                        aspectratio: '16:9',
                        autostart: false
                    });
                    return;
                } catch (e) {
                    console.error('Error initializing JWPlayer playlist:', e);
                }
            }

            // If we have a video ID, use JWPlayer single video
            if (videoId) {
                try {
                    // Fetch video data from WordPress REST API
                    fetch('/wp-json/wp/v2/sh_video/' + videoId)
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(video) {
                            if (video && video.meta) {
                                var videoUrl = video.meta._video_url || '';
                                var playlistM3u8 = video.meta._playlist_m3u8 || '';
                                var jwMediaId = video.meta._jw_media_id || '';
                                var imageUrl = video.meta._cover_image_url || '';

                                var playerConfig = {
                                    width: '100%',
                                    aspectratio: '16:9',
                                    autostart: false
                                };

                                if (jwMediaId) {
                                    playerConfig.mediaid = jwMediaId;
                                } else if (playlistM3u8) {
                                    playerConfig.file = playlistM3u8;
                                } else if (videoUrl) {
                                    playerConfig.file = videoUrl;
                                }

                                if (imageUrl) {
                                    playerConfig.image = imageUrl;
                                }

                                jwplayer(featuredElement).setup(playerConfig);
                            }
                        })
                        .catch(function(error) {
                            console.error('Error fetching video data:', error);
                        });
                    return;
                } catch (e) {
                    console.error('Error initializing JWPlayer video:', e);
                }
            }

            // Handle tags/trending - fetch videos by tags/trending from WordPress
            if (tags || trending) {
                var apiUrl = '/wp-json/sh-api/v1/videos/featured?';
                if (tags) {
                    apiUrl += 'tags=' + encodeURIComponent(tags);
                }
                if (trending) {
                    apiUrl += (tags ? '&' : '') + 'trending=' + encodeURIComponent(trending);
                }
                apiUrl += '&limit=10';
                
                fetch(apiUrl)
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(videos) {
                        if (videos && videos.length > 0 && typeof jwplayer !== 'undefined') {
                            // Create playlist from videos
                            var playlist = videos.map(function(video) {
                                return {
                                    mediaid: video.meta._jw_media_id || '',
                                    file: video.meta._playlist_m3u8 || video.meta._video_url || '',
                                    image: video.meta._cover_image_url || '',
                                    title: video.title
                                };
                            }).filter(function(item) {
                                return item.mediaid || item.file;
                            });
                            
                            if (playlist.length > 0) {
                                jwplayer(featuredElement).setup({
                                    playlist: playlist,
                                    width: '100%',
                                    aspectratio: '16:9',
                                    autostart: false
                                });
                            }
                        }
                    })
                    .catch(function(error) {
                        console.error('Error fetching featured videos:', error);
                    });
            }
        });
    }

    /**
     * Initialize all video players
     */
    function initAllVideoPlayers() {
        initYouTubePlayers();
        initWebVideoPlayers();
        
        // Wait for JWPlayer to load before initializing featured videos
        if (typeof jwplayer !== 'undefined') {
            initFeaturedVideoPlayers();
        } else {
            // Check if JWPlayer script is loading
            var jwplayerScripts = document.querySelectorAll('script[src*="jwplatform"], script[src*="jwplayer"]');
            if (jwplayerScripts.length > 0) {
                // Wait for JWPlayer to load
                var checkJWPlayer = setInterval(function() {
                    if (typeof jwplayer !== 'undefined') {
                        clearInterval(checkJWPlayer);
                        initFeaturedVideoPlayers();
                    }
                }, 100);
                
                // Timeout after 5 seconds
                setTimeout(function() {
                    clearInterval(checkJWPlayer);
                    if (typeof jwplayer === 'undefined') {
                        console.warn('JWPlayer library failed to load. Featured videos may not work.');
                    }
                }, 5000);
            } else {
                // No JWPlayer script found - initialize anyway (will use HTML5 fallback)
                initFeaturedVideoPlayers();
            }
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllVideoPlayers);
    } else {
        initAllVideoPlayers();
    }

    // Re-initialize for dynamically loaded content (AJAX, etc.)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('sh-videos-loaded', initAllVideoPlayers);
    }
    
    // Also listen for JWPlayer ready event
    if (typeof window !== 'undefined') {
        window.addEventListener('jwplayerReady', function() {
            initFeaturedVideoPlayers();
        });
    }

})();

