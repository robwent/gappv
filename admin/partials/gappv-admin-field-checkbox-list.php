<?php

if ( ! empty( $atts['label'] ) ) {
	?><label><?php esc_html_e( $atts['label'], 'gappv' ); ?>: </label>
	<?php
}
?>
<fieldset>
	<?php foreach ( $atts['options'] as $option_value => $option_label ) { ?>
		<label style="display:block;margin-bottom:2px">
			<input
				type="checkbox"
				name="<?php echo esc_attr( $atts['name'] ); ?>"
				value="<?php echo esc_attr( $option_value ); ?>"
				<?php checked( in_array( $option_value, (array) $atts['value'], true ) ); ?>/>
			<?php echo esc_html( $option_label ); ?>
		</label>
	<?php } ?>
</fieldset>
<?php
if ( ! empty( $atts['description'] ) ) {
	?>
	<span class="description" style="display:block"><?php esc_html_e( $atts['description'], 'gappv' ); ?></span>
	<?php
}
