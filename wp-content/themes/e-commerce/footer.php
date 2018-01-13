<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package E-Commerce
 */

?>

	</div><!-- #content -->
	
	<?php                
	    /** 
	     * e_commerce_footer hook
	     *
	     */
	    do_action( 'e_commerce_footer' );
	?>

</div><!-- #page -->     



<?php wp_footer(); ?>

</body>
</html>
