=== WooCommerce Formidable Forms Product Addons ===
Contributors: swells, jamie.wahlin, jbftrick
Tags: ecommerce, e-commerce, forms, formidable forms, product, products
Requires at least: 3.8
Request at least WooCommerce: 2.2.9
Request at least Formidable: 2.0.11
Tested up to: 4.6.1
Stable tag: 1.04
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Use Formidable Forms on individual WooCommerce product pages to create customizable products.

== Description ==

Use Formidable Forms on individual WooCommerce product pages to create customizable products.

== Installation ==

= Minimum Requirements =

* WordPress 3.8 or greater
* PHP version 5.2.4 or greater
* MySQL version 5.0 or greater
* WooCommerce 2.2.9 or greater
* Formidable 2.0.11 or greater

== Changelog ==
= 1.04 =
* Add wc_fp_exclude_fields filter
* Deprecate fp_wc_addons_new_item_data hook and add wc_fp_cart_item_data hook
* Don't remove submit button from non-product forms on product pages
* Fix error when form is deleted that was used for adding items to the cart
* Remove reference to deprecated function

= 1.03 =
* If the product is on sale, use the right price in the cart summary
* Add an option to exclude the addition of the product price. When using this option, the product price should be included somewhere in a form field instead. [post_meta key=_price] works great for this, and can then be used inside more flexible calculations.
* Only use fields from a calculation in the summary on the cart page

= 1.02 =
* More accurately hide amounts in cart. ($0.00) was showing for fields when they shouldn't have an amount anyway
* Fix the add to cart button on the shop page for products without a form
* Allow the quantity option to affect the total
* Added filter wc_frm_apply_per_qty if you want the form total to only apply once
* Strip tags in order line items (as shown on PayPal)
* Include Form when product is show with Product Shortcode
* Use textdomain formidable-woocommerce

= 1.01 =
* Fix auto-updating

= 1.0 =
* First Release
