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
	}

}
