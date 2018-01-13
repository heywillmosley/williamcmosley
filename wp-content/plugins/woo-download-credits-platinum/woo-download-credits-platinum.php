<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Plugin Name: Woo Credits Platinum
 * Plugin URI: http://muth.co/
 * Version: 2.1.0
 * Description: A powerful plugin for Woo Commerce users that lets you create credit packages, which your customers purchase, then redeem towards products in your Woo Commerce store.
 * Author: http://woocredits.com/
 * Author URI: http://woocredits.com/
 * Text Domain: mwdcp
 */
// Load the API Key library if it is not already loaded. Must be placed in the root plugin file.
if (!class_exists('MuthCo_WooCredits_License_Menu')) {
    // Uncomment next line if this is a plugin
    require_once( plugin_dir_path(__FILE__) . 'am-license-menu.php' );

    // Uncomment next line if this is a theme
    // require_once( get_stylesheet_directory() . '/am-license-menu.php' );

    /**
     * @param string $file             Must be __FILE__ from the root plugin file, or theme functions file.
     * @param string $software_title   Must be exactly the same as the Software Title in the product.
     * @param string $software_version This product's current software version.
     * @param string $plugin_or_theme  'plugin' or 'theme'
     * @param string $api_url          The URL to the site that is running the API Manager. Example: https://www.toddlahman.com/
     *
     * @return \AM_License_Submenu|null
     */
    MuthCo_WooCredits_License_Menu::instance(__FILE__, 'Woo Credits Platinum', '2.1.0', 'plugin', 'http://www.woocredits.com/');
}

if (!class_exists('WC_Dependencies')) {
    require_once( plugin_dir_path(__FILE__) . '/includes/class-wc-dependencies.php');
}

if (!function_exists('is_woocommerce_active')) {

    function is_woocommerce_active() {
        return WC_Dependencies::woocommerce_active_check();
    }

}

add_action('plugins_loaded', 'wdcp_custom_product_type_cb');

function wdcp_custom_product_type_cb() {
    if (class_exists('WC_Product')):

        class Woo_Download_Credits_Platinum_Product_Credits extends WC_Product {

            public $virtual = 'yes';
            public $downloadable = 'yes';
            public $sold_individually = 'yes';
            public $product_type = 'credits';
            protected $post_type = 'product';

            public function __construct($product) {
                parent::__construct($product);
                $this->product_type = 'credits';
            }

            public function exists() {
                return true;
            }

            public function get_title() {
                $prod_id = ( WC()->version < '2.7.0' ) ? $this->id : $this->get_id();
                $credit_name = get_post_meta($prod_id, '_credit_name', true);
                return $credit_name;
            }

            public function get_image($size = 'shop_thumbnail', $attr = array(), $placeholder = true) {
                $prod_id = ( WC()->version < '2.7.0' ) ? $this->id : $this->get_id();
                $image_id = get_post_meta($prod_id, '_credit_image', true);
                if ($image_id) {
                    $img = wp_get_attachment_image_src($image_id);
                    if ($img && is_array($img)) {
                        $image = '<img class="" src="' . $img[0] . '" >';
                    }
                } else {
                    $image = wc_placeholder_img($size);
                }
                return $image;
            }

            public function is_purchasable() {
                return true;
            }

            public function is_visible() {
                return false;
            }

            public function set_product_visibility($opt) {
                $prod_id = ( WC()->version < '2.7.0' ) ? $this->id : $this->get_id();
                if (method_exists($this, 'set_catalog_visibility')) {
                    $this->set_catalog_visibility($opt);
                } else {
                    update_post_meta($prod_id, '_visibility', "hidden");
                }
            }

        }

        endif;

    if (class_exists('WC_Product_Data_Store_CPT')):

        class WC_Product_Credits_Data_Store_CPT extends WC_Product_Data_Store_CPT {

            public function read(&$product) {
                $product->set_defaults();

                if (!$product->get_id() || !( $post_object = get_post($product->get_id()) ) || 'credit' !== $post_object->post_type) {
                    throw new Exception(__('Invalid product.', 'woocommerce'));
                }

                $id = $product->get_id();

                $product->set_props(array(
                    'name' => $post_object->post_title,
                    'slug' => $post_object->post_name,
                    'date_created' => 0 < $post_object->post_date_gmt ? wc_string_to_timestamp($post_object->post_date_gmt) : null,
                    'date_modified' => 0 < $post_object->post_modified_gmt ? wc_string_to_timestamp($post_object->post_modified_gmt) : null,
                    'status' => $post_object->post_status,
                    'description' => $post_object->post_content,
                    'short_description' => $post_object->post_excerpt,
                    'parent_id' => $post_object->post_parent,
                    'menu_order' => $post_object->menu_order,
                    'reviews_allowed' => 'open' === $post_object->comment_status,
                ));

                $this->read_attributes($product);
                $this->read_downloads($product);
                $this->read_visibility($product);
                $this->read_product_data($product);
                $this->read_extra_data($product);
                $product->set_object_read(true);
            }

        }

        endif;


    if (class_exists('WC_Payment_Gateway')):

        class Woo_Download_Credits_Platinum_Gateway extends WC_Payment_Gateway {

            public function __construct() {
                $this->id = 'wdc_woo_credits';
                $this->icon = apply_filters('woocommerce_cod_icon', '');
                $this->method_title = __('Woo Credits', 'mwdcp');
                $this->method_description = __('Have your customers pay with their Woo Credits balance.', 'mwdcp');
                $this->has_fields = false;
                $this->init_form_fields();
                $this->init_settings();
                $this->supports = array(
                    'refunds'
                );
                foreach ($this->settings as $setting_key => $value) {
                    $this->$setting_key = $value;
                }
                if (is_admin()) {
                    if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
                    } else {
                        add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
                    }
                    // add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
                    // add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                }
            }

            public function init_form_fields() {
                $label_only = get_option('mwdcp_credits_label');
                $label_only = trim($label_only);
                $label_only = empty($label_only) ? __('Credits', 'mwdcp') : $label_only;
                // $def_desc = esc_html__( 'Pay using your %1$s.', 'mwdcp' , $label_only );

                $def_desc = sprintf(__('Pay using your %s.', 'mwdcp'), $label_only);

                $title_desc = sprintf(__('Woo %s.', 'mwdcp'), $label_only);

                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable Woo Credits', 'mwdcp'),
                        'label' => __('Enable Woo Credits', 'mwdcp'),
                        'type' => 'checkbox',
                        'description' => '',
                        'default' => 'yes'
                    ),
                    'title' => array(
                        'title' => __('Title', 'mwdcp'),
                        'type' => 'text',
                        'description' => __('Payment method description that the customer will see on your checkout.', 'mwdcp'),
                        'default' => $title_desc,
                        'desc_tip' => true,
                    ),
                    'description' => array(
                        'title' => __('Description', 'mwdcp'),
                        'type' => 'textarea',
                        'description' => __('Payment method description that the customer will see on your website.', 'mwdcp'),
                        'default' => $def_desc,
                        'desc_tip' => true,
                    ),
                );
            }

            public function process_payment($order_id) {

                $label_only = get_option('mwdcp_credits_label');
                $label_only = trim($label_only);
                $label_only = empty($label_only) ? __('Credits', 'mwdcp') : $label_only;
                if (Woo_Download_Credits_Platinum::cart_buying_credits()) {
                    wc_add_notice('<strong>' . __('Payment error', 'mwdcp') . ':</strong> ' . __('You can not purchase', 'mwdcp') . ' ' . $label_only . ' ' . __('with', 'mwdcp') . ' ' . $label_only . ' ' . __('Please choose another payment method.', 'mwdcp') . __('IMPORTANT: To successfully purchase', 'mwdcp') . ' ' . $label_only . ', ' . __('please make sure there are', 'mwdcp') . ' <a href="' . get_permalink(wc_get_page_id('cart')) . '" style="text-decoration: underline;">' . __('no other products', 'mwdcp') . '</a>' . __('in your cart upon checkout', 'mwdcp'), 'error');
                    return;
                }

                $shipping_credits = Woo_Download_Credits_Platinum::cart_get_shipping_credits();
                $total_credits_amount = 0;

                $total_credits_amount = get_post_meta($order_id, '_credits_used', true);



                $order = wc_get_order($order_id);
                $user_id = ( WC()->version < '2.7.0' ) ? $order->user_id : $order->get_user_id();

                if ($shipping_credits) {
                    $total_credits_amount += $shipping_credits;
                }

                $download_credits = floatval(get_user_meta($user_id, "_download_credits", true));


                //  $cart_total = floatval(WC()->cart->total);

                if ($total_credits_amount > $download_credits) {
                    wc_add_notice('<strong>' . __('Payment error', 'mwdcp') . ':</strong> ' . __('Insufficient', 'mwdcp') . ' ' . $label_only . '. ' . __('Please purchase more', 'mwdcp') . ' ' . $label_only . ' ' . __('or use a different payment method.', 'mwdcp'), 'error');
                    return;
                }


                $new_user_download_credits = $download_credits - $total_credits_amount;



                if ($user_id && !get_post_meta($order_id, '_credits_removed', true)) {
                    if ($credits = get_post_meta($order_id, '_credits_used', true)) {
                        Woo_Download_Credits_Platinum::remove_credits($user_id, $credits);
                    }
                    update_post_meta($order_id, '_credits_removed', 1);
                }


                $user_download_credits = get_user_meta($user_id, '_download_credits', true);
                $user_download_credits = absint($user_download_credits);
                $new_user_download_credits = absint($new_user_download_credits);

                if ($user_download_credits != $new_user_download_credits) {
                    wc_add_notice('<strong>' . __('System error', 'mwdcp') . ':</strong>' . __('There was an error procesing the payment. Please try another payment method.', 'mwdcp'), 'error');
                    return;
                }

                $user_download_credits = get_user_meta($user_id, '_download_credits', true);
                $user_download_credits = absint($user_download_credits);

                $order_status_processing = get_option('mwdcp_order_status_processing');
                $order_status2 = 'completed';
                if ($order_status_processing) {
                    $order_status2 = 'processing';
                }

                $order->update_status($order_status2, __('Payment completed use Download', 'mwdcp') . ' ' . $label_only);

                $user_download_credits = get_user_meta($user_id, '_download_credits', true);

//                if (!$order_status_processing) {
//                    if (WC()->version < '3.0') {
//                        $order->reduce_order_stock();
//                    } else {
//                        wc_reduce_stock_levels($order->get_id());
//                    }
//                }


                WC()->cart->empty_cart();
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            }

            public function process_refund($order_id, $amount = null, $reason = '') {

                $refnd = Woo_Download_Credits_Platinum::order_refund_credits($order_id, $amount);

                if (!$refnd) {
                    $err = 'Some error in refunding';
                    return new WP_Error('error', $err);
                }

                return true;
            }

            public function is_available() {
                $is_available = ( 'yes' === $this->enabled );
                if (Woo_Download_Credits_Platinum::cart_using_credits()) {
                    $is_available = true;
                } else {
                    $is_available = false;
                }
                return $is_available;
            }

            public function get_icon() {
                $link = null;
                global $woocommerce;
                $label_only = get_option('mwdcp_credits_label');
                $label_only = trim($label_only);
                $label_only = empty($label_only) ? __('Credits', 'mwdcp') : $label_only;
                $download_credits = get_user_meta(get_current_user_id(), '_download_credits', true);
                return apply_filters('woocommerce_gateway_icon', ' | ' . __('Your Current Balance', 'mwdcp') . ': <strong>' . $download_credits . ' </strong> | <a class="buy-more-credits" href="' . get_permalink(wc_get_page_id('myaccount')) . '">' . __('Buy More', 'mwdcp') . ' ' . $label_only . '</a>', $this->id);
            }

        }

        endif;
}

if (!function_exists('wdcp_get_credit_products')) {

    function wdcp_get_credit_products() {
        $credit_tiers = get_posts(array('post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'ASC', 'meta_key' => '_credit_product', 'meta_value' => 1,));
        if ($credit_tiers) {
            return $credit_tiers;
        }
        return false;
    }

}


if (!function_exists('wdcp_get_credit_posts')) {

    function wdcp_get_credit_posts() {
        $credit_tiers = get_posts(array('post_type' => 'credit', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'ASC'));
        if ($credit_tiers) {
            return $credit_tiers;
        }
        return false;
    }

}


if (!function_exists('wdcp_is_credit_product')) {

    function wdcp_is_credit_product($post_id) {
        $credit_product = get_post_meta($post_id, '_credit_product', true);
        if ($credit_product) {
            return true;
        }
        return false;
    }

}

if (!function_exists('wdcp_product_has_credits')) {

    function wdcp_product_has_credits($post_id) {
        $credits_amount = get_post_meta($post_id, '_credits_amount', true);
        if ($credits_amount) {
            return true;
        }
        return false;
    }

}


if (is_woocommerce_active()) {

    class Woo_Download_Credits_Platinum {

        public static $_instance = null;
        private static $signup_option_changed = false;
        private static $guest_checkout_option_changed = false;
        protected $cart_has_noncredits = false;
        protected $cart_has_credits = false;
        protected $cart_product_noncredits = false;
        protected $cart_product_credits = false;
        protected $cart_using_credits = false;
        protected $cart_buying_credits = false;

        public function __construct() {
            add_action('plugins_loaded', array($this, 'load_textdomain'));
            add_action('plugins_loaded', array($this, 'wdcp_init'), 10);
            add_action('admin_enqueue_scripts', array($this, 'register_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'register_styles'));
            add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_woocommerce_style'));
            add_action('wp', array($this, 'credits_buy_form_handler'));
            add_action('woocommerce_before_my_account', array($this, 'before_my_account'));
            add_filter('woocommerce_add_cart_item', array($this, 'add_cart_item'), 10, 1);
            add_action('init', array($this, 'cp_credit_init'));
            add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
            add_filter('woocommerce_get_price_html', array($this, 'variable_get_price_html'), 10, 2);
            // add_filter('woocommerce_variable_price_html', array( $this,'variable_price_html'), 10, 2);
            add_filter('woocommerce_get_variation_price_html', array($this, 'get_variation_price_html'), 10, 2);
            add_action('woocommerce_product_options_general_product_data', array($this, 'add_custom_general_fields'));
            add_action('woocommerce_subscriptions_product_options_pricing', array($this, 'subscriptions_product_options_pricing'));
            add_action('woocommerce_process_product_meta', array($this, 'add_custom_general_fields_save'));
            add_action('woocommerce_product_options_lottery', array($this, 'add_product_options_lottery'));
            add_action('wp_insert_post', array($this, 'product_custom_meta_data_save'));
            add_action('save_post', array($this, 'product_custom_meta_data_save'));
            add_action('woocommerce_product_after_variable_attributes', array($this, 'variation_settings_fields'), 10, 3);
            add_action('woocommerce_save_product_variation', array($this, 'save_variation_settings_fields'), 10, 2);
            add_filter('woocommerce_product_class', array($this, 'woocommerce_product_class_for_credits'), 10, 4);
            add_filter('woocommerce_get_cart_item_from_session', array($this, 'get_cart_item_from_session'), 10, 3);
            add_action('woocommerce_order_status_completed', array($this, 'order_status_completed_remove_credits'));
            add_action('woocommerce_order_status_completed', array($this, 'order_status_completed_add_credits'));
            add_filter('woocommerce_payment_gateways', array($this, 'wdc_woo_credits_init_gateway'));
            add_action('woocommerce_single_product_summary', array($this, 'single_product_summary'), 31);
            add_filter('woocommerce_get_price_html', array($this, 'get_price_html'), 100, 2);
            add_filter('woocommerce_cart_total', array($this, 'cart_total'), 10, 1);
            add_filter('woocommerce_cart_subtotal', array($this, 'cart_subtotal'), 10, 3);
            add_filter('woocommerce_cart_item_quantity', array($this, 'cart_item_quantity'), 10, 2);
            add_filter('woocommerce_checkout_cart_item_quantity', array($this, 'checkout_cart_item_quantity'), 10, 3);
            add_filter('woocommerce_cart_item_subtotal', array($this, 'cart_item_subtotal'), 10, 3);
            add_filter('woocommerce_get_formatted_order_total', array($this, 'get_formatted_order_total'), 10, 2);
            add_filter('wpo_wcpdf_woocommerce_totals', array($this, 'wcpdf_woocommerce_totals'), 11, 3);
            add_action('woocommerce_thankyou', array($this, 'thankyou_wdc_woo_credits'), 10, 1);
            add_filter('woocommerce_order_formatted_line_subtotal', array($this, 'order_formatted_line_subtotal'), 10, 3);
            add_filter('woocommerce_order_subtotal_to_display', array($this, 'order_subtotal_to_display'), 10, 3);
            add_filter('woocommerce_get_item_count', array($this, 'get_item_count'), 10, 3);
            add_filter('woocommerce_order_item_quantity_html', array($this, 'order_item_quantity_html'), 10, 2);
            add_filter('tribe_events_wootickets_ticket_price_html', array($this, 'wootickets_ticket_price_html'), 100, 3);
            add_filter('woocommerce_order_amount_item_total', array($this, 'order_amount_item_total'), 10, 5);
            add_filter('woocommerce_order_get_items', array($this, 'order_get_items'), 10, 2);
            // add_action('woocommerce_add_order_item_meta',array($this,'add_order_item_meta'),10,3);

            add_action('woocommerce_order_item_meta', array($this, 'order_item_meta'), 10, 2);

            add_filter('woocommerce_is_purchasable', array($this, 'credit_product_is_purchasable'), 10, 2);
            add_filter('woocommerce_variation_is_purchasable', array($this, 'variation_is_purchasable'), 10, 2);
            add_filter('woocommerce_variation_is_visible', array($this, 'variation_is_visible'), 10, 4);

            //   add_filter('woocommerce_product_get_price', array($this, 'product_get_price'), 99, 2);

            add_filter('woocommerce_cart_needs_payment', array($this, 'credit_product_cart_needs_payment'), 10, 2);

            add_action('gform_loaded', array($this, 'wdcp_gravity_forms_load'), 5);

            add_action('woocommerce_new_order_item', array($this, 'new_order_item_meta'), 10, 3);

            add_action('woocommerce_payment_complete', array($this, 'payment_complete'), 10, 1);
            add_filter('woocommerce_payment_complete_order_status', array($this, 'payment_complete_order_status'), 10, 2);
            add_action('valid-paypal-standard-ipn-request', array($this, 'valid_response'));
            // add_filter('woocommerce_add_to_cart_sold_individually_quantity',array($this,'add_to_cart_sold_individually_quantity'),10,5);
            // add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
            add_action('show_user_profile', array($this, 'add_customer_wdc_fields'));
            add_action('edit_user_profile', array($this, 'add_customer_wdc_fields'));
            add_action('personal_options_update', array($this, 'save_customer_wdc_fields'));
            add_action('edit_user_profile_update', array($this, 'save_customer_wdc_fields'));
            add_filter('woocommerce_available_payment_gateways', array($this, 'available_payment_gateways'));
            add_filter('woocommerce_json_search_found_products', array($this, 'json_search_found_products'));
            add_action('woocommerce_checkout_update_order_meta', array($this, 'woocommerce_checkout_update_order_meta'), 10, 2);
            add_action('woocommerce_cart_item_price', array($this, 'wdcp_woocommerce_cart_item_price'), 10, 3);
            add_filter('woocommerce_cart_item_removed_title', array($this, 'wdcp_woocommerce_cart_item_removed_title'), 10, 2);
            add_action('wp_ajax_wdcp_delete_credit_post', array($this, 'wdcp_delete_credit_item'));
            add_filter('wc_min_max_quantity_minmax_do_not_count', array($this, 'quantity_minmax_do_not_count'), 10, 4);
            add_filter('wc_min_max_quantity_minmax_cart_exclude', array($this, 'quantity_minmax_cart_exclude'), 10, 4);
            add_shortcode("buy_credits", array($this, 'wdcp_buy_credits_cb'));
            add_shortcode("buy_credit_url", array($this, 'wdcp_buy_credit_url_cb'));
            add_filter('woocommerce_add_to_cart_validation', array($this, 'add_to_cart_validation2'), 10, 5);
            // add_action('woocommerce_add_to_cart_validation', array($this, 'add_to_cart_validation'), 10, 3);

            add_filter('woocommerce_available_variation', array($this, 'available_variation'), 10, 3);
            add_filter('woocommerce_add_to_cart_sold_individually_quantity', array($this, 'add_to_cart_sold_individually_quantity2'), 10, 5);
            add_action('woocommerce_thankyou', array($this, 'custom_process_order'), 10, 1);
            add_filter('woocommerce_coupon_discount_amount_html', array($this, 'coupon_discount_amount_html'), 10, 2);
            add_filter('woocommerce_order_discount_to_display', array($this, 'order_discount_to_display'), 10, 2);
            add_filter('woocommerce_subscription_payment_complete', array($this, 'subscription_payment_complete'), 10, 2);
            // add_filter( 'woocommerce_package_rates', array($this,'credit_free_shipping'), 100 );
            add_shortcode("user_credits", array($this, 'wdcp_user_credits_cb'));
            add_filter('woocommerce_shipping_instance_form_fields_flat_rate', array($this, 'instance_form_fields_flat_rate'));
            add_filter('woocommerce_shipping_instance_form_fields_local_pickup', array($this, 'instance_form_fields_flat_rate'));
            add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'cart_shipping_method_full_label'), 10, 2);
            add_filter('woocommerce_order_shipping_to_display', array($this, 'order_shipping_to_display'), 10, 2);
            add_action('wp', array($this, 'cron_enable'));
            add_action('wdcp_twicedaily_event', array($this, 'cron_remove_expiry_credits'));
            add_action('parse_query', array($this, 'hide_credit_products'));
            add_filter('woocommerce_product_type_query', array($this, 'product_type_query'), 10, 2);
            add_filter('woocommerce_cart_totals_order_total_html', array($this, 'cart_totals_order_total_html'));
            //  add_filter( 'woocommerce_data_stores', array($this,'data_stores') );

            add_filter('woocommerce_order_amount_item_total', array($this, 'order_amount_item_total2'), 10, 5);


            add_filter('woocommerce_order_item_get_total', array($this, 'order_item_get_total'), 99, 2);

            add_filter('admin_body_class', array($this, 'admin_classes'));

            add_action('wp_ajax_woocommerce_refund_line_items', array($this, 'refund_line_items'), 1);
            add_action('wp_ajax_nopriv_woocommerce_refund_line_items', array($this, 'refund_line_items'), 1);

            add_action('wp_ajax_woocommerce_delete_refund', array($this, 'delete_refund'), 1);
            add_action('wp_ajax_nopriv_woocommerce_delete_refund', array($this, 'delete_refund'), 1);
        }

        public function wdcp_gravity_forms_load() {
            if (class_exists("GFForms")) {
                GFForms::include_addon_framework();
                if (is_admin()) {
                    add_filter('gform_form_settings', array($this, 'gform_settings'), 20, 2);
                    add_filter('gform_pre_form_settings_save', array($this, 'pre_form_settings_save'), 20);
                } else {
                    add_filter('gform_validation', array($this, 'form_validation'), 20);
                    add_filter('gform_validation_message', array($this, 'validation_message'), 20, 2);
                    add_action('gform_after_submission', array($this, 'after_submission'), 20, 2);
                }
            }
        }

        public function wdcp_init() {
            // if( version_compare( WC_VERSION, '3.1.1', '<' )){
            //  add_action('woocommerce_add_order_item_meta', array($this, 'order_item_meta_2'), 10, 2);
            // }else{
            //   add_action('woocommerce_new_order_item', array($this, 'new_order_item_meta'), 10, 3);
            // }


            if (version_compare(WC_VERSION, '2.7', '<')) {
                add_action('woocommerce_add_order_item_meta', array($this, 'add_order_item_meta2'), 10, 2);
                // add_filter( 'woocommerce_order_items_meta_display', array( $this, 'order_items_meta_display' ), 10, 2 );
            } else {
                add_action('woocommerce_checkout_create_order_line_item', array($this, 'checkout_create_order_line_item'), 10, 3);
                //  add_filter( 'woocommerce_display_item_meta', array( $this, 'display_item_meta' ), 10, 3 );
            }
        }

        public function gform_settings($settings, $form) {
            $settings[__('Form Basics', 'gravityforms')]['my_custom_setting'] = '
                <tr>
                    <th><label for="wdcp_credit_price">Credit price</label></th>
                    <td><input class="fieldwidth-3" value="' . rgar($form, 'wdcp_credit_price') . '" name="wdcp_credit_price" id="wdcp_credit_price"></td>
                </tr>';

            return $settings;
        }

        public function pre_form_settings_save($form) {
            $form['wdcp_credit_price'] = rgpost('wdcp_credit_price');
            return $form;
        }

        public function form_validation($validation_result) {
            $form = $validation_result['form'];
            $credit_required = (int) $form['wdcp_credit_price'];
            if ($credit_required > 0) {
                $user_credits = Woo_Download_Credits_Platinum::get_credits();
                if ($user_credits < $credit_required) {
                    $validation_result['is_valid'] = false;
                }
            }
            $validation_result['form'] = $form;
            return $validation_result;
        }

        public function validation_message($message, $form) {
            $credit_required = (int) $form['wdcp_credit_price'];
            if ($credit_required > 0) {
                $user_credits = Woo_Download_Credits_Platinum::get_credits();
                if ($user_credits < $credit_required) {
                    $credits_label = trim(get_option('mwdcp_credits_label'));
                    $label_only = empty($credits_label) ? __('Credits', 'mwdcp') : $credits_label;
                    $my_account_url = wdcp_get_myaccount_url();
                    return '<div class="validation_error">You need ' . $credit_required . ' credits to submit this form. Credit Balance is ' . $user_credits . ' -  <a class="buy-more-credits" href="' . $my_account_url . '">' . __('Buy More', 'mwdcp') . ' ' . $label_only . '</a></div>';
                    // return '<div class="validation_error">Your Credit Balance is low -  <a class="buy-more-credits" href="' . $my_account_url . '">' . __('Buy More', 'mwdcp') . ' ' . $label_only . '</a></div>';
                }
            }
            return $message;
        }

        public function after_submission($entry, $form) {
            $credit_required = (int) $form['wdcp_credit_price'];
            if ($credit_required > 0) {
                $user_credits = Woo_Download_Credits_Platinum::get_credits();
                if ($user_credits > $credit_required) {
                    $customer_id = get_current_user_id();
                    Woo_Download_Credits_Platinum::remove_credits($customer_id, $credit_required);
                }
            }
        }

        public function add_order_item_meta2($item_id, $values) {
            if (!empty($values['_booking_duration'])) {
                wc_add_order_item_meta($item_id, '_booking_duration', sanitize_text_field($values['_booking_duration']));
            }
        }

        public function checkout_create_order_line_item($item, $cart_item_key, $values) {
            if (!empty($values['_booking_duration'])) {
                $item->add_meta_data('_booking_duration', sanitize_text_field($values['_booking_duration']));
            }
        }

        public function coupon_is_valid($is_valid, $coupon, $obj) {
            if (self::cart_has_products_with_only_credit_price() || self::cart_has_products_with_credit()) {
                $is_valid = false;
            }

            return $is_valid;
        }

        public function product_get_price($value, $product) {
            return $value;
        }

        public function order_amount_item_total2($total, $order, $item, $inc_tax, $round) {
            $product = $item->get_product();
            $credit = $this->get_product_credits($product);
            if ($credit) {
                return $credit;
            }
            return $total;
        }

        public function order_item_get_total($total, $item) {
            $product = $item->get_product();
            $credit = $this->get_product_credits($product);
            if ($credit) {
                $total = floatval($credit) * max(1, $item->get_quantity());
                //return $credit;
            }
            return $total;
        }

        public function delete_refund() {
            check_ajax_referer('order-item', 'security');
            if (!current_user_can('edit_shop_orders')) {
                wp_die(-1);
            }
            $refund_ids = array_map('absint', is_array($_POST['refund_id']) ? $_POST['refund_id'] : array($_POST['refund_id']));
            foreach ($refund_ids as $refund_id) {
                if ($refund_id && 'shop_order_refund' === get_post_type($refund_id)) {
                    $refund = wc_get_order($refund_id);
                    $order_id = $refund->get_parent_id();
                    if (self::order_using_credits($order_id)) {
                        wp_send_json_error(array('error' => 'Credit Refund can not be deleted.'));
                        wp_die();
                    }
                }
            }
        }

        public function refund_line_items() {
            ob_start();

            check_ajax_referer('order-item', 'security');

            if (!current_user_can('edit_shop_orders')) {
                wp_die(-1);
            }
            $order_id = absint($_POST['order_id']);
            if (self::order_using_credits($order_id)) {

                $refund_amount = sanitize_text_field($_POST['refund_amount']);
                if (!self::order_can_refund($order_id, $refund_amount)) {
                    wp_send_json_error(array('error' => 'Credit can not be refund'));
                }

                remove_action('wp_ajax_woocommerce_refund_line_items', array('WC_AJAX', 'refund_line_items'));
                remove_action('wp_ajax_nopriv_woocommerce_refund_line_items', array('WC_AJAX', 'refund_line_items'));

                $credits_used = get_post_meta($order_id, '_credits_used', true);

                $total_credits_used = get_post_meta($order_id, '_credits_used', true);
                $credits_returned = get_post_meta($order_id, '_credits_returned', true);
                $remaining_refund_amount = $total_credits_used - $credits_returned;


                $refund_reason = sanitize_text_field($_POST['refund_reason']);
                $line_item_qtys = json_decode(sanitize_text_field(stripslashes($_POST['line_item_qtys'])), true);
                $line_item_totals = json_decode(sanitize_text_field(stripslashes($_POST['line_item_totals'])), true);
                $line_item_tax_totals = json_decode(sanitize_text_field(stripslashes($_POST['line_item_tax_totals'])), true);
                $api_refund = 'true' === $_POST['api_refund'];
                $restock_refunded_items = 'true' === $_POST['restock_refunded_items'];
                $refund = false;
                $response_data = array();

                try {
                    $order = wc_get_order($order_id);
                    $order_items = $order->get_items();
                    $max_refund = $credits_used;

                    if (!$refund_amount || $max_refund < $refund_amount || 0 > $refund_amount) {
                        throw new exception(__('Invalid refund amount', 'woocommerce'));
                    }

                    // Prepare line items which we are refunding
                    $line_items = array();

                    // Create the refund object.
                    $refund = $this->wc_create_refund(array(
                        'amount' => $refund_amount,
                        'reason' => $refund_reason,
                        'order_id' => $order_id,
                        'line_items' => $line_items,
                        'refund_payment' => $api_refund,
                        'restock_items' => $restock_refunded_items,
                            ));

                    if (is_wp_error($refund)) {
                        throw new Exception($refund->get_error_message());
                    }

                    if (did_action('woocommerce_order_fully_refunded')) {
                        $response_data['status'] = 'fully_refunded';
                    }

                    wp_send_json_success($response_data);
                } catch (Exception $e) {
                    if ($refund && is_a($refund, 'WC_Order_Refund')) {
                        wp_delete_post($refund->get_id(), true);
                    }
                    wp_send_json_error(array('error' => $e->getMessage()));
                }
                return;
            }
        }

        protected function wc_create_refund($args = array()) {
            $default_args = array(
                'amount' => 0,
                'reason' => null,
                'order_id' => 0,
                'refund_id' => 0,
                'line_items' => array(),
                'refund_payment' => false,
                'restock_items' => false,
            );

            try {
                $args = wp_parse_args($args, $default_args);

                if (!$order = wc_get_order($args['order_id'])) {
                    throw new Exception(__('Invalid order ID.', 'woocommerce'));
                }
                $order_id = $args['order_id'];
                $total_credits_used = get_post_meta($order_id, '_credits_used', true);
                $credits_returned = get_post_meta($order_id, '_credits_returned', true);

                $remaining_refund_amount = $total_credits_used - $credits_returned;

                //     $remaining_refund_amount = $order->get_remaining_refund_amount();
                //     $remaining_refund_items  = $order->get_remaining_refund_items();


                $refund_item_count = 0;

                $refund = new WC_Order_Refund($args['refund_id']);

                if (0 > $args['amount'] || $args['amount'] > $remaining_refund_amount) {
                    throw new Exception(__('Invalid refund amount.', 'woocommerce'));
                }

                $refund->set_currency($order->get_currency());
                $refund->set_amount($args['amount']);
                $refund->set_parent_id(absint($args['order_id']));
                $refund->set_refunded_by(get_current_user_id() ? get_current_user_id() : 1 );

                if (!is_null($args['reason'])) {
                    $refund->set_reason($args['reason']);
                }



                //  $refund->update_taxes();
                //    $refund->calculate_totals( false );
                //   $refund->set_total( $args['amount'] * -1 );

                if (isset($args['date_created'])) {
                    $refund->set_date_created($args['date_created']);
                }

                do_action('woocommerce_create_refund', $refund, $args);

                if ($refund->save()) {
                    if ($args['refund_payment']) {
                        $result = wc_refund_payment($order, $refund->get_amount(), $refund->get_reason());
                        if (is_wp_error($result)) {
                            $refund->delete();
                            return $result;
                        }
                    }

                    // Trigger notification emails
                    if (( $remaining_refund_amount - $args['amount'] ) > 0 || ( $order->has_free_item() && ( $remaining_refund_items - $refund_item_count ) > 0 )) {
                        do_action('woocommerce_order_partially_refunded', $order->get_id(), $refund->get_id());
                    } else {
                        do_action('woocommerce_order_fully_refunded', $order->get_id(), $refund->get_id());

                        $parent_status = apply_filters('woocommerce_order_fully_refunded_status', 'refunded', $order->get_id(), $refund->get_id());

                        if ($parent_status) {
                            $order->update_status($parent_status);
                        }
                    }
                }

                do_action('woocommerce_refund_created', $refund->get_id(), $args);
                do_action('woocommerce_order_refunded', $order->get_id(), $refund->get_id());
            } catch (Exception $e) {
                return new WP_Error('error', $e->getMessage());
            }

            return $refund;
        }

        public function admin_classes($classes) {
            $screen = get_current_screen();
            global $wpdb, $post;
            if ('post' == $screen->base && $screen->post_type == 'shop_order') {
                $order_id = $post->ID;
                if (self::order_using_credits($order_id)) {
                    $classes .= ' wdc-credits-order';
                }
                if (!self::order_can_refund($order_id, 1)) {
                    $classes .= ' wdc-credits-refunded';
                }
            }
            return $classes;
        }

        public function credit_product_is_purchasable($is_purchasable, $product) {
            $prod_id = ( WC()->version < '2.7.0' ) ? $product->id : $product->get_id();
            $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
            if ($credits_amount) {
                $is_purchasable = true;
            }
            return $is_purchasable;
        }

        public function variation_is_purchasable($is_purchasable, $product) {
            $prod_id = ( WC()->version < '2.7.0' ) ? $product->id : $product->get_id();
            $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
            if ($credits_amount) {
                $is_purchasable = true;
            }
            return $is_purchasable;
        }

        public function variation_is_visible($is_visible, $this_get_id, $this_get_parent_id, $product) {
            $credits_amount = get_post_meta($this_get_id, '_credits_amount', true);
            if ($credits_amount) {
                $is_visible = true;
            }
            return $is_visible;
        }

        public function credit_product_cart_needs_payment($is_payment, $object) {
            if (self::cart_using_credits()) {
                $is_payment = true;
            }
            return $is_payment;
        }

        public function cart_totals_order_total_html($value) {
            $chosen_payment_method = WC()->session->get('chosen_payment_method');
            if (self::cart_using_credits() && !empty($chosen_payment_method) && $chosen_payment_method == 'wdc_woo_credits') {
                $value = '<strong>' . WC()->cart->get_total() . '</strong> ';
            }
            return $value;
        }

        public function available_variation($value, $object = null, $variation = null) {
            if (is_numeric($variation)) {
                $variation = wc_get_product($variation);
            }
            $formatted_price = $value['price_html'];
            $variation_id = $variation->get_id();
            $credits_amount = get_post_meta($variation_id, '_credits_amount', true);
            if ($credits_amount) {
                $clabel = self::get_credit_count($credits_amount);
                $prod_price = $variation->get_price();
                $hide_price = get_option('mwdcp_hide_price');
                $hide_price = (int) $hide_price;
                if ($hide_price || !is_numeric($prod_price)) {
                    $formatted_price = $clabel;
                } else {
                    $formatted_price .= '<span class="variation-price"> ( ' . __('or', 'mwdcp') . ' ' . $clabel . ' )</span>';
                }
                $value['price_html'] = $formatted_price;
            }

            return $value;
        }

        public function hide_credit_products($query) {
            if (!is_admin() || !$query->is_main_query())
                return $query;

            global $pagenow, $post_type;
            if ($pagenow == 'edit.php' && $post_type == 'product') {
                $credit_tiers = wdcp_get_credit_products();
                if ($credit_tiers) {
                    $credit_ids = wp_list_pluck($credit_tiers, 'ID');
                    $query->query_vars['post__not_in'] = $credit_ids;
                }
            }

            return $query;
        }

        function data_stores($stores) {
            $stores['product-credits'] = 'WC_Product_Credits_Data_Store_CPT';
            return $stores;
        }

        public function product_type_query($boolr, $product_id) {
            // $credit_tiers = get_posts(array('post_type' => 'credit','posts_per_page'=> -1,'post_status'   => 'publish','orderby' => 'date', 'order'  => 'ASC',));
            $credit_tiers = wdcp_get_credit_products();
            if ($credit_tiers) {
                $credit_ids = wp_list_pluck($credit_tiers, 'ID');
                if (is_array($credit_ids) && in_array($product_id, $credit_ids)) {
                    return 'credits';
                }
            }
            return $boolr;
        }

        public function order_item_meta($item_meta, $cart_item) {
            $prod_id = ( isset($cart_item['variation_id']) && $cart_item['variation_id'] != 0 ) ? $cart_item['variation_id'] : $cart_item['product_id'];
            $product = wc_get_product($prod_id);
            if ($product->is_type('booking') && $product->get_duration_type() == 'customer') {
                $duration = $cart_item['booking']['_duration'];
                $item_meta->add('_booking_duration', $duration);
            }

            if (function_exists('wceb_is_bookable') && wceb_is_bookable($product) && isset($cart_item['_booking_duration'])) {
                $booking_duration = $cart_item['_booking_duration'];
                $item_meta->add('_booking_duration', $booking_duration);
            }
        }

        public function order_item_meta_2($item_id, $values) {
            if (function_exists('woocommerce_add_order_item_meta') && isset($values['booking']) && isset($values['booking']['_duration'])) {
                woocommerce_add_order_item_meta($item_id, '_booking_duration', $values['booking']['_duration']);
            }
        }

        public function new_order_item_meta($item_id, $item, $subscription_id) {
            if (function_exists('woocommerce_new_order_item') && isset($item['booking']) && isset($item['booking']['_duration'])) {
                woocommerce_add_order_item_meta($item_id, '_booking_duration', $item['booking']['_duration']);
            }
        }

        public function order_shipping_to_display($shipping, $order) {
            $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
            $shipping_credits = get_post_meta($order_id, '_shipping_credits_used', true);
            if ($shipping_credits && absint($shipping_credits) > 0) {
                $shipping = self::get_credit_count($shipping_credits);
            }
            return $shipping;
        }

        public function cron_enable() {
            if (!wp_next_scheduled('wdcp_twicedaily_event')) {
                wp_schedule_event(time(), 'twicedaily', 'wdcp_twicedaily_event');
            }
        }

        public function cron_remove_expiry_credits() {
            $metarows = self::customer_get_expiredcredits();
            if ($metarows) {
                foreach ($metarows as $row) {
                    $credits = $row->credits;
                    $credits = (int) $credits;
                    $umeta_id = $row->umeta_id;
                    $umeta_id = (int) $umeta_id;
                    $user_id = $row->user_id;
                    $user_id = (int) $user_id;
                    self::remove_credits($user_id, $credits);
                    self::customer_delete_metas($user_id, $umeta_id);
                }
            }
        }

        public function cart_shipping_method_full_label($label, $method) {
            $method_id = $method->id;
            $method_id = str_replace(':', '_', $method_id);
            $option_key = 'woocommerce_' . $method_id . '_settings';
            $settings = get_option($option_key);
            if (isset($settings['credit_cost']) && absint($settings['credit_cost']) > 0) {
                $label .= ' or (' . self::get_credit_count($settings['credit_cost']) . ')';
            }
            return $label;
        }

        public function subscription_payment_complete($subscription) {
            $user_id = $subscription->get_user_id();
            $last_order = $subscription->get_last_order('all', 'any');
            $items = $last_order->get_items();
            $product_ids = array();
            $reset_credits_opt = get_option('mwdcp_reset_credits');
            $reset_credits_opt = (int) $reset_credits_opt;
            foreach ($items as $item_id => $item) {
                $product_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                $credits_plan = get_post_meta($product_id, '_subscription_credits_plan', true);
                $credit_number = get_post_meta($credits_plan, '_credit_number', true);
                $credit_number = floatval($credit_number);
                if ($credit_number) {
                    self::add_credits($user_id, $credit_number);
                    if ($reset_credits_opt) {
                        $credits_expiry_days = get_post_meta($product_id, '_subscription_credits_expiry_days', true);
                        $credits_expiry_days = (int) $credits_expiry_days;
                        $next_payment_time = $subscription->get_time('next_payment');
                        if ($credits_expiry_days > 0) {
                            $datetime = new DateTime();
                            $datetime->modify('+ ' . $credits_expiry_days . ' days');
                            $next_payment_time = $datetime->getTimestamp();
                        }
                        $umeta_id = add_user_meta($user_id, 'credit_expiry_time', $next_payment_time);
                        add_user_meta($user_id, 'credits_amount_' . $umeta_id, $credit_number);
                    }
                }
            }
        }

        public function wootickets_ticket_price_html($price, $product, $attendee) {
            $credits_amount = 0;
            $prod_id = ( WC()->version < '2.7.0' ) ? $product->id : $product->get_id();
            $product_type = ( WC()->version < '2.7.0' ) ? $product->product_type : $product->get_type();
            if ($product_type == 'simple') {
                $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
            }
            if ($credits_amount) {
                $clabel = self::get_credit_count($credits_amount);
                $hide_price = $this->hide_price();
                if ($hide_price) {
                    $price = $clabel;
                } else {
                    $price .= '<span class="amount">' . ' (' . __('or', 'mwdcp') . ' ' . $clabel . ')</span>';
                }
            }
            return $price;
        }

        public function instance_form_fields_flat_rate($form_fields) {
            $settings = array(
                'credit_cost' => array(
                    'title' => __('Cost( in Credits)', 'woocommerce'),
                    'type' => 'text',
                    'placeholder' => '',
                    'description' => 'Cost in Credits',
                    'default' => '0',
                    'desc_tip' => true
                )
            );
            $form_fields = array_merge($form_fields, $settings);
            return $form_fields;
        }

        public function credit_free_shipping($rates) {
            $free = array();
            if (self::cart_using_credits()) {
                foreach ($rates as $rate_id => $rate) {
                    if ('free_shipping' === $rate->method_id) {
                        $free[$rate_id] = $rate;
                        break;
                    }
                }
            }
            return !empty($free) ? $free : $rates;
        }

        public function quantity_minmax_do_not_count($exclude, $checking_id, $cart_item_key, $values) {
            $product = $values['data'];
            $product_id = ($checking_id && $checking_id != 0) ? $checking_id : $values['product_id'];
            if (wdcp_is_credit_product($product_id)) {
                $exclude = 'yes';
            }
            return $exclude;
        }

        public function quantity_minmax_cart_exclude($exclude, $checking_id, $cart_item_key, $values) {
            $product = $values['data'];
            $product_id = ($checking_id && $checking_id != 0) ? $checking_id : $values['product_id'];
            if (wdcp_is_credit_product($product_id)) {
                $exclude = 'yes';
            }
            return $exclude;
        }

        public function wdcp_user_credits_cb($atts, $content = null) {
            $output = '';
            $id = get_current_user_id();
            if ($id) {
                $output = self::get_credits($id);
            }
            return $output;
        }

        public function order_discount_to_display($price, $order) {
            $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
            $payment_method = get_post_meta($order_id, '_payment_method', true);
            if ('wdc_woo_credits' === $payment_method && self::order_using_credits($order_id)) {
                $credits_discount = get_post_meta($order_id, '_credits_discount', true);
                $price = self::get_credit_count($credits_discount);
            }
            return $price;
        }

        public function coupon_discount_amount_html($discount_html, $coupon) {
            $total_credits_using_amount = self::cart_get_total_using_credits();
            $total_credits_buying_amount = self::cart_get_total_buying_credits();
            $total_credits_using_amount = (int) $total_credits_using_amount;
            $total_credits_buying_amount = (int) $total_credits_buying_amount;

            if ($total_credits_using_amount > 0 && $total_credits_buying_amount == 0) {
                //  $usinglabel = self::get_credit_count(self::cart_get_total_discount_credits($coupon));
                //  $discount_html = '-'.$usinglabel;
            }

            return $discount_html;
        }

        public function load_textdomain() {
            load_plugin_textdomain('mwdcp', false, dirname(plugin_basename(__FILE__)) . '/lang/');
        }

        protected function get_paypal_order($raw_custom) {
            // We have the data in the correct format, so get the order.
            if (( $custom = json_decode($raw_custom) ) && is_object($custom)) {
                $order_id = $custom->order_id;
                $order_key = $custom->order_key;
                // Fallback to serialized data if safe. This is @deprecated in 2.3.11
            } elseif (preg_match('/^a:2:{/', $raw_custom) && !preg_match('/[CO]:\+?[0-9]+:"/', $raw_custom) && ( $custom = maybe_unserialize($raw_custom) )) {
                $order_id = $custom[0];
                $order_key = $custom[1];
                // Nothing was found.
            } else {
                WC_Gateway_Paypal::log('Error: Order ID and key were not found in "custom".');
                return false;
            }
            if (!$order = wc_get_order($order_id)) {
                // We have an invalid $order_id, probably because invoice_prefix has changed.
                $order_id = wc_get_order_id_by_order_key($order_key);
                $order = wc_get_order($order_id);
            }
            if (!$order || $order->order_key !== $order_key) {
                WC_Gateway_Paypal::log('Error: Order Keys do not match.');
                return false;
            }
            return $order;
        }

        public function valid_response($posted) {
            if (!empty($posted['custom']) && ( $order = $this->get_paypal_order($posted['custom']) )) {
                $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
                if (Woo_Download_Credits_Platinum::order_contains_credits($order_id)) {
                    $order->update_status('completed');
                }
            }
        }

        public function payment_complete_order_status($order_status, $order_id) {
            $order = new WC_Order($order_id);
            if (Woo_Download_Credits_Platinum::order_contains_credits($order_id)) {
                $order_status = 'completed';
            }
            return $order_status;
        }

        public function json_search_found_products($found_products) {
            // $credit_tiers = get_posts(array('post_type' => 'credit','posts_per_page'=> -1,'post_status'   => 'publish','orderby' => 'date', 'order'  => 'ASC'));
            $credit_tiers = wdcp_get_credit_products();
            if ($credit_tiers) :
                foreach ($credit_tiers as $credit): $credit_name = get_post_meta($credit->ID, '_credit_name', true);
                    $found_products[$credit->ID] = $credit_name;
                endforeach;
            endif;
            return $found_products;
        }

        public function payment_complete($order_id) {
            $order = new WC_Order($order_id);
            $payment_method = get_post_meta($order_id, '_payment_method', true);
            if ('wdc_woo_credits' === $payment_method && Woo_Download_Credits_Platinum::order_using_credits($order_id)) {
                update_post_meta($order_id, '_order_total', 0);
                foreach ($order->get_items() as $item_id => $item) {
                    $product_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (get_post_meta($product_id, '_credits_amount', true)) {
                        wc_update_order_item_meta($item_id, '_line_subtotal', 0);
                        wc_update_order_item_meta($item_id, '_line_total', 0);
                    }
                }
                $order_status_processing = get_option('mwdcp_order_status_processing');
                $order_status2 = 'completed';
                if ($order_status_processing) {
                    $order_status2 = 'processing';
                }
                $order->update_status($order_status2);
            } elseif (Woo_Download_Credits_Platinum::order_contains_credits($order_id)) {
                $order_status = 'completed';
                $order->update_status($order_status);
            }
        }

//         public function add_order_item_meta($item_id, $values, $cart_item_key){
//          global $wpdb;
//          $get_items_sql  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d ", $item_id );
//              $order_id     = $wpdb->get_var( $get_items_sql,3 );
//              // $order = new WC_Order($order_id);
//              $payment_method = get_post_meta( $order_id, '_payment_method', true );
//              wp_send_json($values); die;
//          $product_id = ( isset( $values['variation_id'] ) && $values['variation_id'] != 0 ) ? $values['variation_id'] : $values['product_id'];
//          if ( get_post_meta( $product_id, '_credits_amount', true ) ) {
//              wc_update_order_item_meta($item_id,'_line_subtotal',0);
//              wc_update_order_item_meta($item_id,'_line_total',0);
//          }
//         }
        public function order_get_items($items, $order) {

            $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
            $payment_method = get_post_meta($order_id, '_payment_method', true);
            if ('wdc_woo_credits' === $payment_method && Woo_Download_Credits_Platinum::order_using_credits($order_id)) {
                update_post_meta($order_id, '_order_total', 0);
                foreach ($items as $item_id => $item) {
                    $product_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (get_post_meta($product_id, '_credits_amount', true)) {
                        $items[$item_id]['line_subtotal'] = 0;
                        $items[$item_id]['line_total'] = 0;
                    }
                }
            }
            return $items;
        }

        public function wdcp_woocommerce_cart_item_removed_title($title, $cart_item) {
            $prod_id = ( isset($cart_item['variation_id']) && $cart_item['variation_id'] != 0 ) ? $cart_item['variation_id'] : $cart_item['product_id'];
            $product = wc_get_product($prod_id);
            if (wdcp_is_credit_product($prod_id)) {
                $credit_name = get_post_meta($prod_id, '_credit_name', true);
                $title = $credit_name;
            }
            return $title;
        }

        public function custom_process_order($order_id) {
            $data = (array) WC()->session->get('_wdc_removed_cart_data');
            global $woocommerce;
            if (!empty($data) && count($data) > 0) {
                foreach ($data as $value) {
                    $values = $value['item_data'];
                    $id = $values['product_id'];
                    $quant = $values['quantity'];
                    WC()->cart->add_to_cart($id, $quant, $values['variation_id'], $values['variation']);
                }
                WC()->session->__unset('_wdc_removed_cart_data');
            }
        }

        public function wdcp_woocommerce_cart_item_price($sub_total, $cart_item, $cart_item_key) {

            $prod_id = ( isset($cart_item['variation_id']) && $cart_item['variation_id'] != 0 ) ? $cart_item['variation_id'] : $cart_item['product_id'];

            $product = wc_get_product($prod_id);
            $credits_amount = get_post_meta($prod_id, '_credits_amount', true);

            if ($product->is_type('booking') && $product->get_duration_type() == 'customer') {
                $duration = $cart_item['booking']['_duration'];
                $credits_amount = $credits_amount * $duration;
            }

            if (function_exists('wceb_is_bookable') && wceb_is_bookable($product) && isset($cart_item['_booking_duration'])) {
                $booking_duration = $cart_item['_booking_duration'];
                $credits_amount = $credits_amount * $booking_duration;
            }

            if ($credits_amount) {
                $clabel = self::get_credit_count($credits_amount);
                $hide_price = $this->hide_price();
                $prod_price = $product->get_price();
                if ($hide_price || !is_numeric($prod_price)) {
                    $sub_total = $clabel;
                } else {
                    $sub_total .= ' &nbsp;&nbsp;&nbsp;(' . __('or', 'mwdcp') . ' ' . $clabel . ')';
                }
            }

            return $sub_total;
        }

        public function pre_get_posts($q) {
            //  $show_creditonly_products = get_option('mwdcp_show_creditonly_products');
            //  $show_creditonly_products = (int) $show_creditonly_products;
            //  if($show_creditonly_products){
            //       if ( ! $q->is_main_query() ) return;
            //        if ( ! $q->is_post_type_archive() ) return;
            //        if ( ! is_admin() && is_shop() ) {
            //            $q->set( 'meta_query', array(array(
            //                'key' => '_credits_amount',
            //                'value' => 0,
            //                'compare' => '>'
            //            )));
            //        }
            //  }
        }

        public function add_to_cart_sold_individually_quantity2($valid, $quantity, $product_id, $variation_id, $cart_item_data) {
            global $woocommerce;
            $product_id = ( isset($variation_id) && $variation_id != 0 ) ? $variation_id : $product_id;
            $product_ids = array();

            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (wdcp_is_credit_product($prod_id)) {
                        $product_ids[] = $prod_id;
                    }
                }
            }

            $product = wc_get_product($product_id);
            if (wdcp_is_credit_product($product_id)) {
                if (in_array($product_id, $product_ids)) {
                    throw new Exception(sprintf('<a href="%s" class="button wc-forward">%s</a> %s', WC_Cart::get_cart_url(), __('View Cart', 'mwdcp'), sprintf(__('You cannot add another', 'mwdcp') . '&quot;%s&quot; ' . __('to your cart.', 'mwdcp'), $cart_item_data['credits_name'])));
                    $valid = 0;
                }
            }
            return $valid;
        }

        public function add_to_cart_validation2($valid, $product_id, $quantity, $variation_id = 0, $variations = array()) {
            $product_id = ($variation_id && $variation_id != 0 ) ? $variation_id : $product_id;

            $product = wc_get_product($product_id);

            if (wdcp_is_credit_product($product_id) && isset($_GET['add-to-cart']) && (!isset($_GET['ptype']) || $_GET['ptype'] != 'credit' )) {
                wc_add_notice(__('credit product can not be added', 'mwdcp'), 'notice');
                return false;
                // wp_redirect(home_url()); exit;
            }
            $label_only = get_option('mwdcp_credits_label');
            $label_only = trim($label_only);
            $label_only = empty($label_only) ? __('Credits', 'mwdcp') : $label_only;
            if (wdcp_is_credit_product($product_id) && self::cart_has_noncredit_products()) {
                self::cart_remove_noncredit_products();
                wc_add_notice(__('Non-credit products have been removed', 'mwdcp'), 'notice');
            } else if (!wdcp_is_credit_product($product_id) && self::cart_has_credit_products()) {
                wc_add_notice(__('While buying', 'mwdcp') . ' ' . $label_only . ', ' . __('you cannot buy non-credit products', 'mwdcp'), 'error');
                return false;
            } else if (wdcp_product_has_credits($product_id) && self::cart_has_products_with_nocredit()) {
                self::cart_remove_products_with_nocredit();
                wc_add_notice(__('Non-credit products have been removed', 'mwdcp'), 'notice');
            } else if (!wdcp_product_has_credits($product_id) && self::cart_has_products_with_credit()) {
                wc_add_notice(__('While buying', 'mwdcp') . ' ' . $label_only . ', ' . __('you cannot buy non-credit products', 'mwdcp'), 'error');
                return false;
            }

            return true;
        }

        public function add_to_cart_validation($valid, $product_id, $quantity) {
            $product = wc_get_product($product_id);
            //  $show_creditonly_products = get_option('mwdcp_show_creditonly_products');
            //  $show_creditonly_products = (int) $show_creditonly_products;
            //  if($show_creditonly_products && !$product->is_type( 'credits' )){
            //    $credits_amount = get_post_meta( $product_id, '_credits_amount', true );
            //    if(!$credits_amount){
            //      wc_add_notice( __( 'you can not add non credit products to cart', 'woocommerce' ), 'error' );
            //      return false;
            //    }
            //  }
            if (wdcp_is_credit_product($product_id) && isset($_GET['add-to-cart']) && (!isset($_GET['ptype']) || $_GET['ptype'] != 'credit' )) {
                wc_add_notice(__('credit product can not be added', 'mwdcp'), 'notice');
                return false;
                // wp_redirect(home_url()); exit;
            }
            $label_only = get_option('mwdcp_credits_label');
            $label_only = trim($label_only);
            $label_only = empty($label_only) ? __('Credits', 'mwdcp') : $label_only;
            if (wdcp_is_credit_product($product_id) && self::cart_has_noncredit_products()) {
                self::cart_remove_noncredit_products();
                wc_add_notice(__('Non-credit products have been removed', 'mwdcp'), 'notice');
            } else if (!wdcp_is_credit_product($product_id) && self::cart_has_credit_products()) {
                wc_add_notice(__('While buying', 'mwdcp') . ' ' . $label_only . ', ' . __('you cannot buy non-credit products', 'mwdcp'), 'error');
                return false;
            } else if (wdcp_product_has_credits($product_id) && self::cart_has_products_with_nocredit()) {
                self::cart_remove_products_with_nocredit();
                wc_add_notice(__('Non-credit products have been removed', 'mwdcp'), 'notice');
            } else if (!wdcp_product_has_credits($product_id) && self::cart_has_products_with_credit()) {
                wc_add_notice(__('While buying', 'mwdcp') . ' ' . $label_only . ', ' . __('you cannot buy non-credit products', 'mwdcp'), 'error');
                return false;
            }

            return true;
        }

        public function available_payment_gateways($available_gateways) {
            global $woocommerce;

            $hide_price = $this->hide_price();
            // $show_creditonly_products = get_option('mwdcp_show_creditonly_products');
            // $show_creditonly_products = (int) $show_creditonly_products;
            $hide_other_paymentsg = get_option('mwdcp_hide_other_paymentsg');
            $hide_other_paymentsg = (int) $hide_other_paymentsg;
            $arrayKeys = array_keys($available_gateways);

            if (self::cart_buying_credits() && in_array('wdc_woo_credits', $arrayKeys)) {
                unset($available_gateways['wdc_woo_credits']);
            }

            if (($hide_other_paymentsg || $hide_price) && self::cart_using_credits() && in_array('wdc_woo_credits', $arrayKeys)) {
                foreach ($arrayKeys as $key) {
                    if ($key != 'wdc_woo_credits') {
                        unset($available_gateways[$key]);
                    }
                }
            }

            return $available_gateways;
        }

        public function add_customer_wdc_fields($user) {
            if (!current_user_can('manage_woocommerce') || !current_user_can('manage_options')) {
                return;
            }
            $credits = floatval(get_user_meta($user->ID, "_download_credits", true));
            $credits = $credits ? $credits : 0;
            ?>
            <h3><?php esc_attr_e('Customer Credits Details', 'mwdcp'); ?></h3>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th><label><?php esc_attr_e('Customer Woo Credits', 'mwdcp'); ?></label></th>
                        <td>
            <?php echo $credits; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wdc_update_credit"><?php esc_attr_e('Enter New Credits Balance', 'mwdcp'); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" value="" id="wdc_update_credit" name="wdc_update_credit" />
                            <span class="description"><?php esc_attr_e('Please enter New Credits Balance to customer account', 'mwdcp'); ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
        }

        public function save_customer_wdc_fields($user_id) {
            if (isset($_POST['wdc_update_credit'])) {
                $wdc_update_credit = wc_clean($_POST['wdc_update_credit']);
                $wdc_update_credit = (int) $wdc_update_credit;
                if ($wdc_update_credit && $wdc_update_credit > 0) {
                    self::update_credits($user_id, $wdc_update_credit);
                }
            }
        }

        public function product_hide_price() {
            $resp = false;
            $hide_price = get_option('mwdcp_hide_price');
            $hide_price = (int) $hide_price;
            if ($hide_price) {
                $resp = true;
            }
            return $resp;
        }

        public function hide_price() {
            $resp = false;
            $hide_price = get_option('mwdcp_hide_price');
            $hide_price = (int) $hide_price;
            if ($hide_price || self::cart_has_products_with_only_credit_price()) {
                $resp = true;
            }
            return $resp;
        }

        public function order_hide_price($order_id) {
            $resp = false;
            $hide_price = get_option('mwdcp_hide_price');
            $hide_price = (int) $hide_price;
            if ($hide_price || self::order_using_credits($order_id)) {
                $resp = true;
            }
            return $resp;
        }

        public function variable_get_price_html($price, $product) {
            $product_type = ( WC()->version < '2.7.0' ) ? $product->product_type : $product->get_type();
            if ($product_type == 'variable') {
                $prices = $product->get_variation_prices();

                $price_arr = $prices['price'];

                if (!empty($price_arr)) {

                    $variation_ids = array_keys($price_arr);
                    $total_count = count($price_arr);
                    $min_id = current($variation_ids);
                    $max_id = end($variation_ids);
                    $price_str = ' or (';
                    $price1 = '';
                    $price2 = '';
                    $min_exist = false;
                    $max_exist = false;
                    $hide_price = $this->hide_price();
                    if ($hide_price) {
                        $price_str = ' (';
                    }

                    $min_variation_price = ( WC()->version < '2.7.0' ) ? $product->min_variation_price : $product->get_variation_price('min');
                    $max_variation_price = ( WC()->version < '2.7.0' ) ? $product->max_variation_price : $product->get_variation_price('max');


                    if ($min_variation_price && $min_variation_price !== $max_variation_price) {
                        $credits_amount = floatval(get_post_meta($min_id, '_credits_amount', true));

                        // echo gettype($credits_amount); die;

                        if ($credits_amount) {
                            $clabel = self::get_credit_count($credits_amount);
                            $price1 = ' <span class="min-price from">' . __($clabel, 'min_price', 'woocommerce') . ' </span>';
                            $min_exist = true;
                        }
                        $price_str .= $price1;
                    }
                    if ($max_variation_price && $max_variation_price !== $min_variation_price) {
                        $credits_amount = floatval(get_post_meta($max_id, '_credits_amount', true));
                        if ($credits_amount) {
                            $clabel = self::get_credit_count($credits_amount);
                            $price2 = ' <span class="max-price to">' . __($clabel, 'max_price', 'woocommerce') . ' </span>';
                            $max_exist = true;
                            if ($min_exist) {
                                $price2 = ' - ' . $price2;
                            }
                        }
                        $price_str .= $price2;
                    }
                    if ($min_exist || $max_exist) {
                        if ($hide_price) {
                            $price = $price_str . ')';
                        } else {
                            $price .= $price_str . ')';
                        }
                    }
                } else {
                    $price_str = ' (';
                    $variations = $product->get_available_variations();
                    $credits = array();
                    foreach ($variations as $variation) {
                        $credits_amount = get_post_meta($variation['variation_id'], '_credits_amount', true);
                        if ($credits_amount) {
                            $credits[] = $credits_amount;
                        }
                    }
                    asort($credits);
                    $min_credit = current($credits);
                    $max_credit = end($credits);
                    if ($min_credit) {
                        $clabel = self::get_credit_count($min_credit);
                        $min_price = ' <span class="min-price from">' . __($clabel, 'min_price', 'woocommerce') . ' </span>';
                        $price_str .= $min_price;
                    }
                    if ($max_credit) {
                        $clabel = self::get_credit_count($max_credit);
                        $max_price = ' <span class="max-price to">' . __($clabel, 'min_price', 'woocommerce') . ' </span>';

                        if ($min_credit) {
                            $max_price = ' - ' . $max_price;
                        }
                        $price_str .= $max_price;
                    }
                    if ($min_credit || $max_credit) {

                        $price = $price_str . ')';
                    }
                }
            }
            return $price;
        }

        public function get_variation_price_html($formatted_price, $product) {
            $product_id = wdc_get_product_id($product);
            $credits_amount = get_post_meta($product_id, '_credits_amount', true);
            if ($credits_amount) {
                $clabel = self::get_credit_count($credits_amount);
                $hide_price = $this->hide_price();
                if ($hide_price) {
                    $formatted_price = $clabel;
                } else {
                    $formatted_price .= '<span class="variation-price"> ( ' . __('or', 'mwdcp') . ' ' . $clabel . '</span>';
                    // $formatted_price .= '<span class="variation-price">' . _x('( or '.$clabel.' )', 'variation-price', 'woocommerce') . ' </span>';
                }
            }
            return $formatted_price;
        }

//        public function variable_price_html( $price, $product ) {
//
//            $prices = $product->get_variation_prices();
//            $price_arr = $prices['price'];
//            $variation_ids = array_keys($price_arr);
//            $total_count = count($price_arr);
//            $min_id = $variation_ids[0];
//            $max_id = $variation_ids[$total_count -1];
//            $hide_price = $this->hide_price();
//            $price_str = '';
//
//            if ( $product->min_variation_price && $product->min_variation_price !== $product->max_variation_price ){
//                  $price = woocommerce_price($product->get_price());
//                  $credits_amount = get_post_meta( $min_id, '_credits_amount', true );
//                 if($credits_amount){
//                   $clabel = self::get_credit_count($credits_amount);
//                   if($hide_price){
//                      $price = $clabel;
//                   }else{
//                      $price .= ' <span class="min-price from">' . _x('( or '.$clabel.' )', 'min_price', 'woocommerce') . ' </span>';
//                   }
//                 }
//                 $price_str .=$price;
//            }
//
//           if ( $product->max_variation_price && $product->max_variation_price !== $product->min_variation_price ) {
//                $credits_amount = get_post_meta( $max_id, '_credits_amount', true );
//               if($credits_amount){
//                 $price = woocommerce_price($product->max_variation_price);
//                 $clabel = self::get_credit_count($credits_amount);
//                 if($hide_price){
//                    $price = $clabel;
//                 }else{
//                    $price .= ' <span class="max-price to">' . _x('( or '.$clabel.' )', 'max_price', 'woocommerce') . ' </span>';
//                 }
//               }
//               $price_str .=' - '.$price;
//           }
//
//           return $price_str;
//      }
        public function wdcp_make_checkout_registration_possible() {
            if (self::cart_buying_credits() && !is_user_logged_in()) {
                if ('no' == get_option('woocommerce_enable_signup_and_login_from_checkout')) {
                    update_option('woocommerce_enable_signup_and_login_from_checkout', 'yes');
                    self::$signup_option_changed = true;
                }
                if ('yes' == get_option('woocommerce_enable_guest_checkout')) {
                    update_option('woocommerce_enable_guest_checkout', 'no');
                    self::$guest_checkout_option_changed = true;
                }
            }
        }

        public function wdcp_restore_checkout_registration_settings() {
            if (self::$signup_option_changed)
                update_option('woocommerce_enable_signup_and_login_from_checkout', 'no');
            if (self::$guest_checkout_option_changed)
                update_option('woocommerce_enable_guest_checkout', 'yes');
        }

        public function wdcp_buy_credits_cb($atts, $content = null) {
            extract(shortcode_atts(array(
                'show_expiry' => 'no',
                            ), $atts));
            $this->download_credits_buy_form2($show_expiry);
        }

        private function wdcp_get_cart_url() {
            if (WC()->version < '2.5.0') {
                global $woocommerce;
                $cart_url = $woocommerce->cart->get_cart_url();
            } else {
                $cart_url = wc_get_cart_url();
            }
            return $cart_url;
        }

        public function wdcp_buy_credit_url_cb($atts, $content = null) {
            $output = '';
            extract(shortcode_atts(array(
                'credit_id' => 0,
                'link_text' => __('Buy Now', 'mwdcp'),
                'class' => ''
                            ), $atts));
            if ($credit_id) {
                $cart_url = $this->wdcp_get_cart_url();
                $arr = array('add-to-cart' => $credit_id, 'ptype' => 'credit');
                $add_url = add_query_arg($arr, $cart_url);
                if ($class && $class != '') {
                    $class .= ' credit-buy-url';
                } else {
                    $class = 'credit-buy-url';
                }
                $output = '<a class="' . $class . '" href="' . $add_url . '">' . $link_text . '</a>';
            }
            return $output;
        }

        // public function add_to_cart_sold_individually_quantity($num, $quantity, $product_id, $variation_id, $cart_item_data){
        //     return $quantity;
        // }
        public function wdcp_delete_credit_item() {
            $credit_id = esc_attr(trim($_POST['credit_id']));
            $credit_id = (int) $credit_id;
            $credit = get_post($credit_id);
            $label_only = get_option('mwdcp_credit_label');
            $label_only = trim($label_only);
            $label_only = empty($label_only) ? __('Credit', 'mwdcp') : $label_only;
            if ($credit) {
                if (wp_delete_post($credit_id, true)) {
                    $ret_array = array(
                        'status' => 'success',
                        'message' => $credit->post_title . ' ' . __('is deleted successfully', 'mwdcp')
                    );
                } else {
                    $ret_array = array(
                        'status' => 'error',
                        'message' => __('there was some error deleting', 'mwdcp') . ' ' . $label_only . ' ' . $credit->post_title
                    );
                }
            } else {
                $ret_array = array(
                    'status' => 'error',
                    'message' => $label_only . ' ' . __('does not exist', 'mwdcp')
                );
            }
            wp_scripts($ret_array);
        }

        public function woocommerce_checkout_update_order_meta($order_id, $posted) {
            if (self::cart_buying_credits() || self::cart_using_credits()) {
                $total_credits = self::cart_get_total_credits();
                if ($posted['payment_method'] !== 'wdc_woo_credits' && self::cart_buying_credits()) {
                    $total_buying_credits = self::cart_get_total_buying_credits();
                    update_post_meta($order_id, '_credits_buying', $total_buying_credits);
                    update_post_meta($order_id, '_credits_added', 0);
                } elseif ($posted['payment_method'] === 'wdc_woo_credits' && self::cart_using_credits()) {
                    $total_using_credits = self::cart_get_total_using_credits();
                    $shipping_credits = self::cart_get_shipping_credits();
                    update_post_meta($order_id, '_credits_used', $total_using_credits);
                    update_post_meta($order_id, '_shipping_credits_used', $shipping_credits);
                    update_post_meta($order_id, '_credits_removed', 0);
                    update_post_meta($order_id, '_order_total', 0);
                    //   update_post_meta( $order_id, '_order_tax', 0 );
                    //   update_post_meta( $order_id, '_order_shipping_tax', 0 );      
                    $order = new WC_Order($order_id);

                    foreach ($order->get_items() as $item_id => $item) {
                        $product_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                        wc_update_order_item_meta($item_id, '_line_subtotal', 0);
                        wc_update_order_item_meta($item_id, '_line_total', 0);
                        //   wc_update_order_item_meta( $item_id, '_line_subtotal_tax', 0 ); 
                        //   wc_update_order_item_meta( $item_id, '_line_tax', 0 ); 
                        //  wc_update_order_item_meta( $item_id, 'tax_amount', 0 ); 
                    }
                }
            }
            $discount_credit = self::cart_get_total_discount_credits();
            $discount_credit = (int) $discount_credit;
            update_post_meta($order_id, '_credits_discount', $discount_credit);
        }

        public function cp_credit_init() {
            // register_taxonomy('credits', array( 'credits' ));

            $labels = array(
                'name' => _x('Credits', 'post type general name', 'mwdcp'),
                'singular_name' => _x('Credit', 'post type singular name', 'mwdcp'),
                'menu_name' => _x('Credits', 'admin menu', 'mwdcp'),
                'name_admin_bar' => _x('Credit', 'add new on admin bar', 'mwdcp'),
                'add_new' => _x('Add New', 'credit', 'mwdcp'),
                'add_new_item' => __('Add New Credit', 'mwdcp'),
                'new_item' => __('New Credit', 'mwdcp'),
                'edit_item' => __('Edit Credit', 'mwdcp'),
                'view_item' => __('View Credit', 'mwdcp'),
                'all_items' => __('All Credits', 'mwdcp'),
                'search_items' => __('Search Credits', 'mwdcp'),
                'parent_item_colon' => __('Parent Credits:', 'mwdcp'),
                'not_found' => __('No credits found.', 'mwdcp'),
                'not_found_in_trash' => __('No credits found in Trash.', 'mwdcp')
            );
            $args = array(
                'labels' => $labels,
                'public' => false,
                'publicly_queryable' => false,
                'show_ui' => false,
                'show_in_menu' => false,
                'query_var' => false,
                'rewrite' => array('slug' => 'credit'),
                'capability_type' => 'post',
                'has_archive' => false,
                'hierarchical' => false,
                'menu_position' => null,
                'supports' => array('title', 'editor')
            );
            register_post_type('credit', $args);

            $credit_posts = wdcp_get_credit_posts();
            if ($credit_posts) {
                foreach ($credit_posts as $cpost) {
                    $product_created = get_post_meta($cpost->ID, '_product_created', true);
                    if (!$product_created) {
                        $credit_image = get_post_meta($cpost->ID, '_credit_image', true);
                        $credit_name = get_post_meta($cpost->ID, '_credit_name', true);
                        $credit_number = get_post_meta($cpost->ID, '_credit_number', true);
                        $credit_price = get_post_meta($cpost->ID, '_credit_price', true);
                        $credit_expiry = get_post_meta($cpost->ID, '_credit_expiry', true);

                        $my_post = array(
                            'post_title' => $credit_name,
                            'post_content' => '',
                            'post_status' => 'publish',
                            'post_author' => 1,
                            'post_type' => 'product'
                        );

                        $post_id = wp_insert_post($my_post);
                        wp_set_object_terms($post_id, 'credits', 'product_type');
                        update_post_meta($post_id, '_credit_product', 1);
                        update_post_meta($post_id, '_credit_image', $credit_image);
                        update_post_meta($post_id, '_credit_name', $credit_name);
                        update_post_meta($post_id, '_credit_number', $credit_number);
                        update_post_meta($post_id, '_credit_price', $credit_price);
                        update_post_meta($post_id, '_credit_expiry', $credit_expiry);
                        update_post_meta($post_id, '_downloadable', "yes");
                        update_post_meta($post_id, '_virtual', "yes");
                        update_post_meta($post_id, '_price', $credit_price);
                        update_post_meta($cpost->ID, '_product_created', 1);
                        wp_delete_post($cpost->ID);
                        $product = wc_get_product($post_id);
                        $product->set_product_visibility('hidden');
                        if (method_exists($product, 'save')) {
                            $product->save();
                        }
                    } else {
                        wp_delete_post($cpost->ID);
                    }
                }
            }
        }

        public function thankyou_wdc_woo_credits($order_id) {
            if (self::order_using_credits($order_id) || self::order_contains_credits($order_id)) {
                $order = wc_get_order($order_id);
                $user_id = $order->get_user_id();
                $credits = floatval(get_user_meta($user_id, "_download_credits", true));
                $clabel = get_option('mwdcp_credits_label');
                $clabel = trim($clabel);
                $clabel = empty($clabel) ? __('Credits', 'mwdcp') : $clabel;
                ?>
                <ul class="order_details credits_remaining">
                    <li class="method">
                <?php echo $clabel . __(' Remaining', 'mwdcp') . ':'; ?>
                        <strong><?php echo $credits; ?></strong>
                    </li>
                </ul>
                <?php
            }
        }

        public function order_item_quantity_html($product_quantity, $item) {
            $product_id = $item['product_id'];

            $product = wc_get_product($product_id);
            if (wdcp_is_credit_product($product_id)) {
                $credit_number = get_post_meta($product_id, '_credit_number', true);
                $clabel = self::get_credit_count($credit_number);
                $product_quantity = ' <strong class="product-quantity"> x ' . $clabel . '</strong>';
            }
            return $product_quantity;
        }

        public function get_credit_count($credits, $quantity = 1) {
            $count = 0;
            if ($credits) {
                $credits *= $quantity;
                if ($credits > 1) {
                    $credits_label = trim(get_option('mwdcp_credits_label'));
                    $label = empty($credits_label) ? __('Credits', 'mwdcp') : $credits_label;
                } else {
                    $credit_label = trim(get_option('mwdcp_credit_label'));
                    $label = empty($credit_label) ? __('Credit', 'mwdcp') : $credit_label;
                }
                $count = $credits . ' ' . $label;
            }

            return $count;
        }

//        public function get_credit_count($credits, $quantity = 1) {
//            $count = 0;
//            if ($credits) {
//                $credits = $credits * $quantity;
//                if ($credits > 1) {
//                    $label = get_option('mwdcp_credits_label');
//                    $label = trim($label);
//                    $label = empty($label) ? __('Credits', 'mwdcp') : $label;
//                } else {
//                    $label = get_option('mwdcp_credit_label');
//                    $label = trim($label);
//                    $label = empty($label) ? __('Credit', 'mwdcp') : $label;
//                }
//                $count = $credits . ' ' . $label;
//            }
//
//            return $count;
//        }


        public function get_credit_count2($credits, $qty = 1) {

            if ($qty > 1) {
                $label = get_option('mwdcp_credits_label');
                $label = trim($label);
                $label = empty($label) ? __('Credits', 'mwdcp') : $label;
            } else {
                $label = get_option('mwdcp_credit_label');
                $label = trim($label);
                $label = empty($label) ? __('Credit', 'mwdcp') : $label;
            }
            $count = $credits . ' ' . $label;


            return $count;
        }

        public function add_label_to_credit_count($credits) {
            if ($credits > 1) {
                $label = get_option('mwdcp_credits_label');
                $label = trim($label);
                $label = empty($label) ? __('Credits', 'mwdcp') : $label;
            } else {
                $label = get_option('mwdcp_credit_label');
                $label = trim($label);
                $label = empty($label) ? __('Credit', 'mwdcp') : $label;
            }
            $count = $credits . ' ' . $label;
            return $count;
        }

        public function get_item_count($count, $type, $order) {
            $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
            if (self::order_contains_credits($order_id)) {
                $credits_used = self::order_get_total_credits($order_id);
                ;
                $credit_number = $credits_used;
                $clabel = self::get_credit_count($credit_number);
                $count = '&nbsp; ' . $clabel;
            }
            return $count;
        }

        public function order_subtotal_to_display($subtotal, $compound, $order) {
            $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
            $payment_method = get_post_meta($order_id, '_payment_method', true);

            if ($payment_method == 'wdc_woo_credits') {
                $hide_price = $this->order_hide_price($order_id);
                if (self::order_using_credits($order_id)) {
                    // if($hide_price){
                    $credits_used = self::order_get_subtotal_used_credits($order_id);
                    $clabel = self::get_credit_count($credits_used);
                    $subtotal = $clabel;
                    // }
                } elseif (self::order_contains_credits($order_id)) {
                    $credits_used = self::order_get_total_credits($order_id);
                    $clabel = self::get_credit_count($credits_used);
                    $subtotal .= '&nbsp;&nbsp;&nbsp; (' . __('for', 'mwdcp') . ' ' . $clabel . ') ';
                }
            }


            return $subtotal;
        }

        public function get_formatted_order_total($formatted_total, $order) {
            $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
            $payment_method = get_post_meta($order_id, '_payment_method', true);
            if ($payment_method == 'wdc_woo_credits') {
                $hide_price = $this->order_hide_price($order_id);
                if (self::order_using_credits($order_id)) {

                    $credits_used = self::order_get_total_used_credits($order_id);
                    $credits_discount = get_post_meta($order_id, '_credits_discount', true);
                    if ($credits_discount) {
                        $credits_used = $credits_used - $credits_discount;
                    }

                    $clabel = self::get_credit_count($credits_used);
                    if ($hide_price) {
                        $formatted_total = $clabel;
                    } else {
                        $formatted_total = $clabel;
                    }

                    if (is_admin()) {
                        $screen = get_current_screen();
                        $total_credits_used = get_post_meta($order_id, '_credits_used', true);
                        $remaining_refund_amount = $total_credits_used;
                        $credits_returned = get_post_meta($order_id, '_credits_returned', true);
                        if ($credits_returned) {
                            $remaining_refund_amount = $total_credits_used - $credits_returned;
                        }

                        $screen = get_current_screen();

                        if ($remaining_refund_amount < $total_credits_used && $screen && 'post' == $screen->base && $screen->post_type == 'shop_order') {

                            $rem_total = '<del>' . $credits_used . '</del> ' . $remaining_refund_amount;
                            $formatted_total = self::get_credit_count2($rem_total, $remaining_refund_amount);
                        }
                    }



                    // $formatted_total = '$0.00';
                } elseif (self::order_contains_credits($order_id) && (is_wc_endpoint_url('view-order') || is_wc_endpoint_url('order-received'))) {
                    $credits_used = self::order_get_total_credits($order_id);
                    $clabel = self::get_credit_count($credits_used);
                    if ($hide_price) {
                        $formatted_total = '(' . __('for', 'mwdcp') . ' ' . $clabel . ') ';
                    } else {
                        $formatted_total .= '&nbsp;&nbsp;&nbsp; (' . __('for', 'mwdcp') . ' ' . $clabel . ') ';
                    }
                }
            }


            return $formatted_total;
        }

        public function wcpdf_woocommerce_totals($totals, $order, $type) {
            $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
            $payment_method = get_post_meta($order_id, '_payment_method', true);
            if ($payment_method == 'wdc_woo_credits') {
                $hide_price = $this->order_hide_price($order_id);
                if (self::order_using_credits($order_id)) {
                    $credits_used = self::order_get_total_used_credits($order_id);
                    $credits_discount = get_post_meta($order_id, '_credits_discount', true);
                    $credits_used = $credits_used - $credits_discount;
                    $clabel = self::get_credit_count($credits_used);
                    if ($hide_price) {
                        $formatted_total = $clabel;
                    } else {
                        $formatted_total = $clabel;
                    }
                    $totals['order_total']['value'] = $formatted_total;
                }
            }


            return $totals;
        }

        public function order_formatted_line_subtotal($subtotal, $item, $order) {
            $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
            $payment_method = get_post_meta($order_id, '_payment_method', true);


            if ($payment_method == 'wdc_woo_credits') {
                if (isset($item['variation_id']) || isset($item['product_id'])) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];

                    $product = wc_get_product($prod_id);
                    $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
                    if ($product->is_type('booking') && $product->get_duration_type() == 'customer') {
                        $duration = $item['item_meta']['_booking_duration'][0];
                        $credits_amount = $credits_amount * $duration;
                    }

                    if (function_exists('wceb_is_bookable') && wceb_is_bookable($product) && isset($item['_booking_duration'])) {

                        $booking_duration = $item['_booking_duration'];
                        $credits_amount = $credits_amount * $booking_duration;
                    }

                    // $credits_amount = get_post_meta( $prod_id, '_credits_amount', true );
                    $clabel = self::get_credit_count($credits_amount, $item['qty']);
                    if ($credits_amount) {
                        $hide_price = $this->order_hide_price($order_id);
                        if ($hide_price || self::order_using_credits($order_id)) {
                            $subtotal = $clabel;
                        } else {
                            $subtotal .= ' &nbsp;&nbsp;&nbsp;(' . __('or', 'mwdcp') . ' ' . $clabel . ')';
                        }
                    }
                }
            }




            return $subtotal;
        }

        public function order_amount_item_total($formatted_total, $order, $item, $inc_tax, $round) {
            $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
            $hide_price = $this->order_hide_price($order_id);
            if (self::order_using_credits($order_id)) {
                //  $formatted_total = '$0.00';
            }
            return $formatted_total;
        }

        public function cart_item_subtotal($sub_total, $cart_item, $cart_item_key) {
            $prod_id = ( isset($cart_item['variation_id']) && $cart_item['variation_id'] != 0 ) ? $cart_item['variation_id'] : $cart_item['product_id'];
            $product = wc_get_product($prod_id);
            $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
            if ($product->is_type('booking') && $product->get_duration_type() == 'customer') {
                $duration = $cart_item['booking']['_duration'];
                $credits_amount = $credits_amount * $duration;
            }

            if (function_exists('wceb_is_bookable') && wceb_is_bookable($product) && isset($cart_item['_booking_duration'])) {
                $booking_duration = $cart_item['_booking_duration'];
                $credits_amount = $credits_amount * $booking_duration;
            }

            $quantity = $cart_item['quantity'];
            $clabel = self::get_credit_count($credits_amount, $quantity);
            if ($credits_amount) {
                $prod_price = $product->get_price();
                $hide_price = $this->hide_price();
                if ($hide_price || !is_numeric($prod_price)) {
                    $sub_total = $clabel;
                } else {
                    $sub_total .= ' &nbsp;&nbsp;&nbsp;(' . __('or', 'mwdcp') . ' ' . $clabel . ')';
                }
            }
            return $sub_total;
        }

        public function checkout_cart_item_quantity($product_quantity, $cart_item, $cart_item_key) {
            $cart_item = WC()->cart->cart_contents[$cart_item_key];
            $prod_id = ( isset($cart_item['variation_id']) && $cart_item['variation_id'] != 0 ) ? $cart_item['variation_id'] : $cart_item['product_id'];
            if (wdcp_is_credit_product($prod_id) && isset($cart_item['credit_id'])) {
                $credit_number = get_post_meta($cart_item['credit_id'], '_credit_number', true);
                $clabel = self::get_credit_count($credit_number);
                if ($credit_number) {
                    $product_quantity = ' <strong class="product-quantity"> x ' . $clabel . '</strong>';
                }
            }
            return $product_quantity;
        }

        public static function get_product_credits2($product) {
            $credits_amount = 0;

            if (is_object($product) && property_exists($product, 'id')) {
                
            } else {
                $product = wc_get_product($product);
            }

            if (is_object($product) && property_exists($product, 'id')) {
                $prod_id = ( WC()->version < '2.7.0' ) ? $product->id : $product->get_id();
                $product_type = ( WC()->version < '2.7.0' ) ? $product->product_type : $product->get_type();
                if (wdcp_product_has_credits($prod_id) && $product_type == 'simple' || $product_type == 'lottery' || $product_type == 'variation') {
                    $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
                }
            }


            return $credits_amount;
        }

        public function get_product_credits($product) {
            $credits_amount = 0;

            if (is_object($product) && property_exists($product, 'id')) {
                $prod_id = ( WC()->version < '2.7.0' ) ? $product->id : $product->get_id();
                $product_type = ( WC()->version < '2.7.0' ) ? $product->product_type : $product->get_type();
                if (wdcp_product_has_credits($prod_id) && $product_type == 'simple' || $product_type == 'lottery' || $product_type == 'variation') {
                    $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
                }
            }


            return $credits_amount;
        }

        public function get_variable_product_credits($product, $min_max = false) {
            $prod_id = ( WC()->version < '2.7.0' ) ? $product->id : $product->get_id();
            if (wdcp_product_has_credits($prod_id)) {
                $product_type = ( WC()->version < '2.7.0' ) ? $product->product_type : $product->get_type();
                if ($product_type == 'simple' || $product_type == 'lottery' || $product_type == 'variation') {
                    $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
                } elseif ($product_type == 'variable') {
                    $prices = $product->get_variation_prices();
                    $price_arr = $prices['price'];

                    if (!empty($price_arr)) {
                        $variation_ids = array_keys($price_arr);
                        $total_count = count($price_arr);
                        $min_id = current($variation_ids);
                        $max_id = end($variation_ids);
                        $min_credits_amount = get_post_meta($min_id, '_credits_amount', true);
                        $max_credits_amount = get_post_meta($max_id, '_credits_amount', true);
                    } else {
                        $variations = $product->get_available_variations();
                        $credits = array();
                        foreach ($variations as $variation) {
                            $credits_amount = get_post_meta($variation['variation_id'], '_credits_amount', true);
                            if ($credits_amount) {
                                $credits[] = $credits_amount;
                            }
                        }
                        asort($credits);
                        $min_credits_amount = current($credits);
                        $max_credits_amount = end($credits);
                    }

                    if ($min_max == 'min') {
                        return $min_credits_amount;
                    } elseif ($min_max == 'max') {
                        return $max_credits_amount;
                    } else {
                        return array(
                            'min' => $min_credits_amount,
                            'max' => $max_credits_amount
                        );
                    }
                }
            }
        }

        public function cart_item_quantity($product_quantity, $cart_item_key) {
            $cart_item = WC()->cart->cart_contents[$cart_item_key];
            $prod_id = ( isset($cart_item['variation_id']) && $cart_item['variation_id'] != 0 ) ? $cart_item['variation_id'] : $cart_item['product_id'];
            if (wdcp_is_credit_product($prod_id) && isset($cart_item['credit_id'])) {
                $credit_number = get_post_meta($cart_item['credit_id'], '_credit_number', true);
                $clabel = self::get_credit_count($credit_number);
                if ($credit_number) {
                    $product_quantity = $clabel;
                }
            }
            return $product_quantity;
        }

        public function get_price_html($price, $product) {
            $credits_amount = 0;

            $prod_id = ( WC()->version < '2.7.0' ) ? $product->id : $product->get_id();
            $product_type = ( WC()->version < '2.7.0' ) ? $product->product_type : $product->get_type();
            if ($product_type == 'simple' || $product_type == 'lottery') {
                $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
            }
            if ($credits_amount) {
                $clabel = self::get_credit_count($credits_amount);
                $prod_price = $product->get_price();
                $hide_price = $this->hide_price();
                if ($hide_price || !is_numeric($prod_price)) {
                    $price = $clabel;
                } else {
                    $price .= '<span class="amount">' . ' (' . __('or', 'mwdcp') . ' ' . $clabel . ')</span>';
                }
            }
            return $price;
        }

//        public function get_price_html($price, $product){
//          $credits_amount = 0;
//        $product_type = ( WC()->version < '2.7.0' ) ? $product->product_type : $product->get_type();
//        $prod_id= ( WC()->version < '2.7.0' ) ? $product->id : $product->get_id();
//           if($product_type == 'simple'){
//             $credits_amount = get_post_meta( $prod_id, '_credits_amount', true );
//           }
//            if($credits_amount){
//                $clabel = self::get_credit_count($credits_amount);
//                $hide_price = $this->hide_price();
//                if($hide_price){
//                     $price = $clabel;
//                }else{
//                    $unit_price = $product->price;
//                    $price = '<span class="amount">' . wc_price( $unit_price ) . ' (or '.$credits_amount.' credits)</span>';
//                    // $price = str_replace( '</span>', ' &nbsp;&nbsp;&nbsp; (or '.$clabel.')</span>', $price );
//                }
//
//            }
//            return $price;
//        }
        public static function cart_remove_noncredit_products() {
            $count = 0;
            $data = array();
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (!wdcp_is_credit_product($prod_id)) {
                        $data[] = array('cart_item_key' => $cart_item_key, 'item_data' => $item);
                        unset(WC()->cart->cart_contents[$cart_item_key]);
                        $count++;
                    }
                }
            }
            if (!empty($data) && count($data) > 0) {
                WC()->session->set('_wdc_removed_cart_data', $data);
            }
            return $count;
        }

        public static function cart_remove_products_with_nocredit() {
            $count = 0;
            $data = array();
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (!wdcp_product_has_credits($prod_id)) {
                        $data[] = array('cart_item_key' => $cart_item_key, 'item_data' => $item);
                        unset(WC()->cart->cart_contents[$cart_item_key]);
                        $count++;
                    }
                }
            }
            if (!empty($data) && count($data) > 0) {
                WC()->session->set('_wdc_removed_cart_data', $data);
            }
            return $count;
        }

        public static function cart_has_products_with_nocredit() {
            $count = 0;
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (!wdcp_product_has_credits($prod_id)) {
                        $count++;
                        break;
                    }
                }
            }
            return $count;
        }

        public static function cart_has_products_with_only_credit_price() {
            $count = 0;
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    $_product = $item['data'];
                    $prod_price = $_product->get_price();
                    if (wdcp_product_has_credits($prod_id) && !is_numeric($prod_price)) {
                        $count++;
                        break;
                    }
                }
            }
            return $count;
        }

        public static function cart_has_products_with_credit() {
            $count = 0;

            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (wdcp_product_has_credits($prod_id)) {
                        $count++;
                        break;
                    }
                }
            }


            return $count;
        }

        public static function cart_has_noncredit_products() {
            $count = 0;
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (!wdcp_is_credit_product($prod_id)) {
                        $count++;
                        break;
                    }
                }
            }
            return $count;
        }

        public static function cart_has_credit_products() {
            $count = 0;
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (wdcp_is_credit_product($prod_id)) {
                        $count++;
                        break;
                    }
                }
            }
            return $count;
        }

        public static function cart_buying_credits() {
            $buying_credits = false;
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (wdcp_is_credit_product($prod_id)) {
                        $buying_credits = true;
                        break;
                    }
                }
            }
            return $buying_credits;
        }

        public static function cart_using_credits() {
            $using_credits = false;
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (!wdcp_is_credit_product($prod_id)) {
                        $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
                        if ($credits_amount) {
                            $using_credits = true;
                            break;
                        }
                    }
                }
            }
            return $using_credits;
        }

        public static function cart_get_total_credits() {
            $total_credits_amount = 0;
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];

                    if (!wdcp_is_credit_product($prod_id)) {

                        $product = wc_get_product($prod_id);
                        $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
                        if ($product->is_type('booking') && $product->get_duration_type() == 'customer') {
                            $duration = $item['booking']['_duration'];
                            $credits_amount = $credits_amount * $duration;
                        }

                        if (function_exists('wceb_is_bookable') && wceb_is_bookable($product) && isset($item['_booking_duration'])) {
                            $booking_duration = $item['_booking_duration'];
                            $credits_amount = $credits_amount * $booking_duration;
                        }

                        // $credits_amount = get_post_meta( $prod_id, '_credits_amount', true );
                        $credits_amount = (int) $credits_amount;
                        $credits_amount *= $item['quantity'];
                        $total_credits_amount += $credits_amount;
                    } else {
                        $credit_number = get_post_meta($item['credit_id'], '_credit_number', true);
                        if ($credit_number) {
                            $total_credits_amount += $credit_number;
                        }
                    }
                }
            }
            return $total_credits_amount;
        }

        public static function cart_get_total_buying_credits() {
            $total_credits_amount = 0;
            $total_discount = 0;
            if (WC()->cart) {
                $coupons = WC()->cart->get_coupons();
                foreach (WC()->cart->get_cart() as $item) {
                    $product = $item['data'];
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (wdcp_is_credit_product($prod_id) && isset($item['credit_id'])) {
                        $credit_number = get_post_meta($item['credit_id'], '_credit_number', true);
                        if ($credit_number) {
                            $undiscounted_price = $credit_number;
                            $price = $credit_number;
                            // if ( ! empty( $coupons ) ) {
                            //     foreach ( $coupons as $code => $coupon ) {
                            //         if ( $coupon->is_valid() && ( $coupon->is_valid_for_product( $product, $item ) || $coupon->is_valid_for_cart() ) ) {
                            //             $discount_amount = $coupon->get_discount_amount( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ? $credit_number : $undiscounted_price, $item, true );
                            //             $discount_amount = min( $price, $discount_amount );
                            //             $credit_number           = max( $credit_number - $discount_amount, 0 );
                            //           //  if ( $add_totals ) {
                            //                 $total_discount     = $discount_amount * $item['quantity'];
                            //            //  }
                            //         }
                            //         if ( 0 >= $credit_number ) {
                            //             break;
                            //         }
                            //     }
                            // }
                            $credit_number = $item['quantity'] * $credit_number;
                            $total_credits_amount += $credit_number;
                        }
                    }
                }
            }
            return $total_credits_amount;
        }

        public static function cart_get_total_discount_credits($coupon1 = false) {
            $total_discount = 0;
            if (WC()->cart) {
                $coupons = WC()->cart->get_coupons();
                foreach (WC()->cart->get_cart() as $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];


                    if (!wdcp_product_has_credits($prod_id)) {
                        $product = $item['data'];
                        $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                        $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
                        $undiscounted_credit = $credits_amount;
                        if ($coupon1) {
                            $discount_amount = 0;
                            if ($coupon1->is_valid() && ( $coupon1->is_valid_for_product($product, $item) || $coupon1->is_valid_for_cart() )) {
                                $discount_amount = $coupon1->get_discount_amount('yes' === get_option('woocommerce_calc_discounts_sequentially', 'no') ? $credits_amount : $undiscounted_credit, $item, true);
                                $discount_amount = min($credits_amount, $discount_amount);
                                $discount_amount = $discount_amount * $item['quantity'];
                                $discount_amount = round($discount_amount, 0);
                                $discount_amount = max($discount_amount, 0);
                            }
                            $total_discount += $discount_amount;
                        } elseif (!empty($coupons)) {
                            foreach ($coupons as $code => $coupon) {
                                $discount_amount = 0;
                                if ($coupon->is_valid() && ( $coupon->is_valid_for_product($product, $item) || $coupon->is_valid_for_cart() )) {
                                    $discount_amount = $coupon->get_discount_amount('yes' === get_option('woocommerce_calc_discounts_sequentially', 'no') ? $credits_amount : $undiscounted_credit, $item, true);
                                    $discount_amount = min($credits_amount, $discount_amount);
                                    $discount_amount = $discount_amount * $item['quantity'];
                                    $discount_amount = max($discount_amount, 0);
                                    if (0 >= $credits_amount) {
                                        break;
                                    }
                                }
                                $total_discount += $discount_amount;
                            }
                        }
                    }
                }
            }
            $total_discount = max($total_discount, 0);
            return $total_discount;
        }

        public static function cart_get_total_using_credits($discount = false) {
            $total_credits_amount = 0;
            $shipping_credits = self::cart_get_shipping_credits();
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (!wdcp_is_credit_product($prod_id)) {
                        $product = $item['data'];
                        //$credits_amount = get_post_meta( $prod_id, '_credits_amount', true ); 

                        $product = wc_get_product($prod_id);
                        $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
                        if ($product->is_type('booking') && $product->get_duration_type() == 'customer') {
                            $duration = $item['booking']['_duration'];
                            $credits_amount = $credits_amount * $duration;
                        }

                        if (function_exists('wceb_is_bookable') && wceb_is_bookable($product) && isset($item['_booking_duration'])) {
                            $booking_duration = $item['_booking_duration'];
                            $credits_amount = $credits_amount * $booking_duration;
                        }

                        if ($credits_amount) {
                            $credits_amount = $item['quantity'] * $credits_amount;
                            $total_credits_amount += $credits_amount;
                        }
                    }
                }
            }
            if ($discount) {
                $total_discount = self::cart_get_total_discount_credits();
                $total_credits_amount = $total_credits_amount - $total_discount;
                $total_credits_amount = round($total_credits_amount, 0);
                $total_credits_amount = max($total_credits_amount, 0);
            }
            $total_credits_amount += $shipping_credits;
            return $total_credits_amount;
        }

        public static function cart_get_subtotal_using_credits($discount = false) {
            $total_credits_amount = 0;
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (!wdcp_is_credit_product($prod_id)) {
                        $product = $item['data'];

                        // $credits_amount = get_post_meta( $prod_id, '_credits_amount', true ); 
                        //  $product = wc_get_product($prod_id);
                        $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
                        if ($product->is_type('booking') && $product->get_duration_type() == 'customer') {
                            $duration = $item['booking']['_duration'];
                            $credits_amount = $credits_amount * $duration;
                        }

                        if (function_exists('wceb_is_bookable') && wceb_is_bookable($product) && isset($item['_booking_duration'])) {
                            $booking_duration = $item['_booking_duration'];
                            $credits_amount = $credits_amount * $booking_duration;
                        }

                        if ($credits_amount) {
                            $credits_amount = $item['quantity'] * $credits_amount;
                            $total_credits_amount += $credits_amount;
                        }
                    }
                }
            }
            if ($discount) {
                $total_discount = self::cart_get_total_discount_credits();
                $total_credits_amount = $total_credits_amount - $total_discount;
                $total_credits_amount = round($total_credits_amount, 0);
                $total_credits_amount = max($total_credits_amount, 0);
            }
            return $total_credits_amount;
        }

        public function cart_subtotal($cart_subtotal, $compound, $obj) {

            $total_credits_using_amount = self::cart_get_subtotal_using_credits();
            $total_credits_buying_amount = self::cart_get_total_buying_credits();
            $total_credits_using_amount = (int) $total_credits_using_amount;
            $total_credits_buying_amount = (int) $total_credits_buying_amount;
            $usinglabel = self::get_credit_count($total_credits_using_amount);
            $buyinglabel = self::get_credit_count($total_credits_buying_amount);
            $hide_price = $this->hide_price();

            if ($total_credits_using_amount > 0 && $total_credits_buying_amount > 0) {
                if ($hide_price) {
                    $cart_subtotal .= '&nbsp;&nbsp;&nbsp; (' . __('or', 'mwdcp') . ' ' . $usinglabel . ' & for ' . $buyinglabel . ')';
                } else {
                    $cart_subtotal .= '&nbsp;&nbsp;&nbsp; (' . __('or', 'mwdcp') . ' ' . $usinglabel . ' & for ' . $buyinglabel . ')';
                }
            }
            if ($total_credits_using_amount > 0 && $total_credits_buying_amount == 0) {
                if ($hide_price) {
                    $cart_subtotal = $usinglabel;
                } else {
                    $cart_subtotal .= '&nbsp;&nbsp;&nbsp; (' . __('or', 'mwdcp') . ' ' . $usinglabel . ')';
                }
            }
            if ($total_credits_buying_amount > 0 && $total_credits_using_amount == 0) {
                $clabel = self::get_credit_count($total_credits_buying_amount);
                $cart_subtotal .= '&nbsp;&nbsp;&nbsp; (' . __('for', 'mwdcp') . ' ' . $buyinglabel . ')';
            }
            return $cart_subtotal;
        }

        public static function cart_get_shipping_credits() {
            $credit_cost = 0;
            $chosen_methods = WC()->session->get('chosen_shipping_methods');
            $chosen_shipping = $chosen_methods[0];
            $method_id = str_replace(':', '_', $chosen_shipping);
            $option_key = 'woocommerce_' . $method_id . '_settings';
            $settings = get_option($option_key);
            if (isset($settings['credit_cost']) && absint($settings['credit_cost']) > 0) {
                $credit_cost = $settings['credit_cost'];
            }
            return $credit_cost;
        }

        public function cart_total($cart_subtotal) {
            $total_credits_using_amount = self::cart_get_total_using_credits();
            $total_credits_buying_amount = self::cart_get_total_buying_credits();
            $total_credits_using_amount = (int) $total_credits_using_amount;
            $total_credits_buying_amount = (int) $total_credits_buying_amount;
            $usinglabel = self::get_credit_count($total_credits_using_amount);
            $buyinglabel = self::get_credit_count($total_credits_buying_amount);
            $hide_price = $this->hide_price();
            if ($total_credits_using_amount > 0 && $total_credits_buying_amount > 0) {
                $usinglabel = self::get_credit_count(self::cart_get_total_using_credits(true));
                $cart_subtotal .= '&nbsp;&nbsp;&nbsp; (' . __('or', 'mwdcp') . ' ' . $usinglabel . ' & for ' . $buyinglabel . ')';
            }
            if ($total_credits_using_amount > 0 && $total_credits_buying_amount == 0) {
                $usinglabel = self::get_credit_count(self::cart_get_total_using_credits(true));
                if ($hide_price) {
                    $cart_subtotal = $usinglabel;
                } else {
                    $cart_subtotal .= '&nbsp;&nbsp;&nbsp; (' . __('or', 'mwdcp') . ' ' . $usinglabel . ')';
                }
            }
            if ($total_credits_buying_amount > 0 && $total_credits_using_amount == 0) {
                $cart_subtotal .= '&nbsp;&nbsp;&nbsp; (' . __('for', 'mwdcp') . ' ' . $buyinglabel . ')';
            }
            return $cart_subtotal;
        }

        public function single_product_summary() {
            global $product;
            $prod_id = ( WC()->version < '2.7.0' ) ? $product->id : $product->get_id();
            $credits_amount = get_post_meta($prod_id, '_credits_amount', true);
            if ($credits_amount) {
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $credits = floatval(get_user_meta($user_id, "_download_credits", true));
                    $clabel = self::get_credit_count($credits);
                    $clabel_only = get_option('mwdcp_credits_label');
                    $clabel_only = trim($clabel_only);
                    $label_default = __('Credits', 'mwdcp');
                    $clabel_only = empty($clabel_only) ? $label_default : $clabel_only;
                    echo '<p class="download-credits"> ' . __('You have', 'mwdcp') . ' ' . $clabel . ' ' . __('remaining', 'mwdcp') . ' <br/><a class="buy-more-credits" href="' . get_permalink(wc_get_page_id('myaccount')) . '">' . __('Buy More', 'mwdcp') . '</a> ' . $clabel_only . '</p>';
                }
            }
        }

        public function wdc_woo_credits_init_gateway($methods) {
            $methods[] = 'Woo_Download_Credits_Platinum_Gateway';
            return $methods;
        }

        public static function instance() {
            if (is_null(self::$_instance))
                self::$_instance = new self();
            return self::$_instance;
        }

        public function wp_enqueue_woocommerce_style() {
            wp_register_style('wdcp-woocommerce', plugins_url('/assets/css/public-min.css', __FILE__), false);
            if (class_exists('woocommerce')) {
                wp_enqueue_style('wdcp-woocommerce');
            }
        }

        public function register_styles($hook) {
            wp_register_style('mwdc_admin', plugins_url('/assets/css/admin-min.css', __FILE__), false);
            $screen = get_current_screen();
            if ($hook == 'post.php' && 'post' == $screen->base && $screen->post_type == 'shop_order') {
                global $wpdb, $post;
                $order_id = $post->ID;
                if (self::order_using_credits($order_id)) {
                    wp_enqueue_style('mwdc_order', plugins_url('/assets/css/admin-order.css', __FILE__), false);
                }
            }
        }

        public function register_scripts() {
            wp_register_script('mwdc_admin', plugins_url('/assets/js/admin-min.js', __FILE__));
            wp_localize_script('mwdc_admin', 'MyAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
        }

        public static function get_account_credits($user_id = null, $formatted = true, $exclude_order_id = 0) {
            $user_id = $user_id ? $user_id : get_current_user_id();
            if ($user_id) {
                $credits = floatval(get_user_meta($user_id, "_download_credits", true));
                $orders_with_pending_credits = get_posts(array(
                    'numberposts' => -1,
                    'post_type' => 'shop_order',
                    'post_status' => array_keys(wc_get_order_statuses()),
                    'fields' => 'ids',
                    'meta_query' => array(
                        array(
                            'key' => '_customer_user',
                            'value' => $user_id
                        ),
                        array(
                            'key' => '_credits_removed',
                            'value' => '0',
                        ),
                        array(
                            'key' => '_credits_used',
                            'value' => '0',
                            'compare' => '>'
                        )
                    )
                ));
                foreach ($orders_with_pending_credits as $order_id) {
                    if (null !== WC()->session && !empty(WC()->session->order_awaiting_payment) && $order_id == WC()->session->order_awaiting_payment) {
                        continue;
                    }
                    if ($exclude_order_id === $order_id) {
                        continue;
                    }
                    $credits = $credits - floatval(get_post_meta($order_id, '_credits_used', true));
                }
            } else {
                $credits = 0;
            }
            return $formatted ? wc_price($credits) : $credits;
        }

        public static function get_credits($customer_id = 0) {
            if (!$customer_id) {
                $customer_id = get_current_user_id();
            }
            $credits = floatval(get_user_meta($customer_id, "_download_credits", true));
            return $credits;
        }

        public static function add_credits($customer_id, $amount) {
            $credits = floatval(get_user_meta($customer_id, "_download_credits", true));
            $credits = $credits ? $credits : 0;
            $credits += floatval($amount);
            update_user_meta($customer_id, '_download_credits', $credits);
        }

        public static function order_can_refund($order, $amount) {
            $resp = false;
            if (is_a($order, 'WC_Order')) {
                $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
            } else {
                $order_id = $order;
            }

            $total_credits_used = get_post_meta($order_id, '_credits_used', true);
            $remaining_refund_amount = $total_credits_used;
            $credits_returned = get_post_meta($order_id, '_credits_returned', true);

            if ($credits_returned) {
                $remaining_refund_amount = $total_credits_used - $credits_returned;
            }

            if ($remaining_refund_amount && $remaining_refund_amount > 0 && $remaining_refund_amount >= $amount) {
                $resp = true;
            }
            return $resp;
        }

        public static function order_refund_credits($order, $amount) {
            $resp = false;

            if (is_a($order, 'WC_Order')) {
                $order_id = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
            } else {
                $order_id = $order;
                $order = wc_get_order($order_id);
            }

            $user_id = ( WC()->version < '2.7.0' ) ? $order->user_id : $order->get_user_id();
            $transaction_id = get_post_meta($order_id, '_transaction_id', true);
            $total_credits_used = get_post_meta($order_id, '_credits_used', true);
            $credits_returned = get_post_meta($order_id, '_credits_returned', true);
            $remaining_refund_amount = $total_credits_used - $credits_returned;

            if ($remaining_refund_amount > 0 && $remaining_refund_amount >= $amount) {
                $credits_returned = $credits_returned + $amount;
                update_user_meta($user_id, '_order_' . $order_id . '_refunded', $credits_returned);
                update_post_meta($order_id, '_credits_returned', $credits_returned);
                self::add_credits($user_id, $amount);
                $resp = true;
            }
            return $resp;
        }

        public static function update_credits($customer_id, $amount) {
            $credits = floatval($amount);
            update_user_meta($customer_id, '_download_credits', $credits);
        }

        public static function customer_get_credits($customer_id) {
            global $wpdb;
            $query = "SELECT mua.*,mub.meta_value as credits 
                      FROM $wpdb->usermeta mua
                      LEFT JOIN $wpdb->usermeta mub ON mub.meta_key = CONCAT('credits_amount_', mua.umeta_id)
                      WHERE mua.user_id = $customer_id AND mua.meta_key = 'credit_expiry_time' ORDER BY mua.meta_value ASC";
            $metarows = $wpdb->get_results($query);
            return $metarows;
        }

        public static function customer_get_expiredcredits() {
            $datetime = new DateTime();
            $currtimestamp = $datetime->getTimestamp();
            global $wpdb;
            $query = "SELECT mua.*,mub.meta_value as credits 
                      FROM $wpdb->usermeta mua
                      LEFT JOIN $wpdb->usermeta mub ON mub.meta_key = CONCAT('credits_amount_', mua.umeta_id)
                      WHERE mua.meta_key = 'credit_expiry_time' AND mua.meta_value < $currtimestamp ORDER BY mua.meta_value ASC";
            $metarows = $wpdb->get_results($query);
            return $metarows;
        }

        public static function customer_update_meta($umeta_id, $meta_value) {
            global $wpdb;
            $wpdb->update(
                    $wpdb->usermeta, array(
                'meta_value' => $meta_value,
                    ), array('meta_key' => 'credits_amount_' . $umeta_id), array(
                '%d'
                    ), array('%s')
            );
        }

        public static function customer_get_credit_expiry_list($customer_id) {
            $output = '';
            $metarows = self::customer_get_credits($customer_id);
            if ($metarows) {
                $output .= '<table class="table-caption-narrow table-expiry-credits">
                       <thead>
                          <tr>
                             <th class="th1">' . __('Credits', 'mwdcp') . '</th>
                             <th class="th2">' . __('Expiring On', 'mwdcp') . '</th>
                          </tr>
                       </thead>
                       <tbody>';
                foreach ($metarows as $row) {
                    $credits = $row->credits;
                    $credits = (int) $credits;
                    $timestamp = $row->meta_value;
                    $output .= '<tr class="credit-row">
                                     <td class="Credits">' . $credits . '</td>
                                     <td class="expiry-date">' . date("j M, Y", $timestamp) . '</td>
                                  </tr>';
                }
                $output .= '   </tbody>
                                   </table>';
            }
            return $output;
        }

        public static function customer_delete_meta_by_id($umeta_id) {
            global $wpdb;
            $wpdb->delete(
                    $wpdb->usermeta, array('umeta_id' => $umeta_id)
            );
        }

        public static function customer_delete_meta_by_key($customer_id, $meta_key) {
            global $wpdb;
            $wpdb->delete(
                    $wpdb->usermeta, array(
                'meta_key' => $meta_key,
                'user_id' => $customer_id
                    ), array(
                '%s',
                '%d',
                    )
            );
        }

        public static function customer_delete_metas($customer_id, $umeta_id) {
            self::customer_delete_meta_by_id($umeta_id);
            $meta_key = 'credits_amount_' . $umeta_id;
            self::customer_delete_meta_by_key($customer_id, $meta_key);
        }

        public static function customer_remove_expire_credits($customer_id, $amount) {
            $metarows = self::customer_get_credits($customer_id);
            $amount = (int) $amount;
            if ($metarows) {
                foreach ($metarows as $row) {
                    $credits = $row->credits;
                    $credits = (int) $credits;
                    $umeta_id = $row->umeta_id;
                    $umeta_id = (int) $umeta_id;
                    if ($credits >= $amount) {
                        $remaining_credit = $credits - $amount;
                        if ($remaining_credit >= 0) {
                            self::customer_update_meta($umeta_id, $remaining_credit);
                            $amount = 0;
                        } else {
                            self::customer_delete_metas($customer_id, $umeta_id);
                            $amount = $amount - $credits;
                        }
                    } else {
                        $amount = $amount - $credits;
                        self::customer_delete_metas($customer_id, $umeta_id);
                    }
                    if ($amount <= 0) {
                        break;
                    }
                }
            }
        }

        public static function remove_credits($customer_id, $amount) {
            $credits = floatval(get_user_meta($customer_id, "_download_credits", true));
            $credits = $credits ? $credits : 0;
            $credits = $credits - floatval($amount);
            update_user_meta($customer_id, '_download_credits', max(0, $credits));
            self::customer_remove_expire_credits($customer_id, $amount);
        }

        public static function cart_contains_item($credit_id) {
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $item) {
                    if ($item['credit_id'] == $credit_id) {
                        return true;
                    }
                }
            }
            return false;
        }

        private function download_credits_buy_form2($show_expiry = false) {
            // $credit_tiers = get_posts(array('post_type' => 'product','posts_per_page'=> -1,'post_status'   => 'publish','orderby' => 'date', 'order'  => 'ASC','meta_key' => '_credit_product','meta_value' => 1,));
            $credit_tiers = wdcp_get_credit_products();
            //  $credit_tiers = get_posts(array('post_type' => 'credit','posts_per_page'=> -1,'post_status'   => 'publish','orderby' => 'date', 'order'  => 'ASC',));
            ?>
            <?php
            if ($credit_tiers) :$label_only = get_option('mwdcp_credits_label');
                $label_only = trim($label_only);
                $label_default = __('Credits', 'mwdcp');
                $label_only = empty($label_only) ? $label_default : $label_only;
                $expire_days_label = get_option('mwdcp_expire_days_label');
                $expire_days_label = trim($expire_days_label);
                $expire_days_label = empty($expire_days_label) ? 'Expire Days' : $expire_days_label;
                ?>
                <form method="post">
                    <table class="table-caption-narrow table-offers">
                        <thead>
                            <tr>
                                <th class="th1"><?php esc_attr_e('Name', 'mwdcp'); ?></th>
                                <th class="th2"><?php echo $label_only; ?></th>
                <?php if ($show_expiry && strtolower($show_expiry) == 'yes'): ?>
                                    <th class="th5"><?php echo $expire_days_label; ?></th>
                <?php endif; ?>   
                                <th class="th3"><?php esc_attr_e('Price', 'mwdcp'); ?></th>
                                <th class="invisible th4"></th>
                            </tr>
                        </thead>
                        <tbody>
                <?php foreach ($credit_tiers as $credit): ?>
                    <?php
                    $expiry_days_txt = '&nbsp;';
                    $credit_id = $credit->ID;
                    $global_expiry_days = get_option('mwdcp_global_expiry_days', 0);
                    $global_expiry_days = (int) $global_expiry_days;
                    $credits_amount = get_post_meta($credit_id, '_credit_number', true);
                    if ($credits_amount) {
                        $credits_expiry = get_post_meta($credit_id, '_credit_expiry', true);
                        $credits_expiry = (int) $credits_expiry;
                        $expiry_days = ($credits_expiry) ? $credits_expiry : $global_expiry_days;
                        if ($expiry_days > 0) {
                            $expiry_days_txt = $expiry_days . ' days';
                        }
                    }
                    ?>
                                <tr class="credit-row">
                                    <td class="name"><?php echo get_post_meta($credit->ID, '_credit_name', true); ?></td>
                                    <td class="credits"><?php echo get_post_meta($credit->ID, '_credit_number', true); ?> <?php echo $label_only; ?></td>
                    <?php if ($show_expiry && strtolower($show_expiry) == 'yes'): ?>
                                        <td class="expiry_days"><?php echo $expiry_days_txt; ?></td>
                    <?php endif; ?>                          
                                    <td class="price"><?php echo get_post_meta($credit->ID, '_credit_price', true); ?></td>
                                    <td class="buy-now-btn"><button type="submit" class="button" value="<?php _e('Buy Now', 'mwdcp'); ?>" name="choose_credits[<?php echo $credit->ID; ?>]"><?php _e('Buy Now', 'mwdcp'); ?></button></td>
                                </tr>
                <?php endforeach; ?>
                        </tbody>
                    </table>
                    <input type="hidden" name="wdc_download_credits_buy" value="true" />
                                <?php wp_nonce_field('download-credits-buy'); ?>
                </form>
                            <?php endif; ?>
            <?php
        }

        public static function cart_contains_credits() {
            if (WC()->cart) {
                foreach (WC()->cart->get_cart() as $item) {
                    $prod_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    if (wdcp_is_credit_product($prod_id)) {
                        return true;
                    }
                }
            }
            return false;
        }

        public static function using_credits() {
            return !is_null(WC()->session) && WC()->session->get('use-download-credits') && self::can_apply_credits();
        }

        public static function can_apply_credits() {
            if (self::cart_contains_credits() || !is_user_logged_in()) {
                $can_apply = false;
            }
            if (!self::get_account_credits(get_current_user_id(), false)) {
                $can_apply = false;
            }
            return $can_apply;
        }

        public static function used_credits_amount() {
            return WC()->session->get('used-download-credits');
        }

        public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
            $prod_id = ( isset($variation_id) && $variation_id != 0 ) ? $variation_id : $product_id;
            $product = wc_get_product($prod_id);
            if (wdcp_is_credit_product($prod_id)) {
                $credits_amount = get_post_meta($prod_id, '_credit_price', true);
                $credit_name = get_post_meta($prod_id, '_credit_name', true);
                $cart_item_data['credits_amount'] = $credits_amount;
                $cart_item_data['credits_name'] = $credit_name;
                $cart_item_data['credit_id'] = $prod_id;
            }
            return $cart_item_data;
        }

        public function add_cart_item($cart_item) {
            $prod_id = ( isset($cart_item['variation_id']) && $cart_item['variation_id'] != 0 ) ? $cart_item['variation_id'] : $cart_item['product_id'];

            if (!empty($cart_item['credits_amount']) && wdcp_is_credit_product($prod_id)) {
                $credits_amount = get_post_meta($prod_id, '_credit_price', true);
                $credit_name = get_post_meta($prod_id, '_credit_name', true);

                $cart_item['data']->set_price($credits_amount);
                $cart_item['data']->title = $credit_name;
                if (isset($cart_item['credit_id'])) {
                    $cart_item['data']->credit_id = $cart_item['credit_id'];
                }
            }
            return $cart_item;
        }

        public function credits_buy_form_handler() {
            if (isset($_POST['wdc_download_credits_buy']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'download-credits-buy')) {
                if (is_array($_POST['choose_credits'])) {
                    $credit_id = key($_POST['choose_credits']);
                    $credit = get_post($credit_id);
                    if (self::cart_remove_noncredit_products()) {
                        wc_add_notice(__('Non-credit products have been removed', 'mwdcp'), 'notice');
                    }
                    WC()->cart->add_to_cart($credit_id, true, '', '', array('credit_id' => $credit_id, 'credits_amount' => get_post_meta($credit->ID, '_credit_price', true), 'credits_name' => get_post_meta($credit->ID, '_credit_name', true)));
                    if (self::cart_buying_credits() && !is_user_logged_in()) {
                        if ('no' == get_option('woocommerce_enable_signup_and_login_from_checkout')) {
                            update_option('woocommerce_enable_signup_and_login_from_checkout', 'yes');
                            self::$signup_option_changed = true;
                        }
                        if ('yes' == get_option('woocommerce_enable_guest_checkout')) {
                            update_option('woocommerce_enable_guest_checkout', 'no');
                            self::$guest_checkout_option_changed = true;
                        }
                    }
                    wp_redirect(get_permalink(wc_get_page_id('cart')));
                    exit;
                }
            }
        }

        public function before_my_account() {
            $user_id = get_current_user_id();
            $label_default = __('Credits', 'mwdcp');
            $label_only = get_option('mwdcp_credits_label');
            $label_only = trim($label_only);
            $label_only = empty($label_only) ? $label_default : $label_only;
            $credits = floatval(get_user_meta($user_id, "_download_credits", true));
            echo '<h2>' . __($label_only, 'mwdcp') . '</h2>';
            echo '<p>' . sprintf(__('You have', 'mwdcp') . "<strong> %s </strong>" . __($label_only, 'mwdcp'), $credits) . '.' . '</p>';
            echo '<div class="credit_expiry_list">' . self::customer_get_credit_expiry_list($user_id) . "</div>";
            $this->download_credits_buy_form();
        }

        private function download_credits_buy_form() {
            $user_id = get_current_user_id();
            // $credit_tiers = get_posts(array('post_type' => 'product','posts_per_page'=> -1,'post_status'   => 'publish','orderby' => 'date', 'order'  => 'ASC','meta_key' => '_credit_product','meta_value' => 1,));
            $credit_tiers = wdcp_get_credit_products();
            //  $credit_tiers = get_posts(array('post_type' => 'credit','posts_per_page'=> -1,'post_status'   => 'publish','orderby' => 'date', 'order'  => 'ASC',));
            ?>
            <?php
            if ($credit_tiers) :
                $label_default = __('Credits', 'mwdcp');
                $label_only = get_option('mwdcp_credits_label');
                $label_only = trim($label_only);
                $label_only = empty($label_only) ? $label_default : $label_only;
                $myacount_label = get_option('mwdcp_myaccount_label');
                $myacount_label = trim($myacount_label);
                $myacount_label = empty($myacount_label) ? __('Buy Credits', 'mwdcp') : $myacount_label;
                ?>
                <form method="post">
                    <h3 class="download-label"><label for="credits_amount"><?php echo __($myacount_label, 'mwdcp'); ?></label></h3>
                    <table class="table-caption-narrow table-offers">
                        <thead>
                            <tr>
                                <th class="th1"><?php esc_attr_e('Name', 'mwdcp'); ?></th>
                                <th class="th2"><?php echo $label_only; ?></th>
                                <th class="th3"><?php esc_attr_e('Price', 'mwdcp'); ?></th>
                                <th class="invisible th4"></th>
                            </tr>
                        </thead>
                        <tbody>
                <?php foreach ($credit_tiers as $credit): ?>
                                <tr class="credit-row">
                                    <td class="name"><?php echo get_post_meta($credit->ID, '_credit_name', true); ?></td>
                                    <td class="credits"><?php echo get_post_meta($credit->ID, '_credit_number', true); ?> <?php echo $label_only; ?></td>
                                    <td class="price"><?php echo get_post_meta($credit->ID, '_credit_price', true); ?></td>
                                    <td class="buy-now-btn"><button type="submit" class="button" value="<?php _e('Buy Now', 'mwdcp'); ?>" name="choose_credits[<?php echo $credit->ID; ?>]"><?php _e('Buy Now', 'mwdcp'); ?></button></td>
                                </tr>
                <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="form-row">
                        <input type="hidden" name="wdc_download_credits_buy" value="true" />
                        <span class="imp-msg" style="">
                <?php esc_attr_e('IMPORTANT: To successfully purchase', 'mwdcp'); ?> <?php echo $label_only; ?>,<br/>
                <?php esc_attr_e('please make sure there are', 'mwdcp'); ?> <a href="<?php echo get_permalink(wc_get_page_id('cart')); ?>" style=""><?php esc_attr_e('no other products', 'mwdcp'); ?></a> <?php esc_attr_e('in your cart upon checkout', 'mwdcp'); ?>.
                        </span>
                    </p>
                <?php wp_nonce_field('download-credits-buy'); ?>
                </form>
            <?php endif; ?>
            <?php
        }

        public function add_custom_general_fields() {
            global $woocommerce, $post;
            $product_id = $post->ID;
            $_product = wc_get_product($product_id);
            $product_type = ( WC()->version < '2.7.0' ) ? $_product->product_type : $_product->get_type();
            if ('simple' == $product_type || 'booking' == $product_type) {
                echo '<div class="options_group">';
                woocommerce_wp_text_input(
                        array(
                            'id' => '_credits_amount',
                            'label' => __('Price in Credits', 'mwdcp'),
                            'placeholder' => '',
                            'desc_tip' => 'true',
                            'description' => __('The credits for this product to download.', 'mwdcp'),
                            'type' => 'text',
                        )
                );
                echo '</div>';
            }
        }

        public function add_product_options_lottery() {
            global $woocommerce, $post;
            $product_id = $post->ID;
            $_product = wc_get_product($product_id);
            $product_type = ( WC()->version < '2.7.0' ) ? $_product->product_type : $_product->get_type();
            if ('lottery' == $product_type) {
                echo '<div class="options_group">';
                woocommerce_wp_text_input(
                        array(
                            'id' => '_credits_amount',
                            'label' => __('Credit Required to Participate ', 'mwdcp'),
                            'placeholder' => '',
                            'desc_tip' => 'true',
                            'description' => __('The credits for Participate.', 'mwdcp'),
                            'type' => 'text',
                        )
                );
                echo '</div>';
            }
        }

        public function subscriptions_product_options_pricing() {
            global $woocommerce, $post;
            $product_id = $post->ID;
            $_product = wc_get_product($product_id);
            $product_type = ( WC()->version < '2.7.0' ) ? $_product->product_type : $_product->get_type();
            if ('subscription' == $product_type) {
                $selects = array();
                //  $credit_tiers = get_posts(array('post_type' => 'credit','posts_per_page'=> -1,'post_status'   => 'publish','orderby' => 'date', 'order'  => 'ASC',));
                $credit_tiers = wdcp_get_credit_products();
                if ($credit_tiers):
                    foreach ($credit_tiers as $credit):
                        $credit_name = get_post_meta($credit->ID, '_credit_name', true);
                        $selects [$credit->ID] = $credit_name;
                    endforeach;
                endif;
                echo '<div class="options_group">';
                woocommerce_wp_select(array(
                    'id' => '_subscription_credits_plan',
                    'class' => 'wc_input_subscription_length select short',
                    'label' => __('Credits Plan', 'mwdcp'),
                    'options' => $selects,
                    'desc_tip' => true,
                    'description' => __('Select credit plan with this subscription.', 'mwdcp'),
                        )
                );

                woocommerce_wp_text_input(array(
                    'id' => '_subscription_credits_expiry_days',
                    'class' => 'wc_input_subscription_credits_expiry_days short',
                    'label' => __('Credits Plan Expiry Days', 'mwdcp'),
                    'placeholder' => __('e.g. 60', 'example Days', 'mwdcp'),
                    'description' => __('Optionally enter expiry days here which will override default days of subscription', 'mwdcp'),
                    'desc_tip' => true,
                    'type' => 'text',
                ));

                echo '</div>';
            }
        }

        public function variation_settings_fields($loop, $variation_data, $variation) {
            woocommerce_wp_text_input(
                    array(
                        'id' => '_credits_amount[' . $variation->ID . ']',
                        'label' => __('Price in Credits ', 'mwdcp'),
                        'placeholder' => '',
                        'desc_tip' => 'true',
                        'description' => __('Enter Price in Credits here.', 'mwdcp'),
                        'value' => get_post_meta($variation->ID, '_credits_amount', true)
                    )
            );
        }

        public function save_variation_settings_fields($post_id) {
            $credits_amount = $_POST['_credits_amount'][$post_id];
            if (!empty($credits_amount)) {
                update_post_meta($post_id, '_credits_amount', esc_attr($credits_amount));
            }
        }

        public function product_custom_meta_data_save($post_id) {
            if (!isset($_POST['post_type']) || 'product' != $_POST['post_type']) {
                return;
            }

            if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX )) {
                $woocommerce_credits_amount = $_POST['_credits_amount'];
                if (!empty($woocommerce_credits_amount)) {
                    update_post_meta($post_id, '_credits_amount', esc_attr($woocommerce_credits_amount));
                } else {
                    delete_post_meta($post_id, '_credits_amount');
                }
            }
        }

        public function add_custom_general_fields_save($post_id) {
            $woocommerce_credits_amount = $_POST['_credits_amount'];
            if (!empty($woocommerce_credits_amount)) {
                update_post_meta($post_id, '_credits_amount', esc_attr($woocommerce_credits_amount));
            } else {
                delete_post_meta($post_id, '_credits_amount');
            }

            $subscription_credits_plan = $_POST['_subscription_credits_plan'];
            if (!empty($subscription_credits_plan)) {
                update_post_meta($post_id, '_subscription_credits_plan', esc_attr($subscription_credits_plan));
            } else {
                delete_post_meta($post_id, '_subscription_credits_plan');
            }

            $credits_expiry_days = $_POST['_subscription_credits_expiry_days'];
            $credits_expiry_days = (int) $credits_expiry_days;
            if ($credits_expiry_days) {
                update_post_meta($post_id, '_subscription_credits_expiry_days', esc_attr($credits_expiry_days));
            } else {
                delete_post_meta($post_id, '_subscription_credits_expiry_days');
            }
        }

        public function woocommerce_product_class_for_credits($classname, $product_type, $post_type, $product_id) {
            if ('product' === get_post_type($product_id) && wdcp_is_credit_product($product_id)) {
                return 'Woo_Download_Credits_Platinum_Product_Credits';
            }
            return $classname;
        }

        public function get_cart_item_from_session($cart_item, $values, $cart_item_key) {
            if (!empty($values['credits_amount'])) {
                $cart_item['credits_amount'] = $values['credits_amount'];
                $cart_item = $this->add_cart_item($cart_item);
            }
            return $cart_item;
        }

        public function order_status_completed_remove_credits($order_id) {
            // $order_status_processing = get_option('mwdcp_order_status_processing');
            // if($order_status_processing){
            //     $order       = wc_get_order( $order_id );
            //     $order->update_status('processing');  
            // }            
        }

        public static function order_get_discount_credits($order_id) {
            $total_discount = 0;
            $order = wc_get_order($order_id);
            $coupon_codes = $order->get_used_coupons();
            foreach ($order->get_items() as $item) {
                $product_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                $product = $order->get_product_from_item($item);
                $credits_amount = get_post_meta($product_id, '_credit_number', true);
                $undiscounted_credit = $credits_amount;
                if (wdcp_is_credit_product($product_id) && $credits_amount) {
                    foreach ($coupon_codes as $code) {
                        $coupon = new WC_Coupon($code);
                        $discount_amount = 0;
                        if ($coupon->is_valid() && ( $coupon->is_valid_for_product($product, $item) || $coupon->is_valid_for_cart() )) {
                            $discount_amount = $coupon->get_discount_amount('yes' === get_option('woocommerce_calc_discounts_sequentially', 'no') ? $credits_amount : $undiscounted_credit, $item, true);
                            $discount_amount = min($credits_amount, $discount_amount);
                            $discount_amount = $discount_amount * $item['quantity'];
                            $discount_amount = max($discount_amount, 0);
                            if (0 >= $credits_amount) {
                                break;
                            }
                        }
                        $total_discount += $discount_amount;
                    }
                }
            }
            return $total_discount;
        }

        public static function order_get_total_credits($order_id) {
            $order_id = (int) $order_id;
            $total_credits_amount = 0;
            if ($order_id) {
                $order = wc_get_order($order_id);
                foreach ($order->get_items() as $item) {
                    $product_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    $credit_number = get_post_meta($product_id, '_credit_number', true);
                    if (wdcp_is_credit_product($product_id) && $credit_number) {
                        $total_credits_amount += $credit_number;
                    }
                }
            }
            return $total_credits_amount;
        }

        public static function order_get_total_using_credits($order_id) {
            $order_id = (int) $order_id;
            $total_credits_amount = 0;
            if ($order_id) {
                $order = wc_get_order($order_id);
                foreach ($order->get_items() as $item) {
                    $product_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                    $credit = self::get_product_credits2($product_id);
                    if ($credit) {
                        $total_credits_amount += $credit_number;
                    }
                }
            }
            return $total_credits_amount;
        }

        public static function order_contains_credits($order_id) {
            $order_id = (int) $order_id;
            $credits_product = false;
            if ($order_id) {
                $credits_used = get_post_meta($order_id, '_credits_buying', true);
                if ($credits_used) {
                    $credits_product = true;
                }
                // $order           = wc_get_order( $order_id );                
                // foreach ( $order->get_items() as $item ) {
                //     $product_id = ( isset( $item['variation_id'] ) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                //     if ( 'credit' === get_post_type($product_id) ) {
                //        $credits_product = true;
                //     }
                // }
            }
            return $credits_product;
        }

        public static function order_using_credits($order_id) {
            $order_id = (int) $order_id;
            $credits_product = false;
            if ($order_id) {
                $credits_used = get_post_meta($order_id, '_credits_used', true);
                if ($credits_used) {
                    $credits_product = true;
                }
                // $order           = wc_get_order( $order_id );            
                // foreach ( $order->get_items() as $item ) {
                //     $product = $order->get_product_from_item( $item );
                //     $product_id = ( isset( $item['variation_id'] ) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                //     if ( get_post_meta( $product_id, '_credits_amount', true ) ) {
                //         $credits_product = true;
                //         break;
                //     }
                // }
            }
            return $credits_product;
        }

        public static function order_get_total_used_credits($order_id) {
            $order_id = (int) $order_id;
            $credits_used = 0;
            if ($order_id) {
                $credits_used = get_post_meta($order_id, '_credits_used', true);
                // $order           = wc_get_order( $order_id );                
                // foreach ( $order->get_items() as $item ) {
                //     $product = $order->get_product_from_item( $item );
                //     $product_id = ( isset( $item['variation_id'] ) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                //     if ( $credits_amount = get_post_meta( $product_id, '_credits_amount', true ) ) {
                //         $credits_amount = $credits_amount * $item['qty'];
                //         $credits_used += $credits_amount;
                //     }
                // }
            }
            return $credits_used;
        }

        public static function order_get_subtotal_used_credits($order_id) {
            $order_id = (int) $order_id;
            $credits_used = 0;
            if ($order_id) {
                $credits_used = get_post_meta($order_id, '_credits_used', true);
                $shipping_credits_used = get_post_meta($order_id, '_shipping_credits_used', true);
                if ($shipping_credits_used) {
                    $credits_used -= $shipping_credits_used;
                }
            }
            return $credits_used;
        }

        public function order_add_expiry_data($order_id) {
            $order = wc_get_order($order_id);
            $customer_id = $order->get_user_id();
            $global_expiry_days = get_option('mwdcp_global_expiry_days', 0);
            $global_expiry_days = (int) $global_expiry_days;
            foreach ($order->get_items() as $item_id => $item) {
                $product_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                $credits_amount = get_post_meta($product_id, '_credit_number', true);
                $qty = $item['qty'];
                $credits_amount = $credits_amount * $qty;
                if ($credits_amount) {
                    $credits_expiry = get_post_meta($product_id, '_credit_expiry', true);
                    $credits_expiry = (int) $credits_expiry;
                    $expiry_days = ($credits_expiry) ? $credits_expiry : $global_expiry_days;
                    if ($expiry_days > 0) {
                        $datetime = new DateTime();
                        $datetime->modify('+ ' . $expiry_days . ' days');
                        $time = $datetime->getTimestamp();
                        $umeta_id = add_user_meta($customer_id, 'credit_expiry_time', $time);
                        add_user_meta($customer_id, 'credits_amount_' . $umeta_id, $credits_amount);
                    }
                }
            }
        }

        public function credit_add_expiry_data($customer_id, $credit_id, $credits_amount = 0) {
            $global_expiry_days = get_option('mwdcp_global_expiry_days', 0);
            $global_expiry_days = (int) $global_expiry_days;

            if (!$credits_amount) {
                $credits_amount = get_post_meta($credit_id, '_credit_number', true);
            }

            if ($credits_amount) {
                $credits_expiry = get_post_meta($credit_id, '_credit_expiry', true);
                $credits_expiry = (int) $credits_expiry;
                $expiry_days = ($credits_expiry) ? $credits_expiry : $global_expiry_days;
                if ($expiry_days > 0) {
                    $datetime = new DateTime();
                    $datetime->modify('+ ' . $expiry_days . ' days');
                    $time = $datetime->getTimestamp();
                    $umeta_id = add_user_meta($customer_id, 'credit_expiry_time', $time);
                    add_user_meta($customer_id, 'credits_amount_' . $umeta_id, $credits_amount);
                }
            }
        }

        public function order_get_buying_credit_counts($order_id) {
            $total_credits = 0;
            $order = wc_get_order($order_id);
            foreach ($order->get_items() as $item_id => $item) {
                $product_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                $qty = $item['qty'];
                $credits_amount = get_post_meta($product_id, '_credit_number', true);
                if ($credits_amount) {
                    $total_credits += $credits_amount * $qty;
                }
            }
            return $total_credits;
        }

        public function order_status_completed_add_credits($order_id) {
            $order = wc_get_order($order_id);
            // $items          = $order->get_items();
            $customer_id = $order->get_user_id();
            if ($customer_id && !get_post_meta($order_id, '_credits_added', true)) {
                $credits_buying = $this->order_get_buying_credit_counts($order_id);
                if ($credits_buying) {
                    self::add_credits($customer_id, $credits_buying);
                    update_post_meta($order_id, '_credits_added', 1);
                    $this->order_add_expiry_data($order_id);
                }
            }
        }

    }

    Woo_Download_Credits_Platinum::instance();

    class Woo_Download_Credits_Platinum_Admin_Options {

        public static function init() {
            add_action('admin_menu', array(new self, 'add_page'));
            add_action('wp_ajax_wdcp_user_searchcredit_post', array(new self, 'wdcp_user_searchcredit_post'));
            add_action('wp_ajax_wdcp_search_userterm_post', array(new self, 'wdcp_search_userterm_post'));
        }

        public function add_page() {
            $page_title = __('Woo Credits Options', 'mwdcp');
            $menu_title = __('Woo Credits', 'mwdcp');
            add_submenu_page('woocommerce', $page_title, $menu_title, 'manage_options', 'mwdc_settings', array($this, 'mwdc_options_page_cb'));
            $page_title = __('Edit Credits', 'mwdcp');
            $menu_title = __('Edit Credits', 'mwdcp');
            add_submenu_page('woocommerce', $page_title, $menu_title, 'manage_options', 'mwdc_credits', array($this, 'mwdc_edit_credits_cb'));
        }

        public function admin_head() {
            wp_enqueue_media();
            wp_enqueue_style('mwdc_admin');
            wp_enqueue_script('mwdc_admin');
        }

        public function mwdc_edit_credits_cb() {
            if (isset($_POST['mwdc_editcredits_submit']) && $_POST['mwdc_editcredits_submit']) {
                $customer_ids = $_POST['customer_id'];
                $credit_numbers = $_POST['credit_number'];
                if (is_array($customer_ids) && count($credit_numbers) > 0) {
                    foreach ($customer_ids as $key => $uid) {
                        if (isset($credit_numbers[$key])) {
                            $credits = $credit_numbers[$key];
                            $credits = (int) $credits;
                            update_user_meta($uid, '_download_credits', $credits);
                        }
                    }
                }
                if (isset($_POST['num_users']) && $_POST['num_users']) {
                    $num_users = $_POST['num_users'];
                    $num_users = (int) $num_users;
                    update_option('mwdcp_num_users', $num_users);
                } else {
                    delete_option('mwdcp_num_users');
                }
            }


            $mwdcp_num_users = get_option('mwdcp_num_users', 25);

            $pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $limit = $mwdcp_num_users;
            $response = $this->get_user_list_form($pagenum, $limit, $search);
            extract($response);
            wp_enqueue_script('jquery-ui-autocomplete');
            $this->admin_head();
            ?>
            <div class="wrap">
                <div id="icon-options-general" class="icon32"></div>
                <h2><?php esc_attr_e('Woo Credits', 'mwdcp'); ?></h2>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <div class="meta-box-sortables ui-sortable">
                                <div class="postbox">
                                    <div class="handlediv" title="Click to toggle"><br></div>
                                    <h3 class="hndle"><span><?php echo __('Manage User Credits', 'mwdcp'); ?></span></h3>
                                    <div class="inside">
                                        <form id="customer_credits_form" method="get" action="<?php echo admin_url('admin.php?page=mwdc_credits'); ?>">
                                            <table id="dataTable4" class="form credit-user-search" style="padding:20px 6px;">
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <!-- <label class="glabel" for="">Search User</label> -->
                                                            <input type="hidden" name="page" value="mwdc_credits">
                                                            <input type="text" name="search" id="cuser-search" class="regular-text" value="<?php echo $search; ?>" placeholder="<?php echo __('Search', 'mwdcp'); ?>">
                                                            <input id="searchcredits-submit" type="submit" class="submit button-primary" value="<?php echo __('Search', 'mwdcp'); ?>">
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </form>
            <?php if ($user_credit_list): ?>
                                            <form id="customer_credits_form" method="post" action="">
                                                <div id="wo_tabs">
                                                    <table id="dataTable" class="form">
                                                        <thead>
                                                            <tr>
                                                                <th>&nbsp;</th>
                                                                <th>
                                                                    <label for="customer_id"><?php echo __('User ID', 'mwdcp'); ?></label>
                                                                </th>
                                                                <th>
                                                                    <label for="customer_name"><?php echo __('Name', 'mwdcp'); ?></label>
                                                                </th>
                                                                <th>
                                                                    <label for="credits_number"<?php echo __('No. of Credits', 'mwdcp'); ?></label>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="user-credits-list-row">
                <?php echo $user_credit_list; ?>
                                                        </tbody>
                                                    </table>

                                                    <table id="dataTable5" class="form credit-user-numbers" style="padding:20px 6px;">
                                                        <tbody>
                                                            <tr>
                                                                <td>
                                                                    <label class="glabel" for=""><?php echo __('Number of Users Per Page', 'mwdcp'); ?></label>
                                                                    <input type="text" name="num_users" id="num_users" class="small" value="<?php echo $mwdcp_num_users; ?>" placeholder="25">
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>

                                                    <input type="hidden" name="mwdc_editcredits_submit" value="true">
                                                    <br/>
                                                    &nbsp;&nbsp;&nbsp;<input id="editcredits-submit" type="submit" class="submit button-primary" value="<?php echo __('Update', 'mwdcp'); ?>">
                                                </div>
                                            </form>
            <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br class="clear">
                </div>
            </div>   
            <?php
            echo '<div id="credits-pagination" class="credits-pagination">' . $pagination . '</div>';
        }

        public function wdcp_user_searchcredit_post() {
            $search = esc_attr(trim($_POST['search']));
            $search = trim($search);
            $ret_array = array();
            $ret_array['status'] = 'error';
            $ret_array['search'] = $search;
            $ret_array['user_credit_list'] = false;
            $ret_array['pagination'] = false;
            // if($search && !empty($search)){
            $ret = $this->get_user_list_form(1, 15, $search);
            if ($ret['user_credit_list']) {
                $ret_array['status'] = 'success';
                $ret_array['user_credit_list'] = $ret['user_credit_list'];
            }
            if ($ret['pagination']) {
                $ret_array['pagination'] = $ret['pagination'];
            }
            // }
            wp_send_json($ret_array);
        }

        public function wdcp_search_userterm_post() {
            $searchTerm = esc_attr(trim($_POST['searchTerm']));
            $searchTerm = trim($searchTerm);
            $term = esc_attr(trim($_POST['term']));
            $term = trim($term);
            $search_terms = $this->get_user_search_terms($term);
            wp_send_json($search_terms);
        }

        public static function get_user_search_terms($search = '') {
            $ret_arr = array();
            $search = trim($search);
            $search = strtolower($search);
            global $wpdb;
            $user_sql = "SELECT DISTINCT u.ID, u.user_login, u.user_nicename, u.user_email, u.display_name
                    FROM $wpdb->users u
                    INNER JOIN $wpdb->usermeta m ON m.user_id = u.ID
                    WHERE LOWER(u.user_login) LIKE '%" . $search . "%'
                    OR LOWER(u.user_nicename) LIKE '%" . $search . "%'
                    OR LOWER(u.user_email) LIKE '%" . $search . "%'
                    OR LOWER(u.display_name) LIKE '%" . $search . "%'
                    ORDER BY u.ID";
            $users = $wpdb->get_results($user_sql);
            if ($users):
                foreach ($users as $usr):
                    $user_login = strtolower($usr->user_login);
                    $user_email = strtolower($usr->user_email);
                    $display_name = strtolower($usr->display_name);
                    $user_nicename = strtolower($usr->user_nicename);
                    if (strpos($user_login, $search) !== false && !in_array($user_login, $ret_arr)) {
                        $ret_arr[] = $user_login;
                    }
                    if (strpos($user_email, $search) !== false && !in_array($user_email, $ret_arr)) {
                        $ret_arr[] = $user_email;
                    }
                    if (strpos($display_name, $search) !== false && !in_array($display_name, $ret_arr)) {
                        $ret_arr[] = $display_name;
                    }
                    if (strpos($user_nicename, $search) !== false && !in_array($user_nicename, $ret_arr)) {
                        $ret_arr[] = $user_nicename;
                    }
                endforeach;
            endif;
            return $ret_arr;
        }

        public function get_user_list_form($pagenum, $limit = 15, $search = '') {
            $ret_arr = array();
            $ret_arr['user_credit_list'] = false;
            $ret_arr['pagination'] = false;
            // $pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
            // $limit = 15; // number of rows in page
            $offset = ( $pagenum - 1 ) * $limit;
            $pagination = '';
            $user_credit_list = '';
            $search = trim($search);
            $args = array(
                'offset' => $offset,
                'number' => $limit,
                'orderby' => 'ID',
                'fields' => 'all',
                'search' => $search,
                'count_total' => true
            );
            $user_query = new WP_User_Query($args);
            $total_users = $user_query->get_total();
            $users = $user_query->get_results();
            if ($users && $total_users):
                ob_start();
                foreach ($users as $usr):
                    $credit_number = get_user_meta($usr->ID, '_download_credits', true);
                    $credit_number = (int) $credit_number;
                    $user_credit_list .= '<tr class="usercreditrow" data-userid="' . $usr->ID . '">
               <td><input type="hidden" name="customer_id[]" value="' . $usr->ID . '"></td>
             <td>
                <input type="text" name="customer_id1[]" value="' . $usr->ID . '" disabled="disabled">
             </td>
             <td>
                <input type="text" required="required" name="customer_name[]" value="' . $usr->display_name . '" disabled="disabled">
             </td>
             <td>
                <input type="text" required="required" class="small" name="credit_number[]" value="' . $credit_number . '">
             </td>                               
          </tr>';
                endforeach;
                $ret_arr['user_credit_list'] = $user_credit_list;
                $num_of_pages = ceil($total_users / $limit);
                $base_url = admin_url('admin.php?page=mwdc_credits');
                if ($search && empty($search)) {
                    $base_url = add_query_arg('search', $search, $base_url);
                }
                $page_links = paginate_links(array(
                    'base' => add_query_arg('pagenum', '%#%', $base_url),
                    'format' => '',
                    'end_size' => 5,
                    'mid_size' => 5,
                    'show_all' => true,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $num_of_pages,
                    'current' => $pagenum
                ));
                if ($page_links) {
                    $pagination = '<div class="tablenav"><div class="tablenav-pages" style="float: left;">' . $page_links . '</div></div>';
                }
                $ret_arr['pagination'] = $pagination;
            endif;
            return $ret_arr;
        }

        public function mwdc_options_page_cb() {
            if (isset($_POST['mwdc_fix_reports_submit']) && $_POST['mwdc_fix_reports_submit']) {
                $args = array(
                    'post_type' => 'shop_order',
                    'post_status' => array('wc-completed'),
                    'posts_per_page' => -1
                );
                $loop = new WP_Query($args);
                while ($loop->have_posts()) : $loop->the_post();
                    $order_id = $loop->post->ID;
                    $order = new WC_Order($order_id);
                    $payment_method = get_post_meta($order_id, '_payment_method', true);
                    if ('wdc_woo_credits' === $payment_method && Woo_Download_Credits_Platinum::order_using_credits($order_id)) {
                        update_post_meta($order_id, '_order_total', 0);
                        foreach ($order->get_items() as $item_id => $item) {
                            $product_id = ( isset($item['variation_id']) && $item['variation_id'] != 0 ) ? $item['variation_id'] : $item['product_id'];
                            if (get_post_meta($product_id, '_credits_amount', true)) {
                                wc_update_order_item_meta($item_id, '_line_subtotal', 0);
                                wc_update_order_item_meta($item_id, '_line_total', 0);
                            }
                        }
                    }
                endwhile;
            }
            if (isset($_POST['mwdc_options_submit']) && $_POST['mwdc_options_submit']) {
                $credit_ids = $_POST['credit_id'];
                $credit_names = $_POST['credit_name'];
                $credit_numbers = $_POST['credit_number'];
                $credit_prices = $_POST['credit_price'];
                $credit_images = $_POST['credit_image'];
                $credit_expirys = $_POST['credit_expiry'];
                if (isset($_POST['mwdcp_hide_price'])) {
                    $mwdc_hide_price = $_POST['mwdcp_hide_price'];
                    $mwdc_hide_price = (int) $mwdc_hide_price;
                }
                //  $show_creditonly_products = $_POST['mwdcp_show_creditonly_products'];
                //  $show_creditonly_products = (int) $show_creditonly_products;
                if (isset($_POST['mwdcp_hide_other_paymentsg'])) {
                    $hide_other_paymentsg = $_POST['mwdcp_hide_other_paymentsg'];
                    $hide_other_paymentsg = (int) $hide_other_paymentsg;
                }

                if (isset($_POST['mwdcp_order_status_processing'])) {
                    $order_status_processing = $_POST['mwdcp_order_status_processing'];
                    $order_status_processing = (int) $order_status_processing;
                }

                $mwdcp_reset_credits = 0;
                if (isset($_POST['mwdcp_reset_credits'])) {
                    $mwdcp_reset_credits = $_POST['mwdcp_reset_credits'];
                    $mwdcp_reset_credits = (int) $mwdcp_reset_credits;
                }


                if (isset($_POST['mwdcp_hide_price']) && $_POST['mwdcp_hide_price'] == 1) {
                    update_option('mwdcp_hide_price', $mwdc_hide_price);
                } else {
                    delete_option('mwdcp_hide_price');
                }

                if (isset($_POST['mwdcp_reset_credits']) && $mwdcp_reset_credits == 1) {
                    update_option('mwdcp_reset_credits', $mwdcp_reset_credits);
                } else {
                    delete_option('mwdcp_reset_credits');
                }

                //  if ( isset( $_POST['mwdcp_show_creditonly_products'] ) &&  $show_creditonly_products == 1) {
                //        update_option ( 'mwdcp_show_creditonly_products', $show_creditonly_products );
                //  }else{
                //       delete_option ( 'mwdcp_show_creditonly_products');
                //  }
                if (isset($_POST['mwdcp_hide_other_paymentsg']) && $hide_other_paymentsg == 1) {
                    update_option('mwdcp_hide_other_paymentsg', $hide_other_paymentsg);
                } else {
                    delete_option('mwdcp_hide_other_paymentsg');
                }

                if (isset($_POST['mwdcp_order_status_processing']) && $order_status_processing == 1) {
                    update_option('mwdcp_order_status_processing', $order_status_processing);
                } else {
                    delete_option('mwdcp_order_status_processing');
                }

                if (isset($_POST['mwdcp_credit_label']) && !empty($_POST['mwdcp_credit_label'])) {
                    update_option('mwdcp_credit_label', trim($_POST['mwdcp_credit_label']));
                } else {
                    delete_option('mwdcp_credit_label');
                }

                if (isset($_POST['mwdcp_credits_label']) && !empty($_POST['mwdcp_credits_label'])) {
                    update_option('mwdcp_credits_label', trim($_POST['mwdcp_credits_label']));
                } else {
                    delete_option('mwdcp_credits_label');
                }

                if (isset($_POST['mwdcp_myaccount_label']) && !empty($_POST['mwdcp_myaccount_label'])) {
                    update_option('mwdcp_myaccount_label', trim($_POST['mwdcp_myaccount_label']));
                } else {
                    delete_option('mwdcp_myaccount_label');
                }

                if (isset($_POST['mwdcp_expire_days_label']) && !empty($_POST['mwdcp_expire_days_label'])) {
                    update_option('mwdcp_expire_days_label', trim($_POST['mwdcp_expire_days_label']));
                } else {
                    delete_option('mwdcp_expire_days_label');
                }


                if (isset($_POST['mwdcp_global_expiry_days']) && !empty($_POST['mwdcp_global_expiry_days'])) {
                    update_option('mwdcp_global_expiry_days', trim($_POST['mwdcp_global_expiry_days']));
                } else {
                    delete_option('mwdcp_global_expiry_days');
                }
                if (is_array($credit_names) && count($credit_names) > 0) {
                    foreach ($credit_names as $key => $value) {
                        if (empty($credit_ids[$key])) {
                            $my_post = array(
                                'post_title' => $credit_names[$key],
                                'post_content' => '',
                                'post_status' => 'publish',
                                'post_author' => 1,
                                'post_type' => 'product'
                            );
                            $post_id = wp_insert_post($my_post);
                            wp_set_object_terms($post_id, 'credits', 'product_type');
                            update_post_meta($post_id, '_credit_product', 1);
                            update_post_meta($post_id, '_credit_image', $credit_images[$key]);
                            update_post_meta($post_id, '_credit_name', $credit_names[$key]);
                            update_post_meta($post_id, '_credit_number', $credit_numbers[$key]);
                            update_post_meta($post_id, '_credit_price', $credit_prices[$key]);
                            update_post_meta($post_id, '_credit_expiry', $credit_expirys[$key]);
                            update_post_meta($post_id, '_downloadable', "yes");
                            update_post_meta($post_id, '_virtual', "yes");
                            update_post_meta($post_id, '_price', $credit_prices[$key]);
                            $product = wc_get_product($post_id);
                            $product->set_product_visibility('hidden');
                            if (method_exists($product, 'save')) {
                                $product->save();
                            }
                        } else {
                            $post_id = $credit_ids[$key];
                            $post_arr = array(
                                'ID' => $post_id,
                                'post_type' => 'product',
                                'post_title' => $credit_names[$key],
                            );
                            wp_update_post($post_arr);
                            wp_set_object_terms($post_id, 'credits', 'product_type');
                            update_post_meta($post_id, '_credit_product', 1);
                            update_post_meta($post_id, '_credit_image', $credit_images[$key]);
                            update_post_meta($post_id, '_credit_name', $credit_names[$key]);
                            update_post_meta($post_id, '_credit_number', $credit_numbers[$key]);
                            update_post_meta($post_id, '_credit_price', $credit_prices[$key]);
                            update_post_meta($post_id, '_credit_expiry', $credit_expirys[$key]);
                            update_post_meta($post_id, '_downloadable', "yes");
                            update_post_meta($post_id, '_virtual', "yes");
                            //  update_post_meta ( $post_id, '_sale_price', $credit_prices[$key] );
                            update_post_meta($post_id, '_price', $credit_prices[$key]);
                            $product = wc_get_product($post_id);
                            $product->set_product_visibility('hidden');
                            if (method_exists($product, 'save')) {
                                $product->save();
                            }
                        }
                    }
                }
            }
            $this->admin_head();
            $credit_default = __('Credits', 'mwdcp');
            $credit_tiers = wdcp_get_credit_products();
            $label_only = get_option('mwdcp_credits_label');
            $label_only = trim($label_only);
            $label_only = empty($label_only) ? $credit_default : $label_only;
            $mwdcp_global_expiry_days = get_option('mwdcp_global_expiry_days', 0);
            ?>
            <div class="wrap">
                <div id="icon-options-general" class="icon32"></div>
                <h2><?php esc_attr_e('Woo Credits', 'mwdcp'); ?></h2>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <div class="meta-box-sortables ui-sortable">

                                <div class="postbox-20">
                                    <div class="woo-credits-admin-logo">
            <?php
            echo '<img src="' . plugins_url('/woo-download-credits-platinum/assets/img/logo.png') . '"/>';
            ?>
                                    </div>
                                    <ul class="woo-credits-admin-submenu">
                                        <li><a href="https://www.woocredits.com/my-account" target="_blank"><?php esc_attr_e('Get API Key', 'mwdcp'); ?></a></li>
                                        <li><a href="/wp-admin/options-general.php?page=woo_credits_platinum_dashboard"><?php esc_attr_e('Activate API Key', 'mwdcp'); ?></a></li>
                                        <li><a href="https://www.woocredits.com/my-account" target="_blank"><?php esc_attr_e('Manage Renewal', 'mwdcp'); ?></a></li>
                                        <li><a href="http://woocredits.supportico.us"><?php esc_attr_e('Support', 'mwdcp'); ?></a></li>
                                    </ul>
                                </div>

                                <div class="postbox-80">
                                    <!--<div class="handlediv" title="<?php esc_attr_e('Click to toggle', 'mwdcp'); ?>"><br></div>-->

                                    <div class="woo-credits-section-header">
                                        <h3 class="hndle"><span><?php esc_attr_e('Credit Bundles', 'mwdcp'); ?></span></h3>
                                        <input type="button" class="submit button-primary" id="add-tier-new" value="<?php esc_attr_e('Add New Bundle', 'mwdcp'); ?>" onClick="addRow('dataTable', 'dataTable1')" />
                                    </div><!-- end woo-credits-section-header -->
                                    <div class="inside">
                                        <table id="dataTable1" class="form" >
                                            <tbody>
                                                <tr class="creditrow">
                                                    <td class="woo-credits-admin-hidden"><input type="hidden" value="" name="credit_id[]"></td>
                                                    <td class="product-thumbnail">
                                                        <input type="hidden" value="" name="credit_image[]" class="credit_image">
            <?php echo wc_placeholder_img(); ?>                                                                                                          </td>
                                                    <td class="woo-credits-bundle-name">
                                                        <input class="woo-credits-bundle-name" type="text" value="" name="credit_name[]" required="required">
                                                    </td>
                                                    <td class="woo-credits-no-credits">
                                                        <input class="woo-credits-no-credits" type="text" value="" name="credit_number[]" class="small" required="required">
                                                    </td>
                                                    <td class="woo-credits-price">
                                                        <input class="woo-credits-price" type="text" value="" name="credit_price[]" class="small" required="required">
                                                    </td>
                                                    <td class="woo-credits-expiry">
                                                        <input class="woo-credits-expiry" type="text" value="" name="credit_expiry[]" class="small">
                                                    </td>
                                                    <td class="woo-credits-credit-id">
                                                        <input class="woo-credits-credit-id" type="hidden" value="" name="credit_id1[]">
                                                    </td>                                              
                                                    <td class="woo-credits-remove-bundle-button"><input type="button" value="<?php esc_attr_e('Remove', 'mwdcp'); ?>" class="remove-tiers submit button-primary"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <form id="credits_form" method="post" action="">
                                            <div id="wo_tabs">
                                                <table id="dataTable" class="form" >
                                                    <thead>
                                                        <tr>
                                                            <th class="woo-credits-icon">
                                                                <label for="credit_image"><?php esc_attr_e('Icon', 'mwdcp'); ?></label>
                                                            </th>
                                                            <th class="woo-credits-bundle-name">
                                                                <label for="credit_name"><?php esc_attr_e('Name', 'mwdcp'); ?></label>
                                                            </th>
                                                            <th class="woo-credits-no-credits">
                                                                <label for="credit_number"><?php esc_attr_e('# of Credits', 'mwdcp'); ?></label>
                                                            </th>
                                                            <th class="woo-credits-price">
                                                                <label for="credit_price"><?php esc_attr_e('Price', 'mwdcp'); ?></label>
                                                            </th>
                                                            <th class="woo-credits-expiry">
                                                                <label for="credit_expiry"><?php esc_attr_e('Expiry Days', 'mwdcp'); ?></label>
                                                            </th>  
                                                            <th class="woo-credits-credit-id">
                                                                <label for="credit_id"><?php esc_attr_e('Credit ID', 'mwdcp'); ?></label>
                                                            </th>                                                   
                                                        </tr>
                                                    </thead>
                                                    <tbody>
            <?php if ($credit_tiers): ?>
                <?php foreach ($credit_tiers as $credit): ?>
                    <?php
                    $credit_image = get_post_meta($credit->ID, '_credit_image', true);
                    ?>
                                                                <tr class="creditrow" data-creditid="<?php echo $credit->ID; ?>">
                                                                    <td class="woo-credits-admin-hidden"><input type="hidden" name="credit_id[]" value="<?php echo $credit->ID; ?>"></td>
                                                                    <td class="product-thumbnail">
                                                                        <input type="hidden" class="credit_image" name="credit_image[]" value="<?php echo $credit_image; ?>">
                    <?php if ($credit_image): $img = wp_get_attachment_image_src($credit_image); ?>
                        <?php
                        if ($img && is_array($img)) {
                            echo '<img class="" src="' . $img[0] . '" >';
                        }
                        ?>
                    <?php else: ?>
                        <?php echo wc_placeholder_img(); ?>
                    <?php endif; ?>
                                                                    </td>
                                                                    <td class="woo-credits-bundle-name">
                                                                        <input class="woo-credits-bundle-name" type="text" required="required" name="credit_name[]" value="<?php echo get_post_meta($credit->ID, '_credit_name', true); ?>">
                                                                    </td>
                                                                    <td class="woo-credits-no-credits">
                                                                        <input class="woo-credits-no-credits" type="text" required="required" class="small"  name="credit_number[]" value="<?php echo get_post_meta($credit->ID, '_credit_number', true); ?>">
                                                                    </td>
                                                                    <td class="woo-credits-price">
                                                                        <input class="woo-credits-price" type="text" required="required" class="small"  name="credit_price[]" value="<?php echo get_post_meta($credit->ID, '_credit_price', true); ?>">
                                                                    </td>
                                                                    <td class="woo-credits-expiry">
                                                                        <input class="woo-credits-expiry" type="text" class="small"  name="credit_expiry[]" value="<?php echo get_post_meta($credit->ID, '_credit_expiry', true); ?>">
                                                                    </td>
                                                                    <td class="woo-credits-credit-id">
                                                                        <input class="woo-credits-credit-id" type="text" name="credit_id1[]" value="<?php echo $credit->ID; ?>">
                                                                    </td>                                                     
                                                                    <td class="woo-credits-remove-bundle-button"><input type="button" class="remove-tiers submit button-primary" value="<?php esc_attr_e('Remove', 'mwdcp'); ?>"   /></td>
                                                                </tr>
                <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr class="creditrow">
                                                                <td><input type="hidden" value="" name="credit_id[]"></td>
                                                                <td class="product-thumbnail">
                                                                    <input type="hidden" value="" name="credit_image[]" class="credit_image">
                                                            <?php echo wc_placeholder_img(); ?>                
                                                                </td>
                                                                <td class="woo-credits-bundle-name">
                                                                    <input class="woo-credits-bundle-name" type="text" value="" name="credit_name[]" required="required">
                                                                </td>
                                                                <td class="woo-credits-no-credits">
                                                                    <input class="woo-credits-no-credits" type="text" value="" name="credit_number[]" class="small" required="required">
                                                                </td>
                                                                <td class="woo-credits-price">
                                                                    <input class="woo-credits-price" type="text" value="" name="credit_price[]" class="small" required="required">
                                                                </td>
                                                                <td class="woo-credits-expiry">
                                                                    <input class="woo-credits-expiry" type="text" value="" name="credit_expiry[]" class="small" >
                                                                </td>
                                                                <td class="woo-credits-credit-id">
                                                                    <input class="woo-credits-credit-id" type="hidden" value="" name="credit_id1[]">
                                                                </td>                                               
                                                                <td class="woo-credits-remove-bundle-button"><input type="button" value="<?php esc_attr_e('Remove', 'mwdcp'); ?>" class="remove-tiers submit button-primary"></td>
                                                            </tr>
            <?php endif; ?>
                                                    </tbody>
                                                </table>
                                                <table id="dataTable4" class="form label-settings" style="padding:20px 6px;">
                                                    <tbody>
                                                        <tr>
                                                            <td>
                                                                <label class="glabel" for=""><?php echo __('Global Credit Expiry Days', 'mwdcp'); ?></label>
                                                                <input type="text" name="mwdcp_global_expiry_days" id="mwdcp_global_expiry_days" class="regular-text" value="<?php echo $mwdcp_global_expiry_days; ?>" placeholder="<?php echo __('Global Expiry Days', 'mwdcp'); ?>">
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>

                                                <div class="woo-credits-section-header">
                                                    <h3 class="hndle"><span><?php esc_attr_e('Shortcodes', 'mwdcp'); ?></span></h3>
                                                    <p><?php esc_attr_e('Below you will find available shortcodes and how to use them.', 'mwdcp'); ?></p>
                                                </div><!-- end woo-credits-section-header-->
                                                <div class="wdcp-wrap wrap">
                                                    <p>
            <?php esc_attr_e('you can create credit buy url by using below shortcode replacing', 'mwdcp'); ?> <strong>'xxxx'</strong> <?php esc_attr_e('with corresponding credit ID from above table', 'mwdcp'); ?>
                                                    </p>
                                                    <p>
            <?php esc_attr_e('you can also change link text by using', 'mwdcp'); ?> <strong>'link_text'</strong> <?php esc_attr_e('parameter', 'mwdcp'); ?>
                                                    </p>
                                                    <p>
                                                        <strong>[buy_credit_url credit_id= xxxx link_text='<?php esc_attr_e('Buy Now', 'mwdcp'); ?>']</strong>
                                                    </p>
                                                </div><!-- end wdcp-wrap wrap-->

            <?php
            $mwdc_hide_price = get_option('mwdcp_hide_price');
            $checked = '';
            if ($mwdc_hide_price) {
                $checked = 'checked="checked"';
            }
            $hide_other_paymentsg = get_option('mwdcp_hide_other_paymentsg');
            $checked2 = '';
            if ($hide_other_paymentsg) {
                $checked2 = 'checked="checked"';
            }

            $order_status_processing = get_option('mwdcp_order_status_processing');
            $checked3 = '';
            if ($order_status_processing) {
                $checked3 = 'checked="checked"';
            }

            $reset_credits_opt = get_option('mwdcp_reset_credits');
            $reset_credits = '';
            if ($reset_credits_opt) {
                $reset_credits = 'checked="checked"';
            }
            ?>
                                                <div class="woo-credits-section-header">
                                                    <h3 class="hndle"><span><?php esc_attr_e('Optional Settings', 'mwdcp'); ?></span></h3>
                                                    <p><?php esc_attr_e('Woo Credits gives you the ability to run a "credits only" store. To activate a "credits only" store, select the two boxes below. The first will hide all currency prices associated with your WooCommerce products. The second will hide other payment gateways (PayPal, Stripe, Bank Transfer, etc), forcing your customers to checkout with their Credits balance (note: currency gateways will be viewable when they are replenishing or buying Credits for the first time).', 'mwdcp'); ?></p>
                                                </div><!-- end woo-credits-section-header-->
                                                <table id="dataTable2" class="form hide-price"  style="padding:20px 6px;">
                                                    <tbody>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" <?php echo $checked; ?> name="mwdcp_hide_price" value="1"><?php esc_attr_e('Hide $ Prices for Credit Products', 'mwdcp'); ?> 
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" <?php echo $checked2; ?> name="mwdcp_hide_other_paymentsg" value="1"><?php esc_attr_e('Hide Other Payment Gateways', 'mwdcp'); ?> 
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" <?php echo $checked3; ?> name="mwdcp_order_status_processing" value="1"><?php esc_attr_e('Set Order Status Processing', 'mwdcp'); ?> 
                                                            </td>
                                                        </tr>                                              
                                                    </tbody>
                                                </table>

                                                <?php if (class_exists('WC_Subscription')): ?>
                                                    <div class="woo-credits-section-header">
                                                        <h3 class="hndle"><span><?php esc_attr_e('WooCommerce Subscriptions', 'mwdcp'); ?></span></h3>
                                                        <p>Woo Credits is integrated to work with popular WooCommerce plugin, <a href="https://woocommerce.com/products/woocommerce-subscriptions/" target="_blank">Woo Subscriptions</a>. The option below is only for customers who are using WooSubscription products. If you've setup a Credit Based Subscription product and wish to reset the customer's Credit Balance at the beginning of each renewal period, please select the box below. If not selected, the customer's current credit balance will be added to whatever the credit renewal amount is for each renewal period.
                                                    </div><!-- end woo-credits-section-header-->
                                                    <table id="dataTable5" class="form reset-credits"  style="padding:20px 6px;">
                                                        <tbody>
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" <?php echo $reset_credits; ?> name="mwdcp_reset_credits" value="1"><?php esc_attr_e('Reset Credits Upon Subscripiton Renewal', 'mwdcp'); ?> 
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
            <?php endif; ?>
                                                <div class="woo-credits-section-header">
                                                    <h3 class="hndle"><span><?php esc_attr_e('Manage Labels Here', 'mwdcp'); ?></span></h3>
                                                    <!-- end woo-credits-section-header-->
            <?php
            $credit_label = get_option('mwdcp_credit_label');
            $credit_label = trim($credit_label);
            $credit_label = empty($credit_label) ? 'Credit' : $credit_label;
            $credits_label = get_option('mwdcp_credits_label');
            $credits_label = trim($credits_label);
            $credits_label = empty($credits_label) ? 'Credits' : $credits_label;
            $myacount_label = get_option('mwdcp_myaccount_label');
            $myacount_label = trim($myacount_label);
            $myacount_label = empty($myacount_label) ? 'Buy Credits' : $myacount_label;
            $expire_days_label = get_option('mwdcp_expire_days_label');
            $expire_days_label = trim($expire_days_label);
            $expire_days_label = empty($expire_days_label) ? 'Expire Days' : $expire_days_label;
            ?>
                                                    <table id="dataTable3" class="form label-settings"  style="padding:20px 6px;">
                                                        <tbody>
                                                            <tr>
                                                                <td>
                                                                    <label class="glabel" for=""><?php esc_attr_e('For Credit', 'mwdcp'); ?></label>
                                                                    <input type="text" name="mwdcp_credit_label" value="<?php echo $credit_label; ?>" placeholder="Credit">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    <label class="glabel" for=""><?php esc_attr_e('For Credits', 'mwdcp'); ?></label>
                                                                    <input type="text" name="mwdcp_credits_label" value="<?php echo $credits_label; ?>" placeholder="Credits">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    <label class="glabel" for=""><?php esc_attr_e('My Account Label', 'mwdcp'); ?></label>
                                                                    <input type="text" name="mwdcp_myaccount_label" value="<?php echo $myacount_label; ?>" placeholder="Credits">
                                                                </td>
                                                            </tr> 
                                                            <tr>
                                                                <td>
                                                                    <label class="glabel" for=""><?php esc_attr_e('Expire Days', 'mwdcp'); ?></label>
                                                                    <input type="text" name="mwdcp_expire_days_label" value="<?php echo $expire_days_label; ?>" placeholder="Credits">
                                                                </td>
                                                            </tr>                                                                                           
                                                        </tbody>
                                                    </table>
                                                    <input type="hidden" name="mwdc_options_submit" value="true" />
                                                    <input id="save-submit" type="submit" class="submit button-primary" value="<?php _e('Save Plugin Settings', 'mwdcp') ?>" />
                                                </div>
                                        </form>
                                        <form class="fix_reports_form" method="post" action="">
                                            <input type="hidden" name="mwdc_fix_reports_submit" value="true" />
                                            <input id="save-submit" type="submit" class="submit button-primary" value="<?php _e('Fix Reports', 'mwdcp') ?>" />                 
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--<div id="woo-credits-admin-submenu" class="postbox-container">
                            <div class="meta-box-sortables">
                                <div class="postbox">
                                    <div class="handlediv" title="<?php esc_attr_e('Click to toggle', 'mwdcp'); ?>"><br></div>
                                    <h3 class="hndle"><span><?php esc_attr_e('About', 'mwdcp'); ?></span></h3>
                                    <div class="inside">
                                         <p><a target="_blank" href="http://woocredits.supportico.us/"><?php esc_attr_e('Documentation', 'mwdcp'); ?></a></p>
                                         <p><a target="_blank" href="http://woocredits.supportico.us/article/general-tutorial-video/"><?php esc_attr_e('Tutorial Video', 'mwdcp'); ?></a></p>
                                         <p><a target="_blank" href="https://twitter.com/woocredits"><?php esc_attr_e('Twitter', 'mwdcp'); ?></a></p>
                                    </div>
                                </div>
                            </div>
                        </div>-->
                    </div>
                    <br class="clear">
                </div>
            </div>
            <?php
        }

    }

    Woo_Download_Credits_Platinum_Admin_Options::init();
}

if (!function_exists('wdc_get_product_id')):

    function wdc_get_product_id($product) {
        $product_id = ( WC()->version < '2.7.0' ) ? $product->id : $product->get_id();
        $product_type = ( WC()->version < '2.7.0' ) ? $product->product_type : $product->get_type();
        if ($product_type == 'variation') {
            $product_id = ( WC()->version < '2.7.0' ) ? $product->variation_id : $product->get_variation_id();
        }
        return $product_id;
    }

endif;

if (!function_exists('custom_print_r')):

    function custom_print_r($data) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

endif;

if (!function_exists('wdcp_get_myaccount_url')):

    function wdcp_get_myaccount_url() {
        $myaccount_page_url = '';
        if (function_exists('wc_get_page_permalink')) {
            $myaccount_page_url = wc_get_page_permalink('myaccount');
        } else if (function_exists('wc_get_page_id')) {
            $myaccount_page_id = wc_get_page_id('myaccount');
            if ($myaccount_page_id) {
                $myaccount_page_url = get_permalink($myaccount_page_id);
            }
        } else {
            $myaccount_page_id = get_option('woocommerce_myaccount_page_id');
            if ($myaccount_page_id) {
                $myaccount_page_url = get_permalink($myaccount_page_id);
            }
        }

        return $myaccount_page_url;
    }


endif;







