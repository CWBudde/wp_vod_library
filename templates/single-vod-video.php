<?php
get_header();

global $post;

$post_id = $post->ID;
$user_id = get_current_user_id();

if (!WP_VOD_Access::user_can_access_video($user_id, $post_id)) {
  echo '<main id="main" class="site-main"><p>' . __('You do not have access to this video.', 'wp-vod-library') . '</p></main>';
  get_footer();
  exit;
}

$mp4 = get_post_meta($post_id, 'vod_mp4_file', true);
$hls = get_post_meta($post_id, 'vod_hls_master', true);
$symlink_url = get_post_meta($post_id, 'vod_symlink_url', true);
$proxy_url = plugin_dir_url(__DIR__) . '/../vod-proxy.php';

$mp4_url = $mp4 ? esc_url($proxy_url . '?file=' . urlencode($mp4) . '&post=' . $post_id) : '';
$hls_url = $hls ? esc_url(trailingslashit($symlink_url) . 'HLS/' . basename($hls)) : '';
?>

<main id="main" class="site-main">
  <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
      <?php the_title('<h1 class="wp-block-post-title entry-title">', '</h1>'); ?>
    </header>

    <div class="entry-content wp-block-post-content">
      <div class="wp-vod-player-wrap" style="margin-bottom: 2rem;">
        <video class="wp-vod-player" controls playsinline>
          <?php if ($hls_url): ?>
            <source src="<?php echo $hls_url; ?>" type="application/x-mpegURL">
          <?php endif; ?>
          <?php if ($mp4_url): ?>
            <source src="<?php echo $mp4_url; ?>" type="video/mp4">
          <?php endif; ?>
        </video>
      </div>

      <?php if (has_excerpt()) : ?>
        <div class="wp-vod-excerpt"><?php the_excerpt(); ?></div>
      <?php endif; ?>

      <?php the_content(); ?>
    </div>

    <footer class="entry-footer">
      <?php
      the_terms($post_id, 'vod_tag', '<div class="vod-tags">' . __('Tags:', 'wp-vod-library') . ' ', ', ', '</div>');
      ?>
    </footer>
  </article>
</main>

<?php get_footer(); ?>