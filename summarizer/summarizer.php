<?php
/*
  Plugin Name: Summarizer
  Description: Plugin which summarizes blog posts and product reviews.
  Version: 1.0.0
  Author: Dev@StableWP
*/

if (!defined('ABSPATH')){ die(); }

if (!defined('SUMMARIZER_PLUGIN_DIR')){
    define('SUMMARIZER_PLUGIN_DIR',untrailingslashit( plugin_dir_path( __FILE__ )));
    define('SUMMARIZER_INCLUDES_PATH',untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/includes/');
    define('SUMMARIZER_TEMPLATES_PATH',untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/');
    define( 'OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/engines/text-davinci-002/completions' );
//    define('SUMMARIZER_JS_PATH', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) . '/assets/js/' );
}

if ( ! class_exists( 'Summarizer' ) ) {
    include_once SUMMARIZER_INCLUDES_PATH . "class-summarizer.php";
}

function summarizer_init(): Summarizer
{
    return Summarizer::getInstance();
}

summarizer_init();