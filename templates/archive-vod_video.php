<?php
get_header();

$user_id = get_current_user_id();
$accessible_ids = WP_VOD_Access::get_user_accessible_videos($user_id);

// Build custom query with filter + search
$args = [
  'post_type' => 'vod_video',
  'post_status' => 'publish',
  'posts_per_page' => 12,
  'post__in' => $accessible_ids,
  'paged' => get_query_var('paged') ?: 1,
];

if (!empty($_GET['s'])) {
  $args['s'] = sanitize_text_field($_GET['s']);
}

if (!empty($_GET['tag'])) {
  $args['tax_query'] = [[
    'taxonomy' => 'vod_tag',
    'field'    => 'slug',
    'terms'    => sanitize_text_field($_GET['tag']),
  ]];
}

$query = new WP_Query($args);
?>

<div class="vod-gallery-wrapper">

  <form method="get" class="vod-filter-form">
    <input type="text" name="s" placeholder="<?php _e('Search videos...', 'wp-vod-library'); ?>" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>" />
    <select name="tag">
      <option value=""><?php _e('All Tags', 'wp-vod-library'); ?></option>
      <?php foreach (get_terms(['taxonomy' => 'vod_tag', 'hide_empty' => false]) as $term): ?>
        <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($_GET['tag'] ?? '', $term->slug); ?>>
          <?php echo esc_html($term->name); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit"><?php _e('Filter', 'wp-vod-library'); ?></button>
  </form>

  <div class="vod-gallery">
    <?php if ($query->have_posts()): ?>
      <?php while ($query->have_posts()): $query->the_post(); ?>
        <div class="vod-gallery-item">
          <a href="<?php the_permalink(); ?>">
            <?php if (has_post_thumbnail()): ?>
              <?php the_post_thumbnail('medium'); ?>
            <?php else: ?>
              <img src="<?php echo plugin_dir_url(__FILE__) . '../assets/img/default-thumb.jpg'; ?>" />
            <?php endif; ?>
            <div class="vod-gallery-title"><?php the_title(); ?></div>
          </a>
        </div>
      <?php endwhile; ?>
      <?php wp_reset_postdata(); ?>
    <?php else: ?>
      <p><?php _e('No videos found.', 'wp-vod-library'); ?></p>
    <?php endif; ?>
  </div>

  <div class="vod-pagination">
    <?php
    echo paginate_links([
      'total' => $query->max_num_pages
    ]);
    ?>
  </div>

</div>

<?php get_footer(); ?>