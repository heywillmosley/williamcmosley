<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmWooUpdate extends FrmAddon {
	public $plugin_file;
	public $plugin_name = 'WooCommerce';
	public $download_id = 174006;
	public $version = '1.04';

	public function __construct() {
		$this->plugin_file = dirname( dirname( __FILE__ ) ) . '/formidable-woocommerce.php';
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmWooUpdate();
	}
}
