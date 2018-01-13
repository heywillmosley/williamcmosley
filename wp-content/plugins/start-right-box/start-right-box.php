<?php
/**
 * Plugin Name: Start Right Box
 * Author: William Mosley, III
 * Version: 0.1.0
 * Description: A beautiful box to implement custom hooks, actions and functions specific to Start Right.
 */
 

// Better login security
add_filter( 'login_errors', 'modify_login_errors' );
function modify_login_errors() {
    return 'Login unsuccessful, try again.';
}

add_shortcode('current_credits', 'current_credits_func');
function current_credits_func( $atts ) {
    global $wpdb;
    $uid = get_current_user_id();
    $credits = $wpdb->get_var("SELECT `meta_value` FROM `wp_usermeta` WHERE `meta_key` = '_download_credits' AND `user_id` = $uid");
    
    if( empty( $credits ) ) {
        $credits = 0;
    }
    
    $render = "<strong>$credits</strong> HRS LEFT - ";
    
    return $render;
    
}


// Date in 30 Dates
add_shortcode('date_in_30_days', 'date_in_30_days_func');
function date_in_30_days_func() {
    return date('m/d/Y', strtotime("+30 days"));
}