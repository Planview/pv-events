<?php
/**
 * PV Events
 *
 * @package   PV_Events
 * @author    Steve Crockett <crockett95@gmail.com
 * @license   GPL-2.0+
 * @link      https://github.com/Planview/pv-events
 * @copyright 2014 Planview, Inc.
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-plugin-name-admin.php`
 *
 * @package   PV_Events
 * @author    Steve Crockett <crockett95@gmail.com
 */
class PV_Events {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'pv-events';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( 'init', array( $this, 'action_init' ) );
		add_filter( '@TODO', array( $this, 'filter_method_name' ) );

		$this->custom_fields_libraries();
		if ( ! defined( 'ACF_LITE' ) ) define( 'ACF_LITE', true );
		include dirname( dirname(__FILE__) ) . '/vendor/advanced-custom-fields/acf.php';

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		flush_rewrite_rules();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_init() {
		$this->post_types();
		$this->taxonomies();
		$this->options_pages();
		$this->custom_fields();
	}

	/**
	 * Use the plugins_loaded hook to load all the dependencies
	 */
	public function action_plugins_loaded() {

		if ( ! defined( 'ACF_LITE' ) ) define( 'ACF_LITE', true );

		include dirname( dirname(__FILE__) ) . '/vendor/advanced-custom-fields/acf.php';
		// require_once( dirname( dirname(__FILE__) ) . '/vendor/acf-options-page/acf-options-page.php' );
		// require_once( dirname( dirname(__FILE__) ) . '/vendor/acf-repeater/acf-repeater.php' );
	}

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}

	/**
	 * Register the custom post types used for the plugin
	 */
	private function post_types() {
		// Register custom post-types
		$presentation_labels = array(
			'name'                => __( 'Live Presentations', 'pv-events' ),
			'singular_name'       => __( 'Live Presentation', 'pv-events' ),
			'add_new'             => _x( 'Add New Presentation', 'pv-events', 'pv-events' ),
			'add_new_item'        => __( 'Add New Presentation', 'pv-events' ),
			'edit_item'           => __( 'Edit Presentation', 'pv-events' ),
			'new_item'            => __( 'New Presentation', 'pv-events' ),
			'view_item'           => __( 'View Presentation', 'pv-events' ),
			'search_items'        => __( 'Search Presentations', 'pv-events' ),
			'not_found'           => __( 'No Presentations found', 'pv-events' ),
			'not_found_in_trash'  => __( 'No Presentations found in Trash', 'pv-events' ),
			'parent_item_colon'   => __( 'Parent Presentation:', 'pv-events' ),
			'menu_name'           => __( 'Presentations', 'pv-events' ),
		);
	
		$presentation_args = array(
			'labels'              => $presentation_labels,
			'hierarchical'        => false,
			'description'         => 'Live featured presentations',
			'taxonomies'          => array(),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-id',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post',
			'supports'            => array(
				'title', 'editor', 'thumbnail',
				'excerpt', 'comments', 'revisions'
			)
		);
	
		register_post_type( 'presentations', $presentation_args );


		$topic_labels = array(
			'name'                => __( 'Topic Areas', 'pv-events' ),
			'singular_name'       => __( 'Topic Area', 'pv-events' ),
			'add_new'             => _x( 'Add New Topic Area', 'pv-events', 'pv-events' ),
			'add_new_item'        => __( 'Add New Topic Area', 'pv-events' ),
			'edit_item'           => __( 'Edit Topic Area', 'pv-events' ),
			'new_item'            => __( 'New Topic Area', 'pv-events' ),
			'view_item'           => __( 'View Topic Area', 'pv-events' ),
			'search_items'        => __( 'Search Topic Areas', 'pv-events' ),
			'not_found'           => __( 'No Topic Areas found', 'pv-events' ),
			'not_found_in_trash'  => __( 'No Topic Areas found in Trash', 'pv-events' ),
			'parent_item_colon'   => __( 'Parent Topic Area:', 'pv-events' ),
			'menu_name'           => __( 'Topic Areas', 'pv-events' ),
		);
	
		$topic_args = array(
			'labels'              => $topic_labels,
			'hierarchical'        => false,
			'description'         => 'Event topic areas',
			'taxonomies'          => array(),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-analytics',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post',
			'supports'            => array(
				'title', 'editor', 'thumbnail',
				'excerpt','revisions'
			)
		);
	
		register_post_type( 'topics', $topic_args );


		$resource_labels = array(
			'name'                => __( 'Resources', 'pv-events' ),
			'singular_name'       => __( 'Resource', 'pv-events' ),
			'add_new'             => _x( 'Add New Resource', 'pv-events', 'pv-events' ),
			'add_new_item'        => __( 'Add New Resource', 'pv-events' ),
			'edit_item'           => __( 'Edit Resource', 'pv-events' ),
			'new_item'            => __( 'New Resource', 'pv-events' ),
			'view_item'           => __( 'View Resource', 'pv-events' ),
			'search_items'        => __( 'Search Resources', 'pv-events' ),
			'not_found'           => __( 'No Resources found', 'pv-events' ),
			'not_found_in_trash'  => __( 'No Resources found in Trash', 'pv-events' ),
			'parent_item_colon'   => __( 'Parent Resource:', 'pv-events' ),
			'menu_name'           => __( 'Resource Library', 'pv-events' ),
		);
	
		$resource_args = array(
			'labels'              => $resource_labels,
			'hierarchical'        => false,
			'description'         => 'Resources from the event',
			'taxonomies'          => array( 'type', 'release' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-book',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post',
			'supports'            => array(
				'title', 'editor', 'thumbnail',
				'excerpt','revisions'
			)
		);
	
		register_post_type( 'library', $resource_args );

	}

	/**
	 * Register the custom taxonomies used for the plugin
	 */
	private function taxonomies() {
		
		$type_labels = array(
			'name'					=> _x( 'Resource Types', 'Taxonomy plural name', 'pv-events' ),
			'singular_name'			=> _x( 'Resource Type', 'Taxonomy singular name', 'pv-events' ),
			'search_items'			=> __( 'Search Resource Types', 'pv-events' ),
			'popular_items'			=> __( 'Popular Resource Types', 'pv-events' ),
			'all_items'				=> __( 'All Resource Types', 'pv-events' ),
			'parent_item'			=> __( 'Parent Type', 'pv-events' ),
			'parent_item_colon'		=> __( 'Parent Type:', 'pv-events' ),
			'edit_item'				=> __( 'Edit Resource Type', 'pv-events' ),
			'update_item'			=> __( 'Update Resource Type', 'pv-events' ),
			'add_new_item'			=> __( 'Add New Resource Type', 'pv-events' ),
			'new_item_name'			=> __( 'New Resource Type Name', 'pv-events' ),
			'add_or_remove_items'	=> __( 'Add or remove Resource Types', 'pv-events' ),
			'choose_from_most_used'	=> __( 'Choose from most used types', 'pv-events' ),
			'menu_name'				=> __( 'Resource Types', 'pv-events' ),
		);
	
		$type_args = array(
			'labels'            => $type_labels,
			'public'            => true,
			'show_in_nav_menus' => true,
			'show_admin_column' => false,
			'hierarchical'      => true,
			'show_tagcloud'     => true,
			'show_ui'           => true,
			'query_var'         => true,
			'rewrite'           => true,
			'query_var'         => true,
		);
	
		register_taxonomy( 'type', array( 'library' ), $type_args );		


		$release_labels = array(
			'name'					=> _x( 'Releases', 'Taxonomy plural name', 'pv-events' ),
			'singular_name'			=> _x( 'Release', 'Taxonomy singular name', 'pv-events' ),
			'search_items'			=> __( 'Search Releases', 'pv-events' ),
			'popular_items'			=> __( 'Popular Releases', 'pv-events' ),
			'all_items'				=> __( 'All Releases', 'pv-events' ),
			'parent_item'			=> __( 'Parent Release', 'pv-events' ),
			'parent_item_colon'		=> __( 'Parent Release:', 'pv-events' ),
			'edit_item'				=> __( 'Edit Release', 'pv-events' ),
			'update_item'			=> __( 'Update Release', 'pv-events' ),
			'add_new_item'			=> __( 'Add New Release', 'pv-events' ),
			'new_item_name'			=> __( 'New Release Name', 'pv-events' ),
			'add_or_remove_items'	=> __( 'Add or remove Release', 'pv-events' ),
			'choose_from_most_used'	=> __( 'Choose from most used releases', 'pv-events' ),
			'menu_name'				=> __( 'Releases', 'pv-events' ),
		);
	
		$release_args = array(
			'labels'            => $release_labels,
			'public'            => true,
			'show_in_nav_menus' => true,
			'show_admin_column' => false,
			'hierarchical'      => true,
			'show_tagcloud'     => true,
			'show_ui'           => true,
			'query_var'         => true,
			'rewrite'           => true,
			'query_var'         => true,
		);
	
		register_taxonomy( 'release', array( 'library' ), $release_args );	
		
	}

	private function custom_fields_libraries() {
		if ( ! defined( 'ACF_LITE' ) ) define( 'ACF_LITE', true );
		include dirname( dirname(__FILE__) ) . '/vendor/advanced-custom-fields/acf.php';
		include dirname( dirname(__FILE__) ) . '/vendor/acf-repeater/acf-repeater.php';
		include dirname( dirname(__FILE__) ) . '/vendor/acf-field-date-time-picker/acf-date_time_picker.php';
		include dirname( dirname(__FILE__) ) . '/vendor/acf-options-page/acf-options-page.php';
		include dirname( dirname(__FILE__) ) . '/vendor/acf-gravity-forms/acf-gravity_forms.php';
	}


	/**
	 * Register the custom fields for the plugin with ACF
	 */
	private function custom_fields() {
		if( ! function_exists("register_field_group") ) wp_die(file_get_contents( dirname( dirname(__FILE__) ) . '/vendor/advanced-custom-fields/acf.php'));
		register_field_group(array (
			'id' => 'pv_event_resource',
			'title' => 'Resource',
			'fields' => array (
				array (
					'key' => 'field_53750a5d69efc',
					'label' => 'Type',
					'name' => 'pv_event_resource_doc_type',
					'type' => 'radio',
					'required' => 1,
					'choices' => array (
						'video' => 'Video',
						'pdf' => 'PDF',
						'link' => 'Link',
						'slideshare' => 'Slideshare',
					),
					'other_choice' => 0,
					'save_other_choice' => 0,
					'default_value' => '',
					'layout' => 'horizontal',
				),
				array (
					'key' => 'field_53750ab669efd',
					'label' => 'Video Code',
					'name' => 'pv_event_resource_video_code',
					'type' => 'textarea',
					'conditional_logic' => array (
						'status' => 1,
						'rules' => array (
							array (
								'field' => 'field_53750a5d69efc',
								'operator' => '==',
								'value' => 'video',
							),
						),
						'allorany' => 'all',
					),
					'default_value' => '',
					'placeholder' => '',
					'maxlength' => '',
					'rows' => '',
					'formatting' => 'html',
				),
				array (
					'key' => 'field_53750ae569efe',
					'label' => 'File',
					'name' => 'pv_event_resource_file',
					'type' => 'file',
					'conditional_logic' => array (
						'status' => 1,
						'rules' => array (
							array (
								'field' => 'field_53750a5d69efc',
								'operator' => '==',
								'value' => 'pdf',
							),
						),
						'allorany' => 'all',
					),
					'save_format' => 'object',
					'library' => 'all',
				),
				array (
					'key' => 'field_53750b0569eff',
					'label' => 'URL',
					'name' => 'pv_event_resource_url',
					'type' => 'text',
					'conditional_logic' => array (
						'status' => 1,
						'rules' => array (
							array (
								'field' => 'field_53750a5d69efc',
								'operator' => '==',
								'value' => 'link',
							),
							array (
								'field' => 'field_53750a5d69efc',
								'operator' => '==',
								'value' => 'slideshare',
							),
						),
						'allorany' => 'any',
					),
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'formatting' => 'none',
					'maxlength' => '',
				),
				array (
					'key' => 'field_5375145e4ae02',
					'label' => 'Resource Type',
					'name' => 'pv_event_resource_type',
					'type' => 'taxonomy',
					'required' => 1,
					'taxonomy' => 'type',
					'field_type' => 'select',
					'allow_null' => 0,
					'load_save_terms' => 1,
					'return_format' => 'object',
					'multiple' => 0,
				),
				array (
					'key' => 'field_537514974ae03',
					'label' => 'Releases',
					'name' => 'pv_event_resource_release',
					'type' => 'taxonomy',
					'required' => 1,
					'taxonomy' => 'release',
					'field_type' => 'select',
					'allow_null' => 0,
					'load_save_terms' => 1,
					'return_format' => 'object',
					'multiple' => 0,
				),
			),
			'location' => array (
				array (
					array (
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'library',
						'order_no' => 0,
						'group_no' => 0,
					),
				),
			),
			'options' => array (
				'position' => 'acf_after_title',
				'layout' => 'default',
				'hide_on_screen' => array (
				),
			),
			'menu_order' => 0,
		));
		

		register_field_group(array (
			'id' => 'pv_event_featured-presentation',
			'title' => 'Featured Presentation',
			'fields' => array (
				array (
					'key' => 'field_537633309a4a6',
					'label' => 'Video Code',
					'name' => 'pv_event_presentation_video',
					'type' => 'textarea',
					'default_value' => '',
					'placeholder' => '',
					'maxlength' => '',
					'rows' => '',
					'formatting' => 'html',
				),
				array (
					'key' => 'field_537633709a4a7',
					'label' => 'Abstract (PL) or Sidebar Content (Launch)',
					'name' => 'pv_event_presentation_abstract',
					'type' => 'wysiwyg',
					'default_value' => '',
					'toolbar' => 'full',
					'media_upload' => 'yes',
				),
				array (
					'key' => 'field_537635a69a4a8',
					'label' => 'Start Date / Time',
					'name' => 'pv_event_presentation_start_time',
					'type' => 'date_time_picker',
					'show_date' => 'true',
					'date_format' => 'm/d/y',
					'time_format' => 'H:mm z',
					'show_week_number' => 'false',
					'picker' => 'slider',
					'save_as_timestamp' => 'true',
					'get_as_timestamp' => 'true',
				),
				array (
					'key' => 'field_537639879a4a9',
					'label' => 'End Date / Time',
					'name' => 'pv_event_presentation_end_time',
					'type' => 'date_time_picker',
					'show_date' => 'true',
					'date_format' => 'm/d/y',
					'time_format' => 'H:mm z',
					'show_week_number' => 'false',
					'picker' => 'slider',
					'save_as_timestamp' => 'true',
					'get_as_timestamp' => 'true',
				),
				array (
					'key' => 'field_537e4c5e6c26e',
					'label' => 'Q&A Form',
					'name' => 'pv_event_presentation_qa_form',
					'type' => 'gravity_forms_field',
					'allow_null' => 1,
					'multiple' => 0,
				),
			),
			'location' => array (
				array (
					array (
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'presentations',
						'order_no' => 0,
						'group_no' => 0,
					),
				),
			),
			'options' => array (
				'position' => 'acf_after_title',
				'layout' => 'default',
				'hide_on_screen' => array (
				),
			),
			'menu_order' => 0,
		));

		register_field_group(array (
			'id' => 'pv_event_speakers-representatives-info',
			'title' => 'Speakers / Representatives Info',
			'fields' => array (
				array (
					'key' => 'field_5376418699d41',
					'label' => 'Speakers / Representatives Info',
					'name' => 'pv_event_speakers',
					'type' => 'repeater',
					'sub_fields' => array (
						array (
							'key' => 'field_5376437f99d43',
							'label' => 'Name',
							'name' => 'name',
							'type' => 'text',
							'required' => 1,
							'column_width' => '',
							'default_value' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
							'formatting' => 'html',
							'maxlength' => '',
						),
						array (
							'key' => 'field_5376448499d49',
							'label' => 'Photo',
							'name' => 'photo',
							'type' => 'image',
							'column_width' => '',
							'save_format' => 'object',
							'preview_size' => 'medium',
							'library' => 'all',
						),
						array (
							'key' => 'field_5376439499d44',
							'label' => 'Title',
							'name' => 'title',
							'type' => 'text',
							'column_width' => '',
							'default_value' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
							'formatting' => 'html',
							'maxlength' => '',
						),
						array (
							'key' => 'field_537643aa99d45',
							'label' => 'Tagline',
							'name' => 'tagline',
							'type' => 'text',
							'instructions' => 'Use this for information such as ',
							'column_width' => '',
							'default_value' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
							'formatting' => 'html',
							'maxlength' => '',
						),
						array (
							'key' => 'field_5376443599d46',
							'label' => 'Twitter',
							'name' => 'twitter',
							'type' => 'text',
							'column_width' => '',
							'default_value' => '',
							'placeholder' => '',
							'prepend' => '@',
							'append' => '',
							'formatting' => 'html',
							'maxlength' => '',
						),
						array (
							'key' => 'field_5376445199d47',
							'label' => 'Email',
							'name' => 'email',
							'type' => 'email',
							'column_width' => '',
							'default_value' => '',
							'placeholder' => '',
							'prepend' => '',
							'append' => '',
						),
						array (
							'key' => 'field_5376447399d48',
							'label' => 'Bio',
							'name' => 'bio',
							'type' => 'wysiwyg',
							'column_width' => '',
							'default_value' => '',
							'toolbar' => 'full',
							'media_upload' => 'yes',
						),
					),
					'row_min' => '',
					'row_limit' => '',
					'layout' => 'row',
					'button_label' => 'Add Speaker',
				),
			),
			'location' => array (
				array (
					array (
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'presentations',
						'order_no' => 0,
						'group_no' => 0,
					),
				),
				array (
					array (
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'topics',
						'order_no' => 0,
						'group_no' => 1,
					),
				),
			),
			'options' => array (
				'position' => 'normal',
				'layout' => 'default',
				'hide_on_screen' => array (
				),
			),
			'menu_order' => 0,
		));

		register_field_group(array (
			'id' => 'pv_event_topic-area-info',
			'title' => 'Topic Area Info',
			'fields' => array (
				array (
					'key' => 'field_53765d7d199bb',
					'label' => 'General',
					'name' => '',
					'type' => 'tab',
				),
				array (
					'key' => 'field_53765c29199b4',
					'label' => 'Video Playlist Code',
					'name' => 'pv_event_topic_playlist',
					'type' => 'textarea',
					'default_value' => '',
					'placeholder' => '',
					'maxlength' => '',
					'rows' => '',
					'formatting' => 'html',
				),
				array (
					'key' => 'field_53765c4a199b5',
					'label' => 'Chat Shortcode',
					'name' => 'pv_event_topic_chat',
					'type' => 'text',
					'default_value' => '[chat]',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'formatting' => 'none',
					'maxlength' => '',
				),
				array (
					'key' => 'field_53765d5a199ba',
					'label' => 'Sponsor Info',
					'name' => '',
					'type' => 'tab',
				),
				array (
					'key' => 'field_53765c70199b6',
					'label' => 'Sponsor Name',
					'name' => 'pv_event_topic_sponsor_name',
					'type' => 'text',
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'formatting' => 'html',
					'maxlength' => '',
				),
				array (
					'key' => 'field_53765c96199b7',
					'label' => 'Sponsor Logo',
					'name' => 'pv_event_topic_sponsor_logo',
					'type' => 'image',
					'save_format' => 'object',
					'preview_size' => 'thumbnail',
					'library' => 'all',
				),
				array (
					'key' => 'field_53765d0e199b8',
					'label' => 'Sponsor Website',
					'name' => 'pv_event_topic_sponsor_url',
					'type' => 'text',
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'formatting' => 'none',
					'maxlength' => '',
				),
				array (
					'key' => 'field_53765d26199b9',
					'label' => 'Sponsor Description',
					'name' => 'pv_event_topic_sponsor_desc',
					'type' => 'wysiwyg',
					'default_value' => '',
					'toolbar' => 'full',
					'media_upload' => 'yes',
				),
			),
			'location' => array (
				array (
					array (
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'topics',
						'order_no' => 0,
						'group_no' => 0,
					),
				),
			),
			'options' => array (
				'position' => 'acf_after_title',
				'layout' => 'default',
				'hide_on_screen' => array (
				),
			),
			'menu_order' => 0,
		));
		register_field_group(array (
			'id' => 'pv_event_resources',
			'title' => 'Associated Resources',
			'fields' => array (
				array (
					'key' => 'field_537639d19a4aa',
					'label' => 'Resources',
					'name' => 'pv_event_presentation_resources',
					'type' => 'relationship',
					'return_format' => 'object',
					'post_type' => array (
						0 => 'library',
					),
					'taxonomy' => array (
						0 => 'all',
					),
					'filters' => array (
						0 => 'search',
					),
					'result_elements' => array (
						0 => 'post_title',
					),
					'max' => '',
				),
			),
			'location' => array (
				array (
					array (
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'presentations',
						'order_no' => 0,
						'group_no' => 0,
					),
				),
				array (
					array (
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'topics',
						'order_no' => 0,
						'group_no' => 1,
					),
				),
			),
			'options' => array (
				'position' => 'normal',
				'layout' => 'default',
				'hide_on_screen' => array (
				),
			),
			'menu_order' => 0,
		) );
		register_field_group(array (
			'id' => 'pv_event_video-controls-settings',
			'title' => 'Video Controls Settings',
			'fields' => array (
				array (
					'key' => 'field_537a71d67ca68',
					'label' => 'Height',
					'name' => 'pv_event_vid_controls_height',
					'type' => 'number',
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'min' => 0,
					'max' => '',
					'step' => 1,
				),
				array (
					'key' => 'field_537a71f87ca69',
					'label' => 'Width',
					'name' => 'pv_event_vid_controls_width',
					'type' => 'number',
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'min' => 0,
					'max' => '',
					'step' => 1,
				),
			),
			'location' => array (
				array (
					array (
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'topics',
						'order_no' => 0,
						'group_no' => 0,
					),
				),
				array (
					array (
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'presentations',
						'order_no' => 0,
						'group_no' => 1,
					),
				),
			),
			'options' => array (
				'position' => 'side',
				'layout' => 'default',
				'hide_on_screen' => array (
				),
			),
			'menu_order' => 0,
		));

		register_field_group(array (
			'id' => 'pv_event_resource-library-archive-settings',
			'title' => 'Resource Library Archive Settings',
			'fields' => array (
				array (
					'key' => 'field_5380e29023d17',
					'label' => 'Page Title',
					'name' => 'pv_event_resources_archive_title',
					'type' => 'text',
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'formatting' => 'html',
					'maxlength' => '',
				),
				array (
					'key' => 'field_5380e2a723d18',
					'label' => 'Intro Content',
					'name' => 'pv_event_resources_archive_intro',
					'type' => 'wysiwyg',
					'default_value' => '',
					'toolbar' => 'full',
					'media_upload' => 'yes',
				),
			),
			'location' => array (
				array (
					array (
						'param' => 'options_page',
						'operator' => '==',
						'value' => 'pv-event-resources-options',
						'order_no' => 0,
						'group_no' => 0,
					),
				),
			),
			'options' => array (
				'position' => 'normal',
				'layout' => 'no_box',
				'hide_on_screen' => array (
				),
			),
			'menu_order' => 0,
		));

		register_field_group(array (
			'id' => 'pv_event_topic-area-archive-settings',
			'title' => 'Topic Area Archive Settings',
			'fields' => array (
				array (
					'key' => 'field_53812abb031cc',
					'label' => 'Page Title',
					'name' => 'pv_event_topics_archive_title',
					'type' => 'text',
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'formatting' => 'html',
					'maxlength' => '',
				),
				array (
					'key' => 'field_53812ab7d48a8',
					'label' => 'Intro Content',
					'name' => 'pv_event_topics_archive_intro',
					'type' => 'wysiwyg',
					'default_value' => '',
					'toolbar' => 'full',
					'media_upload' => 'yes',
				),
			),
			'location' => array (
				array (
					array (
						'param' => 'options_page',
						'operator' => '==',
						'value' => 'pv-event-topics-options',
						'order_no' => 0,
						'group_no' => 0,
					),
				),
			),
			'options' => array (
				'position' => 'normal',
				'layout' => 'no_box',
				'hide_on_screen' => array (
				),
			),
			'menu_order' => 0,
		));
	}
	private function options_pages() {
		if ( ! function_exists('acf_add_options_sub_page') )
			return;

		acf_add_options_sub_page(array(
	        'title' => _x('Resource Library Options', 'Option page title', 'pv-events'),
	        'menu' => _x('Options', 'Option page title', 'pv-events'),
	        'parent' => 'edit.php?post_type=library',
	        'slug' => 'pv-event-resources-options',
	        'capability' => 'edit_theme_options'
	    ));

		acf_add_options_sub_page(array(
	        'title' => _x('Topics Options', 'Option page title', 'pv-events'),
	        'menu' => _x('Options', 'Option page title', 'pv-events'),
	        'parent' => 'edit.php?post_type=topics',
	        'slug' => 'pv-event-topics-options',
	        'capability' => 'edit_theme_options'
	    ));
	}
}
