<?php

class Affiliate_WP_Recurring_PMP extends Affiliate_WP_Recurring_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.1
	*/
	public function init() {

		$this->context = 'pmp';

		add_action( 'pmpro_added_order', array( $this, 'record_referral_on_payment' ), -1 );

	}

	/**
	 * Insert referrals on subscription payments
	 *
	 * @access  public
	 * @since   1.1
	*/
	public function record_referral_on_payment( $order ) {

		$first_order = $this->get_first_order( $order );

		if( empty( $first_order ) ) {
			return;
		}

		$parent = affiliate_wp()->referrals->get_by( 'reference', $first_order->id, $this->context );

		if( ! $parent || ! is_object( $parent ) || 'rejected' == $parent->status ) {
			return false; // This signup wasn't referred or is the very first payment of a referred subscription
		}

		$this->affiliate_id = $parent->affiliate_id;

		$referral_amount = $this->calc_referral_amount( $order->subtotal );

		//make sure membership level data is populated
		$order->getMembershipLevel();

		$args = array(
			'reference'    => $order->id,
			'affiliate_id' => $parent->affiliate_id,
			'description'  => sprintf( __( 'Subscription payment for %s', 'affiliate-wp-recurring' ), $order->membership_level->name ),
			'amount'       => $order->subtotal,
			'custom'       => $first_order->id
		);

		$referral_id = $this->insert_referral( $args );

		// Prevent infinite loop
		remove_action( 'pmpro_updated_order', array( $this, 'complete_referral' ), -1 );

		$order->affiliate_id = $parent->affiliate_id;
		$amount = html_entity_decode( affwp_currency_filter( affwp_format_amount( $referral_amount ) ), ENT_QUOTES, 'UTF-8' );
		$name   = affiliate_wp()->affiliates->get_affiliate_name( $parent->affiliate_id );
		$note   = sprintf( __( 'Referral #%d for %s recorded for %s', 'affiliate-wp-recurring' ), $referral_id, $amount, $name );

		if( empty( $order->notes ) ) {
			$order->notes = $note;
		} else {
			$order->notes = $order->notes . "\n\n" . $note;
		}

		$order->saveOrder();

		$this->complete_referral( $referral_id );

	}

	/**
	 * Retrieves the order ID of the first payment made for a subscription
	 *
	 * @access  public
	 * @since   1.1
	*/
	public function get_first_order( $order ) {

		global $wpdb;

		// Make sure a subscription transaction ID is present
		if( empty( $order->subscription_transaction_id ) ) {
			return false;
		}

		// get the order ID of the first payment of this subscription
		$query = "SELECT id FROM $wpdb->pmpro_membership_orders WHERE
					gateway = '" . esc_sql( $order->gateway ) . "' AND
					gateway_environment = '" . esc_sql( $order->gateway_environment ) . "' AND
					user_id = '" . esc_sql( $order->user_id ) . "' AND
					membership_id = '" . esc_sql( $order->membership_id ) . "' AND
					subscription_transaction_id = '" . esc_sql( $order->subscription_transaction_id ) . "' ";

		//if this is an existing order, make sure we don't select our self
		if( ! empty( $order->id ) ) {
			$query .= "AND id < '" . esc_sql( $order->id ) . "' ";
		}

		//just the first
		$query .= "ORDER BY id ASC LIMIT 1";

		$id = $wpdb->get_col( $query );

		if( ! empty( $id ) ) {
			return new MemberOrder( $id[0] );
		}

		return false;

	}

}
new Affiliate_WP_Recurring_PMP;