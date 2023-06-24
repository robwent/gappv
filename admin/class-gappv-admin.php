<?php

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter;

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

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Gappv_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Gappv_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( is_admin() && get_current_screen()->id === 'edit-post' ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/gappv-admin.js', array( 'jquery' ), $this->version, false );
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
			$valid[ $option[0] ] = $this->sanitizer( $type, $input[ $name ] );

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

	}

	/**
	 * Manage the admin columns
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function manage_admin_columns( $columns ) {

		if ( ! array_key_exists( 'gappv', $columns ) ) {
			$columns['gappv'] = __( 'GA V4', 'iyba-data-import' );
		}

		return $columns;
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
				$needs_views =  $total_views ? 'false' : 'true';
				if ( ! $total_views ) {
					$total_views = 0;
				}
				echo '<span class="gappv-total-views" data-id="' . $post_id . '" data-update="' . $needs_views . '">' . $total_views . '</span>';
				break;

		}
	}

	public function ajax_views_update() {

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : false;

		$options = $this->options;
		if ( ! $options['json-key'] || ! $options['property-id'] || ! $post_id ) {
			return false;
		}
		if ( ! $options['cache-time'] ) {
			$options['cache-time'] = 1;
		}

		$transient   = '_gappv-' . $post_id;
		$total_views = get_transient( $transient );

		if ( $total_views !== false && is_numeric( $total_views ) ) {
			return $total_views;
		}

		$basename  = basename( get_permalink( $post_id ) );
		$link      = '/' . $basename . '/';
		$post_date = get_the_date( 'Y-m-d', $post_id );

		if ( isset( $options['start-date'] ) && strtotime( $post_date ) < strtotime( $options['start-date'] ) ) {
			$post_date = $options['start-date'];
		}

		$views = $this->call_api( $post_id, $link, $post_date );

		echo $views;

		wp_die();
	}

	private function call_api( $post_id, $link, $post_date ) {

		if ( get_post_status( $post_id ) !== 'publish' ) {
			return 0;
		}

		$options = $this->options;
		if ( ! $options['json-key'] || ! $options['property-id'] ) {
			return false;
		}

		$transient = '_gappv-' . $post_id;

		try {
			$client = new BetaAnalyticsDataClient(
				array(
					'credentials' => json_decode( $options['json-key'], true ),
				)
			);

			$filter = new FilterExpression([
				'filter' =>  new Filter([
					'field_name' => 'pagePath',
					'string_filter' => new StringFilter([
						'match_type' => MatchType::EXACT,
						'value' => $link,
					])
				])
			]);

			$response = $client->runReport(
				array(
					'property'          => 'properties/' . $options['property-id'],
					'dateRanges'        => array(
						new DateRange(
							array(
								'start_date' => $post_date,
								'end_date'   => 'today',
							)
						),
					),
					'dimensions'        => array(
						new Dimension(
							array(
								'name' => 'pagePath',
							)
						),
					),
					'metrics'           => array(
						new Metric(
							array(
								'name' => 'screenPageViews',
							)
						),
					),
					'dimensionFilter' => $filter,
					'metricAggregations' => [1],
					'sampling' => 'LARGE',
				)
			);

			$views = 0;

			if (count($response->getRows()) > 0) {
				$views = $response->getRows()[0]->getMetricValues()[0]->getValue();

				/**
				if ( $views >= 1000000 ) {
					$views = round( $views / 1000000, 1 ) . 'm';
				} elseif ( $views >= 1000 ) {
					$views = round( $views / 1000, 1 ) . 'k';
				} else {
					$views = number_format_i18n( $views );
				}
				**/

				$views = number_format_i18n( $views );
	
				set_transient( $transient, $views, $options['cache-time'] * HOUR_IN_SECONDS );

			}

			return $views;

		} catch ( Exception $e ) {
			echo 'Error';
		}

	}


}
