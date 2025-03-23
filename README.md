# WP VOD Library

A modern, lightweight Video-on-Demand plugin for WordPress — featuring HLS + MP4 support, user-based access control, tagging, a gallery view, and player integration using Plyr.js + HLS.js.

---

## 🚀 Features

- ✅ Custom Post Type: `vod_video`
- ✅ Folder-based scanning (MP4 + HLS)
- ✅ Video metadata + thumbnail auto-import
- ✅ Tag-based organization (`vod_tag`)
- ✅ User-based access control
- ✅ Gallery shortcode: `[vod_video_gallery]`
- ✅ Video player shortcode: `[vod_video id="123"]`
- ✅ Frontend playback using [Plyr.js](https://github.com/sampotts/plyr) + [HLS.js](https://github.com/video-dev/hls.js)
- ✅ Dedicated templates: single and archive views
- ✅ Clean admin UI for managing access

---

## 📦 Installation

1. Download or clone the repository:
   ```bash
   git clone https://github.com/CWBudde/wp_vod_library.git
2. Move the folder into your wp-content/plugins/ directory.
3. Activate the plugin from your WordPress admin.
4. Set the base scan path in Settings > VOD Scanner.
5. Run the scanner manually to import videos.

## Folder Structure (expected input)

Each video should reside in its own folder and contain:

```plaintext
/My Video Folder/
├── video.mp4                # Fallback video
├── HLS/master.m3u8          # HLS stream index
├── thumbnail.jpg/png        # Used as featured image
```

Only folders with:

- Exactly one MP4 file (fallback / download)
- A valid HLS/master.m3u8 file

...will be imported.

## Shortcodes

Embed a single video:

```plaintext
[vod_video id="123"]
```

Render user-accessible video gallery:

```plaintext
[vod_video_gallery]
```

## Access Control

You can assign video access to individual users or by video tags (vod_tag). The plugin syncs and stores access relationships via user meta and post meta fields.

## Contributing

Feel free to fork, open PRs, or create issues!

```bash	
# Clone locally
git clone https://github.com/CWBudde/wp_vod_library.git
```

## License

GPL v2 or later
