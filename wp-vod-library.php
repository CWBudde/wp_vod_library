<?php

/**
 * Plugin Name: WP VOD Library
 * Plugin URI: https://github.com/CWBudde/wp_vod_library
 * Description: A video-on-demand library plugin for WordPress with HLS + MP4 support, tagging, and access control.
 * Version: 0.1.0
 * Author: Christian-W. Budde
 * Author URI: https://pcjv.de
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-vod-library
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
  exit;
}

// specify the plugin path
define('WP_VOD_PATH', plugin_dir_path(__FILE__));

// Autoload classes
foreach (glob(plugin_dir_path(__FILE__) . 'includes/class-*.php') as $file) {
  require_once $file;
}

// Register assets
function wp_vod_library_register_assets() {
  $plugin_url = plugin_dir_url(__FILE__);

  wp_register_style('plyr', $plugin_url . 'assets/css/plyr.css');
  wp_register_script('plyr', $plugin_url . 'assets/js/plyr.min.js', [], null, true);
  wp_register_script('hls', $plugin_url . 'assets/js/hls.min.js', [], null, true);
  wp_register_script('vod-player', $plugin_url . 'assets/js/vod-player.js', ['plyr', 'hls'], null, true);
}
add_action('wp_enqueue_scripts', 'wp_vod_library_register_assets');

// Init plugin functionality
add_action('plugins_loaded', function () {
  new WP_VOD_Post_Type();
  new WP_VOD_Taxonomies();
  new WP_VOD_Access();
  new WP_VOD_Scanner();
  new WP_VOD_Frontend();
  new WP_VOD_Admin_Columns();
  new WP_VOD_Access_UI();
  new WP_VOD_Templates();
});
