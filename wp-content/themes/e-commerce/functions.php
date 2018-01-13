<?php
/**
 * E-Commerce functions and definitions
 *
 * @package E-Commerce
 */

if ( ! function_exists( 'e_commerce_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function e_commerce_setup() {
	/*
	 * Make theme available for translation.
	 * Translations can be filed in the /languages/ directory.
	 * If you're building a theme based on E-Commerce, use a find and replace
	 * to change 'e-commerce' to the name of your theme in all the template files
	 */
	load_theme_textdomain( 'e-commerce', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	// Add Styles the visual editor to resemble the theme style.
	add_editor_style( array( 'css/editor-style.css', e_commerce_fonts_url() ) );

	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support( 'title-tag' );

	//@remove Remove check when WordPress 4.8 is released
	if ( function_exists( 'has_custom_logo' ) ) {
		/**
		* Setup Custom Logo Support for theme
		* Supported from WordPress version 4.5 onwards
		* More Info: https://make.wordpress.org/core/2016/03/10/custom-logo/
		*/
		add_theme_support( 'custom-logo' );
	}

	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
	 */
	add_theme_support( 'post-thumbnails' );

	//Featured Image for singlular ( Ratio 16:9 )
	add_image_size( 'e-commerce-single', '890', '501', true );

	// Enable WooCommerce support.
	add_theme_support( 'woocommerce' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus( array(
		'primary' => esc_html__( 'Primary Menu', 'e-commerce' ),
		'social'  => esc_html__( 'Social Menu', 'e-commerce' ),
	) );

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	) );

	/*
	 * Enable support for Post Formats.
	 * See http://codex.wordpress.org/Post_Formats
	 */
	add_theme_support( 'post-formats', array(
		'aside',
		'image',
		'video',
		'quote',
		'link',
	) );

	// Set up the WordPress core custom background feature.
	add_theme_support( 'custom-background', apply_filters( 'e_commerce_custom_background_args', array(
		'default-color' => 'ffffff',
		'default-image' => '',
	) ) );
}
endif; // e_commerce_setup
add_action( 'after_setup_theme', 'e_commerce_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function e_commerce_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'e_commerce_content_width', 700 );
}
add_action( 'after_setup_theme', 'e_commerce_content_width', 0 );

/**
 * Register widget area.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_sidebar
 */
function e_commerce_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'e-commerce' ),
		'id'            => 'sidebar-1',
		'description'   => '',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'e_commerce_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function e_commerce_scripts() {
	wp_enqueue_style( 'e-commerce-style', get_stylesheet_uri(), array(), '1.0.0' );

	wp_enqueue_style( 'e-commerce-fonts', e_commerce_fonts_url(), array(), '1.0.0' );

	wp_enqueue_style( 'e-commerce-icons', get_template_directory_uri() . '/css/typicons.css', array(), '1.0.0' );

	wp_enqueue_script( 'e-commerce-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '1.0.0', true );

	wp_enqueue_script( 'e-commerce-helpers', get_template_directory_uri() . '/js/helpers.js', array( 'jquery' ), '1.0.0', true );

	wp_enqueue_script( 'e-commerce-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '1.0.0', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// Localize script (only few lines in helpers.js)
    wp_localize_script( 'e-commerce-helpers', 'placeholder', array(
 	    'author'   => __( 'Name', 'e-commerce' ),
 	    'email'    => __( 'Email', 'e-commerce' ),
		'url'      => __( 'URL', 'e-commerce' ),
		'comment'  => __( 'Comment', 'e-commerce' )
 	) );
}
add_action( 'wp_enqueue_scripts', 'e_commerce_scripts' );

/**
 * Google fonts.
 */
function e_commerce_fonts_url() {
	$font_url = '';
	/*
	 * Translators: If there are characters in your language that are not supported
	 * by chosen font(s), translate this to 'off'. Do not translate into your own language.
	 */
	if ( 'off' !== _x( 'on', 'Google fonts: on or off', 'e-commerce' ) ) {
		$font_url = add_query_arg( 'family', urlencode( 'Dosis:300,400,500,600,700,800|Ubuntu:300,400,500,700,300italic,400italic,500italic,700italic&subset=latin,cyrillic' ), "//fonts.googleapis.com/css" );
	}

	return $font_url;
}

/**
 * Enqueue Google fonts style to admin screen for custom header display.
 */
function e_commerce_admin_fonts() {
	wp_enqueue_style( 'e-commerce-font', e_commerce_fonts_url(), array(), '1.0.0' );
}
add_action( 'admin_print_scripts-appearance_page_custom-header', 'e_commerce_admin_fonts' );

/**
 * Implement the Custom Header feature.
 */
//require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
require get_template_directory() . '/inc/jetpack.php';

/**
 * Load WooCommerce compatibility file.
 */
require get_template_directory() . '/inc/woocommerce.php';