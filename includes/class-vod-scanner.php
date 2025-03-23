<?php
if (!defined('ABSPATH')) exit;

class WP_VOD_Scanner {

  private $option_name = 'wp_vod_library_scan_path';
  private $created_count = 0;

  public function __construct() {
    add_action('admin_menu', [$this, 'add_settings_page']);
    add_action('admin_init', [$this, 'register_settings']);
    add_action('admin_init', [$this, 'maybe_run_scan']);
  }

  public function add_settings_page() {
    add_options_page(
      __('VOD Scanner Settings', 'wp-vod-library'),
      __('VOD Scanner', 'wp-vod-library'),
      'manage_options',
      'wp_vod_scanner',
      [$this, 'render_settings_page']
    );
  }

  public function register_settings() {
    register_setting('wp_vod_scanner', $this->option_name);
    add_settings_section('vod_scanner_main', '', null, 'wp_vod_scanner');

    add_settings_field(
      'vod_base_path',
      __('Base Folder Path', 'wp-vod-library'),
      function () {
        $value = esc_attr(get_option($this->option_name, ''));
        echo "<input type='text' name='{$this->option_name}' value='{$value}' class='regular-text' />";
        echo "<p class='description'>" . __('Absolute path on the server to scan for videos.', 'wp-vod-library') . "</p>";
      },
      'wp_vod_scanner',
      'vod_scanner_main'
    );
  }

  public function render_settings_page() {
?>
    <div class="wrap">
      <h1><?php _e('VOD Scanner Settings', 'wp-vod-library'); ?></h1>
      <form method="post" action="options.php">
        <?php
        settings_fields('wp_vod_scanner');
        do_settings_sections('wp_vod_scanner');
        submit_button(__('Save Changes', 'wp-vod-library'));
        ?>
      </form>

      <hr />
      <h2><?php _e('Manual Scan', 'wp-vod-library'); ?></h2>
      <p><?php _e('Click the button below to scan the defined path for new videos and import them.', 'wp-vod-library'); ?></p>
      <form method="post">
        <input type="hidden" name="run_vod_scan" value="1" />
        <?php submit_button(__('Run Scanner', 'wp-vod-library'), 'secondary'); ?>
      </form>
    </div>
<?php
  }

  public function maybe_run_scan() {
    if (isset($_POST['run_vod_scan']) && current_user_can('manage_options')) {
      $this->created_count = 0; // Reset counter
      $this->scan();

      add_action('admin_notices', function () {
        $msg = sprintf(
          __('VOD scan completed. %d new video(s) imported.', 'wp-vod-library'),
          $this->created_count
        );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
      });
    }
  }

  public function scan() {
    $base = get_option($this->option_name);
    if (!$base || !is_dir($base)) return;

    $this->scan_directory_for_valid_video_folders($base, $base);
  }

  private function scan_directory_for_valid_video_folders($base, $current) {
    $folders = scandir($current);

    foreach ($folders as $folder) {
      if ($folder === '.' || $folder === '..') continue;

      $full_path = $current . DIRECTORY_SEPARATOR . $folder;

      if (is_dir($full_path)) {
        // Check for valid video folder
        $mp4_files = glob($full_path . '/*.mp4');
        $hls_master = $full_path . '/HLS/master.m3u8';

        if (count($mp4_files) === 1 && file_exists($hls_master)) {
          $this->create_video_from_folder($base, $full_path, $mp4_files[0], $hls_master);
        }

        // Recursive check
        $this->scan_directory_for_valid_video_folders($base, $full_path);
      }
    }
  }

  private function create_video_from_folder($base, $folder_path, $mp4_path, $hls_path) {
    $relative_folder = $this->get_relative_folder($base, $folder_path);
    if ($this->video_post_exists($relative_folder)) return;

    $post_id = $this->create_video_post($relative_folder, $folder_path);
    if (!$post_id) return;

    $this->add_video_meta($post_id, $base, $relative_folder, $mp4_path, $hls_path);

    $slug = sanitize_title($relative_folder);
    $symlink_url = $this->ensure_public_symlink($folder_path, $slug);
    update_post_meta($post_id, 'vod_symlink_url', $symlink_url);

    $this->assign_tags_from_folder($post_id, $relative_folder);
    $this->import_and_attach_images($post_id, $folder_path);
  }

  private function get_relative_folder($base, $folder_path) {
    return trim(str_replace($base, '', $folder_path), DIRECTORY_SEPARATOR);
  }

  private function video_post_exists($relative_folder) {
    $existing = get_posts([
      'post_type'  => 'vod_video',
      'meta_query' => [[
        'key'   => 'vod_video_folder',
        'value' => $relative_folder,
        'compare' => '='
      ]],
      'fields' => 'ids'
    ]);
    return !empty($existing);
  }

  private function create_video_post($relative_folder, $folder_path) {
    $title = basename($folder_path);
    $slug = sanitize_title($title);

    return wp_insert_post([
      'post_title'  => $title,
      'post_name'   => $slug,
      'post_type'   => 'vod_video',
      'post_status' => 'publish',
    ]);

    if ($post_id) {
      $this->created_count++;
    }

    return $post_id;
  }

  private function add_video_meta($post_id, $base, $relative_folder, $mp4_path, $hls_path) {
    update_post_meta($post_id, 'vod_video_folder', $relative_folder);
    update_post_meta($post_id, 'vod_mp4_file', str_replace($base, '', $mp4_path));
    update_post_meta($post_id, 'vod_hls_master', str_replace($base, '', $hls_path));
  }

  private function assign_tags_from_folder($post_id, $relative_folder) {
    $tags = explode(DIRECTORY_SEPARATOR, $relative_folder);
    array_pop($tags);
    if (!empty($tags)) {
      wp_set_post_terms($post_id, $tags, 'vod_tag', true);
    }
  }

  private function import_and_attach_images($post_id, $folder_path) {
    $image_files = glob($folder_path . '/*.{jpg,jpeg,png}', GLOB_BRACE);
    if (empty($image_files)) return;

    $upload_dir = wp_upload_dir();
    $upload_base = $upload_dir['basedir'];
    $upload_url  = $upload_dir['baseurl'];

    foreach ($image_files as $image_file) {
      $image_name = basename($image_file);

      $existing = get_posts([
        'post_type'  => 'attachment',
        'meta_query' => [[
          'key'   => '_vod_original_file',
          'value' => $image_file,
          'compare' => '='
        ]],
        'fields' => 'ids'
      ]);

      if (!empty($existing)) {
        if (!has_post_thumbnail($post_id)) {
          set_post_thumbnail($post_id, $existing[0]);
        }
        continue;
      }

      $target_path = $upload_base . '/vod_thumbs/' . $image_name;
      $target_url  = $upload_url . '/vod_thumbs/' . $image_name;

      if (!file_exists(dirname($target_path))) {
        wp_mkdir_p(dirname($target_path));
      }

      if (!is_readable($image_file)) {
        continue;
      }

      require_once ABSPATH . 'wp-admin/includes/file.php';
      require_once ABSPATH . 'wp-admin/includes/media.php';
      require_once ABSPATH . 'wp-admin/includes/image.php';

      $tmp_array = [
        'name'     => basename($image_file),
        'tmp_name' => $image_file
      ];

      $attachment_id = media_handle_sideload($tmp_array, $post_id);

      if (is_wp_error($attachment_id)) {
        return;
      }

      // Store custom meta to prevent duplicates later
      update_post_meta($attachment_id, '_vod_original_file', $image_file);

      if (!has_post_thumbnail($post_id)) {
        set_post_thumbnail($post_id, $attachment_id);
      }

      $attachment_id = wp_insert_attachment([
        'guid'           => $target_url,
        'post_mime_type' => mime_content_type($target_path),
        'post_title'     => sanitize_file_name($image_name),
        'post_content'   => '',
        'post_status'    => 'inherit',
      ]);

      require_once(ABSPATH . 'wp-admin/includes/image.php');
      $attach_data = wp_generate_attachment_metadata($attachment_id, $target_path);
      wp_update_attachment_metadata($attachment_id, $attach_data);

      update_post_meta($attachment_id, '_vod_original_file', $image_file);

      if (!has_post_thumbnail($post_id)) {
        set_post_thumbnail($post_id, $attachment_id);
      }
    }
  }

  private function ensure_public_symlink($source_path, $slug) {
    $uploads = wp_upload_dir();
    $public_base = $uploads['basedir'] . '/vod_data';
    $public_path = $public_base . '/' . $slug;

    if (!file_exists($public_base)) {
      wp_mkdir_p($public_base);
    }

    // Only create if not already linked
    if (!file_exists($public_path)) {
      symlink($source_path, $public_path);
    }

    // Return the public URL
    return $uploads['baseurl'] . '/vod_data/' . $slug;
  }
}
