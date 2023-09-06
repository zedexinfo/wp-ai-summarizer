<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( "Summarizer" ) ) {
	class Summarizer
	{
		protected static $instance;
		protected $apiKey;
		public $SummarizerAdminMenu;

		public function __construct()
		{
			$this->apiKey = get_option('sm_api_key_option');

            		add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
			add_action('plugins_loaded', array($this, 'initialize'));
			add_action( 'add_meta_boxes', [$this, 'summarize_meta_box'] );
			add_action( 'comment_post', [$this, 'schedule_ai_cron'] );
			add_action('edit_comment', [$this, 'schedule_ai_cron']);
			add_action('trash_comment', [$this, 'schedule_ai_cron']);
			add_action('ai_cron_hook', [$this, 'ai_cron_hook']);
		}

		public static function getInstance()
		{
			if (self::$instance === null) {
				self::$instance = new self();
			}

			return self::$instance;
		}

	        public function plugin_row_meta($links, $file) {
	            if ('wp-ai-summarizer-main/summarizer.php' === $file) {
	                $new_links = array(
	                    'support' => '<a href="https://zedexinfo.com/contact-us/" target="_blank">Support</a>',
	                );
	                $links = array_merge($links, $new_links);
	            }
	            return $links;
	        }

		public function initialize()
		{
			$this->includes();
			$this->init();
		}

		public function includes()
		{
			include_once SUMMARIZER_INCLUDES_PATH . 'class-summarizer-admin-menu.php';
		}

		public function init()
		{
			$this->SummarizerAdminMenu = SummarizerAdminMenu::getInstance();
		}

		public static function defaultValues(){
			$default_values = [
				'sm_cron_delay_time_option' => 300,
				'sm_error_log_option' => 1
			];

			foreach ($default_values as $key => $value){
				if (get_option($key) == false) {
					update_option($key, $value);
				}
			}
		}

		public function fetch_reviews($product_id) {
			$args = array(
				'status' => 'approve',
				'type' => 'review',
				'meta_query' => array(
					array(
						'key' => 'rating',
						'compare' => 'EXISTS'
					)
				)
			);

			$reviews = get_comments(array(
				'post_id' => $product_id->ID,
				'args' => $args
			));

			if (!empty($reviews)) {
				$res = [];
				foreach ($reviews as $review) {
					array_push($res, $review->comment_content);
				}
				$comments = implode('. Next review - ', $res);
			}
			else {
				$comments = 'No reviews found.';
			}
			$summary = $this->summarizer( "summarize the following reviews - " . $comments );
			return $summary;
		}

		public function summarizer( $content ) {
			$data = array(
				'prompt'            => $content,
				'temperature'       => 0.5,
				'max_tokens'        => 600,
				'top_p'             => 1,
				'frequency_penalty' => 0,
				'presence_penalty'  => 0,
			);
			$args = array(
				'body'    => json_encode( $data ),
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->apiKey,
				),
			);
			$response = wp_remote_post( OPENAI_API_ENDPOINT, $args );
			$body     = wp_remote_retrieve_body( $response );
			$result   = json_decode( $body, true );

			if (isset($result['error']['message'])) {
				$summary['error'] = $result['error']['message'];
			}
			else {
				$summary = $result['choices'][0]['text'];
			}
			return $summary;
		}

		public function display_summary( $post ) {
			$err_label = ['Unable to summarize post content.' ,'Unable to summarize product review(s).'];

			if ($post->post_type == 'post') {
				$content = $post->post_content;
				if (empty($content)){
					echo '<p style="color: red">Content is Empty!</p>';
				}
				else {
					$summary = $this->summarizer( "Please summarize the following text: " . $content );
					if (isset($summary['error'])) {
						echo '<p style="color: red">' . $summary['error'] . '</p>';
					}
					else {
						echo '<p>' . $summary . '</p>';
					}
				}
			}
			else {
				$sm_error = get_post_meta( $post->ID, '_reviews_summary_error', true);
				if (!empty($sm_error)) {
					echo '<p style="color: red">' . $sm_error . '</p>';
				}
				else {
					$summary = get_post_meta( $post->ID, '_reviews_summary', true);
					if (empty($summary)) {
						echo '<p style="color: red">' . $err_label[1] . '</p>';
					}
					else {
						echo '<p>' . $summary . '</p>';
					}
				}
			}
		}

		public function summarize_meta_box($post) {
			$label = ['Post Content Summary','Product Review(s) Summary'];
			if ($post == 'post') {
				add_meta_box('summarize', $label[0], [$this, 'display_summary'], $post, 'normal', 'high');
			}
			elseif ($post == 'product') {
				add_meta_box('summarize', $label[1], [$this, 'display_summary'], $post, 'normal', 'high');
			}
		}

		public function schedule_ai_cron($comment_ID) {
			$comment = get_comment($comment_ID);

			$product = get_post($comment->comment_post_ID);
			if ( ! wp_next_scheduled( 'ai_cron_hook' ) ) {
				wp_schedule_single_event(time() + get_option('sm_cron_delay_time_option'), 'ai_cron_hook', ['product_id' => $product->ID]);
			}
		}

		public function ai_cron_hook($args) {
			$product = get_post($args);

			if ($product && $product->post_type === 'product') {
				if ($product->comment_count > 0) {
					$summary = $this->fetch_reviews($product);
					if (isset($summary['error'])) {
						if (get_option('sm_error_log_option') == 1) {
							summarizer_log( $product->ID, $product->post_title, $summary['error'] );
						}
						update_post_meta($product->ID, '_reviews_summary_error', $summary['error']);
					}
					else {
						update_post_meta($product->ID, '_reviews_summary', $summary);
						delete_post_meta($product->ID, '_reviews_summary_error');
					}
				}
			}
		}
	}
}
