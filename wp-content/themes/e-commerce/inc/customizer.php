<?php
/**
 * E-Commerce Theme Customizer
 *
 * @package E-Commerce
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function e_commerce_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	//Theme Options
	$wp_customize->add_panel( 'e_commerce_theme_options', array(
	    'description'    => __( 'Basic theme Options', 'e-commerce' ),
	    'capability'     => 'edit_theme_options',
	    'priority'       => 200,
	    'title'    		 => __( 'Theme Options', 'e-commerce' ),
	) );

	// Custom CSS Option
	$wp_customize->add_section( 'e_commerce_custom_css', array(
		'description'	=> __( 'Custom/Inline CSS', 'e-commerce'),
		'panel'  		=> 'e_commerce_theme_options',
		'priority' 		=> 1,
		'title'    		=> __( 'Custom CSS Options', 'e-commerce' ),
	) );

	$wp_customize->add_setting( 'custom_css', array(
		'capability'		=> 'edit_theme_options',
		'sanitize_callback' => 'e_commerce_sanitize_custom_css',
	) );

	$wp_customize->add_control( 'custom_css', array(
			'label'		=> __( 'Enter Custom CSS', 'e-commerce' ),
	        'priority'	=> 1,
			'section'   => 'e_commerce_custom_css',
	        'settings'  => 'custom_css',
			'type'		=> 'textarea',
	) ) ;
}
add_action( 'customize_register', 'e_commerce_customize_register' );

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function e_commerce_customize_preview_js() {
	wp_enqueue_script( 'e_commerce_customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '1.0.0', true );
}
add_action( 'customize_preview_init', 'e_commerce_customize_preview_js' );

/**
 * Custom scripts and styles on customize.php
 *
 * @since E-Commerce 1.0.5
 */
function e_commerce_customize_scripts() {
	wp_register_script( 'e_commerce_customizer_custom', get_template_directory_uri() . '/js/customizer-custom-scripts.js', array( 'jquery' ), '20131028', true );

	$e_commerce_misc_links = array(
							'upgrade_link' 				=> esc_url( 'http://catchthemes.com/themes/e-commerce-pro/' ),
							'upgrade_text'	 			=> __( 'Upgrade To Pro &raquo;', 'e-commerce' ),
						);

	//Add Upgrade Button via localized script
	wp_localize_script( 'e_commerce_customizer_custom', 'e_commerce_misc_links', $e_commerce_misc_links );

	wp_enqueue_script( 'e_commerce_customizer_custom' );

	wp_enqueue_style( 'e_commerce_customizer_custom', get_template_directory_uri() . '/css/customizer.css');
}
add_action( 'customize_controls_enqueue_scripts', 'e_commerce_customize_scripts' );


/**
 * Sanitizes Custom CSS 
 * @param  $input entered value
 * @return sanitized output
 *
 * @since  E-Commerce 1.0.1
 */
function e_commerce_sanitize_custom_css( $input ) {
	if ( $input != '' ) { 
        $input = str_replace( '<=', '&lt;=', $input ); 
        
        $input = wp_kses_split( $input, array(), array() ); 
        
        $input = str_replace( '&gt;', '>', $input ); 
        
        $input = strip_tags( $input ); 

        return $input;
 	}
    else {
    	return '';
    }
}
