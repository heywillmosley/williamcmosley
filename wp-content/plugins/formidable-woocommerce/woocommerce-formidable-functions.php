<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Check and see if the form has a total field.
 *
 * @since  1.0
 * @param  int $form_id the attached form (if any)
 * @param  int $product_id the current product
 * @return bool
 */
function wc_fp_form_has_total_field( $form_id ) {

	// get the form
	$fields = FrmField::get_all_for_form( $form_id );

	// reverse the fields so we get the last total
	$fields = array_reverse( $fields );

	// check for the total field
	$found = false;
	foreach ( $fields as $field ) {
		if ( wc_fp_field_is_total( $field ) ) {
			$found = $field;
			break;
		}
	}

	return $found;

}

function wc_fp_field_is_total( $field ) {
	return ( isset( $field->field_options['use_calc'] ) && 1 == $field->field_options['use_calc'] );
}

function wc_fp_get_total_for_entry( $entry ) {
	$total_field = wc_fp_form_has_total_field( $entry->form_id );
	$total = FrmProEntryMetaHelper::get_post_or_meta_value( $entry, $total_field );
	return $total;
}
