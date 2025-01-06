<?php
/**
 * Plugin Name:           AutomatorWP - EmailIt
 * Plugin URI:            https://automatorwp.com/add-ons/mailpoet/
 * Description:           Connect AutomatorWP with EmailIt.
 * Version:               1.0
 * Author:                Chalam Ministries
 * Author URI:            https://automatorwp.com/
 * Text Domain:           automatorwp-mailpoet
 * Domain Path:           /languages/
 * Requires at least:     4.4
 * Tested up to:          6.7
 * License:               GNU AGPL v3.0 (http://www.gnu.org/licenses/agpl.txt)
 *
 * @package               AutomatorWP\MailPoet
 * @author                AutomatorWP
 * @copyright             Copyright (c) AutomatorWP
 */

final class AutomatorWP_MailPoet {

    /**
     * @var         AutomatorWP_MailPoet $instance The one true AutomatorWP_MailPoet
     * @since       1.0.0
     */
    private static $instance;

    /**
     * Get active instance
     *
     * @access      public
     * @since       1.0.0
     * @return      AutomatorWP_MailPoet self::$instance The one true AutomatorWP_MailPoet
     */
    public static function instance() {
        if( !self::$instance ) {
            self::$instance = new AutomatorWP_MailPoet();
            self::$instance->constants();
            self::$instance->includes();
            self::$instance->hooks();
            self::$instance->load_textdomain();
        }

        return self::$instance;
    }

    /**
     * Setup plugin constants
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function constants() {
        // Plugin version
        define( 'AUTOMATORWP_MAILPOET_VER', '1.0.6' );

        // Plugin file
        define( 'AUTOMATORWP_MAILPOET_FILE', __FILE__ );

        // Plugin path
        define( 'AUTOMATORWP_MAILPOET_DIR', plugin_dir_path( __FILE__ ) );

        // Plugin URL
        define( 'AUTOMATORWP_MAILPOET_URL', plugin_dir_url( __FILE__ ) );
    }

    /**
     * Include plugin files
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function includes() {

        if( $this->meets_requirements() ) {

            // Includes
            require_once AUTOMATORWP_MAILPOET_DIR . 'includes/functions.php';
            require_once AUTOMATORWP_MAILPOET_DIR . 'includes/ajax-functions.php';

            // Actions
            require_once AUTOMATORWP_MAILPOET_DIR . 'includes/actions/add-user-to-list.php';
            require_once AUTOMATORWP_MAILPOET_DIR . 'includes/actions/add-subscriber-to-list.php';
            require_once AUTOMATORWP_MAILPOET_DIR . 'includes/actions/remove-user-from-list.php';
            require_once AUTOMATORWP_MAILPOET_DIR . 'includes/actions/remove-subscriber-from-list.php';

            // Triggers
            require_once AUTOMATORWP_MAILPOET_DIR . 'includes/triggers/submit-form.php';
            require_once AUTOMATORWP_MAILPOET_DIR . 'includes/triggers/anonymous-submit-form.php';

        }
    }

    /**
     * Setup plugin hooks
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function hooks() {

        add_action( 'automatorwp_init', array( $this, 'register_integration' ) );

        add_filter( 'automatorwp_licenses_meta_boxes', array( $this, 'license' ) );

        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    /**
     * Registers this integration
     *
     * @since 1.0.0
     */
    function register_integration() {

        automatorwp_register_integration( 'mailpoet', array(
            'label' => 'MailPoet',
            'icon'  => AUTOMATORWP_MAILPOET_URL . 'assets/mailpoet.svg',
        ) );

    }

    /**
     * Licensing
     *
     * @since 1.0.0
     *
     * @param array $meta_boxes
     *
     * @return array
     */
    function license( $meta_boxes ) {

        $meta_boxes['automatorwp-mailpoet-license'] = array(
            'title' => 'MailPoet',
            'fields' => array(
                'automatorwp_mailpoet_license' => array(
                    'type' => 'edd_license',
                    'file' => AUTOMATORWP_MAILPOET_FILE,
                    'item_name' => 'MailPoet',
                ),
            )
        );

        return $meta_boxes;

    }

    /**
     * Plugin admin notices.
     *
     * @since  1.0.0
     */
    public function admin_notices() {

        if ( ! $this->meets_requirements() && ! defined( 'AUTOMATORWP_ADMIN_NOTICES' ) ) : ?>

            <div id="message" class="notice notice-error is-dismissible">
                <p>
                    <?php printf(
                        __( 'AutomatorWP - MailPoet requires %s and %s in order to work. Please install and activate them.', 'automatorwp-mailpoet' ),
                        '<a href="https://wordpress.org/plugins/automatorwp/" target="_blank">AutomatorWP</a>',
                        '<a href="https://wordpress.org/plugins/mailpoet/" target="_blank">MailPoet</a>'
                    ); ?>
                </p>
            </div>

            <?php define( 'AUTOMATORWP_ADMIN_NOTICES', true ); ?>

        <?php endif;

    }

    /**
     * Check if there are all plugin requirements
     *
     * @since  1.0.0
     *
     * @return bool True if installation meets all requirements
     */
    private function meets_requirements() {

        if ( ! class_exists( 'AutomatorWP' ) ) {
            return false;
        }

        if ( ! defined( 'MAILPOET_VERSION' ) ) {
            return false;
        }

        return true;

    }

    /**
     * Internationalization
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function load_textdomain() {

        // Set filter for language directory
        $lang_dir = AUTOMATORWP_MAILPOET_DIR . '/languages/';
        $lang_dir = apply_filters( 'automatorwp_mailpoet_languages_directory', $lang_dir );

        // Traditional WordPress plugin locale filter
        $locale = apply_filters( 'plugin_locale', get_locale(), 'automatorwp-mailpoet' );
        $mofile = sprintf( '%1$s-%2$s.mo', 'automatorwp-mailpoet', $locale );

        // Setup paths to current locale file
        $mofile_local   = $lang_dir . $mofile;
        $mofile_global  = WP_LANG_DIR . '/automatorwp-mailpoet/' . $mofile;

        if( file_exists( $mofile_global ) ) {
            // Look in global /wp-content/languages/automatorwp-mailpoet/ folder
            load_textdomain( 'automatorwp-mailpoet', $mofile_global );
        } elseif( file_exists( $mofile_local ) ) {
            // Look in local /wp-content/plugins/automatorwp-mailpoet/languages/ folder
            load_textdomain( 'automatorwp-mailpoet', $mofile_local );
        } else {
            // Load the default language files
            load_plugin_textdomain( 'automatorwp-mailpoet', false, $lang_dir );
        }

    }

}

/**
 * The main function responsible for returning the one true AutomatorWP_MailPoet instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \AutomatorWP_MailPoet The one true AutomatorWP_MailPoet
 */
function AutomatorWP_MailPoet() {
    return AutomatorWP_MailPoet::instance();
}
add_action( 'plugins_loaded', 'AutomatorWP_MailPoet' );
