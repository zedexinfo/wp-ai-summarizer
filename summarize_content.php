<?php
/*
Plugin Name: Post Content Summarizer
Description: A plugin that uses OpenAI to summarize post content.
Version: 1.0
Author: Stablewp
*/


define( 'OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/engines/text-davinci-002/completions' );


add_action( 'add_meta_boxes', 'summarize_content_box' );
function summarize_content_box() {
	add_meta_box( 'summarize_content', 'Post Content Summary', 'summarize_content_box_callback', '', 'normal', 'high' );
}

function summarize_content_box_callback( $post ) {
	$content = $post->post_content;
	$summary = summarize_content( "Please summarize the following text: " . $content );
	if (empty($content) || empty($summary)){
		echo "Unable to summarize post content.";
	}
	else {
		echo '<p>' . $summary . '</p>';
	}
}

function summarize_content( $content ) {
	$data = array(
		'prompt'            => $content,
		'temperature'       => 0.5,
		'max_tokens'        => 200,
		'top_p'             => 1,
		'frequency_penalty' => 0,
		'presence_penalty'  => 0,
	);
	$args = array(
		'body'    => json_encode( $data ),
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . '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',
		),
	);

	$response = wp_remote_post( OPENAI_API_ENDPOINT, $args );
	$body     = wp_remote_retrieve_body( $response );
	$result   = json_decode( $body, true );

	$summary = $result['choices'][0]['text'];
	return $summary;
}