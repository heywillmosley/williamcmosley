/**
 * Theme Customizer custom scripts
 */
(function($) {
    //Add Upgrade Button
    $('.preview-notice').prepend('<span id="e_commerce_upgrade"><a target="_blank" class="button btn-upgrade" href="' + e_commerce_misc_links.upgrade_link + '">' + e_commerce_misc_links.upgrade_text + '</a></span>');
    jQuery('#customize-info .btn-upgrade, .misc_links').click(function(event) {
        event.stopPropagation();
    });
})(jQuery);