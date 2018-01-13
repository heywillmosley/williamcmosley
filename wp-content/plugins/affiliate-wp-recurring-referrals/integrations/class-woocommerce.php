<?php

class Affiliate_WP_Recurring_WooCommerce extends Affiliate_WP_Recurring_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function init() {

		$this->context = 'woocommerce';

		add_action( 'woocommerce_subscription_renewal_payment_complete', array( $this, 'record_referral_on_payment' ), -1 );

	}

	/**
	 * Insert referrals on subscription payments
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function record_referral_on_payment( $subscription ) {

		$last_order = $subscription->get_last_order( 'all' );
		$referral   = affiliate_wp()->referrals->get_by( 'reference', $subscription->order->id, $this->context );

		if( ! $referral || ! is_object( $referral ) || 'rejected' == $referral->status ) {
			return false; // This signup wasn't referred or is the very first payment of a referred subscription
		}

		$order_total = $last_order->get_total();

		if ( affiliate_wp()->settings->get( 'exclude_tax' ) ) {
			$order_total -= $last_order->get_total_tax();
		}

		if ( affiliate_wp()->settings->get( 'exclude_shipping' ) ) {
			$order_total -= $last_order->get_shipping_tax();
			$order_total -= $last_order->get_shipping_total();
		}

		$args = array(
			'reference'    => $last_order->id,
			'affiliate_id' => $referral->affiliate_id,
			'description'  => sprintf( __( 'Subscription payment for %d', 'affiliate-wp-recurring' ), $subscription->order->id ),
			'amount'       => $order_total,
			'custom'       => $subscription->order->id
		);

		$referral_id = $this->insert_referral( $args );

		$this->complete_referral( $referral_id );

	}

}
new Affiliate_WP_Recurring_WooCommerce;
