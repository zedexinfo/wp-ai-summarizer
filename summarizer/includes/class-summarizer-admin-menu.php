<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( "SummarizerAdminMenu" ) ) {
    class SummarizerAdminMenu {
        protected static $instance;

        public function __construct() {
            add_action('admin_menu', [$this, 'api_key'] );
        }

        public static function getInstance() {
            if ( self::$instance === null ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function api_key() {
            add_menu_page(
                "API Key",
                "Add API Key",
                "manage_options",
                "manage_cd",
                [$this, "api_key_template"]
            );

            add_action( 'admin_init', [$this, 'register_apiKey_settings'] );
        }

        public function api_key_template() {
            settings_errors();
            ?>
            <div class="wrap">
                <h1>API Key Settings</h1>
                <form action="options.php" method="post">
                    <?php
                    settings_fields('apiKey-setting-section');
                    do_settings_sections('apiKey-setting-section');
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        public function register_apiKey_settings() {
            add_settings_section(
                __( 'apiKey_admin_setting_section' ),
                __( 'API Key' ),
                '',
                'apiKey-setting-section'
            );

            register_setting('apiKey-setting-section', 'api_key_option');
            add_settings_field(
                __('api_key'),
                __('Enter API Key'),
                [$this, 'api_key_callback'],
                'apiKey-setting-section',
                'apiKey_admin_setting_section'
            );
        }

        public function api_key_callback() {
            $api_key = get_option( 'api_key_option' );
            ?>
            <input type="text" name="api_key_option" class="regular-text"
                   value="<?php echo isset( $api_key ) ? esc_attr( $api_key ) : ''; ?> ">
            <?php
        }
    }
}