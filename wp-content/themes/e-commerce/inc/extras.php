<?php
/**
 * Custom functions that act independently of the theme templates
 *
 * Eventually, some of the functionality here could be replaced by core features
 *
 * @package E-Commerce
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function e_commerce_body_classes( $classes ) {
	// Adds a class of group-blog to blogs with more than 1 published author.
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}

	return $classes;
}
add_filter( 'body_class', 'e_commerce_body_classes' );

if ( ! function_exists( 'e_commerce_custom_css' ) ) :
	/**
	 * Enqueue Custom CSS
	 *
	 * @uses  get_theme_mod
	 *
	 * @action wp_head
	 *
	 * @since E-commerce 1.0.1
	 */
	function e_commerce_custom_css() {
		if( $e_commerce_custom_css = get_theme_mod( 'custom_css' ) ) {
				echo '<!-- refreshing cache -->' . "\n";

				echo '<!-- '.get_bloginfo('name').' inline CSS Styles -->' . "\n" . '<style type="text/css" media="screen">' . "\n" . $e_commerce_custom_css;

				echo '</style>' . "\n";
			}

	}
endif; //e_commerce_custom_css
add_action( 'wp_head', 'e_commerce_custom_css', 101 );

if ( ! function_exists( 'e_commerce_get_logo' ) ) :
	/**
	 * Get the logo
	 *
	 * @get logo from options
	 *
	 * @since E-commerce 1.0.5
	 */
	function e_commerce_get_logo() {
		$output = '';
		//Checking Logo
		if ( function_exists( 'has_custom_logo' ) ) {
			if ( has_custom_logo() ) {
				$output = '
				<div class="site-logo">'. get_custom_logo() . '</div><!-- #site-logo -->';
			}
		}
		return $output;
	}
endif; // e_commerce_get_logo