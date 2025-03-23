<?php
if (!defined('ABSPATH')) exit;

class WP_VOD_Templates {
  public function __construct() {
    add_filter('template_include', [$this, 'maybe_override_template']);
  }

  public function maybe_override_template($template) {
    if (is_singular('vod_video')) {
      $custom = WP_VOD_PATH . 'templates/single-vod-video.php';
      if (file_exists($custom)) {
        return $custom;
      }
    }

    if (is_post_type_archive('vod_video')) {
      $custom = WP_VOD_PATH . 'templates/archive-vod_video.php';
      if (file_exists($custom)) {
        return $custom;
      }
    }

    return $template;
  }
}
