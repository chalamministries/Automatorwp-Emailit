<?php
/**
 * Plugin Name:           AutomatorWP - EmailIt
 * Plugin URI:            https://github.com/chalamministries/Automatorwp-Emailit
 * Description:           Connect AutomatorWP with EmailIt.
 * Version:               1.0
 * Author:                Chalam Ministries
 * Author URI:            https://automatorwp.com/
 * Text Domain:           automatorwp-emailit
 * Domain Path:           /languages/
 * Requires at least:     4.4
 * Tested up to:          6.7
 * License:               GNU AGPL v3.0 (http://www.gnu.org/licenses/agpl.txt)
 *
 * @package               AutomatorWP\EmailIt
 * @author                AutomatorWP
 * @copyright             Copyright (c) AutomatorWP
 */

final class AutomatorWP_EmailIt {

    /**
     * @var         AutomatorWP_EmailIt $instance The one true AutomatorWP_EmailIt
     * @since       1.0.0
     */
    private static $instance;

    /**
     * Get active instance
     *
     * @access      public
     * @since       1.0.0
     * @return      AutomatorWP_EmailIt self::$instance The one true AutomatorWP_EmailIt
     */
    public static function instance() {
        if( !self::$instance ) {
            self::$instance = new AutomatorWP_EmailIt();
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
        define( 'AUTOMATORWP_EMAILIT_VER', '1.0.6' );

        // Plugin file
        define( 'AUTOMATORWP_EMAILIT_FILE', __FILE__ );

        // Plugin path
        define( 'AUTOMATORWP_EMAILIT_DIR', plugin_dir_path( __FILE__ ) );

        // Plugin URL
        define( 'AUTOMATORWP_EMAILIT_URL', plugin_dir_url( __FILE__ ) );
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
            
            // API Files
            require_once AUTOMATORWP_EMAILIT_DIR . 'includes/sdk/autoload.php';

            // Includes
            require_once AUTOMATORWP_EMAILIT_DIR . 'includes/functions.php';
            require_once AUTOMATORWP_EMAILIT_DIR . 'includes/ajax-functions.php';

            // Actions
            require_once AUTOMATORWP_EMAILIT_DIR . 'includes/actions/add-user-to-list.php';
            require_once AUTOMATORWP_EMAILIT_DIR . 'includes/actions/add-subscriber-to-list.php';
            require_once AUTOMATORWP_EMAILIT_DIR . 'includes/actions/remove-user-from-list.php';
            require_once AUTOMATORWP_EMAILIT_DIR . 'includes/actions/remove-subscriber-from-list.php';

            // Triggers
            require_once AUTOMATORWP_EMAILIT_DIR . 'includes/triggers/submit-form.php';
            require_once AUTOMATORWP_EMAILIT_DIR . 'includes/triggers/anonymous-submit-form.php';

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

        //add_filter( 'automatorwp_licenses_meta_boxes', array( $this, 'license' ) );

        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    /**
     * Registers this integration
     *
     * @since 1.0.0
     */
    function register_integration() {

        automatorwp_register_integration( 'emailit', array(
            'label' => 'emailit',
            'icon'  => AUTOMATORWP_EMAILIT_URL . 'assets/emailit.svg',
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
//     function license( $meta_boxes ) {
// 
//         $meta_boxes['automatorwp-Emailit-license'] = array(
//             'title' => 'Emailit',
//             'fields' => array(
//                 'AUTOMATORWP_EMAILIT_license' => array(
//                     'type' => 'edd_license',
//                     'file' => AUTOMATORWP_EMAILIT_FILE,
//                     'item_name' => 'Emailit',
//                 ),
//             )
//         );
// 
//         return $meta_boxes;
// 
//     }

    /**
     * Plugin admin notices.
     *
     * @since  1.0.0
     */
    public function admin_notices() {

        if ( ! defined( 'AUTOMATORWP_ADMIN_NOTICES' ) ) :
            $emailit_mode = get_option('emailit_mode', 'API');
            
            if ( ! $this->meets_requirements() ) { ?>
                <div id="message" class="notice notice-error is-dismissible">
                    <p>
                        <?php printf(
                            __( 'AutomatorWP - EmailIt requires %s and %s in order to work. Please install and activate them.', 'automatorwp-emailit' ),
                            '<a href="https://wordpress.org/plugins/automatorwp/" target="_blank">AutomatorWP</a>',
                            '<a href="https://github.com/stingray82/EmailitWP" target="_blank">EmailitWP</a>'
                        ); ?>
                    </p>
                </div>
            <?php define( 'AUTOMATORWP_ADMIN_NOTICES', true ); ?>
            
            <?php } elseif ( ! $emailit_mode ) { ?>
                <div id="message" class="notice notice-error is-dismissible">
                    <p>
                        <?php printf(
                            __( 'AutomatorWP - EmailIt requires EmailItWP Mode to be set to `API`. You can find this option under Tools -> EmailItWP Settings.', 'automatorwp-emailit' ) ); ?>
                    </p>
                </div>
            <?php define( 'AUTOMATORWP_ADMIN_NOTICES', true ); ?>
            
            <?php } ?>
           
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

        if ( ! defined( 'EMAILIT_VERSION' ) ) {
            return false;
        }
        
        if (EMAILIT_VERSION < '1.3') {
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
        $lang_dir = AUTOMATORWP_EMAILIT_DIR . '/languages/';
        $lang_dir = apply_filters( 'automatorwp_emailit_languages_directory', $lang_dir );

        // Traditional WordPress plugin locale filter
        $locale = apply_filters( 'plugin_locale', get_locale(), 'automatorwp-emailit' );
        $mofile = sprintf( '%1$s-%2$s.mo', 'automatorwp_emailit', $locale );

        // Setup paths to current locale file
        $mofile_local   = $lang_dir . $mofile;
        $mofile_global  = WP_LANG_DIR . '/automatorwp-emailit/' . $mofile;

        if( file_exists( $mofile_global ) ) {
            // Look in global /wp-content/languages/automatorwp-Emailit/ folder
            load_textdomain( 'automatorwp_emailit', $mofile_global );
        } elseif( file_exists( $mofile_local ) ) {
            // Look in local /wp-content/plugins/automatorwp-Emailit/languages/ folder
            load_textdomain( 'automatorwp_emailit', $mofile_local );
        } else {
            // Load the default language files
            load_plugin_textdomain( 'automatorwp_emailit', false, $lang_dir );
        }

    }

}

/**
 * The main function responsible for returning the one true AutomatorWP_EmailIt instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \AutomatorWP_EmailIt The one true AutomatorWP_EmailIt
 */
function AutomatorWP_EmailIt() {
    return AutomatorWP_EmailIt::instance();
}
add_action( 'plugins_loaded', 'AutomatorWP_EmailIt' );
