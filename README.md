# Sweety High Content Management Plugin

Complete content management system for SweetyHigh. Migrated from headless WordPress to full WordPress.

## Features

### Custom Post Types
- **Videos** (`sh_video`) - Manage web videos with S3/CloudFront URLs
- **Hero Banners** (`sh_hero_banner`) - Scheduled hero banners with single-active logic

### Shortcodes
- `[webvideo slug="..."]` - Embed videos (updated to query WordPress CPTs)
- `[articlesponsor unit="..."]` - Article sponsor ads
- `[embedjs name="..."]` - Embed custom scripts
- `[facebookembed height="..." url="..."]` - Facebook embeds
- `[featuredvideo]` - Featured video playlists
- `[pagebreak slug="..."]` - Page breaks
- `[shvideo video_id="..." playlist_id="..."]` - JWPlayer videos
- `[sponsoredpost post_id="..."]` - Display sponsored post information from ACF fields
- `[youtubevideo id="..."]` - YouTube videos

### Admin Features
- Related Posts selector (WordPress admin page)
- Video Embed selector (WordPress admin page)
- Page Break modal
- Unpublish button (admin only)
- TinyMCE custom buttons
- Analytics settings (Google Tag Manager, Mixpanel, Facebook)

### Utilities
- Custom excerpt length (100 characters)
- Comment IP address display
- Cache clearing on comment actions
- TinyMCE configuration
- Hero banner widgets (legacy support)

## Installation

1. Upload the `sh-content-management` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically register Custom Post Types and taxonomies

## Usage

### Videos

1. Go to **Videos** → **Add New**
2. Enter video title and description
3. Set video slug (used in shortcodes)
4. Add S3/CloudFront URLs:
   - Video URL
   - Playlist M3U8 (for HLS)
   - Cover Image URL
   - Optional JWPlayer Media ID
5. Assign categories and tags
6. Publish

**Shortcode:** `[webvideo slug="your-video-slug"]`

### Hero Banners

1. Go to **Hero Banners** → **Add New**
2. Enter title (for admin reference)
3. Set publish date/time (WordPress scheduling)
4. Add desktop hero:
   - Desktop Image URL (1024x343)
   - Desktop Image Alt Text
   - Desktop Link
   - Optional Desktop JWPlayer Media ID
5. Add mobile hero:
   - Mobile Image URL (768x458 or 768x432)
   - Mobile Image Alt Text
   - Mobile Link
   - Optional Mobile JWPlayer Media ID
6. Publish

**Note:** Only one hero banner can be active at a time. When you publish a new hero, all others are automatically unpublished.

**Frontend:** Use `SH_Hero_Logic::get_current_hero()` or query WordPress REST API:
```
GET /wp-json/wp/v2/sh_hero_banner?status=publish&per_page=1&orderby=date&order=desc
```

### Related Posts

1. Edit a post
2. Find the "Related Posts" field (ACF field)
3. Click "Choose Related Articles" button
4. Select posts from the modal
5. Click "Done"

### Embed Video

1. Edit a post
2. Click "JWPlayer" or "Featured Video" button in TinyMCE
3. Or manually add: `[webvideo slug="video-slug"]`

### Sponsored Posts

1. Edit a post
2. Configure ACF fields for sponsored content:
   - **Is this article being sponsored?** (radio): Set to "Yes"
   - **Sponsor Name** (text): Name of the sponsor
   - **Sponsor Byline** (text): Optional sponsor description/byline
   - **Sponsor Link** (url): Link to sponsor website
   - **Sponsor Logo** (image): Sponsor logo image
   - **Sponsor Logo CSS** (text, optional): Custom CSS for logo styling
3. Add shortcode to post content: `[sponsoredpost]`
   - Or specify a post ID: `[sponsoredpost post_id="123"]`

**Note:** The shortcode only displays if the post is marked as sponsored. The sponsored box will appear in a styled, prominent box at the top of the post content.

## Migration

This plugin replaces:
- Cassandra database (video metadata)
- External Node.js API (`api-admin-integration`)
- Elasticsearch (search)

All data should be migrated using the separate migration plugin (`sh-content-migration-tool`).

## Requirements

- WordPress 5.0+
- PHP 7.0+
- GeneratePress theme (for frontend display)

## File Structure

```
sh-content-management/
├── sh-content-management.php (main plugin file)
├── README.md
├── includes/
│   ├── class-video-post-type.php
│   ├── class-hero-post-type.php
│   ├── class-video-meta-boxes.php
│   ├── class-hero-meta-boxes.php
│   ├── class-hero-logic.php
│   ├── class-shortcodes.php
│   ├── class-admin-features.php
│   └── class-utilities.php
├── admin/
│   ├── css/
│   │   └── modal.css
│   └── js/
│       └── modal.js
└── public/
    └── (frontend assets if needed)
```

## Developer Notes

### Getting Current Hero Banner

```php
$current_hero = SH_Hero_Logic::get_current_hero();
if ($current_hero) {
    $desktop_image = get_post_meta($current_hero->ID, '_desktop_image_url', true);
    $mobile_image = get_post_meta($current_hero->ID, '_mobile_image_url', true);
}
```

### Video Meta Fields

- `_video_url` - S3/CloudFront video URL
- `_playlist_m3u8` - HLS playlist URL
- `_jw_media_id` - JWPlayer Media ID
- `_cover_image_url` - Cover image URL
- `_cassandra_id` - Original Cassandra UUID (reference)
- `_position` - Display order
- `_show_category_slug` - Show category slug

### Hero Meta Fields

- `_desktop_image_url` - Desktop image URL
- `_desktop_image_alt` - Desktop alt text
- `_desktop_link` - Desktop click-through URL
- `_desktop_jw_media_id` - Desktop JWPlayer Media ID
- `_mobile_image_url` - Mobile image URL
- `_mobile_image_alt` - Mobile alt text
- `_mobile_link` - Mobile click-through URL
- `_mobile_jw_media_id` - Mobile JWPlayer Media ID
- `_hero_api_id` - Original API ID (reference)

## Changelog

### 1.0.0
- Initial release
- Migrated from headless WordPress setup
- Consolidated all shortcodes and admin features
- Added Video and Hero Banner Custom Post Types
- Implemented single-active hero logic

## License

GPL v2 or later

