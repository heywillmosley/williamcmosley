<?php
/**
 * Plugin Name: Formidable WooCommerce
 * Plugin URI: http://formidablepro.com
 * Description: Use Formidable Forms on individual WooCommerce product pages to create customizable products. Requires the Formidable Forms plugin.
 * Author: Strategy11
 * Author URI: http://strategy11.com
 * Version: 1.04
 * Text Domain: formidable-woocommerce
 * Domain Path: /languages/
 *
 * Copyright: (c) 2013 Strategy11
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Formidable
 * @author    Strategy11
 * @copyright Copyright (c) 2015, Strategy11
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

if ( ! class_exists( 'WC_Formidable' ) ) :

/**
 * WooCommerce Formidable Forms Product Addons main class.
 *
 * @since 1.0
 */
class WC_Formidable {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;


	/**
	 * Initialize the plugin.
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// get required woo functions
		require_once( 'woo-includes/woo-functions.php' );

		// no sense in doing this if WC & FP aren't active
		if ( is_woocommerce_active() && function_exists( 'frm_forms_autoloader' ) ) {

			add_action('admin_init', array( $this, 'include_updater' ), 1);

			// load the classes
			require_once( 'classes/class-wc-formidable-admin.php' );
			$WC_Formidable_Admin = new WC_Formidable_Admin();

			require_once( 'classes/class-wc-formidable-product.php' );
			$WC_Formidable_Product = new WC_Formidable_Product();

			require_once( 'woocommerce-formidable-functions.php' );

		} else {
			// add admin notice about plugin requiring other plugins
			add_action( 'admin_notices', array( $this, 'required_plugins_error' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

    public function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			include_once( dirname( __FILE__ ) .'/woo-includes/FrmWooUpdate.php' );
			FrmWooUpdate::load_hooks();
		}
    }

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'formidable-woocommerce' );

		load_textdomain( 'formidable-woocommerce', trailingslashit( WP_LANG_DIR ) . 'woocommerce-formidable-product-addons/woocommerce-formidable-product-addons-' . $locale . '.mo' );
		load_plugin_textdomain( 'formidable-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Display an error to the user that the plugin has been disabled
	 *
	 * @since 1.0
	 */
	function required_plugins_error( ) {
		?>
		<div class="error">
			<p><?php _e( 'WooCommerce Formidable Forms Product Addons requires both Formidable Forms & WooCommerce to be active.', 'formidable-woocommerce' ); ?></p>
		</div>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'WC_Formidable', 'get_instance' ) );

// plugin activation
require_once( 'classes/class-wc-formidable-admin.php' );
$WC_Formidable_Admin = new WC_Formidable_Admin();
register_activation_hook( __FILE__, array( 'WC_Formidable_Admin', 'activation' ) );

// constants
define( 'WC_FP_PRODUCT_ADDONS_PLUGIN_FILE', __FILE__ );

endif;

// That's all folks!
