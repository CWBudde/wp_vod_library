<?php
if (!defined('ABSPATH')) exit;

class WP_VOD_Admin_Columns {

  public function __construct() {
    // Admin columns
    add_filter('manage_vod_video_posts_columns', [$this, 'add_video_access_column']);
    add_action('manage_vod_video_posts_custom_column', [$this, 'render_video_access_column'], 10, 2);

    add_filter('manage_edit-vod_group_columns', [$this, 'add_group_access_column']);
    add_filter('manage_vod_group_custom_column', [$this, 'render_group_access_column'], 10, 3);

    // User profile
    add_action('show_user_profile', [$this, 'show_user_access']);
    add_action('edit_user_profile', [$this, 'show_user_access']);
  }

  public function add_video_access_column($columns) {
    $columns['vod_access_users'] = __('Access Users', 'wp-vod-library');
    return $columns;
  }

  public function render_video_access_column($column, $post_id) {
    if ($column === 'vod_access_users') {
      $user_ids = get_post_meta($post_id, 'vod_video_user_access', true);
      if (!empty($user_ids) && is_array($user_ids)) {
        $usernames = array_map(function ($id) {
          $user = get_user_by('ID', $id);
          return $user ? esc_html($user->user_login) : '';
        }, $user_ids);
        echo implode(', ', $usernames);
      } else {
        echo '<em>' . __('No users', 'wp-vod-library') . '</em>';
      }
    }
  }

  public function add_group_access_column($columns) {
    $columns['vod_access_users'] = __('Access Users', 'wp-vod-library');
    return $columns;
  }

  public function render_group_access_column($content, $column, $term_id) {
    if ($column === 'vod_access_users') {
      $user_ids = get_term_meta($term_id, 'vod_group_user_access', true);
      if (!empty($user_ids) && is_array($user_ids)) {
        $usernames = array_map(function ($id) {
          $user = get_user_by('ID', $id);
          return $user ? esc_html($user->user_login) : '';
        }, $user_ids);
        return implode(', ', $usernames);
      } else {
        return '<em>' . __('No users', 'wp-vod-library') . '</em>';
      }
    }
    return $content;
  }

  public function show_user_access($user) {
    if (!current_user_can('edit_users')) return;

    $video_ids = get_user_meta($user->ID, 'vod_accessible_videos', true);
    $group_ids = get_user_meta($user->ID, 'vod_accessible_groups', true);

    echo '<h2>' . __('VOD Access Overview', 'wp-vod-library') . '</h2>';
    echo '<table class="form-table">';

    echo '<tr><th>' . __('Videos', 'wp-vod-library') . '</th><td>';
    if (!empty($video_ids)) {
      echo '<ul>';
      foreach ($video_ids as $video_id) {
        echo '<li><a href="' . get_edit_post_link($video_id) . '">' . get_the_title($video_id) . '</a></li>';
      }
      echo '</ul>';
    } else {
      echo '<em>' . __('No video access', 'wp-vod-library') . '</em>';
    }
    echo '</td></tr>';

    echo '<tr><th>' . __('Groups', 'wp-vod-library') . '</th><td>';
    if (!empty($group_ids)) {
      echo '<ul>';
      foreach ($group_ids as $term_id) {
        $term = get_term($term_id, 'vod_group');
        if ($term && !is_wp_error($term)) {
          echo '<li><a href="' . get_edit_term_link($term_id, 'vod_group') . '">' . esc_html($term->name) . '</a></li>';
        }
      }
      echo '</ul>';
    } else {
      echo '<em>' . __('No group access', 'wp-vod-library') . '</em>';
    }
    echo '</td></tr>';

    echo '</table>';
  }
}
