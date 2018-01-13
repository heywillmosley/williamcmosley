<?php
// Defines
define( 'FL_CHILD_THEME_DIR', get_stylesheet_directory() );
define( 'FL_CHILD_THEME_URL', get_stylesheet_directory_uri() );

// Classes
require_once 'classes/class-fl-child-theme.php';

// Actions
add_action( 'fl_head', 'FLChildTheme::stylesheet' );

// Currently doing
function currently_doing_func( $atts ){
    
    /* This sets the $time variable to the current hour in the 24 hour clock format */
    date_default_timezone_set('US/Eastern');
    $date = date("Y-m-d");
    $time = date("H");
    /* Set the $timezone variable to become the current timezone */
    $timezone = date("e");
    
    /* If the time is less than 1200 hours, show good morning */
    if ($time < "12") {
        $time_of_day = "Good morning";
    } else
    /* If the time is grater than or equal to 1200 hours, but less than 1700 hours, so good afternoon */
    if ($time >= "12" && $time < "17") {
        $time_of_day = "Good afternoon";
    } else
    /* Should the time be between or equal to 1700 and 1900 hours, show good evening */
    if ($time >= "17" && $time < "19") {
        $time_of_day = "Good evening";
    } else
    /* Finally, show good night if the time is greater than or equal to 1900 hours */
    if ($time >= "19") {
        $time_of_day = "Good night";
    }
    
  
   if($time >= 0 && $time <= 6) {
           $doing = "sleeping. Dreaming about the next big move......";
    }
    elseif($time >= 22 && $time <= 24) {
           $doing = "snoring, waiting on Michael J Sullivan's next novel: Age of War......";
    }
    // The weekday 
   elseif (date('N', strtotime($date)) <= 5) {
       
       if($time >= 6 && $time <= 7) {
           $doing = "freelancing on high profile projects.";
       }
       elseif($time >= 8 && $time <= 17) {
           $doing = "building something epic as Creative Director at New Human.";
       }

       elseif($time >= 20 && $time <= 22) {
           $doing = "freelancing.";
       }
       else {
           $doing = "hanging out with my wife and kids.";
       }
   }
   else {

       if($time >= 8 && $time <= 17) {
           $doing = "on a weekend adventure with Laurah, Eyden & King!";
       }
       elseif($time >= 18 && $time <= 22) {
           $doing = "freelancing on high profile projects.";
       }else {
           $doing = "hanging out with my wife and kids.";
       }
   }
    
	return "<p class='currently_doing'> $time_of_day, I'm currently $doing</p>";
}
add_shortcode( 'currently_doing', 'currently_doing_func' );

add_action( 'template_redirect', 'wc_custom_redirect_after_purchase' ); 
	function wc_custom_redirect_after_purchase() {
		global $wp;
		
		if ( is_checkout() && ! empty( $wp->query_vars['order-received'] ) ) {
			wp_redirect( '/thanks' );
			exit;
		}
	}
	

add_action('frm_after_create_entry', 'after_entry_created', 30, 2);

function after_entry_created($entry_id, $form_id){

    if($form_id == 2){ //change 5 to the ID of your form
    
    $current_user = wp_get_current_user();
    
    //$current_user->remove_role('rolea'); //change rolea to the role you want to remove
    
    $current_user->add_role('ready'); // change roleb to the role you want to add
    
    }
}