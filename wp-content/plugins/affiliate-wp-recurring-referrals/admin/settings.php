<?php

class AffiliateWP_Recurring_Admin {

	/**
	 * Get things started
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {
		add_filter( 'affwp_settings_tabs', array( $this, 'setting_tab' ) );
		add_filter( 'affwp_settings', array( $this, 'register_settings' ) );

	}

	/**
	 * Register the new settings tab
	 *
	 * @access public
	 * @since  1.5
	 * @return array
	 */
	public function setting_tab( $tabs ) {
		$tabs['recurring'] = __( 'Recurring Referrals', 'affiliate-wp-recurring' );
		return $tabs;
	}

	/**
	 * Register our settings
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function register_settings( $settings = array() ) {

		$settings[ 'recurring' ] = array(
			'recurring' => array(
				'name' => __( 'Enable Recurring Referrals', 'affiliate-wp-recurrring' ),
				'desc' => __( 'Check this box to enable referral tracking on all subscription payments', 'affiliate-wp-recurrring' ),
				'type' => 'checkbox'
			),
			'recurring_rate' => array(
				'name' => __( 'Recurring Rate', 'affiliate-wp-recurrring' ),
				'desc' => __( 'Enter the commission rate for recurring payments. If no rate is entered, the affiliate\'s standard rate will be used.', 'affiliate-wp-recurrring' ),
				'type' => 'number',
				'min'  => 0,
				'step' => '0.01',
				'size' => 'small'
			),
			'recurring_rate_type' => array(
				'name'    => __( 'Recurring Rate Type', 'affiliate-wp-recurrring' ),
				'desc'    => __( 'Select the commission rate type for recurring payments. If no rate type is entered, the affiliate\'s standard rate type will be used.', 'affiliate-wp-recurrring' ),
				'type'    => 'select',
				'options' => affwp_get_affiliate_rate_types()
			),
		);

		return $settings;

	}

}
new AffiliateWP_Recurring_Admin;
