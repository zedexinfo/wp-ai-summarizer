<?php
/*
  * Plugin Name: ZX AI Summarizer
  * Description: The ZX AI Summarizer Plugin is a powerful tool that leverages OpenAI to summarize post content and product reviews on your WordPress website. It provides an easy way to generate concise summaries of your content and display them in a meta box in the admin panel.
  * Version: 1.0.0
  * Author: wpdev@zedexinfo.com
  * Contact Email: wpdev@zedexinfo.com
  * Author URI: https://zedexinfo.com/
  * License: GPL v2 or later
*/

if (!defined('ABSPATH')){ die(); }

if (!defined('SUMMARIZER_PLUGIN_DIR')){
	define('SUMMARIZER_PLUGIN_DIR',untrailingslashit( plugin_dir_path( __FILE__ )));
	define('SUMMARIZER_INCLUDES_PATH',untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/includes/');
	define('SUMMARIZER_TEMPLATES_PATH',untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/');
	define('OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/engines/text-davinci-002/completions');
}

if ( ! class_exists( 'Summarizer' ) ) {
	include_once SUMMARIZER_INCLUDES_PATH . "class-summarizer.php";
}

function summarizer_init(): Summarizer
{
	return Summarizer::getInstance();
}

if (!function_exists('summarizer_log')){
	function summarizer_log( $prod_id, $prod_name, $data, $mode = 'a', $file = 'sm-log' ) {
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		if ( is_array( $data ) ) {
			$data = json_encode( $data );
		}
		$file  = $upload_dir . '/' . $file . '.log';
		$file  = fopen( $file, $mode );
		$bytes = fwrite( $file, current_time( 'mysql' ) . " | " . $prod_id . " | " . $prod_name . " | " . $data . "\n" );
		fclose( $file );
		return $bytes;
	}
}

register_activation_hook(__FILE__, [Summarizer::class, 'defaultValues']);

summarizer_init();
