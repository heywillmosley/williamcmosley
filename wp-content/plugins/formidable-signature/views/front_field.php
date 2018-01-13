<?php 

$typed_value = is_array( $field['value'] ) && isset( $field['value']['typed'] ) ? $field['value']['typed'] : '';
$drawn_value = is_array( $field['value'] ) && isset( $field['value']['output'] ) ? $field['value']['output'] : '';

if ( $entry_id && $field['value'] && ! empty( $drawn_value ) ) {
    echo FrmSigAppController::get_final_signature_value( $field['value'], array(
			'field_id' => $field['id'], 'entry_id' => $entry_id, 'use_html' => true,
    ) ); ?>
    <input type="hidden" name="<?php echo esc_attr( $field_name ) ?>[typed]" value="<?php echo esc_attr( $typed_value ) ?>" />
    <input type="hidden" name="<?php echo esc_attr( $field_name ) ?>[output]" id="frmsig_<?php echo (int) $field['id'] ?>_output" class="output" value="<?php echo esc_attr( $drawn_value ) ?>" />

<?php
} else {
    $hide_tabs = isset( $field['restrict'] ) ? $field['restrict'] : false;

?>
    <style type="text/css">.sigWrapper.current{border-color:#<?php echo esc_attr( $style_settings['border_color'] ) ?>;}</style>

    <div class="sigPad" id="sigPad<?php echo (int) $field['id'] ?>" style="max-width:<?php echo (int) $width ?>px;">

<ul class="sigNav <?php echo $hide_tabs ? 'sigHideTabs' : ''; ?>">
    <li class="drawIt"><a href="#draw-it" class="current"><?php echo esc_html( $field['label1'] ) ?></a></li>
    <li class="typeIt"><a href="#type-it" ><?php echo esc_html( $field['label2'] ) ?></a></li>
    <li class="clearButton"><a href="#clear"><?php echo esc_html( $field['label3'] ) ?></a></li>
</ul>
<div class="sig sigWrapper" style="height:<?php echo esc_attr( $height ) ?>px;border-color:#<?php echo esc_attr( $style_settings['border_color'] ) ?>;">
    <div class="typed" style="height:<?php echo esc_attr( $height ) ?>px;">
        <input type="text" name="<?php echo esc_attr( $field_name ) ?>[typed]" class="name" id="field_<?php echo esc_attr( $field['field_key'] ) ?>" autocomplete="off" style="width:<?php echo ($width-20) ?>px;" value="<?php echo esc_attr( $typed_value ) ?>" />
    </div>
    <canvas class="pad" width="<?php echo esc_attr( $width - 2 ) ?>" height="<?php echo esc_attr( $height ) ?>"></canvas>
    <input type="hidden" name="<?php echo esc_attr( $field_name ) ?>[output]" class="output" value="<?php echo esc_attr( $drawn_value ) ?>" />
    </div>
</div>
<?php } ?>