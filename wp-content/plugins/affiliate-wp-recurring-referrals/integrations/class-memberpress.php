<?php

class Affiliate_WP_Recurring_MemberPress extends Affiliate_WP_Recurring_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.4
	*/
	public function init() {

		$this->context = 'memberpress';

		add_action( 'mepr-txn-status-pending', array( $this, 'record_referral_on_payment' ), -1 );
		add_action( 'mepr-txn-status-complete', array( $this, 'record_referral_on_payment' ), -1 );
		add_action( 'mepr-txn-status-complete', array( $this, 'mark_referral_complete' ), 100 );

	}

	/**
	 * Insert referrals on subscription payments
	 *
	 * @access  public
	 * @since   1.4
	*/
	public function record_referral_on_payment( $txn ) {

		if( empty( $txn->subscription_id ) ) {
			return;
		}

		$referral = affiliate_wp()->referrals->get_by( 'custom', $txn->subscription_id, $this->context );

		if( ! $referral || ! is_object( $referral ) || 'rejected' == $referral->status || 'pending' == $referral->status ) {
			return; // This signup wasn't referred or is the very first payment of a referred subscription
		}

		if( ! $txn->subscription()->trial ) {

			// Determine if this is the initial payment recorded moments after a subscription is purchased and bail if so
			$transactions = $txn->get_all_by_subscription_id( $txn->subscription_id );
			$transactions = wp_list_pluck( $transactions, 'txn_type' );

			if( count( $transactions ) <= 2 && in_array( 'subscription_confirmation', $transactions ) ) {
				return; // This is the first payment recorded right after a subscription, skip it
			}

		}

		$args = array(
			'reference'    => $txn->id,
			'affiliate_id' => $referral->affiliate_id,
			'description'  => sprintf( __( 'Subscription payment for %s', 'affiliate-wp-recurring' ), $txn->subscription_id ),
			'amount'       => $txn->amount,
			'custom'       => $referral->reference
		);

		$referral_id = $this->insert_referral( $args );

		$this->complete_referral( $referral_id );

	}

	/**
	 * Mark referral as complete
	 *
	 * See https://github.com/AffiliateWP/affiliate-wp-recurring-referrals/issues/59
	 *
	 * @access  public
	 * @since   1.6
	*/
	public function mark_referral_complete( $txn ) {

		$referral = affiliate_wp()->referrals->get_by( 'reference', $txn->id, $this->context );

		if( $referral && 'pending' === $referral->status ) {

			$this->complete_referral( $referral->referral_id );

		}

	}

}
new Affiliate_WP_Recurring_MemberPress;