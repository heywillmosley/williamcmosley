AffiliateWP - Recurring Referrals
======================

This add-on for AffiliateWP allows you to track referrals on all subscription payments.

Currently supports:

- WooCommerce Subscriptions
- EDD Recurring Payments
- Restrict Content Pro
- MemberPress
- MemberMouse
- Paid Memberships Pro
- iThemes Exchange Subscriptions
- Zippy Courses
- Stripe through WP Simple Pay
- Gravity Forms

----

= Version 1.6.2, June 15, 2017 =
* Fix: Referral rate not calculated properly on recurring referrals.

= Version 1.6.1, May 5, 2017 =
* Fix: Incorrect formatting of recurring rates used in the Edit Affiliate screen.

= Version 1.6, May 4, 2017 =
* New: Zippy Courses integration.
* New: WP Simple Pay / Stripe integration.
* New: Gravity Forms integration.
* New: Set the referral rate type, either flat, or percentage, for recurring referrals!
* New A new filter: `affwp_insert_pending_referral`, allows for filtering of the referral data, as well as whether or not to create the referral itself.
* Fix: Incorrect referral amounts, as well as secondary referrals, were being generated in the MemberPress integration.

= Version 1.5.8, July 1, 2016 =
* Fix: Incorrect referral ID and amount on some recurring referrals in Paid Memberships Pro.
* Fix: Incorrectly logged recurring referral on initial transaction.

= Version 1.5.7, May 3, 2016 =
* Update WooCommerce integration to use woocommerce_subscription_renewal_payment_complete.
* Add affiliate-specific referral rates

= Version 1.5.6, April 28, 2016 =
* Fixed a bug with manually adding renewal payments for subscriptions.

= Version 1.5.5, March 10, 2016 =
* Fixed a bug with an undefined variable in the WooCommerce integration.

= Version 1.5.4, January 29, 2016 =
* Fixed a bug with tracking in Paid Memberships Pro.

= Version 1.5.3, January 8, 2016 =
* Fixed a bug with flat rate commissions getting multiplied by 100.
* Fixed a bug with Paid Memberships Pro and subscription level names.

= Version 1.5.2, December 10, 2015 =
* Fixed a bug with recurring tracking in Restrict Content Pro.

= Version 1.5.1, December 7, 2015 =
* Fixed a bug with recurring tracking in Paid Memberships Pro.
* Fixed a bug with recurring referrals rates being calculated incorrectly.

= Version 1.5, November 3, 2015 =
* Added support for setting the commission rate for subscription payments separately from the rate used on initial payments.
* Added support for disabling recurring referrals on a per-affiliate basis.

= Version 1.4.3, August 31, 2015 =
* Fixed a conflict with several 3rd party extensions.

= Version 1.4.2, July 19, 2015 =
* Fixed a bug with tracking in Paid Memberships Pro.

= Version 1.4.1, June 6, 2015 =
* Fixed a bug with recurring referrals not tracking in MemberPress.

= Version 1.4, January 16, 2015 =
* Added support for MemberMouse and MemberPress plugins.

= Version 1.3.1, November 7, 2014 =
* Fixed a bug with subscription tracking in WooCommerce Subscriptions.

= Version 1.3, August 12, 2014 =
* Fixed a bug with recurring referrals not tracking properly in Paid Memberships Pro.

= Version 1.2, August 6, 2014 =
* Fixed a fatal error that occurred when the main AffiliateWP plugin is not activated.

= Version 1.1, July 7, 2014 =
* Added support for tracking recurring orders in Paid Memberships Pro.

= Version 1.0, June 24, 2014 =
* Initial release!
