<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Bootstrap WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

if (!is_user_logged_in()) {
  status_header(403);
  exit('Forbidden: You must be logged in.');
}

$file = $_GET['file'] ?? '';
$post_id = intval($_GET['post'] ?? 0);

// Sanity checks
if (empty($file) || !$post_id || get_post_type($post_id) !== 'vod_video') {
  status_header(400);
  exit('Invalid request.');
}

// Check access
if (!WP_VOD_Access::user_can_access_video(get_current_user_id(), $post_id)) {
  status_header(403);
  exit('You do not have access to this video.');
}

// Get base folder from plugin option
$base = get_option('wp_vod_library_scan_path');
$real_file = realpath($base . '/' . ltrim($file, '/'));

if (!$real_file || !file_exists($real_file)) {
  status_header(404);
  exit('File not found.');
}

// Optional: enforce that the file is inside the base path
if (strpos($real_file, realpath($base)) !== 0) {
  status_header(403);
  exit('Access outside base path is not allowed.');
}

// Send headers
$mime = mime_content_type($real_file);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($real_file));
header('Content-Disposition: inline; filename="' . basename($real_file) . '"');
readfile($real_file);
exit;
