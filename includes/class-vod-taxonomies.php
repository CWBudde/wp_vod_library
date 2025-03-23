<?php
if (!defined('ABSPATH')) exit;

class WP_VOD_Taxonomies {

  public function __construct() {
    add_action('init', [$this, 'register_taxonomies']);
  }

  public function register_taxonomies() {
    // Grouping taxonomy (like "packages" or "categories")
    $group_labels = [
      'name'              => __('Video Groups', 'wp-vod-library'),
      'singular_name'     => __('Video Group', 'wp-vod-library'),
      'search_items'      => __('Search Groups', 'wp-vod-library'),
      'all_items'         => __('All Groups', 'wp-vod-library'),
      'parent_item'       => __('Parent Group', 'wp-vod-library'),
      'parent_item_colon' => __('Parent Group:', 'wp-vod-library'),
      'edit_item'         => __('Edit Group', 'wp-vod-library'),
      'update_item'       => __('Update Group', 'wp-vod-library'),
      'add_new_item'      => __('Add New Group', 'wp-vod-library'),
      'new_item_name'     => __('New Group Name', 'wp-vod-library'),
      'menu_name'         => __('Groups', 'wp-vod-library'),
    ];

    register_taxonomy('vod_group', 'vod_video', [
      'hierarchical'    => true,
      'labels'      => $group_labels,
      'show_ui'       => true,
      'show_admin_column' => true,
      'query_var'     => true,
      'rewrite'       => ['slug' => 'vod-group'],
      'show_in_rest'    => true,
    ]);

    // Tagging taxonomy (non-hierarchical)
    $tag_labels = [
      'name'        => __('Video Tags', 'wp-vod-library'),
      'singular_name'   => __('Video Tag', 'wp-vod-library'),
      'search_items'    => __('Search Tags', 'wp-vod-library'),
      'all_items'     => __('All Tags', 'wp-vod-library'),
      'edit_item'     => __('Edit Tag', 'wp-vod-library'),
      'update_item'     => __('Update Tag', 'wp-vod-library'),
      'add_new_item'    => __('Add New Tag', 'wp-vod-library'),
      'new_item_name'   => __('New Tag Name', 'wp-vod-library'),
      'menu_name'     => __('Tags', 'wp-vod-library'),
    ];

    register_taxonomy('vod_tag', 'vod_video', [
      'hierarchical'    => false,
      'labels'      => $tag_labels,
      'show_ui'       => true,
      'show_admin_column' => true,
      'query_var'     => true,
      'rewrite'       => ['slug' => 'vod-tag'],
      'show_in_rest'    => true,
    ]);
  }
}
