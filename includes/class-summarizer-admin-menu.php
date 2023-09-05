<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( "SummarizerAdminMenu" ) ) {
	class SummarizerAdminMenu {
		protected static $instance;

		public function __construct() {
			add_action('admin_menu', [$this, 'summarizer_settings'] );
		}

		public static function getInstance() {
			if ( self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function summarizer_settings() {
			add_menu_page(
				"Summarizer settings",
				"Summarizer settings",
				"manage_options",
				"manage_sm",
				[$this, "summarizer_settings_template"]
			);

			add_submenu_page(
				"manage_sm",
				"Error Log",
				"View Error Log",
				"manage_options",
				"manage_sm_error_log",
				[$this, "error_log_template"]
			);

			add_action( 'admin_init', [$this, 'register_summarizer_settings'] );
		}

        public function error_log_template() {
            load_template(SUMMARIZER_TEMPLATES_PATH . 'view-error-log.php');
        }

		public function summarizer_settings_template() {
			settings_errors();
			?>
            <div class="wrap">
                <form action="options.php" method="post">
					<?php
					settings_fields('summarizer-setting-section');
					do_settings_sections('summarizer-setting-section');
					submit_button();
					?>
                </form>
            </div>
			<?php
		}

        public function api_key_field_validation($value) {
            if (empty($value)) {
                $value = get_option('sm_api_key_option');
                add_settings_error('summarizer-setting-section', 'summarizer-setting-section_error', 'Please enter API Key', 'error');
            }
            return $value;
        }

        public function cron_delay_time_field_validation($value) {
            if (empty($value)) {
                $value = get_option('sm_cron_delay_time_option');
                add_settings_error('summarizer-setting-section', 'summarizer-setting-section_error', 'Please enter Cron Delay Time', 'error');
            }
            return $value;
        }

		public function register_summarizer_settings() {
            $admin_menu = [
                'sm_api_key_option' => [
                    'id' => 'api_key',
                    'title' => 'Enter API Key',
                    'callback' => 'api_key_callback',
                    'page' => 'summarizer-setting-section',
                    'section' => 'summarizer_admin_setting_section',
                    'validation callback' => 'api_key_field_validation'
                ],
                'sm_cron_delay_time_option' => [
                    'id' => 'cron_delay_time',
                    'title' => 'Enter Cron Delay Time (in seconds)',
                    'callback' => 'cron_delay_time_callback',
                    'page' => 'summarizer-setting-section',
                    'section' => 'summarizer_admin_setting_section',
                    'validation callback' => 'cron_delay_time_field_validation'
                ],
                'sm_error_log_option' => array(
	                'id' => 'sm_error_log',
	                'title' => 'Error Log',
	                'callback' => 'sm_error_log_callback',
	                'page' => 'summarizer-setting-section',
	                'section' => 'summarizer_admin_setting_section'
                )
            ];

			add_settings_section(
				__( 'summarizer_admin_setting_section' ),
				__( 'Summarizer settings' ),
				'',
				'summarizer-setting-section'
			);

			foreach ($admin_menu as $key => $value) {
				register_setting('summarizer-setting-section', $key, [$this, $value["validation callback"]]);
				add_settings_field(
					__($value["id"]),
					__($value["title"]),
					[$this, $value["callback"]],
					$value["page"],
					$value["section"]
				);
			}
		}

		public function api_key_callback() {
			$api_key = get_option( 'sm_api_key_option' );
			?>
            <input type="text" name="sm_api_key_option" class="regular-text"
                   value="<?php echo isset( $api_key ) ? esc_attr( $api_key ) : ''; ?>">
			<?php
		}

        public function cron_delay_time_callback() {
            $cron_delay_time = get_option( 'sm_cron_delay_time_option' );
            ?>
            <input type="text" name="sm_cron_delay_time_option" class="regular-text"
                   value="<?php echo isset( $cron_delay_time ) ? esc_attr( $cron_delay_time ) : ''; ?>">
            <?php
        }

		public function sm_error_log_callback() {
			$error_log = get_option('sm_error_log_option');
			?>
            <input type="checkbox" name="sm_error_log_option" value="1" <?php checked( '1', $error_log ); ?> />
			<?php
		}
	}
}