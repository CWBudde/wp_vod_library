<?php
if (!defined('ABSPATH')) exit;

class WP_VOD_Frontend {

  public function __construct() {
    add_shortcode('vod_video', [$this, 'render_video_shortcode']);
    add_shortcode('vod_video_gallery', [$this, 'render_gallery_shortcode']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
  }

  public function enqueue_assets() {
    $plugin_url = plugin_dir_url(__DIR__);
    wp_enqueue_style('plyr');
    wp_enqueue_style('vod-frontend', $plugin_url . 'assets/css/vod-frontend.css');
    wp_enqueue_script('plyr');
    wp_enqueue_script('hls');
    wp_enqueue_script('vod-player');
  }

  public function render_gallery_shortcode($atts) {
    $user_id = get_current_user_id();
    $video_ids = WP_VOD_Access::get_user_accessible_videos($user_id);

    if (empty($video_ids)) {
      return '<p>' . __('No videos available.', 'wp-vod-library') . '</p>';
    }

    ob_start();
    echo '<div class="vod-gallery">';
    foreach ($video_ids as $video_id) {
      $title = get_the_title($video_id);
      $thumb = get_the_post_thumbnail_url($video_id, 'medium');
      $thumb = $thumb ?: plugin_dir_url(__FILE__) . '../assets/img/default-thumb.jpg';

      echo '<div class="vod-gallery-item">';
      echo '<div class="vod-gallery-thumb">';
      echo '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($title) . '" />';
      echo '</div>';
      echo '<div class="vod-gallery-title">' . esc_html($title) . '</div>';
      echo '<a class="vod-gallery-link" href="' . esc_url(get_permalink($video_id)) . '">' . __('Watch now', 'wp-vod-library') . '</a>';
      echo '</div>';
    }
    echo '</div>';
    return ob_get_clean();
  }

  public function render_video_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts, 'vod_video');
    $post_id = intval($atts['id']);

    if (!$post_id || get_post_type($post_id) !== 'vod_video') {
      return '<p>' . __('Invalid video.', 'wp-vod-library') . '</p>';
    }

    if (!WP_VOD_Access::user_can_access_video(get_current_user_id(), $post_id)) {
      return '<p>' . __('You do not have access to this video.', 'wp-vod-library') . '</p>';
    }

    $mp4 = get_post_meta($post_id, 'vod_mp4_file', true);
    $hls = get_post_meta($post_id, 'vod_hls_master', true);

    if (!$mp4 && !$hls) {
      return '<p>' . __('Video source not available.', 'wp-vod-library') . '</p>';
    }

    // Build the full URL
    $proxy_url = plugin_dir_url(dirname(__FILE__)) . 'vod-proxy.php';
    $symlink_url = get_post_meta($post_id, 'vod_symlink_url', true);

    $mp4_url = $mp4 ? esc_url($proxy_url . '?file=' . urlencode($mp4) . '&post=' . $post_id) : '';
    $hls_url = $hls ? esc_url(trailingslashit($symlink_url) . 'HLS/' . basename($hls)) : '';

    ob_start();
?>
    <div class="wp-vod-wrapper">
      <video class="wp-vod-player" controls playsinline>
        <?php if ($hls_url): ?>
          <source src="<?php echo $hls_url; ?>" type="application/x-mpegURL">
        <?php endif; ?>
        <?php if ($mp4_url): ?>
          <source src="<?php echo $mp4_url; ?>" type="video/mp4">
        <?php endif; ?>
      </video>
    </div>
<?php
    return ob_get_clean();
  }
}
