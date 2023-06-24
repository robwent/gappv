<?php

if ( ! empty( $atts['label'] ) ) {

    ?><label for="<?php echo esc_attr( $atts['id'] ); ?>"><?php esc_html_e( $atts['label'], 'gappv' ); ?>: </label><?php

}

?><textarea
        class="<?php echo esc_attr( $atts['class'] ); ?>"
        cols="<?php echo esc_attr( $atts['cols'] ); ?>"
        id="<?php echo esc_attr( $atts['id'] ); ?>"
        name="<?php echo esc_attr( $atts['name'] ); ?>"
        rows="<?php echo esc_attr( $atts['rows'] ); ?>"><?php

    echo esc_textarea( $atts['value'] );

    ?></textarea>
<?php if ( ! empty( $atts['description'] ) ) {
?>
<span class="description" style="display:block"><?php esc_html_e( $atts['description'], 'gappv' ); ?></span>
<?php } ?>