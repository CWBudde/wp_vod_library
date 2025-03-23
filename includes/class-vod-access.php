<?php
if (!defined('ABSPATH')) exit;

class WP_VOD_Access {

  private $video_post_type = 'vod_video';
  private $group_taxonomy = 'vod_group';

  public function __construct() {
    add_action('save_post', [$this, 'handle_video_save'], 10, 3);
    add_action('profile_update', [$this, 'handle_user_update']);
  }

  public function handle_video_save($post_id, $post, $update) {
    if ($post->post_type !== $this->video_post_type || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    $user_meta_key = 'vod_accessible_videos';
    $video_meta_key = 'vod_video_user_access';

    $users_with_access = get_post_meta($post_id, $video_meta_key, true);

    if (!empty($users_with_access) && is_array($users_with_access)) {
      foreach ($users_with_access as $user_id) {
        $current_access = get_user_meta($user_id, $user_meta_key, true);
        if (!is_array($current_access)) $current_access = [];

        if (!in_array($post_id, $current_access)) {
          $current_access[] = $post_id;
          update_user_meta($user_id, $user_meta_key, $current_access);
        }
      }
    }
  }

  public function handle_user_update($user_id) {
    if (!current_user_can('edit_user', $user_id)) return;

    // Sync access to videos
    $videos = get_user_meta($user_id, 'vod_accessible_videos', true);
    $this->update_content_meta($videos, $user_id, 'vod_video_user_access');

    // Sync access to groups
    $groups = get_user_meta($user_id, 'vod_accessible_groups', true);
    if (is_array($groups)) {
      foreach ($groups as $term_id) {
        $current_users = get_term_meta($term_id, 'vod_group_user_access', true);
        if (!is_array($current_users)) $current_users = [];

        if (!in_array($user_id, $current_users)) {
          $current_users[] = $user_id;
          update_term_meta($term_id, 'vod_group_user_access', $current_users);
        }
      }
    }
  }

  private function update_content_meta($content_ids, $user_id, $meta_key) {
    if (!is_array($content_ids)) return;

    foreach ($content_ids as $id) {
      $current_users = get_post_meta($id, $meta_key, true);
      if (!is_array($current_users)) $current_users = [];

      if (!in_array($user_id, $current_users)) {
        $current_users[] = $user_id;
        update_post_meta($id, $meta_key, $current_users);
      }
    }
  }

  public static function user_can_access_video($user_id, $video_id) {
    $video_access_users = get_post_meta($video_id, 'vod_video_user_access', true);
    if (is_array($video_access_users) && in_array($user_id, $video_access_users)) {
      return true;
    }

    // Check access via taxonomy group
    $video_terms = wp_get_post_terms($video_id, 'vod_group');
    foreach ($video_terms as $term) {
      $group_users = get_term_meta($term->term_id, 'vod_group_user_access', true);
      if (is_array($group_users) && in_array($user_id, $group_users)) {
        return true;
      }
    }

    return false;
  }

  public static function get_user_accessible_videos($user_id) {
    $accessible = [];

    $query = new WP_Query([
      'post_type' => 'vod_video',
      'posts_per_page' => -1,
      'fields' => 'ids',
    ]);

    if ($query->have_posts()) {
      foreach ($query->posts as $video_id) {
        if (self::user_can_access_video($user_id, $video_id)) {
          $accessible[] = $video_id;
        }
      }
    }

    return $accessible;
  }

  public static function get_user_accessible_groups($user_id) {
    $accessible = [];

    $groups = get_terms([
      'taxonomy' => 'vod_group',
      'hide_empty' => false,
    ]);

    foreach ($groups as $group) {
      $users = get_term_meta($group->term_id, 'vod_group_user_access', true);
      if (is_array($users) && in_array($user_id, $users)) {
        $accessible[] = $group->term_id;
      }
    }

    return $accessible;
  }

  public static function resync_user_access($user_id) {
    $videos = self::get_user_accessible_videos($user_id);
    $groups = self::get_user_accessible_groups($user_id);

    update_user_meta($user_id, 'vod_accessible_videos', $videos);
    update_user_meta($user_id, 'vod_accessible_groups', $groups);
  }

  public static function resync_all_users() {
    $users = get_users();
    foreach ($users as $user) {
      self::resync_user_access($user->ID);
    }
  }
}
