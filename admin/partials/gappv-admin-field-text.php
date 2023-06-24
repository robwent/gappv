<?php


if ( ! empty( $atts['label'] ) ) {
	?><label for="<?php echo esc_attr( $atts['id'] ); ?>"><?php esc_html_e( $atts['label'], 'gappv' ); ?>: </label>
	<?php
}
?>
<input
	class="<?php echo esc_attr( $atts['class'] ); ?>"
	id="<?php echo esc_attr( $atts['id'] ); ?>"
	name="<?php echo esc_attr( $atts['name'] ); ?>"
	placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
	type="<?php echo esc_attr( $atts['type'] ); ?>"
	value="<?php echo esc_attr( $atts['value'] ); ?>"
	size="<?php echo esc_attr( $atts['size'] ); ?>"/>
					 <?php
						if ( ! empty( $atts['description'] ) ) {
							?>
	<span class="description" style="display:block"><?php esc_html_e( $atts['description'], 'gappv' ); ?></span>
							<?php
						}
