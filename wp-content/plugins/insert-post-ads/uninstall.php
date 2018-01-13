<?php 
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 
$option_name = 'wporg_option';
 
delete_option($option_name);
 
// for site options in Multisite
delete_site_option($option_name);
 
// drop a custom database table
global $wpdb;
$wpdb->query("UPDATE `wp_usermeta` SET `meta_value` = '' WHERE `meta_key` = 'insert_ads_1.6_admin_notice_dismissed'");
?>