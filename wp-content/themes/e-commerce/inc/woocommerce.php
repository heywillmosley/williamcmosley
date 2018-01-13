<?php
/**
 * WooCommerce Compatibility File
 * See: https://wordpress.org/plugins/woocommerce/
 *
 * @package E-Commerce
 */

/**
 * Query WooCommerce activation
 */
if ( ! function_exists( 'is_woocommerce_activated' ) ) {
	function is_woocommerce_activated() {
		return class_exists( 'woocommerce' ) ? true : false;
	}
}

/**
 * Theme layout integration.
 * See: http://docs.woothemes.com/document/third-party-custom-theme-compatibility/#section-2
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

add_action('woocommerce_before_main_content', 'e_commerce_wrapper_start', 10);
add_action('woocommerce_after_main_content', 'e_commerce_wrapper_end', 10);

function e_commerce_wrapper_start() {
  echo '<div id="primary" class="content-area">';
}

function e_commerce_wrapper_end() {
  echo '</div>';
}

/**
 * Show cart contents (total link).
 */
if ( ! function_exists( 'e_commerce_cart_link' ) ) {
	
	function e_commerce_cart_link() {
		
		if ( is_woocommerce_activated() ) { 
		?>
		<div class="cart-contents cart_totals">
            <a href="<?php echo esc_url( WC()->cart->get_cart_url() ); ?>" title="<?php _e( 'View cart', 'e-commerce' ); ?>">
				<span class="cart-icon"></span><span class="subtotal"><?php echo wp_kses_data( WC()->cart->get_cart_subtotal() ); ?></span> <span class="count"><?php echo wp_kses_data( sprintf( _n( '%d item', '%d items', WC()->cart->get_cart_contents_count(), 'e-commerce' ), WC()->cart->get_cart_contents_count() ) );?></span>
			</a>
        </div>
		<?php
		}
	}
}