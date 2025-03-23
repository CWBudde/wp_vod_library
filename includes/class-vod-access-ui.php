<?php
if (!defined('ABSPATH')) exit;

class WP_VOD_Access_UI {

  public function __construct() {
    // Post edit screen: Add and save user access
    add_action('add_meta_boxes', [$this, 'add_video_access_metabox']);
    add_action('save_post_vod_video', [$this, 'save_video_access_metabox']);

    // Taxonomy term edit screen: Add and save user access
    add_action('vod_group_edit_form_fields', [$this, 'add_group_access_fields']);
    add_action('edited_vod_group', [$this, 'save_group_access_fields']);
  }

  /**
   * Add user access metabox to video post type
   */
  public function add_video_access_metabox() {
    add_meta_box(
      'vod_video_user_access',
      __('User Access', 'wp-vod-library'),
      [$this, 'render_video_access_metabox'],
      'vod_video',
      'side',
      'default'
    );
  }

  public function render_video_access_metabox($post) {
    wp_nonce_field('vod_save_user_access', 'vod_user_access_nonce');

    $user_ids = get_post_meta($post->ID, 'vod_video_user_access', true);
    $user_ids = is_array($user_ids) ? $user_ids : [];

    $users = get_users(['fields' => ['ID', 'user_login']]);

    echo '<ul style="max-height:200px;overflow:auto">';
    foreach ($users as $user) {
      echo '<li>';
      echo '<label>';
      echo '<input type="checkbox" name="vod_video_user_access[]" value="' . esc_attr($user->ID) . '" ' . checked(in_array($user->ID, $user_ids), true, false) . ' />';
      echo ' ' . esc_html($user->user_login);
      echo '</label>';
      echo '</li>';
    }
    echo '</ul>';
  }

  public function save_video_access_metabox($post_id) {
    if (!isset($_POST['vod_user_access_nonce']) || !wp_verify_nonce($_POST['vod_user_access_nonce'], 'vod_save_user_access')) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $access = isset($_POST['vod_video_user_access']) ? array_map('intval', $_POST['vod_video_user_access']) : [];
    update_post_meta($post_id, 'vod_video_user_access', $access);
  }

  /**
   * Add user access checkbox list to vod_group taxonomy term edit form
   */
  public function add_group_access_fields($term) {
    $user_ids = get_term_meta($term->term_id, 'vod_group_user_access', true);
    $user_ids = is_array($user_ids) ? $user_ids : [];

    $users = get_users(['fields' => ['ID', 'user_login']]);

    echo '<tr class="form-field">';
    echo '<th scope="row"><label>' . __('User Access', 'wp-vod-library') . '</label></th>';
    echo '<td>';
    echo '<ul style="max-height:200px;overflow:auto">';
    foreach ($users as $user) {
      echo '<li>';
      echo '<label>';
      echo '<input type="checkbox" name="vod_group_user_access[]" value="' . esc_attr($user->ID) . '" ' . checked(in_array($user->ID, $user_ids), true, false) . ' />';
      echo ' ' . esc_html($user->user_login);
      echo '</label>';
      echo '</li>';
    }
    echo '</ul>';
    echo '</td></tr>';
  }

  public function save_group_access_fields($term_id) {
    if (!current_user_can('manage_options')) return;

    $access = isset($_POST['vod_group_user_access']) ? array_map('intval', $_POST['vod_group_user_access']) : [];
    update_term_meta($term_id, 'vod_group_user_access', $access);
  }
}
