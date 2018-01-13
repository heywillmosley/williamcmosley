<?php
/**
 * WooCommerce Formidable Forms Product Add-ons
 *
 * @package     WC-formidable/Classes
 * @author      Strategy11
 * @copyright   Copyright (c) 2015, Strategy11
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * This class handles all of the admin interface
 */
class WC_Formidable_Admin {


	/**
	 * Initialize the WooCommerce Formidable Forms Admin class
	 *
	 * @since 1.0
	 */
	public function __construct() {

		// Add a write panel on the product page
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'process_meta_box' ), 1, 2 );
		add_action( 'admin_notices', array( $this, 'check_form_total_field' ) );
	}

	/**
	 * Add a metabox to the edit product page
	 *
	 * @since 1.0
	 */
	public function add_meta_box( ) {
		global $post;
		add_meta_box( 'woocommerce-formidable-meta', __( 'Choose Form', 'formidable-woocommerce' ), array( $this, 'meta_box' ), 'product', 'side', 'default' );
	}


	/**
	 * Render the metabox on the edit product page
	 *
	 * @since 1.0
	 * @param object $post the current post
	 */
	public function meta_box( $post ) {
		// get a Formidable form if it's already attached
		$attached_form_id = get_post_meta( $post->ID, '_attached_formidable_form', true );
		$stop_price = get_post_meta( $post->ID, '_frm_stop_woo_price', true );

		?>
		<div class="panel">
			<div class="options_group">
				<p>
					<?php FrmFormsHelper::forms_dropdown( 'formidable-form-id', $attached_form_id, array( 'blank' => __( 'None', 'formidable-woocommerce' ) ) ); ?>
				</p>
				<?php if ( $attached_form_id && is_numeric( $attached_form_id ) ) { ?>
					<p><a href="<?php echo esc_url( sprintf( '%s/admin.php?page=formidable&frm_action=edit&id=%d', get_admin_url(), $attached_form_id ) ) ?>">Edit <?php echo wptexturize( FrmForm::getName( $attached_form_id ) ); ?> Formidable Form</a></p>
				<?php } ?>

				<p>
					<label for="formidable_stop_price">
						<input type="checkbox" value="1" name="formidable_stop_price" <?php checked( $stop_price, true ) ?> />
						<?php esc_html_e( 'Use the total in the form without adding the product price.', 'formidable-woocommerce' ) ?>
					</label>
				</p>
			</div>
		</div>
		<?php
	}


	/**
	 * Save the metabox options
	 *
	 * @since 1.0
	 * @param int $post_id the current post id
	 * @param int $post the current post
	 */
	function process_meta_box( $post_id, $post ) {

		// Save Formidable form data
		if ( isset( $_POST['formidable-form-id'] ) && ! empty( $_POST['formidable-form-id'] ) ) {
			$form_id = absint( $_POST['formidable-form-id'] );
			update_post_meta($post_id, '_attached_formidable_form', $form_id);

			if ( isset( $_POST['formidable_stop_price'] ) ) {
				update_post_meta( $post_id, '_frm_stop_woo_price', true );
			} else {
				delete_post_meta( $post_id, '_frm_stop_woo_price' );
			}
		} else {
			// delete the post meta if there was no Formidable form id set
			delete_post_meta( $post_id, '_attached_formidable_form' );
			delete_post_meta( $post_id, '_frm_stop_woo_price' );
		}
	}


	/**
	 * Check and see if the form has a total field. If it doesn't print out an error.
	 *
	 * @since 1.0
	 */
	function check_form_total_field( ) {

		global $post;

		if ( isset( $post ) && property_exists( $post, 'ID' ) ) {

			// get a Formidable form if it's already attached
			$form_id = get_post_meta( $post->ID, '_attached_formidable_form', true );

			// make sure we have a valid form id
			if ( is_numeric( $form_id ) ) {

				$found = wc_fp_form_has_total_field( $form_id );

				// prep the error
				if ( empty( $found ) ) {
					$this->no_total_field_error();
				}
			}
		}

	}


	/**
	 * Print the no total field error.
	 *
	 * @since 1.0
	 */
	function no_total_field_error( ) {
		?>
		<div class="error">
			<p><?php _e( 'Your form doesn&apos;t contain a total field. You must add a total field to the form for it to display on the front end.', 'formidable-woocommerce' ); ?></p>
		</div>
		<?php
	}


	/**
	 * Add a sample form on plugin activation
	 *
	 * @since 1.0
	 */
	public static function activation( ) {
		$option_name = 'wc_fp_starter_form';

		// check if there's a starter form
		$starter_form_exists = WC_Formidable_Admin::get_starter_form( $option_name );

		// if a starter form doesn't exist them create one
		if ( ! $starter_form_exists ) {
			// if a starter form doesn't exist them create it
			WC_Formidable_Admin::add_starter_form( $option_name );
		}
	}


	/**
	 * Check to see if a form exists.
	 *
	 * @since 1.0
	 * @param string $option_name the name of the db option
	 * @return bool
	 */
	public static function get_starter_form( $option_name ) {

		$return = false;
		if ( class_exists( 'FrmForm' ) ) {
			// if Formidable forms is loaded

			$form = FrmForm::getOne( $option_name );
			if ( $form ) {
				// if the form exists return true
				$return = true;
			}
		}

		return $return;
	}


	/**
	 * Add a starter form. Return the ID.
	 *
	 * @since 1.0
	 * @param string $option_name the name of the db option
	 */
	public static function add_starter_form( $option_name ) {
		if ( is_callable( 'FrmXMLController::add_default_templates' ) ) {
			add_filter( 'frm_default_templates_files', 'WC_Formidable_Admin::add_default_template' );
			FrmXMLController::add_default_templates();
		}
	}

	public static function add_default_template( $files ) {
		$dir = plugin_dir_path( WC_FP_PRODUCT_ADDONS_PLUGIN_FILE );
		$file = $dir . 'assets/forms/sample-form.xml';
		$files[] = $file;
		return $files;
	}

}
