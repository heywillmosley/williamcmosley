<?php
/**
 * WooCommerce Formidable Forms Product Add-ons
 *
 * @package     WC-formidable/Classes
 * @author      Strategy11
 * @copyright   Copyright (c) 2015, Strategy11
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * This class handles all of the front end implementation on the product page
 */
class WC_Formidable_Product {

	private $form_submission_id;
	private $calc_totals;


	/**
	 * Initialize the WooCommerce Formidable Forms Product class
	 *
	 * @since 1.0
	 */
	public function __construct() {

		// display form immediately after product excerpt
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_form' ), 10 );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'total_field' ), 20 );

		// process Formidable form form data
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'init_formidable_processing' ), 5, 2 );
		add_filter( 'frm_after_create_entry', array( $this, 'save_form_submission_id' ) );
		add_filter( 'frm_after_create_entry', array( $this, 'save_calc_totals' ) );

		// filters for cart actions
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
		add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 20, 1 );
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'add_order_item_meta' ), 10, 2 );

		// filters for cart template
		add_filter( 'woocommerce_cart_item_name', array( $this, 'cart_item_name' ), 10, 3 );

		// Validate Formidable Form error on add to cart action
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart'), 10, 2 );

		// Change the Add to cart button related stuff
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'change_add_to_cart_text' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'change_add_to_cart_url' ), 10, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'loop_add_to_cart_buttom_remove_add_to_cart_class'), 10, 2 );

		add_filter( 'frm_action_triggers',  array( $this, 'add_payment_trigger' ) );
		add_filter( 'frm_email_action_options', array( $this, 'add_trigger_to_action' ) );
		add_filter( 'frm_twilio_action_options', array( $this, 'add_trigger_to_action' ) );
		add_filter( 'frm_mailchimp_action_options', array( $this, 'add_trigger_to_action' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'trigger_actions_after_payment' ) );
	}


	/**
	 * Don't add to cart if there are Formidable Form errors
	 *
	 * @param bool $add_to_cart Whether product is allowed to be added to the cart
	 * @param int  $product_id  Product ID
	 *
	 * @return bool True if product can be added to the cart
	 */
	public function validate_add_to_cart( $add_to_cart, $product_id ) {

		$errors = $this->maybe_do_form_processing( $product_id );

		if ( is_array( $errors ) && count( $errors ) > 0 ) {
			$add_to_cart = false;
		}

		return $add_to_cart;
	}

	public function maybe_do_form_processing( $product_id = null ) {
		if ( ! $product_id ) {
			return false;
		}

		$errors = array();

		// Get form id.
		$form_id = $this->get_attached_form_id( $product_id );

		if ( $form_id ) {
			if ( ! empty( $_POST ) ) {
				$errors = FrmEntryValidate::validate( $_POST );
			} else {
				$errors['form'] = __( 'Oops! Nothing was submitted', 'formidable-woocommerce' );
			}
		}

		return $errors;
	}

	/**
	 * Change the add to cart text for Formidable Form enabled products
	 *
	 * @param String $text
	 * @param WC_Product $product
	 *
	 * @return String
	 */
	public function change_add_to_cart_text( $text, $product ) {
		$form_id = get_post_meta( $product->id, '_attached_formidable_form', true );
		if( '' !== $form_id && $form_id > 0 ) {
			$text = apply_filters( 'wc_fp_addons_add_to_cart_text', __( 'Select Options', 'formidable-woocommerce' ) );
		}
		return $text;
	}


	/**
	 * Change the add to cart URL for Formidable Form enabled products
	 *
	 * @param String $url
	 * @param WC_Product $product
	 *
	 * @return String
	 */
	public function change_add_to_cart_url( $url, $product ) {
		$form_id = get_post_meta( $product->id, '_attached_formidable_form', true );
		if( '' !== $form_id && $form_id > 0 ) {
			$url = get_permalink( $product->id );
		}
		return $url;
	}

	/**
	 * Remove the add to cart class from Formidable Form Enabled products because this triggers AJX
	 *
	 * @param String $link
	 * @param WC_Product $product
	 *
	 * @return String
	 */
	public function loop_add_to_cart_buttom_remove_add_to_cart_class( $link, $product ) {
		$form_id = get_post_meta( $product->id, '_attached_formidable_form', true );
		if( '' !== $form_id && $form_id > 0 ) {
			$link = str_ireplace( 'add_to_cart_button', '', $link );
		}
		return $link;
	}

	/**
	 * Add form to the single product page for simple products
	 *
	 * @since 1.0
	 */
	public function display_form() {
		global $product;

		// Only display on single product page or a page with [product_page] shortcode.
		if ( ! ( is_product() || is_page() ) ) {
			return;
		}

		// get form id
		$attached_form_id = $this->get_attached_form_id();

		// check to make sure we have a numeric form id
		if ( ! isset( $attached_form_id ) || ! is_numeric( $attached_form_id ) ) {
			return;
		}

		$this->add_filters_before_displaying_form_in_product();

		// if we're on the right page - enqueue our scripts
		$this->add_scripts();

		// display the form
		echo FrmFormsController::get_form_shortcode( array('id' => $attached_form_id ) );

		$this->remove_filters_after_displaying_form_in_product();
	}

	private function add_filters_before_displaying_form_in_product() {
		// hide the form tags
		add_filter( 'frm_include_form_tag', '__return_false' );

		// remove the submit button
		add_filter( 'frm_show_submit_button', '__return_false' );

		add_filter( 'frm_setup_new_fields_vars', array( $this, 'hide_total_field' ), 10, 2 );
	}

	private function remove_filters_after_displaying_form_in_product() {
		remove_filter( 'frm_include_form_tag', '__return_false' );
		remove_filter( 'frm_show_submit_button', '__return_false' );
		remove_filter( 'frm_setup_new_fields_vars', array( $this, 'hide_total_field' ), 10 );
	}


	/**
	 * Get the attached form if one exists
	 *
	 * @param int $product_id the id of the current product
	 * @return int
	 * @since 1.0
	 */
	public function get_attached_form_id( $product_id = null ) {
		global $product;

		$result = false;

		// if we don't pass in a product id we might be able to get it from the product
		if ( ! $product_id && is_object( $product ) ) {
			$product_id = $product->id;
		}

		if ( $product_id ) {
			// get form id
			$result = get_post_meta( $product_id, '_attached_formidable_form', true );
		}

		return $result;
	}

	public function hide_total_field( $values, $field ) {
		if ( wc_fp_field_is_total( $field ) ) {
			$last_total = wc_fp_form_has_total_field( $field->form_id );
			if ( $field->id == $last_total->id ) {
				$values['type'] = 'hidden';
				$values['input_class'] = isset( $values['input_class'] ) ? $values['input_class'] : '';
				$values['input_class'] .= ' frm_final_total';
			}
		}
		return $values;
	}

	/**
	 * Add the totals field to the product page
	 *
	 * @since 1.0
	 */
	public function total_field( $post_id = false ) {
		global $product;

		// get the post ID
		if ( ! $post_id ) {
			global $post;
			$post_id = $post->ID;
		}

		// get the product
		$the_product = get_product( $post_id );

		// check if the product price should be added
		$stop_price = $this->do_not_add_product_price( $post_id );
		if ( $stop_price ) {
			$price = '0';
		} else {
			$price = is_object( $the_product ) ? $the_product->get_price() : '0';
		}

		echo '<div id="formidable-addons-total" data-type="' . esc_attr( $the_product->product_type ) . '" data-price="' . esc_attr( $price ) . '"></div>';
	}


	/**
	 * Add the data to the product in the cart
	 *
	 * @since 1.0
	 */
	public function init_formidable_processing( $cart_item_meta, $product_id ) {

		// make sure there's an actual form on this page
		if ( $this->get_attached_form_id( $product_id ) ) {

			// disable the admin notification emails since most users won't need these
			remove_action( 'frm_trigger_email_action', 'FrmNotification::trigger_email', 10 );

			// send data to Formidable Forms to validate & process the form
			//formidable_forms_pre_process();
		}

		// still need to return the default cart item meta
		return $cart_item_meta;
	}


	/**
	 * Save the form submission ID
	 *
	 * @since 1.0
	 */
	public function save_form_submission_id( $entry_id ) {
		// get the form submission ID
		$this->form_submission_id = $entry_id;
	}


	/**
	 * Save the calculated values
	 * This HAS to be done during form processing since the values aren't stored anywhere else
	 *
	 * @since 1.0
	 */
	public function save_calc_totals( $entry_id ) {
		if ( is_callable('FrmProEntryMetaHelper::get_post_or_meta_value') ) {
			// get the totals
			$this->calc_totals = wc_fp_get_total_for_entry( FrmEntry::getOne( $entry_id ) );
		}
	}


	/**
	 * When added to cart, save any Formidable forms data
	 *
	 * @param mixed $cart_item_meta
	 * @param mixed $product_id
	 * @return array
	 * @since 1.0
	 */
	public function add_cart_item_data( $cart_item_meta, $product_id ) {

		// add Formidable form submission id
		$cart_item_meta[ '_formidable_form_data' ] = $this->form_submission_id;
		$cart_item_meta[ '_formidable_form_calc_data' ] = $this->calc_totals;

		return $cart_item_meta;
	}


	/**
	 * Add field data to cart item
	 *
	 * @param mixed $cart_item
	 * @param mixed $values
	 * @return void
	 * @since 1.0
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {

		// make sure this product ha some attached FP data
		if ( ! empty( $values['_formidable_form_data'] ) ) {
			// get the Formidable form data out of the session
			$cart_item['_formidable_form_data'] = $values['_formidable_form_data'];
			$cart_item['_formidable_form_calc_data'] = $values['_formidable_form_calc_data'];

			// pull the adjusted product price out of the session
			$cart_item = $this->add_cart_item( $cart_item );
		}

		return $cart_item;
	}


	/**
	 * Add Formidable Forms data to item
	 *
	 * @param mixed $other_data
	 * @param mixed $cart_item
	 * @return array
	 * @since 1.0
	 */
	public function get_item_data( $item_data, $cart_item ) {

		return $this->add_fp_submission_data_as_meta( $item_data, $cart_item );
	}


	/**
	 * Adjust price of product after adding to cart
	 *
	 * @param mixed $cart_item
	 * @return void
	 * @since 1.0
	 */
	public function add_cart_item( $cart_item ) {

		// proceed if there is some Formidable forms data
		if ( ! empty( $cart_item['_formidable_form_data'] ) ) {

			// get form submission
			$submission = FrmEntry::getOne( $cart_item['_formidable_form_data'] );

			if ( ! $submission ) {
				return $cart_item;
			}

			// find the total field
			$total_price = wc_fp_get_total_for_entry( $submission );

			// add the total value to the cart.
			if ( $this->do_not_add_product_price( $cart_item['data']->id ) ) {
				$cart_item['data']->set_price( $total_price );
			} else {
				$cart_item['data']->adjust_price( $total_price );
			}
		}

		return $cart_item;
	}

	private function do_not_add_product_price( $post_id ) {
		$stop_price = false;
		if ( $post_id ) {
			$stop_price = get_post_meta( $post_id, '_frm_stop_woo_price', true );
		}
		return $stop_price;
	}

	/**
	 * After ordering, add the data to the order line items.
	 *
	 * @param mixed $item_id
	 * @param mixed $values
	 * @since 1.0
	 */
	public function add_order_item_meta( $item_id, $values ) {

		$item_data = $this->add_fp_submission_data_as_meta( array(), $values );

		// now add the item data to the order meta
		if ( empty ( $item_data ) ) {
			return;
		}

		// add each individual item data to the order
		foreach ( $item_data as $key => $value ) {
			wc_add_order_item_meta( $item_id, strip_tags( $value['name'] ), strip_tags( $value['display'] ) );
		}

		wc_add_order_item_meta( $item_id, '_formidable_form_data', $values['_formidable_form_data'] );
	}


	/**
	 * Adjust the name of the product in the cart to include the base price
	 *
	 * @param  mixed  $product_title
	 * @param  mixed  $cart_item
	 * @param  string $cart_item_key
	 * @return string
	 * @since  1.0
	 */
	public function cart_item_name( $product_title, $cart_item, $cart_item_key ) {

		// make sure we're only targeting items that have Formidable Forms modifiers
		if ( isset( $cart_item['_formidable_form_calc_data'] ) ) {
			$form_total = $cart_item['_formidable_form_calc_data'];
			$total_price = $cart_item['data']->get_price();

			// get the product's base price and format it
			$price = $total_price - (float) $form_total;
			if ( $price > 0 ) {
				$price = wc_price( $price );

				// now let's rebuild the product title with the products base price
				$product_title = apply_filters( 'wc_fp_addons_format_product_title', sprintf( '<a href="%s">%s (%s)</a>', $cart_item['data']->get_permalink(), $cart_item['data']->get_title(), $price ), $cart_item );
			}
		}

		return $product_title;
	}


	/**
	 * Convert FP submission into cart data
	 * Adds this meta to both the cart data & order data
	 *
	 * @param array $item_data
	 * @param array $cart_item
	 * @return array
	 * @since 1.0
	 */
	public function add_fp_submission_data_as_meta( $item_data, $cart_item ) {

		// continue if there's some Formidable forms data to process
		if ( empty( $cart_item['_formidable_form_data'] ) ) {
			return $item_data;
		}

		// get form submission
		$submission = FrmEntry::getOne( $cart_item['_formidable_form_data'], true );

		// make sure there's some form data to process
		if ( empty( $submission ) ) {
			return $item_data;
		}

		// get all of the fields
		$all_fields = FrmField::get_all_for_form( $submission->form_id );

		// loop through each field and add data to item in cart
		foreach ( $all_fields as $field ) {

			// get field data
			$submitted_value = FrmProEntryMetaHelper::get_post_or_meta_value( $submission, $field );

			// hide irrelevant fp field values from appearing in the cart
			if ( $this->should_display_fp_option_in_cart( $field, $submitted_value ) ) {

				// get the label from the saved values
				$displayed_value = apply_filters( 'frm_display_value_custom', $submitted_value, $field, array() );

				// get submitted field value
				$field_calc_value = $this->get_fp_field_calc_value( $field, $submitted_value, compact('all_fields') );

				// format the submitted values to be a bit easier on the eyes
				$display = $this->display_option_in_cart( $displayed_value, $field_calc_value, $field );

				$cart_values = array(
					'name'    => '<strong>' . $field->name . '</strong>',
					'value'   => $field_calc_value,
					'display' => $display
				);

				$cart_values = $this->apply_deprecated_item_data_filter( $cart_values, $field->name );

				$item_data[] = apply_filters( 'wc_fp_cart_item_data', $cart_values, array( 'field' => $field ) );

			}
		}

		return $item_data;
	}

	/**
	 * Apply deprecated filter
	 * @since 1.04
	 */
	private function apply_deprecated_item_data_filter( $cart_values, $field_name ) {
		$cart_values = apply_filters( 'wc_fp_addons_new_item_data',
			$cart_values, $field_name, $cart_values['value'], $cart_values['display'] );

		if ( has_filter( 'wc_fp_addons_new_item_data' ) ) {
			_deprecated_function( 'The wc_fp_addons_new_item_data filter', '1.04', 'the wc_fp_cart_item_data filter' );
		}

		return $cart_values;
	}

	/**
	 * return the calc value (which is the amount that is contributed to the total) of a field
	 *
	 * @param int $field_id
	 * @param Array $calc_data
	 * @return double
	 * @since 1.0
	 */
	public function get_fp_field_calc_value( $field, $submitted_value, $args ) {

		$result = 0;

		$no_value_fields = array(
			'date', 'time', 'captcha', 'rte',
			'textarea', 'email', 'url',
			'phone',
		);

		if ( in_array( $field->type, $no_value_fields ) ) {
			return $result;
		}

		$in_calculation = $this->is_field_in_calculation( $field, $args );
		if ( ! $in_calculation ) {
			return $result;
		}

		// get the value of this option
		if ( ! empty( $submitted_value ) ) {
			foreach ( (array) $submitted_value as $amount ) {
				// get the amount from the value
				$this_amount = trim( $amount );
				preg_match_all( '/[0-9,]*\.?[0-9]+$/', $this_amount, $matches );
				$this_amount = $matches ? end( $matches[0] ) : 0;

				// format amount
				$decimal = wc_get_price_decimal_separator();
				$this_amount = str_replace( $decimal, '.', str_replace( wc_get_price_thousand_separator(), '', $this_amount ) );
				$this_amount = round( (float) $this_amount, wc_get_price_decimals() );
				$result += $this_amount;
			}
		}

		// return the value
		return $result;
	}


	/**
	 * determine if we should display the particular Formidable forms field in the cart
	 *
	 * @param array $field
	 * @param array $value
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function should_display_fp_option_in_cart( $field, $value ) {
		$display = true;

		// check fields we dont want to display
		if ( $value == '' ) {
			// hide empty fields
			$display = false;
		} else if ( wc_fp_field_is_total( $field ) ) {
			// hide the total field
			$display = false;
		} else {
			$exclude_fields = array(
				'captcha', 'rte', 'textarea', 'signature', 'user_id',
				'password', 'html', 'divider', 'file', 'image', 'form', 'tag', 'hidden',
			);
			$exclude_fields = apply_filters( 'wc_fp_exclude_fields', $exclude_fields );

			$field_values = array( $field->type, $field->id, $field->field_key );
			$exclude_field = array_intersect( $field_values, $exclude_fields );
			if ( ! empty( $exclude_field )  ) {
				$display = false;
			} else {
				$displayed_value = apply_filters( 'frm_display_value_custom', $value, $field, array() );
				if ( empty( $displayed_value ) ) {
					$display = false;
				}
			}
		}

		return $display;
	}

	private function is_field_in_calculation( $this_field, $args ) {
		$in_calc = false;
		foreach ( $args['all_fields'] as $field ) {
			if ( wc_fp_field_is_total( $field ) ) {
				$calc = $field->field_options['calc'];
				$in_calc = strpos( $field->field_options['calc'], '[' . $this_field->id . ']');
				if ( $in_calc !== false ) {
					return true;
				}

				$in_calc = strpos( $field->field_options['calc'], '[' . $this_field->field_key . ']');
				if ( $in_calc !== false ) {
					return true;
				}
			}
		}
		return $in_calc;
	}

	/**
	 * return a string displaying the Formidable forms product option in the cart
	 *
	 * @param string $field_value
	 * @param double $calc_value
	 * @param array $field
	 * @return string
	 * @since 1.0
	 */
	public function display_option_in_cart( $field_value, $calc_value, $field ) {

		// check and see if it's an array of field values
		if ( is_array( $field_value ) ) {
			// if we so we need to do some extra styling.
			$display = $this->write_list_for_cart( $field_value, $calc_value );
		} else {
			// display the field value
			$display = $field_value . $this->format_price_for_cart( $calc_value );
		}

		// return the value
		return $display = apply_filters( 'wc_fp_addons_cart_option', $display );
	}

	/**
	 * create an html list out of an array
	 *
	 * @param array $arr
	 * @param double $calc_value
	 * @return string
	 * @since 1.0
	 */
	public function write_list_for_cart( $arr, $calc_value ) {
		ob_start();
		echo $this->format_price_for_cart( $calc_value );
		?>
		<ul>
			<?php foreach( $arr as $item ): ?>
			<li><?php echo $item; ?></li>
			<?php endforeach; ?>
		</ul>
		<?php
		return ob_get_clean();
	}


	/**
	 * format the price for the cart
	 *
	 * @param double $value
	 * @return string
	 * @since 1.0
	 */
	public function format_price_for_cart( $value ) {
		$formatted = '';
		if ( ! empty( $value ) ) {
			$price = wc_price( $value );
			$formatted = apply_filters( 'wc_fp_addons_format_cart_item_price', sprintf( ' (%s)', $price ), $value );
		}

		return $formatted;
	}


	/**
	 * Add & enqueue scripts
	 *
	 * @since 1.0
	 */
	public function add_scripts() {

		wp_register_script( 'accounting', plugins_url( basename( dirname( dirname( __FILE__ ) ) ) ) . '/assets/scripts/accounting.js' );

		wp_enqueue_script( 'woocommerce-formidable-add-ons', plugins_url( basename( dirname( dirname( __FILE__ ) ) ) ) . '/assets/scripts/formidable-addons.js', array( 'jquery', 'accounting' ), false, true );

		$params = array(
			'i18n_total'                   => __( 'Total:', 'formidable-woocommerce' ),
			'currency_format_num_decimals' => absint( get_option( 'woocommerce_price_num_decimals' ) ),
			'currency_format_symbol'       => get_woocommerce_currency_symbol(),
			'currency_format_decimal_sep'  => esc_attr( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ) ),
			'currency_format_thousand_sep' => esc_attr( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ) ),
			'currency_format'              => $this->get_price_format(),
			'apply_per_qty'                => apply_filters( 'wc_frm_apply_per_qty', '__return_true' ),
		);

		wp_localize_script( 'woocommerce-formidable-add-ons', 'wc_fp_addons_params', $params );
	}


	/**
	 * Get the WooCommerce Price format and format it for JS instead of PHP
	 *
	 * @return string
	 * @since 1.0
	 */
	public function get_price_format() {
		return esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) );
	}

	public static function add_payment_trigger( $triggers ) {
		$triggers['woocommerce'] = __( 'WooCommerce payment', 'formidable-woocommerce' );
		return $triggers;
	}

	public static function add_trigger_to_action( $options ) {
		$options['event'][] = 'woocommerce';
		return $options;
	}

	/**
	 * Trigger the form actions that are set to run on completed WooCommerce payment
	 */
	public function trigger_actions_after_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		$items = $order->get_items();
		foreach ( $items as $item_id => $product ) {
			if ( isset( $product['formidable_form_data'] ) && is_numeric( $product['formidable_form_data'] ) ) {
				$entry_id = $product['formidable_form_data'];
				$entry = FrmEntry::getOne( $entry_id );
				if ( $entry ) {
					FrmFormActionsController::trigger_actions( 'woocommerce', $entry->form_id, $entry->id );
				}
			}
		}
	}

}
