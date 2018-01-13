<?php

class Affiliate_WP_Recurring_Stripe extends Affiliate_WP_Recurring_Base {

	/**
	 * Property which holds an instance of the Stripe API.
	 * The Stripe API is referenced within Recurring Referrals via WP Simple Pay.
	 *
	 * @since 1.6
	 * @var   Stripe class instance.
	 */
	public $stripe_api;

	/**
	 * Plugin version.
	 *
	 * @var int
	 */
	public $version;

	/**
	 * Get things started.
	 *
	 * @access  public
	 * @since   1.6
	*/
	public function init() {

		$affwp_rr = new AffiliateWP_Recurring_Referrals;

		$this->version = '1.6';
		$this->context = 'stripe';

		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_string' ), 11, 2 );

		add_filter( 'affwp_settings', array( $this, 'register_settings' ) );

		// Process Stripe webhooks on init.
		add_action( 'init', array( $this, 'process_webhooks' ) );

	}

	/**
	 * Register our settings
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function register_settings( $settings = array() ) {

		$integrations = affiliate_wp()->integrations->get_enabled_integrations();

		if ( isset( $integrations['stripe'] ) ) {
			$settings[ 'recurring' ][ 'recurring_stripe_webhook' ] = array(
				'name' => __( 'Stripe Webhook URL', 'affiliate-wp' ),
				'callback' => array( $this, 'setting_callback' ),
				'type' => ''
				);
		}

		return $settings;

	}

	/**
	 * Callback which displays the webhook url used in Stripe, and related information.
	 *
	 * @return void
	 * @since  1.6
	 */
	public function setting_callback() {

		$notice  = '<p>' . __( 'The Recurring Referrals add-on requires the ability to track recurring payments from Stripe, via the WP Simple Pay plugin integration.', 'affiliate-wp' ) . '</p>';
		$notice .= '<p>' . __( 'In order to do this, the following webhook must be entered in your Stripe dashbaord.', 'affiliate-wp' ) . '</p>';
		$notice .= '<p>' . sprintf( __( 'Copy the webook url below, and enter it into your <a href="%1$s">Stripe Dashboard</a>.', 'affiliate-wp' ), 'https://dashboard.stripe.com/account/webhooks' ) . '</p>';
		$notice .= '<strong>' . esc_url( add_query_arg( 'affwp-listener', 'stripe', home_url( 'index.php' ) ) ) . '</strong>';

		echo $notice;
	}

	/**
	 * Checks if the transaction successfully initiated a valid subscription.
	 *
	 * @since  1.6
	 *
	 * @param  array  $charge  The transaction charge object.
	 *
	 * @return boolean         Returns true if the transaction successfully initiated a valid subscription.
	 */
	public function is_subscription( $subscription ) {

		if ( ! $subscription ) {
			return false;
		}

		return true === $subscription ? true : false;
	}

	/**
	 * Determines if the WP Simple Pay Stripe Subscription add-on is installed, and active.
	 *
	 * @since  1.6
	 *
	 * @return boolean Returns true if the Subscriptions add-on is installed and active, otherwise false.
	 */
	public function has_subscription_addon() {
		return class_exists( 'Stripe_Subscriptions_Shortcodes' );
	}

	/**
	 * Check for, and get the Simple Pay Stripe API.
	 * Sets the $api property of this class to an instance of the class `Stripe\Stripe`.
	 *
	 * @since  1.6
	 * @return bool Returns true if the Stripe API class eixsts, otherwise false.
	 */
	public function simpay_stripe_api() {

		if ( class_exists( 'Stripe\Stripe' ) ) {
			$this->api = new Stripe\Stripe;
			return true;
		}

		return false;
	}

	/**
	 * Gets the Stripe API keys set from WP Simple Pay options.
	 *
	 * @return void
	 * @since 1.6
	 */
	public function set_stripe_api_keys() {

		// Bail if the Stripe API is not loaded.
		if ( ! $this->simpay_stripe_api() ) {
			return false;
		}

		/**
		 * Checks if test mode is active in WP Simple Pay.
	     * This can be determined from $_POST as well as $_GET within WP Simple Pay, so both are checked.
		 *
		 */
		$test_mode = ( isset( $_POST['sc_test_mode'] ) ? true : false );

		if ( ! $test_mode ) {
			$test_mode = ( isset( $_GET['test_mode'] ) ? true : false );
		}

		// Simple Pay Options
		global $sc_options;

		if( function_exists( 'simpay_get_secret_key' ) ) {

			$key = simpay_get_secret_key();

		} else {

			// Check first if in live or test mode.
			if ( $sc_options->get_setting_value( 'enable_live_key' ) == 1 && $test_mode !== true ) {
				$key = $sc_options->get_setting_value( 'live_secret_key' );
				$test_mode = false;
			} else {
				$key = $sc_options->get_setting_value( 'test_secret_key' );
				$test_mode = true;
			}

		}

		/**
		 * Gets the WP Simple Pay Stripe API keys.
		 *
		 * @param string $key        The API key
		 * @param bool   $test_mode  Whether or not Stripe test mode is active within WP Simple Pay.
		 *                           Returns true if active, otherwise false.
		 * @since 1.6
		 */
		$key = apply_filters( 'affwp_rr_stripe_secret_key', $key, $test_mode );

		\Stripe\Stripe::setApiKey( $key );
	}

	/**
	 * Processes incoming Stripe webhook events.
	 *
	 * @return void
	 * @since  1.6
	 */
	public function process_webhooks() {

		if( empty( $_GET['affwp-listener'] ) || 'stripe' !== strtolower( $_GET['affwp-listener'] ) ) {
			return;
		}

		// Ensure listener URL is not cached by W3TC.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		$this->set_stripe_api_keys();

		// Retrieve the request body and parse it as JSON.
		$body          = @file_get_contents( 'php://input' );
		$event_json    = json_decode( $body );

		if( ! isset( $event_json->id ) ) {
			return;
		}

		$event = \Stripe\Event::retrieve( $event_json->id );

		status_header( 200 );

		try {

			$event = \Stripe\Event::retrieve( $event_json->id );

		} catch ( Exception $e ) {

			$msg = 'AffiliateWP RR: Invalid event ID.';
			affiliate_wp()->utils->log( $msg );
			die( $msg );

		}

		/**
		 * Checks first for a failed Stripe payment, and bails if so.
		 */
		if ( $event->type == 'charge.failed' ) {

			/**
			 * Fires when a Stripe charge fails.
			 *
			 * @param $invoice The Stripe invoice object.
			 * @since 1.6
			 */
			do_action( 'affwp_stripe_charge_failed', $invoice );

			$msg = 'AffiliateWP RR: affwp_stripe_charge_failed action fired successfully. No recurring referral generated.';

			affiliate_wp()->utils->log( $msg );

			die( $msg );

		}

		if( $event->type == 'invoice.payment_succeeded' ) {

			$invoice_id = \Stripe\Invoice::retrieve( $event->data->object->id );

			if( affiliate_wp()->referrals->get_by( 'reference', $event->data->object->id, $this->context ) ) {

				$msg = 'AffiliateWP RR: Referral already recorded for this invoice payment.';
				affiliate_wp()->utils->log( $msg );
				die( $msg );
			}

			if ( ! empty( $event->data->object->subscription ) ) {

				$msg = 'AffiliateWP RR: Stripe subscription object successfully located.';
				affiliate_wp()->utils->log( $msg );

				$parent_referral = affiliate_wp()->referrals->get_by( 'reference', $event->data->object->subscription, $this->context );

				if ( $parent_referral ) {

					$msg = 'AffiliateWP RR: Parent referral successfully located via reference for this Stripe subscription ID.';
					affiliate_wp()->utils->log( $msg );

					$core_stripe = new Affiliate_WP_Stripe;

					if( $core_stripe->is_zero_decimal( $currency ) ) {
						$amount = $event->data->object->total;
					} else {
						$amount = round( $event->data->object->total / 100, 2 );
					}

					$referral_data = array(
						'affiliate_id'      => $parent_referral->affiliate_id,
						'context'           => $this->context,
						'amount'            => $this->calc_referral_amount( $amount ),
						'reference'         => $event->data->object->id,
						'custom'			=> array(
							'parent'        => $parent_referral->referral_id,
							'livemode'      => $event->data->object->livemode
						),
						'date'              => date_i18n( 'Y-m-d g:i:s', $event->created ),
						'description'       => sprintf( __( 'Subscription payment for: %d', 'affiliate-wp-recurring' ), $parent_referral->referral_id ),
						'currency'          => affwp_get_currency(),
						'visit_id'          => $parent_referral->visit_id,
					);


					// Insert this referral if it hasn't been recorded yet.
					$referral_id = $this->insert_referral( $referral_data );

					$this->complete_referral( $referral_id );

					$msg = 'AffiliateWP RR: Recurring referral inserted successfully.';
					affiliate_wp()->utils->log( $msg );

					// Passes the parent referral ID into the action noted below.
					$parent_referral_id = $parent_referral->referral_id;

					/**
					 * Fires when a referral is successfully added.
					 *
					 * @since 1.6
					 * @param int $referral_id         The referral ID.
					 * @param int $parent_referral_id  The ID of the parent referral.
					 */
					do_action( 'affwprr_stripe_subscription_payment_succeeded', $referral_id, $parent_referral_id );

					$msg = 'AffiliateWP RR: The affwprr_stripe_subscription_payment_succeeded action fired successfully.';

					affiliate_wp()->utils->log( $msg );
					die( $msg );

				} else {

					$msg = 'AffiliateWP RR: Parent referral not located for this subscription payment.';
					die( $msg );
				}

				/**
				 * Fires at the very end of the webhook event.
				 * Actions will contain the Stripe event type as the siffix of the action.
				 *
				 * @param object  $payment_event  The payment event object.
				 * @since 1.6
				 */
				do_action( 'affwprr_stripe_' . $event->type, $event );
			}

			die( '1' );
		} else {
			$msg = 'AffiliateWP RR: no Stripe event ID found';
			affiliate_wp()->utils->log( $msg );
			die( $msg );
		}
	}

	/**
	 * Builds the reference string for the referrals table.
	 * Uses the Stripe transaction or subscription ID as the unique reference.
	 *
	 * @access  public
	 * @since   1.6
	*/
	public function reference_string( $ref_string = '', $referral ) {

		if( empty( $referral->context ) || 'stripe' != $referral->context ) {

			return $ref_string;
		}

		$test = '';

		if( ! empty( $referral->custom ) ) {
			$custom = maybe_unserialize( $referral->custom );
			$test   = empty( $custom['livemode'] ) ? 'test/' : '';
		}

		if( false === strpos( $referral->reference, 'in_' ) ) {
			return $ref_string;
		}

		$url = 'https://dashboard.stripe.com/' . $test . 'invoices/' . $referral->reference ;

		return '<a href="' . esc_url( $url ) . '">' . $referral->reference . '</a>';

		return $ref_string;
	}

}

new Affiliate_WP_Recurring_Stripe;
