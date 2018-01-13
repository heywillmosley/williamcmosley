<?php
/*
Plugin Name: Formidable Signature field
Description: Add a signature field to your forms
Version: 1.08
Plugin URI: http://formidablepro.com
Author URI: http://strategy11.com
Author: Strategy11
Text domain: frmsig
*/

// Instansiate Controllers
include_once(dirname( __FILE__ ) . '/controllers/FrmSigAppController.php');
$obj = new FrmSigAppController();
unset($obj);

