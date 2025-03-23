<?php
if (!defined('ABSPATH')) exit;

class WP_VOD_Post_Type {

  public function __construct() {
    add_action('init', [$this, 'register_post_type']);
  }

  public function register_post_type() {
    $labels = [
      'name'               => __('Videos', 'wp-vod-library'),
      'singular_name'      => __('Video', 'wp-vod-library'),
      'add_new'            => __('Add New', 'wp-vod-library'),
      'add_new_item'       => __('Add New Video', 'wp-vod-library'),
      'edit_item'          => __('Edit Video', 'wp-vod-library'),
      'new_item'           => __('New Video', 'wp-vod-library'),
      'view_item'          => __('View Video', 'wp-vod-library'),
      'search_items'       => __('Search Videos', 'wp-vod-library'),
      'not_found'          => __('No videos found', 'wp-vod-library'),
      'not_found_in_trash' => __('No videos found in Trash', 'wp-vod-library'),
      'menu_name'          => __('VOD Library', 'wp-vod-library'),
    ];

    $args = [ 
      'labels'             => $labels,
      'public'             => true,
      'has_archive'        => true,
      'rewrite'            => ['slug' => 'vod'],
      'show_in_rest'       => true,
      'menu_icon'          => 'dashicons-video-alt3',
      'supports'           => ['title', 'thumbnail', 'custom-fields'],
      'capability_type'    => 'post',
      'publicly_queryable' => true,
    ];

    register_post_type('vod_video', $args);
  }
}
