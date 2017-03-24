<?php
	
/*
Plugin Name: WP VBX Phone System
Plugin URI: http://www.rmlsoft.com/wp-vbx
Description: Wordpress VBX - Powered by Twilio. Provides an internet based phone system managed right from your wordpress site.
Version: 0.3
Author: Robert Lester
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
require_once __DIR__ . '/vendor/autoload.php';

foreach (scandir( __DIR__ . '/includes', 1) as $file) {
	if (strpos($file, '.php') !== false) {
		require __DIR__ . '/includes/' . $file;
	}
}

foreach (scandir( __DIR__ . '/applets', 1) as $file) {
	if (strpos($file, '.php') !== false) {
		require __DIR__ . '/applets/' . $file;
	}
}

define('WPRLVBX_S', 'wp-vbx-settings-group');
define('WPRLVBX_URL', plugin_dir_url( __FILE__ ));
define('WPRLVBX_TD', 'wp-vbx');

// Developer global functions

/**
 * vbx_permalink function. Returns the Applet permalink.
 * 
 * @access public
 * @param mixed $postid
 * @param mixed $index
 * @return void
 */
function vbx_permalink($postid, $index) {
	$post = get_post($postid);
	return WPRLVBX::filter_link('', $post, $index);
}

/**
 * twilio function. Returns the twilio client instance for REST API calls.
 * 
 * @access public
 * @return void
 */
function twilio() {
	return WPRLVBX::$client;
}