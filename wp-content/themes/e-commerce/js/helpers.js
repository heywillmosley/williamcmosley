(function($) {
	$(document).ready(function() {
		/*
		 * Comment placeholders
		 * (Localized via wp_localize_script in functions file)
		 */
		$( '#author' ).attr( 'placeholder','Name' );
		$( '#email' ).attr( 'placeholder','Email' );
		$( '#url' ).attr( 'placeholder','URL' );
		$( '#comment' ).attr( 'placeholder','Comment' );
		
		/*
		 * Responsive navigation
		 */
        $('.menu-toggle').sidr({
            name: 'sidr-main',
		    side: 'right',
		    renaming: false,
            source: '.main-navigation'
		});
	});
})(jQuery);
