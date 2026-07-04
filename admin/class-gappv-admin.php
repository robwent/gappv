<?php

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\MetricAggregation;
use Google\Analytics\Data\V1beta\RunReportRequest;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://robertwent.com
 * @since      1.0.0
 *
 * @package    Gappv
 * @subpackage Gappv/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Gappv
 * @subpackage Gappv/admin
 * @author     Robert Went <i@robertwent.com>
 */
class Gappv_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The plugin options.
	 *
	 * @since        1.0.0
	 * @access        private
	 * @var        string $options The plugin options.
	 */
	private $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->set_options();

	}

	public function should_run() {
		if ( is_admin() ) {
			// Get the current screen
			$screen = get_current_screen();

			if ( ! $screen ) {
				return false;
			}

			// Post type listing pages for enabled post types
			if ( $screen->base == 'edit' && post_type_exists( $screen->post_type ) ) {
				return $this->is_post_type_enabled( $screen->post_type );
			}

			// Term listing pages for enabled taxonomies
			if ( $screen->base == 'edit-tags' && ! empty( $screen->taxonomy ) ) {
				return $this->is_taxonomy_enabled( $screen->taxonomy );
			}

			return false;
		}
		return false;
	}

	/**
	 * Returns the post types the views column is enabled for.
	 *
	 * An empty setting means all public post types (the behaviour before the
	 * setting existed).
	 *
	 * @return array Array of post type names.
	 */
	public function get_enabled_post_types() {
		if ( ! empty( $this->options['enabled-post-types'] ) && is_array( $this->options['enabled-post-types'] ) ) {
			return $this->options['enabled-post-types'];
		}

		return array_values( get_post_types( array( 'public' => true ), 'names' ) );
	}

	/**
	 * Returns the taxonomies the views column is enabled for.
	 *
	 * Empty by default - taxonomies are opt-in.
	 *
	 * @return array Array of taxonomy names.
	 */
	public function get_enabled_taxonomies() {
		if ( ! empty( $this->options['enabled-taxonomies'] ) && is_array( $this->options['enabled-taxonomies'] ) ) {
			return $this->options['enabled-taxonomies'];
		}

		return array();
	}

	/**
	 * Whether the views column should show for a post type.
	 *
	 * @param string $post_type The post type name.
	 * @return bool
	 */
	public function is_post_type_enabled( $post_type ) {
		if ( ! empty( $this->options['enabled-post-types'] ) && is_array( $this->options['enabled-post-types'] ) ) {
			return in_array( $post_type, $this->options['enabled-post-types'], true );
		}

		// No setting saved: default to all public post types.
		$post_type_object = get_post_type_object( $post_type );

		return $post_type_object && $post_type_object->public;
	}

	/**
	 * Whether the views column should show for a taxonomy.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return bool
	 */
	public function is_taxonomy_enabled( $taxonomy ) {
		return in_array( $taxonomy, $this->get_enabled_taxonomies(), true );
	}

	/**
	 * Registers the screen-specific column hooks for the enabled post types
	 * and taxonomies. Runs on admin_init so all post types/taxonomies are
	 * registered by the time the lists are read.
	 */
	public function register_dynamic_hooks() {
		foreach ( $this->get_enabled_post_types() as $post_type ) {
			add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'make_views_column_sortable' ) );
		}

		foreach ( $this->get_enabled_taxonomies() as $taxonomy ) {
			add_filter( "manage_edit-{$taxonomy}_columns", array( $this, 'manage_admin_columns' ) );
			add_filter( "manage_{$taxonomy}_custom_column", array( $this, 'custom_taxonomy_column' ), 10, 3 );
			add_filter( "manage_edit-{$taxonomy}_sortable_columns", array( $this, 'make_views_column_sortable' ) );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if ( $this->should_run() ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/gappv-admin.js', array( 'jquery' ), $this->version, false );
			wp_localize_script(
				$this->plugin_name,
				'gappv_ajax',
				array(
					'nonce' => wp_create_nonce( 'gappv_ajax_views_update' ),
				)
			);
		}
	}

	/**
	 * Warns when the PHP extensions the Google client library depends on are
	 * missing. The bundled protobuf library needs either the protobuf
	 * extension or bcmath - without them every API call is a fatal error.
	 */
	public function requirements_notice() {
		if ( extension_loaded( 'bcmath' ) || extension_loaded( 'protobuf' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// Only nag where it matters: our settings page and enabled list screens.
		if ( 'settings_page_gappv-options' !== $screen->id && ! $this->should_run() ) {
			return;
		}

		echo '<div class="notice notice-error"><p>' . esc_html__( 'GAPPV: the PHP bcmath extension (or the protobuf extension) is required to query the Google Analytics API. View counts cannot be fetched until it is installed.', 'gappv' ) . '</p></div>';
	}

	/**
	 * Adds inline CSS to the head of the admin page
	 *
	 * @since    1.0.0
	 */
	public function add_inline_css() {
		if ( $this->should_run() ) {
			echo '<style>.column-gappv{width:120px}</style>';
		}
	}

	/**
	 * Sets the class variable $options
	 */
	private function set_options() {

		$this->options = get_option( $this->plugin_name . '-options' );

	}

	/**
	 * Adds a settings page link to a menu
	 *
	 * @link        https://codex.wordpress.org/Administration_Menus
	 * @since        1.0.0
	 * @return        void
	 */
	public function add_menu() {

		add_options_page(
			esc_html__( 'Post Page Views', 'gappv' ),
			esc_html__( 'GAPPV Settings', 'gappv' ),
			'manage_options',
			'gappv-options',
			array( $this, 'gappv_settings' )
		);

	}

	/**
	 * Creates the options page
	 *
	 * @return        void
	 * @since        1.0.0
	 */
	public function gappv_settings() {
		include plugin_dir_path( __FILE__ ) . 'partials/gappv-admin-display.php';
	}

	/**
	 * Validates saved options
	 *
	 * @param array $input array of submitted plugin options
	 *
	 * @return        array                        array of validated plugin options
	 * @since        1.0.0
	 */
	public function validate_options( $input ) {
		$valid   = array();
		$options = $this->get_options_list();

		foreach ( $options as $option ) {

			$name                = $option[0];
			$type                = $option[1];
			$value               = isset( $input[ $name ] ) ? $input[ $name ] : null;
			$valid[ $option[0] ] = $this->sanitizer( $type, $value );

		}

		return $valid;
	}

	private function sanitizer( $type, $data ) {
		if ( empty( $type ) ) {
			return false;
		}
		if ( empty( $data ) ) {
			return false;
		}
		$return    = '';
		$sanitizer = new Gappv_Sanitize();
		$sanitizer->set_data( $data );
		$sanitizer->set_type( $type );
		$return = $sanitizer->clean();
		unset( $sanitizer );

		return $return;
	}

	/**
	 * Returns an array of options names, fields types, and default values
	 *
	 * @return        array            An array of options
	 */
	public static function get_options_list() {
		$options   = array();
		$options[] = array( 'json-key', 'raw', '' );
		$options[] = array( 'property-id', 'text', '' );
		$options[] = array( 'start-date', 'text', '' );
		$options[] = array( 'cache-time', 'text', '' );
		$options[] = array( 'enabled-post-types', 'array', array() );
		$options[] = array( 'enabled-taxonomies', 'array', array() );

		return $options;
	}

	/**
	 * Creates a settings section
	 *
	 * @param array $params Array of parameters for the section
	 *
	 * @return        mixed                        The settings section
	 * @since        1.0.0
	 */
	public function section_options( $params ) {
		include plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-section-options.php';
	}

	/**
	 * Creates a text field
	 *
	 * @param array $args The arguments for the field
	 *
	 * @return    string                        The HTML field
	 */
	public function field_text( $args ) {
		$defaults['class']       = 'text fat';
		$defaults['description'] = '';
		$defaults['label']       = '';
		$defaults['name']        = $this->plugin_name . '-options[' . $args['id'] . ']';
		$defaults['placeholder'] = '';
		$defaults['type']        = 'text';
		$defaults['value']       = '';
		$defaults['size']        = '';
		apply_filters( $this->plugin_name . '-field-text-options-defaults', $defaults );
		$atts = wp_parse_args( $args, $defaults );
		if ( ! empty( $this->options[ $atts['id'] ] ) ) {
			$atts['value'] = $this->options[ $atts['id'] ];
		}
		include plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-text.php';
	}

	/**
	 * Creates a textarea field
	 *
	 * @param array $args The arguments for the field
	 *
	 * @return    string                        The HTML field
	 */
	public function field_textarea( $args ) {

		$defaults['class']       = 'large-text';
		$defaults['cols']        = 50;
		$defaults['context']     = '';
		$defaults['description'] = '';
		$defaults['label']       = '';
		$defaults['name']        = $this->plugin_name . '-options[' . $args['id'] . ']';
		$defaults['rows']        = 10;
		$defaults['value']       = '';

		apply_filters( $this->plugin_name . '-field-textarea-options-defaults', $defaults );

		$atts = wp_parse_args( $args, $defaults );

		if ( ! empty( $this->options[ $atts['id'] ] ) ) {

			$atts['value'] = $this->options[ $atts['id'] ];

		}

		include plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-textarea.php';

	}

	/**
	 * Creates a list of checkboxes
	 *
	 * Expects $args['options'] as value => label pairs, or $args['source'] set
	 * to 'post_types' / 'taxonomies' to build the list from the registered
	 * public post types / taxonomies.
	 *
	 * @param array $args The arguments for the field
	 *
	 * @return    string                        The HTML field
	 */
	public function field_checkbox_list( $args ) {

		$defaults['description'] = '';
		$defaults['label']       = '';
		$defaults['name']        = $this->plugin_name . '-options[' . $args['id'] . '][]';
		$defaults['options']     = array();
		$defaults['source']      = '';
		$defaults['value']       = array();

		apply_filters( $this->plugin_name . '-field-checkbox-list-options-defaults', $defaults );

		$atts = wp_parse_args( $args, $defaults );

		if ( empty( $atts['options'] ) && ! empty( $atts['source'] ) ) {
			$objects = array();
			if ( 'post_types' === $atts['source'] ) {
				$objects = get_post_types( array( 'public' => true ), 'objects' );
			} elseif ( 'taxonomies' === $atts['source'] ) {
				$objects = get_taxonomies( array( 'public' => true ), 'objects' );
			}
			foreach ( $objects as $object ) {
				$atts['options'][ $object->name ] = $object->labels->name . ' (' . $object->name . ')';
			}
		}

		if ( ! empty( $this->options[ $atts['id'] ] ) && is_array( $this->options[ $atts['id'] ] ) ) {

			$atts['value'] = $this->options[ $atts['id'] ];

		}

		include plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-checkbox-list.php';

	}

	/**
	 * Registers settings sections with WordPress
	 *
	 * @return        void
	 * @since        1.0.0
	 */
	public function register_sections() {
		// add_settings_section( $id, $title, $callback, $menu_slug );
		add_settings_section(
			$this->plugin_name . '-api',
			apply_filters( $this->plugin_name . 'section-title-api', esc_html__( 'API Connection Details', 'gappv' ) ),
			array( $this, 'section_options' ),
			$this->plugin_name
		);

		add_settings_section(
			$this->plugin_name . '-config',
			apply_filters( $this->plugin_name . 'section-title-api', esc_html__( 'Configuration', 'gappv' ) ),
			array( $this, 'section_options' ),
			$this->plugin_name
		);
	}

	/**
	 * Registers plugin settings
	 *
	 * @return        void
	 * @since        1.0.0
	 */
	public function register_settings() {
		// register_setting( $option_group, $option_name, $sanitize_callback );
		register_setting(
			$this->plugin_name . '-options',
			$this->plugin_name . '-options',
			array( $this, 'validate_options' )
		);
	}

	/**
	 * Registers settings fields with WordPress
	 */
	public function register_fields() {
		// add_settings_field( $id, $title, $callback, $menu_slug, $section, $args );
		add_settings_field(
			'json-key',
			esc_html__( 'JSON Key', 'gappv' ),
			array( $this, 'field_textarea' ),
			$this->plugin_name,
			$this->plugin_name . '-api',
			array(
				'description' => __( 'A valid service account JSON key with access to the analytics API.', 'gappv' ),
				'id'          => 'json-key',
				'type'        => 'raw',
			)
		);

		add_settings_field(
			'property-id',
			esc_html__( 'The Analytics Property ID', 'gappv' ),
			array( $this, 'field_text' ),
			$this->plugin_name,
			$this->plugin_name . '-api',
			array(
				'description' => '',
				'id'          => 'property-id',
				'type'        => 'number',
			)
		);

		add_settings_field(
			'start-date',
			esc_html__( 'Start Date for Analytics', 'gappv' ),
			array( $this, 'field_text' ),
			$this->plugin_name,
			$this->plugin_name . '-config',
			array(
				'description' => '',
				'id'          => 'start-date',
				'type'        => 'date',
			)
		);

		add_settings_field(
			'cache-time',
			esc_html__( 'Cache Time (H)', 'gappv' ),
			array( $this, 'field_text' ),
			$this->plugin_name,
			$this->plugin_name . '-config',
			array(
				'description' => '',
				'id'          => 'cache-time',
				'type'        => 'number',
			)
		);

		add_settings_field(
			'enabled-post-types',
			esc_html__( 'Post Types', 'gappv' ),
			array( $this, 'field_checkbox_list' ),
			$this->plugin_name,
			$this->plugin_name . '-config',
			array(
				'description' => __( 'Show the views column on these post types. Leave all unchecked to show it on every public post type.', 'gappv' ),
				'id'          => 'enabled-post-types',
				'type'        => 'array',
				'source'      => 'post_types',
			)
		);

		add_settings_field(
			'enabled-taxonomies',
			esc_html__( 'Taxonomies', 'gappv' ),
			array( $this, 'field_checkbox_list' ),
			$this->plugin_name,
			$this->plugin_name . '-config',
			array(
				'description' => __( 'Show the views column on the term listings for these taxonomies.', 'gappv' ),
				'id'          => 'enabled-taxonomies',
				'type'        => 'array',
				'source'      => 'taxonomies',
			)
		);

	}

	/**
	 * Manage the admin columns
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function manage_admin_columns( $columns ) {

		// Post list screens: only add the column for enabled post types.
		// Term list screens pass through - the taxonomy column filter is only
		// registered for enabled taxonomies.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'edit' === $screen->base && ! $this->is_post_type_enabled( $screen->post_type ) ) {
			return $columns;
		}

		if ( ! array_key_exists( 'gappv', $columns ) ) {
			$columns['gappv'] = __( 'GA Views', 'gappv' );
		}

		return $columns;
	}

	/**
	 * Make the views column sortable
	 *
	 * @param [type] $columns
	 * @return void
	 */
	public function make_views_column_sortable( $columns ) {
		$columns['gappv'] = '_gappv_views';

		return $columns;
	}

	/**
	 * Include posts with no value when sorting by views
	 *
	 * @param [type] $query
	 * @return void
	 */
	public function sort_views_custom_column_query( $query ) {
		if ( $this->should_run() ) {
			$orderby = $query->get( 'orderby' );

			if ( '_gappv_views' == $orderby ) {

				$meta_query = array(
					'relation' => 'OR',
					array(
						'key'     => '_gappv_views',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key' => '_gappv_views',
					),
				);

				$query->set( 'meta_query', $meta_query );
				$query->set( 'orderby', 'meta_value_num' );
			}
		}
	}

	/**
	 * Sorts term list tables by views when requested.
	 *
	 * The '_gappv_views' orderby value only comes from our sortable column,
	 * so it is safe to react to it on any term query. Terms without a stored
	 * count are included (sorted as no value) via the OR meta query.
	 *
	 * Uses pre_get_terms (not get_terms_args) because WP_Term_Query parses
	 * its meta_query object before the get_terms_args filter runs, and the
	 * orderby parser reads the meta clauses from that object.
	 *
	 * @param WP_Term_Query $query The term query, passed by reference.
	 */
	public function sort_terms_by_views( $query ) {

		if ( isset( $query->query_vars['orderby'] ) && '_gappv_views' === $query->query_vars['orderby'] ) {

			$query->query_vars['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_gappv_views',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key' => '_gappv_views',
				),
			);
			$query->query_vars['orderby']    = 'meta_value_num';

			// Re-parse so the orderby parser sees the new meta clauses.
			$query->meta_query->parse_query_vars( $query->query_vars );
		}
	}

	/**
	 * Adds the column data specified above
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function custom_admin_columns( $column, $post_id ) {
		switch ( $column ) {

			case 'gappv':
				$transient   = '_gappv-' . $post_id;
				$total_views = get_transient( $transient );
				$needs_views = $total_views ? 'false' : 'true';
				if ( ! $total_views ) {
					// Check for postmeta and show that before the update.
					$total_views = get_post_meta( $post_id, '_gappv_views', true );
					$total_views = $total_views ?: 0;
				}
				echo '<span class="gappv-total-views" data-id="' . $post_id . '" data-type="post" data-update="' . $needs_views . '">' . number_format_i18n( $total_views ) . '</span>';
				break;

		}
	}

	/**
	 * Renders the views column on term list screens.
	 *
	 * Unlike the post column hook (an action that echoes), the taxonomy
	 * column hook is a filter that must return the cell content.
	 *
	 * @param string $content     The current column content.
	 * @param string $column_name The column name.
	 * @param int    $term_id     The term ID.
	 *
	 * @return string
	 */
	public function custom_taxonomy_column( $content, $column_name, $term_id ) {

		if ( 'gappv' !== $column_name ) {
			return $content;
		}

		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return $content;
		}

		$transient   = '_gappv-term-' . $term_id;
		$total_views = get_transient( $transient );
		$needs_views = $total_views ? 'false' : 'true';
		if ( ! $total_views ) {
			// Check for term meta and show that before the update.
			$total_views = get_term_meta( $term_id, '_gappv_views', true );
			$total_views = $total_views ?: 0;
		}

		return $content . '<span class="gappv-total-views" data-id="' . esc_attr( $term_id ) . '" data-type="term" data-taxonomy="' . esc_attr( $term->taxonomy ) . '" data-update="' . $needs_views . '">' . number_format_i18n( $total_views ) . '</span>';
	}

	public function ajax_views_update() {

		check_ajax_referer( 'gappv_ajax_views_update', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die();
		}

		$object_type = ( isset( $_POST['object_type'] ) && 'term' === $_POST['object_type'] ) ? 'term' : 'post';
		$object_id   = isset( $_POST['object_id'] ) ? intval( $_POST['object_id'] ) : 0;
		// Back-compat with the original parameter name.
		if ( ! $object_id && isset( $_POST['post_id'] ) ) {
			$object_id = intval( $_POST['post_id'] );
		}

		$options = $this->options;
		if ( ! $options['json-key'] || ! $options['property-id'] || ! $object_id ) {
			wp_die();
		}

		if ( 'term' === $object_type ) {
			$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( $_POST['taxonomy'] ) : '';
			echo $this->get_term_views( $object_id, $taxonomy );
			wp_die();
		}

		$transient   = '_gappv-' . $object_id;
		$total_views = get_transient( $transient );

		if ( $total_views !== false && is_numeric( $total_views ) ) {
			echo $total_views;
			wp_die();
		}

		$link      = wp_make_link_relative( get_permalink( $object_id ) );
		$post_date = get_the_date( 'Y-m-d', $object_id );

		if ( isset( $options['start-date'] ) && strtotime( $post_date ) < strtotime( $options['start-date'] ) ) {
			$post_date = $options['start-date'];
		}

		$views = $this->call_api( $object_id, $link, $post_date );

		echo $views;

		wp_die();
	}

	/**
	 * Returns the view count for a term, from cache or the API.
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 *
	 * @return int|string The view count, or 'Error'.
	 */
	public function get_term_views( $term_id, $taxonomy ) {

		if ( ! $taxonomy || ! $this->is_taxonomy_enabled( $taxonomy ) ) {
			return 'Error';
		}

		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return 'Error';
		}

		$transient   = '_gappv-term-' . $term_id;
		$total_views = get_transient( $transient );

		if ( $total_views !== false && is_numeric( $total_views ) ) {
			return $total_views;
		}

		$term_link = get_term_link( $term );
		if ( is_wp_error( $term_link ) ) {
			return 'Error';
		}

		$options = $this->options;

		// Terms have no publish date - use the configured start date, or the
		// earliest date the GA4 API accepts.
		$start_date = ! empty( $options['start-date'] ) ? $options['start-date'] : '2015-08-14';

		$views = $this->fetch_views( wp_make_link_relative( $term_link ), $start_date );

		if ( false === $views ) {
			return 'Error';
		}

		$cache_time = ! empty( $options['cache-time'] ) ? $options['cache-time'] : 1;

		update_term_meta( $term_id, '_gappv_views', $views );
		set_transient( $transient, $views, $cache_time * HOUR_IN_SECONDS );

		return $views;
	}

	public function call_api( $post_id, $link, $post_date ) {

		if ( get_post_status( $post_id ) !== 'publish' ) {
			return 0;
		}

		$views = $this->fetch_views( $link, $post_date );

		if ( false === $views ) {
			echo 'Error';
			return;
		}

		if ( $views > 0 ) {
			$options    = $this->options;
			$cache_time = ! empty( $options['cache-time'] ) ? $options['cache-time'] : 1;

			update_post_meta( $post_id, '_gappv_views', $views );
			set_transient( '_gappv-' . $post_id, $views, $cache_time * HOUR_IN_SECONDS );
		} else {
			update_post_meta( $post_id, '_gappv_views', 0 );
		}

		return $views;
	}

	/**
	 * Queries the GA4 API for the pageview count of a relative link.
	 *
	 * @param string $link       The relative link to match (exact pagePath).
	 * @param string $start_date The report start date (Y-m-d).
	 *
	 * @return int|false The view count, or false on failure.
	 */
	public function fetch_views( $link, $start_date ) {

		$options = $this->options;
		if ( ! $options['json-key'] || ! $options['property-id'] ) {
			return false;
		}

		try {
			$client = new BetaAnalyticsDataClient(
				array(
					'credentials' => json_decode( $options['json-key'], true ),
				)
			);

			$filter = new FilterExpression(
				array(
					'filter' => new Filter(
						array(
							'field_name'    => 'pagePath',
							'string_filter' => new StringFilter(
								array(
									'match_type' => MatchType::EXACT,
									'value'      => $link,
								)
							),
						)
					),
				)
			);

			$request = ( new RunReportRequest() )
				->setProperty( 'properties/' . $options['property-id'] )
				->setDateRanges(
					array(
						new DateRange(
							array(
								'start_date' => $start_date,
								'end_date'   => 'today',
							)
						),
					)
				)
				->setDimensions(
					array(
						new Dimension(
							array(
								'name' => 'pagePath',
							)
						),
					)
				)
				->setMetrics(
					array(
						new Metric(
							array(
								'name' => 'screenPageViews',
							)
						),
					)
				)
				->setDimensionFilter( $filter )
				->setMetricAggregations( array( MetricAggregation::TOTAL ) );

			$response = $client->runReport( $request );

			$views = 0;

			if ( count( $response->getRows() ) > 0 ) {
				$views = (int) $response->getRows()[0]->getMetricValues()[0]->getValue();
			}

			return $views;

		} catch ( Throwable $e ) {
			// Catches API/auth exceptions and also fatal errors such as the
			// protobuf library calling bcmath functions on hosts without the
			// bcmath extension.
			return false;
		}

	}

}

