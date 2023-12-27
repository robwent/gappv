<?php

/**
 * Custom command line actions for wp-cli
 *
 * @link       https://robertwent.com
 * @since      1.0.0
 *
 * @package    Gappv
 * @subpackage Gappv/includes
 */

class Gappv_Wp_Cli_Commands extends WP_CLI_Command {


	public function populate() {
		if ( ! class_exists( 'Gappv_Admin' ) ) {
			WP_CLI::line( __( 'Gappv_Admin Class does not exist!', 'gappv' ) );
		}
		$gappv  = new Gappv_Admin( 'gappv', GAPPV_VERSION );
		$args   = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_gappv_views',
					'compare' => 'NOT EXISTS',
				),
			),
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		$query  = new WP_Query( $args );
		$output = '';
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id   = get_the_ID();
				$link      = wp_make_link_relative( get_permalink( $post_id ) );
				$post_date = get_the_date( 'Y-m-d', $post_id );
				$views     = $gappv->call_api( $post_id, $link, $post_date );
				$output    .= WP_CLI::line( sprintf( __( 'Post Path: %1$s, Views: %2$d', 'gappv' ), $link, $views ) );
			}
		}
		wp_reset_postdata();
	}


}

WP_CLI::add_command( 'gappv', 'Gappv_Wp_Cli_Commands' );
