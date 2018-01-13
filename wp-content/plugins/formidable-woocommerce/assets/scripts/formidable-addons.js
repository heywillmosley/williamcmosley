jQuery(document).ready(function($) {

	function init_fp_addon_totals() {

		// monitor any changes that go on in a Formidable form on the product page right before the add to cart button
		$('.product .cart').on( 'change', 'input', function() {
			wc_fp_update_totals();
		});

		// Hide default product price
		$('.product .price, .single_variation_wrap .single_variation').hide();

		// display the FP totals right away
		wc_fp_update_totals();

		// watch the variation form and any time there's a change save the new variation price
		$('.variations_form').on('found_variation', function( event, variation ) {
			wc_fp_save_variation_price( $(this), variation );
		});

	}
	init_fp_addon_totals();


	// take the passed in calculated value and display it on the front end
	function wc_fp_update_totals() {

		// get the Formidable forms calcuated value
		calc_value = $('.product .cart input.frm_final_total').val();

		// cache the jquery selector
		var $cart = $('.product form.cart');
		var $fp_totals = $cart.find("#formidable-addons-total");

		// get the base product price
		var base_value = wc_fp_get_base_price( $cart, $fp_totals );

		// some people may want to apply addons irrespective of quantity
		// in that case use the filter '' to change this value to false
		if ( wc_fp_addons_params.apply_per_qty ) {
			// determine the quantity
			var qty = parseFloat( $cart.find('input.qty').val() );
			if ( qty <= 0 || isNaN( qty ) ) {
				qty = 1;
			}

			// multiple the quantity of products ordered by the add on cost
			calc_value *= qty;
		}

		// get the new html
		var html = wc_fp_create_html( calc_value, base_value );

		// make sure we have some html
		if ( html ) {
			// display the totals area
			$fp_totals.html( html );
		}
		
	}


	// when a variation selected get the price and store it
	function wc_fp_save_variation_price( $variation_form, variation ) {
		
		var $totals = $('#formidable-addons-total');

		if ( $( variation.price_html ).find('.amount:last').size() ) {
			product_price = $( variation.price_html ).find('.amount:last').text();
			product_price = product_price.replace( wc_fp_addons_params.currency_format_thousand_sep, '' );
			product_price = product_price.replace( wc_fp_addons_params.currency_format_decimal_sep, '.' );
			product_price = product_price.replace(/[^0-9\.]/g, '');
			product_price = parseFloat( product_price );

			$totals.data( 'price', product_price );
		}
		$variation_form.trigger('woocommerce-formidable-product-addons-update');

		// update the totals
		wc_fp_update_totals();
		
	}


	// get the products base price
	function wc_fp_get_base_price( $cart, $fp_totals ) {

		// get the base price (already saved in the html)
		var result = $fp_totals.data( 'price' );

		// get the quantity from the quantity field
		var qty = parseFloat( $cart.find('input.qty').val() );

		// make sure we have both a quantity and base price
		if ( result > 0 && qty > 0 ) {
			result = parseFloat( result * qty );
		}

		return result;
	}


	// create the HTML for totals area
	function wc_fp_create_html( calc_value, base_value ) {

		var total = parseFloat( base_value ) + parseFloat( calc_value );

		var formatted_addon_total = accounting.formatMoney( total, {
			symbol 		: wc_fp_addons_params.currency_format_symbol,
			decimal 	: wc_fp_addons_params.currency_format_decimal_sep,
			thousand	: wc_fp_addons_params.currency_format_thousand_sep,
			precision 	: wc_fp_addons_params.currency_format_num_decimals,
			format		: wc_fp_addons_params.currency_format
		} );

		result = "<p class='price fp-product-addon-totals'><span class='fp-product-addon-label'>" + wc_fp_addons_params.i18n_total + "</span> <span class='amount fp-product-addon-amount'>" + formatted_addon_total + "</span></p>";

		return result;
	}

});