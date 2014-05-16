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
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @package   PV_Events
 * @author    Steve Crockett <crockett95@gmail.com
 */
class PV_Events_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Call $plugin_slug from public plugin class.
		$plugin = PV_Events::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		/*
		 * Define custom functionality.
		 *
		 * Read more about actions and filters:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( 'init', array( $this, 'action_init' ) );
		add_filter( '@TODO', array( $this, 'filter_method_name' ) );

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
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), PV_Events::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), PV_Events::VERSION );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'PV Events', $this->plugin_slug ),
			__( 'Events', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * NOTE:     Actions are points in the execution of a page or process
	 *           lifecycle that WordPress fires.
	 *
	 *           Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_init() {
		$this->custom_fields();
	}

	/**
	 * NOTE:     Filters are points of execution in which WordPress modifies data
	 *           before saving it or sending it to the browser.
	 *
	 *           Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
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
					'key' => 'pv_event_field_53750a5d69efc',
					'label' => 'Type',
					'name' => 'pv_event_resource_type',
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
					'key' => 'pv_event_field_53750ab669efd',
					'label' => 'Video Code',
					'name' => 'pv_event_resource_video_code',
					'type' => 'textarea',
					'conditional_logic' => array (
						'status' => 1,
						'rules' => array (
							array (
								'field' => 'pv_event_field_53750a5d69efc',
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
					'key' => 'pv_event_field_53750ae569efe',
					'label' => 'File',
					'name' => 'pv_event_resource_file',
					'type' => 'file',
					'conditional_logic' => array (
						'status' => 1,
						'rules' => array (
							array (
								'field' => 'pv_event_field_53750a5d69efc',
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
					'key' => 'pv_event_field_53750b0569eff',
					'label' => 'URL',
					'name' => 'pv_event_resource_url',
					'type' => 'text',
					'conditional_logic' => array (
						'status' => 1,
						'rules' => array (
							array (
								'field' => 'pv_event_field_53750a5d69efc',
								'operator' => '==',
								'value' => 'link',
							),
							array (
								'field' => 'pv_event_field_53750a5d69efc',
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
					'key' => 'pv_event_field_5375145e4ae02',
					'label' => 'Resource Type',
					'name' => 'pv_event_resource_type',
					'type' => 'taxonomy',
					'required' => 1,
					'taxonomy' => 'type',
					'field_type' => 'select',
					'allow_null' => 0,
					'load_save_terms' => 1,
					'return_format' => 'id',
					'multiple' => 0,
				),
				array (
					'key' => 'pv_event_field_537514974ae03',
					'label' => 'Releases',
					'name' => 'pv_event_resource_release',
					'type' => 'taxonomy',
					'required' => 1,
					'taxonomy' => 'release',
					'field_type' => 'select',
					'allow_null' => 0,
					'load_save_terms' => 1,
					'return_format' => 'id',
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
					'key' => 'pv_event_field_537633309a4a6',
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
					'key' => 'pv_event_field_537633709a4a7',
					'label' => 'Abstract',
					'name' => 'pv_event_presentation_abstract',
					'type' => 'wysiwyg',
					'default_value' => '',
					'toolbar' => 'full',
					'media_upload' => 'yes',
				),
				array (
					'key' => 'pv_event_field_537635a69a4a8',
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
					'key' => 'pv_event_field_537639879a4a9',
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
					'key' => 'pv_event_field_5376418699d41',
					'label' => 'Speakers / Representatives Info',
					'name' => 'pv_event_speakers',
					'type' => 'repeater',
					'sub_fields' => array (
						array (
							'key' => 'pv_event_field_5376437f99d43',
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
							'key' => 'pv_event_field_5376448499d49',
							'label' => 'Photo',
							'name' => 'photo',
							'type' => 'image',
							'column_width' => '',
							'save_format' => 'object',
							'preview_size' => 'medium',
							'library' => 'all',
						),
						array (
							'key' => 'pv_event_field_5376439499d44',
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
							'key' => 'pv_event_field_537643aa99d45',
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
							'key' => 'pv_event_field_5376443599d46',
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
							'key' => 'pv_event_field_5376445199d47',
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
							'key' => 'pv_event_field_5376447399d48',
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
					'key' => 'pv_event_field_53765d7d199bb',
					'label' => 'General',
					'name' => '',
					'type' => 'tab',
				),
				array (
					'key' => 'pv_event_field_53765c29199b4',
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
					'key' => 'pv_event_field_53765c4a199b5',
					'label' => 'Chat URL',
					'name' => 'pv_event_topic_chat',
					'type' => 'text',
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'formatting' => 'none',
					'maxlength' => '',
				),
				array (
					'key' => 'pv_event_field_53765d5a199ba',
					'label' => 'Sponsor Info',
					'name' => '',
					'type' => 'tab',
				),
				array (
					'key' => 'pv_event_field_53765c70199b6',
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
					'key' => 'pv_event_field_53765c96199b7',
					'label' => 'Sponsor Logo',
					'name' => 'pv_event_topic_sponsor_logo',
					'type' => 'image',
					'save_format' => 'object',
					'preview_size' => 'thumbnail',
					'library' => 'all',
				),
				array (
					'key' => 'pv_event_field_53765d0e199b8',
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
					'key' => 'pv_event_field_53765d26199b9',
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
					'key' => 'pv_event_field_537639d19a4aa',
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
	}
}
