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


	/**
	 * Fills in view counts for posts and terms that don't have one yet.
	 *
	 * Processes the post types and taxonomies the views column is enabled
	 * for in the plugin settings.
	 *
	 * ## OPTIONS
	 *
	 * [--number=<number>]
	 * : How many posts (and how many terms) to process per run.
	 * ---
	 * default: 10
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp gappv populate
	 *     wp gappv populate --number=25
	 */
	public function populate( $args, $assoc_args ) {
		if ( ! class_exists( 'Gappv_Admin' ) ) {
			WP_CLI::error( __( 'Gappv_Admin Class does not exist!', 'gappv' ) );
		}
		$number = isset( $assoc_args['number'] ) ? max( 1, intval( $assoc_args['number'] ) ) : 10;
		$gappv  = new Gappv_Admin( 'gappv', GAPPV_VERSION );

		// Posts of the enabled post types without a stored count.
		$post_types = $gappv->get_enabled_post_types();

		$query = new WP_Query(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'     => '_gappv_views',
						'compare' => 'NOT EXISTS',
					),
				),
				'posts_per_page' => $number,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id   = get_the_ID();
				$link      = wp_make_link_relative( get_permalink( $post_id ) );
				$post_date = get_the_date( 'Y-m-d', $post_id );
				$views     = $gappv->call_api( $post_id, $link, $post_date );
				WP_CLI::line( sprintf( __( 'Post Path: %1$s, Views: %2$d', 'gappv' ), $link, $views ) );
			}
		}
		wp_reset_postdata();

		// Terms of the enabled taxonomies without a stored count.
		$taxonomies = $gappv->get_enabled_taxonomies();
		if ( empty( $taxonomies ) ) {
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomies,
				'hide_empty' => false,
				'number'     => $number,
				'meta_query' => array(
					array(
						'key'     => '_gappv_views',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		if ( is_wp_error( $terms ) ) {
			WP_CLI::warning( $terms->get_error_message() );
			return;
		}

		foreach ( $terms as $term ) {
			$views = $gappv->get_term_views( $term->term_id, $term->taxonomy );
			$link  = wp_make_link_relative( get_term_link( $term ) );
			WP_CLI::line( sprintf( __( 'Term Path: %1$s, Views: %2$s', 'gappv' ), $link, $views ) );
		}
	}


}

WP_CLI::add_command( 'gappv', 'Gappv_Wp_Cli_Commands' );
