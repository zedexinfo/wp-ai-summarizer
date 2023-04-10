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
            $this->apiKey = get_option('api_key_option');

            add_action('plugins_loaded', array($this, 'initialize'));
            add_action( 'add_meta_boxes', [$this, 'summarize_meta_box'] );
        }

        public static function getInstance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
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
                $comments = implode(' Next review - ', $res);
            }
            else {
                $comments = 'No reviews found.';
            }
            $summary = $this->summarizer( "summarize the following reviews -" . $comments );
            return $summary;
        }

        public function summarizer( $content ) {
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
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ),
            );
            $response = wp_remote_post( OPENAI_API_ENDPOINT, $args );
            $body     = wp_remote_retrieve_body( $response );
            $result   = json_decode( $body, true );

            $summary = $result['choices'][0]['text'];
            return $summary;
        }

        public function display_summary( $post, $product_id ) {
            $err_label = ['Unable to summarize product review(s).', 'Unable to summarize post content.'];

            if ($post->post_type == 'post') {
                $content = $post->post_content;
                $summary = $this->summarizer( "Please summarize the following text: " . $content );
                if (empty($content) || empty($summary)){
                    echo $err_label[0];
                }
                else {
                    echo '<p>' . $summary . '</p>';
                }
            }
            else {
                $summary = $this->fetch_reviews($product_id);
//            update_post_meta( $product_id->ID, '_summary', $summary );
                if (empty($summary)) {
                    echo $err_label[1];
                } else {
                    echo '<p>' . $summary . '</p>';
                }
            }
        }

        public function summarize_meta_box($post) {
            $label = ['Post Content Summary','Product Review(s) Summary'];
            if ($post == 'post') {
                add_meta_box('summarize', $label[0], [$this, 'display_summary'], $post, 'normal', 'high');
            }
            else {
                add_meta_box('summarize', $label[1], [$this, 'display_summary'], $post, 'normal', 'high');
            }
        }
    }
}