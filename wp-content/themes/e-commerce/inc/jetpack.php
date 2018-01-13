<?php
/**
 * Jetpack Compatibility File
 * See: https://jetpack.me/
 *
 * @package E-Commerce
 */

/**
 * Jetpack support
 */
function e_commerce_jetpack_setup() {
    /**
     * Add theme support for infinite scroll.
     */
	add_theme_support( 'infinite-scroll', array(
		'container' => 'main',
		'render'    => 'e_commerce_infinite_scroll_render',
		'footer'    => 'page',
	) );	
    /**
     * Add theme support for responsive videos.
     */
    add_theme_support( 'jetpack-responsive-videos' );	
} // end function e_commerce_jetpack_setup
add_action( 'after_setup_theme', 'e_commerce_jetpack_setup' );

/**
 * Custom render function for Infinite Scroll.
 */
function e_commerce_infinite_scroll_render() {
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/content', get_post_format() );
	}
} // end function e_commerce_infinite_scroll_render