<?php

class Affiliate_WP_Recurring_Gravityforms extends Affiliate_WP_Recurring_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.6
	*/
	public function init() {
		$this->context = 'gravityforms';
		add_action( 'gform_post_add_subscription_payment', array( $this, 'record_referral_on_payment' ), 10, 2 );
		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 11, 2 );
	}

	/**
	 * Insert referrals on Gravity Forms subscription payments.
	 *
	 * @param  $entry  object  The gravity forms entry object.
	 * @param  $action array   The webhook transaction object for subscription payments.
	 *
	 * @since  1.6
	 * @see    GFPaymentAddOn::add_subscription_payment()
	*/
	public function record_referral_on_payment( $entry, $action ) {

		// Bail if a subscription payment wasn't successfully processed.
		if ( 'add_subscription_payment' != $action[ 'type' ] ) {
			$msg = 'AffiliateWP RR: GF $action["type"] is not add_subscription_payment.';
			affiliate_wp()->utils->log( $msg );

			return false;
		}

		// Get initial/parent referral. Gravity Forms entry.
		$parent_referral = affiliate_wp()->referrals->get_by( 'reference', $entry['id'], 'gravityforms' );

		if ( ! $parent_referral || ! is_object( $parent_referral ) || ( 'paid' != $parent_referral->status && 'unpaid' != $parent_referral->status ) ) {

			if ( ! $parent_referral || ! is_object( $parent_referral ) ) {
				$msg = 'AffiliateWP RR: Unable to locate parent referral. No recurring referral generated.';
			} else {
				$msg = 'AffiliateWP RR: Parent recurring referral status is: rejected. No recurring referral generated.';
			}

			affiliate_wp()->utils->log( $msg );

			/**
			 * Bail, since this signup wasn't referred.
			 *
			 * No need to check if it's the first payment, since the gform_post_add_subscription_payment
			 * hook only fires on subsequent subscription payments.
			 */
			return false;
		}

		$args = array(
			'reference'    => $action[ 'transaction_id' ],
			'affiliate_id' => $parent_referral->affiliate_id,
			'description'  => sprintf( __( 'Subscription payment for %d', 'affiliate-wp-recurring' ), $entry['id'] ),
			'amount'       => $action[ 'amount' ],
			'custom'       => $entry['id']
		);

		$referral_id = $this->insert_referral( $args );

		$this->complete_referral( $referral_id );

		/**
		 * Fires when a recurring referral is successfully generated.
		 *
		 * @param int $referral_id  The generated referral ID.
		 * @since 1.6
		 */
		do_action( 'affwprr_gravityforms_insert_referral', $referral_id );

		$msg = 'AffiliateWP RR: affwprr_gravityforms_insert_referral action fired successfully.';
		affiliate_wp()->utils->log( $msg );

	}

	/**
	 * Sets up the reference link in the Referrals list table.
	 *
	 * @param  string  $link      Referral reference link.
	 * @param  object  $referral  Referral object.
	 * @return string             Reference link.
	 * @since  1.6
	 * @uses   GFFormsModel::get_lead()
	 */
	public function reference_link( $link = '', $referral ) {

		// Bail if the referral context is not gravityforms.
		if ( empty( $referral->context ) || 'gravityforms' != $referral->context ) {
			return $link;
		}

		// The Entry ID is stored in the custom field for recurring referrals.
		$parent_entry_id = is_numeric( $referral->custom ) ? absint( $referral->custom ) : 0;

		if( $parent_entry_id ) {

			$entry = GFFormsModel::get_lead( $parent_entry_id );

			if( $entry ) {

				$url  = admin_url( 'admin.php?page=gf_entries&view=entry&id=' . $entry['form_id'] . '&lid=' . $entry['id'] );
				$link = '<a href="' . esc_url( $url ) . '">' . $referral->reference . '</a>';
			}

		}

		return $link;

	}

}
new Affiliate_WP_Recurring_Gravityforms;
