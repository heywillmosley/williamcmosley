<?php

abstract class Affiliate_WP_Recurring_Base {

	/**
	 * The context of the referral.
	 * This is usually the name of the AffiliateWP integration from which a referral was generated.
	 *
	 * @var string $context The referral context.
	 */
	public $context;

	/**
	 * The affiliate ID.
	 *
	 * @var integer $affiliate_id
	 */
	public $affiliate_id;

	/**
	 * Construct class.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Gets things started.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function init() {
	}

	/**
	 * Determines if a signup was referred.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function was_signup_referred() {
		return false;
	}

	/**
	 * Inserts a pending referral for a subscription payment.
	 *
	 * @access public
	 * @since  1.0
	 * @return array  $args  Referral arguments.
	 */
	public function insert_referral( $args = array() ) {

		/**
		 * Allow extensions to prevent recurring referrals from being created,
		 * as well as filter the referral arguments array itself.
		 *
		 * @param bool          True if recurring referrals should generate a referral, otherwise false.
		 *                      Defaults to true.
		 * @param array  $args  Referral arguments. Specify an array of referral data.
		 * @param object $this  Instance of the Affiliate_WP_Recurring_Base class.
		 * @since 1.6
		 */
		if( ! (bool) apply_filters( 'affwp_recurring_create_referral', true, $args, $this ) ) {
			return false;
		}

		if( function_exists( 'affwp_get_affiliate_meta' ) && affwp_get_affiliate_meta( $args['affiliate_id'], 'recurring_disabled', true ) ) {
			return false;
		}

		if( affiliate_wp()->referrals->get_by( 'reference', $args['reference'], $this->context ) ) {
			return false; // Referral already created for this reference
		}

		if( empty( $this->affiliate_id ) ) {
			$this->affiliate_id = $args['affiliate_id'];
		}

		$amount = $this->calc_referral_amount( $args['amount'] );

		if( 0 == $amount && affiliate_wp()->settings->get( 'ignore_zero_referrals' ) ) {
			return false; // Ignore a zero amount referral
		}

		$args = array(
			'amount'       => $amount,
			'reference'    => $args['reference'],
			'description'  => $args['description'],
			'affiliate_id' => $args['affiliate_id'],
			'context'      => $this->context,
			'custom'       => ! empty( $args['custom'] ) ? $args['custom'] : ''
		);

		/**
		 * Defines the referral data to be inserted when creating a recurring referral.
		 * Specify an array of referral properties to use this filter.
		 *
		 * @param  array  $args  Referral object arguments array.
		 * @param  object $this  Instance of the Affiliate_WP_Recurring_Base class.
		 * @since  1.6
		 */
		return affiliate_wp()->referrals->add( apply_filters( 'affwp_insert_pending_recurring_referral', $args, $this ) );

	}

	/**
	 * Marks a referral as complete.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function complete_referral( $referral_id = 0 ) {

		if ( empty( $referral_id ) ) {
			return false;
		}

		if ( affwp_set_referral_status( $referral_id, 'unpaid' ) ) {

			/**
			 * Fires when a recurring referral is marked complete.
			 *
			 * @param $referral_id The ID of the completed referral.
			 * @since 1.0
			 */
			do_action( 'affwp_complete_recurring_referral', $referral_id );

			return true;
		}

		return false;

	}

	/**
	 * Calculates the referral amount for a subscription payment.
	 *
	 * @access public
	 * @since  1.5.7
	 * @return float
	 */
	public function calc_referral_amount( $amount = '' ) {

		$rate     = $this->get_referral_rate();
		$type     = $this->get_referral_rate_type();
		$decimals = affwp_get_decimal_count();

		$referral_amount = ( 'percentage' === $type ) ? round( $amount * $rate, $decimals ) : $rate;

		if ( $referral_amount < 0 ) {
			$referral_amount = 0;
		}

		/**
		 * Fires when the amount of a recurring referral is calculated.
		 *
		 * @param float $referral_amount  The referral amount.
		 * @param int   $affiliate_id     The affiliate ID.
		 * @param float $amount           The full transaction amount.
		 *
		 * @since 1.5
		 */
		return (string) apply_filters( 'affwp_recurring_calc_referral_amount', $referral_amount, $this->affiliate_id, $amount );

	}

	/**
	 * Retrieves the referral rate for a subscription payment.
	 *
	 * @access public
	 * @since  1.5
	 * @return float
	 */
	public function get_referral_rate() {

		$rate      = affwp_get_affiliate_meta( $this->affiliate_id, 'recurring_rate', true );
		$rate      = affwp_abs_number_round( $rate );
		$rate_type = $this->get_referral_rate_type();

		if( ! empty( $rate ) && 'flat' !== $rate_type ) {
			$rate /= 100;
		}

		if( empty( $rate ) ) {

			$rate = affiliate_wp()->settings->get( 'recurring_rate' );
		
			// I hate this duplication but meh
			if( ! empty( $rate ) && 'flat' !== $rate_type ) {
				$rate /= 100;
			}

			if( empty( $rate ) ) {

				$rate = affwp_get_affiliate_rate( $this->affiliate_id, false, $rate );

			}
		}

		/**
		 * Sets the recurring referral rate for an affiliate.
		 * To use, specify the afiliate ID and desired recurring rate for the affiliate.
		 *
		 * @param int     $rate          The recurring referral rate.
		 * @param int     $affiliate_id  The affiliate ID.
		 * @param string  $context       The context of the referral.
		 *
		 * @since 1.5
		 */
		return apply_filters( 'affwp_get_recurring_referral_rate', $rate, $this->affiliate_id, $this->context );

	}

	/**
	 * Retrieves the referral rate type for a subscription payment.
	 *
	 * @access public
	 * @since  1.6
	 * @return float
	 */
	public function get_referral_rate_type() {

		$rate_type = affwp_get_affiliate_meta( $this->affiliate_id, 'recurring_rate_type', true );

		if( empty( $rate_type ) ) {

			$rate_type = affiliate_wp()->settings->get( 'recurring_rate_type' );

			if( empty( $rate_type ) ) {

				$rate_type = affwp_get_affiliate_rate_type( $this->affiliate_id );

			}
		}

		/**
		 * Sets the recurring referral rate type for an affiliate.
		 * To use, specify the afiliate ID and recurring rate type (either flat or recurring).
		 * To see available affiliate rate types, use `affwp_get_affiliate_rate_types`.
		 *
		 * @param int     $rate_type     The recurring referral rate type.
		 * @param int     $affiliate_id  The affiliate ID.
		 * @param  string $context       The context of the referral.
		 *
		 * @since 1.6
		 */
		return apply_filters( 'affwp_get_recurring_referral_rate_type', $rate_type, $this->affiliate_id, $this->context );

	}

}
