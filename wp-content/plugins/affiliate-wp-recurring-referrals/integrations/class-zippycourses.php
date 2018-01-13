<?php

class Affiliate_WP_Recurring_ZippyCourses extends Affiliate_WP_Recurring_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.6
	*/
	public function init() {
		$this->context = 'zippycourses';
		add_action( 'save_post_transaction', array( $this, 'record_referral_on_payment' ), 10, 3 );
		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );
	}

	/**
	 * Insert referrals on Zippy Courses subscription payments.
	 *
	 * int     $post_ID  The post ID.
	 * object  $post     The post object.
	 * bool    $update   Whether an existing or new post.
	 * @since  1.6
	*/
	public function record_referral_on_payment( $post_ID, $post, $update ) {

		if ( ! $post_ID || ! $post ) {
			return false;
		}

		$transaction_key = get_post_meta( $post_ID, 'transaction_key', true );
		$recurring       = get_post_meta( $post_ID, 'recurring', true );
		$is_recurring    = $recurring ? $recurring : false;

		// Bail if a subscription payment wasn't successfully processed by Zippy Courses.
		if ( $is_recurring ) {
			$recurring_id = get_post_meta( $post_ID, 'recurring_id', true );
		} else {
			$msg = 'AffiliateWP RR: Zippy Courses transaction is not a recurring subscription. No referral recorded.';
			affiliate_wp()->utils->log( $msg );
			return false;
		}

		$details = get_post_meta( $post_ID, 'details', true );

		if ( $details ) {
			$num_payments = $details[ 'num_payments' ];
		}

		$parent_transaction = get_post_meta( $post_ID, 'order_id', true ) ? get_post_meta( $post_ID, 'order_id', true ) : false;

		if ( $parent_transaction ) {
			$parent_referral = affiliate_wp()->referrals->get_by( 'reference', $parent_transaction, $this->context );
		}

		if ( empty( $parent_referral ) ) {
			$msg = 'AffiliateWP RR: Zippy Courses - Parent referral not located. No recurring referral generated.';
			affiliate_wp()->utils->log( $msg );
			return false;
		}

		$args = array(
			'reference'    => $post_ID,
			'affiliate_id' => $parent_referral->affiliate_id,
			'description'  => sprintf( __( 'Subscription payment for order %d', 'affiliate-wp-recurring' ), $parent_transaction ),
			'amount'       => $details[ 'total' ],
			'custom'       => array(
				'parent' => $parent_referral->referral_id
			)
		);

		$referral_id = $this->insert_referral( $args );

		$this->complete_referral( $referral_id );

		/**
		 * Fires when a recurring referral is successfully generated.
		 *
		 * @param int $referral_id  The generated referral ID.
		 * @since 1.6
		 */
		do_action( 'affwprr_zippycourses_insert_referral', $referral_id );

		$msg = 'AffiliateWP RR: affwprr_zippycourses_insert_referral action fired successfully.';
		affiliate_wp()->utils->log( $msg );
	}

	/**
	 * Sets up the reference link in the Referrals list table.
	 *
	 * @param  int                $link      Zippy Courses transaction array.
	 * @param  object             $referral  Referral object.
	 * @return mixed bool|string             Reference link.
	 * @since  1.6
	 */
	public function reference_link( $link, $referral ) {

		// Bail if the referral context is not zippycourses.
		if ( empty( $referral->context ) || 'zippycourses' != $referral->context ) {
			return $link;
		}

		// The transaction ID is used as the referral reference.
		$post_ID         = get_post( $referral->reference );
		$transaction_id  = $post_ID ? $referral->reference : 0;

		if ( ! $transaction_id ) {
			$msg = 'AffiliateWP RR: No transaction post ID could be located for referral ' . $post_ID;
			affiliate_wp()->utils->log( $msg );
		}

		$url  = get_edit_post_link( $transaction_id, '' );
		$link = '<a href="' . esc_url( $url ) . '">' . $transaction_id . '</a>';

		return $link;
	}

}
new Affiliate_WP_Recurring_ZippyCourses;
