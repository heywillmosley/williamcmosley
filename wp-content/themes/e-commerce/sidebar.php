<?php
/**
 * The sidebar containing the main widget area.
 *
 * @package E-Commerce
 */
?>
<div id="secondary" class="widget-area" role="complementary">
	<?php 
	if ( is_active_sidebar( 'sidebar-1' ) ) {
    	dynamic_sidebar( 'sidebar-1' ); 
		}	
	else { 
		//Helper Text
		if ( current_user_can( 'edit_theme_options' ) ) { ?>
			<section id="widget-default-text" class="widget widget_text">	
				<div class="widget-wrap">
                	<h4 class="widget-title"><?php _e( 'Primary Sidebar Widget Area', 'e-commerce' ); ?></h4>
           		
           			<div class="textwidget">
                   		<p><?php _e( 'This is the Primary Sidebar Widget Area.', 'e-commerce' ); ?></p>
                   		<p><?php printf( __( 'By default it will load Search and Archives widgets as shown below. You can add widget to this area by visiting your <a href="%s">Widgets Panel</a> which will replace default widgets.', 'e-commerce' ), admin_url( 'widgets.php' ) ); ?></p>
                 	</div>
           		</div><!-- .widget-wrap -->
       		</section><!-- #widget-default-text -->
		<?php
		} ?>
		<section class="widget widget_search" id="default-search">
			<div class="widget-wrap">
				<?php get_search_form(); ?>
			</div><!-- .widget-wrap -->
		</section><!-- #default-search -->
		<section class="widget widget_archive" id="default-archives">
			<div class="widget-wrap">
				<h4 class="widget-title"><?php _e( 'Archives', 'e-commerce' ); ?></h4>
				<ul>
					<?php wp_get_archives( array( 'type' => 'monthly' ) ); ?>
				</ul>
			</div><!-- .widget-wrap -->
		</section><!-- #default-archives -->
		<?php 
	} ?>

	<?php                
	    /** 
	     * e_commerce_after_sidebar hook
	     */
	    do_action( 'e_commerce_after_sidebar' );
	?>
</div><!-- #secondary -->

