<?php

/**
 * Handles admin maps interface.
 */
class AgmAdminMaps {

	/**
	 * Entry method.
	 *
	 * Creates and handles the Admin interface for the Plugin.
	 *
	 * @access public
	 * @static
	 */
	static function serve () {
		$me = new AgmAdminMaps();
		$me->add_hooks();
		$me->model = new AgmMapModel();
	}

	/**
	 * Registers settings.
	 */
	function register_settings () {
		register_setting('agm_google_maps', 'agm_google_maps');
		$form = new AgmAdminFormRenderer;
		add_settings_section('agm_google_maps', 'Options', create_function('', ''), 'agm_google_maps_options_page');
		add_settings_field('agm_google_maps_default_height', 'Default map height', array($form, create_height_box), 'agm_google_maps_options_page', 'agm_google_maps');
		add_settings_field('agm_google_maps_default_width', 'Default map width', array($form, create_width_box), 'agm_google_maps_options_page', 'agm_google_maps');
		add_settings_field('agm_google_maps_default_map_type', 'Default map type', array($form, create_map_type_box), 'agm_google_maps_options_page', 'agm_google_maps');
		add_settings_field('agm_google_maps_default_image_size', 'Default image size', array($form, create_image_size_box), 'agm_google_maps_options_page', 'agm_google_maps');
		add_settings_field('agm_google_maps_default_image_limit', 'Default image limit', array($form, create_image_limit_box), 'agm_google_maps_options_page', 'agm_google_maps');
	}

	/**
	 * Creates Admin menu entry.
	 */
	function create_admin_menu_entry () {
		add_options_page('WPMU DEV Maps Options', 'WPMU DEV Maps', 'manage_options', 'agm_google_maps', array($this, 'create_admin_page'));
	}

	/**
	 * Creates Admin menu page.
	 */
	function create_admin_page () {
		include(AGM_PLUGIN_BASE_DIR . '/lib/forms/plugin_settings.php');
	}

	/**
	 * Introduces plugins_url() as root variable (global).
	 */
	function js_plugin_url () {
		printf(
			'<script type="text/javascript">var _agm_root_url="%s";</script>',
			AGM_PLUGIN_URL
		);
	}

	/**
	 * Adds an editor button to WordPress editor and handle Editor interface.
	 */
	function js_editor_button () {
		wp_enqueue_script('thickbox');
		wp_enqueue_script('agm_editor', AGM_PLUGIN_URL . '/js/editor.js', array('jquery'));
		wp_localize_script('agm_editor', 'l10nEditor', array(
			'loading' => __('Loading maps... please wait', 'agm_google_maps'),
			'use_this_map' => __('Insert this map', 'agm_google_maps'),
			'preview_or_edit' => __('Preview/Edit', 'agm_google_maps'),
			'delete_map' => __('Delete', 'agm_google_maps'),
			'add_map' => __('Add Map', 'agm_google_maps'),
			'existing_map' => __('Existing map', 'agm_google_maps'),
			'no_existing_maps' => __('No existing maps', 'agm_google_maps'),
			'new_map' => __('Create new map', 'agm_google_maps'),
			'new_map_intro' => __('Create a new map which can be inserted into this post or page. Once you are done you can manage all maps below', 'agm_google_maps'),
			'please_upgrade' => __('You have reached the map number limit for our Lite version. If you need more maps, please consider upgrade.', 'agm_google_maps'),
		));
	}

	function js_widget_editor () {
		wp_enqueue_script('thickbox');
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_script('agm_editor', AGM_PLUGIN_URL . '/js/widget_editor.js', array('jquery'));
		wp_localize_script('agm_editor', 'l10nEditor', array(
			'add_map' => __('Add Map', 'agm_google_maps'),
			'new_map' => __('Create new map', 'agm_google_maps'),
			'please_upgrade' => __('You have reached the map number limit for our Lite version. If you need more maps, please consider upgrade.', 'agm_google_maps'),
		));
	}

	/**
	 * Include Google Maps dependencies.
	 */
	function js_google_maps_api () {
		wp_enqueue_script('google_maps_api', AGM_PLUGIN_URL . '/js/google_maps_loader.js');

		wp_enqueue_script('google_maps_admin', AGM_PLUGIN_URL . '/js/google_maps_admin.js', array('jquery'));
		wp_localize_script('google_maps_admin', 'l10nStrings', array(
			'geocoding_error' => __('There was an error geocoding your location. Check the address and try again', 'agm_google_maps'),
			'type_in_location' => __('Please, type in the location', 'agm_google_maps'),
			'add' => __('Add', 'agm_google_maps'),
			'title' => __('Title', 'agm_google_maps'),
			'body' => __('Body', 'agm_google_maps'),
			'delete_item' => __('Delete', 'agm_google_maps'),
			'save' => __('Save changes to this map', 'agm_google_maps'),
			'insert' => __('Insert this map', 'agm_google_maps'),
			'map_not_saved' => __('Map not saved', 'agm_google_maps'),
			'map_name_missing' => __('Please, give this map a name', 'agm_google_maps'),
			'please_save_map' => __('Please, save the map first', 'agm_google_maps'),
			'go_back' => __('Go back', 'agm_google_maps'),
			'map_title' => __('Give this map a name:', 'agm_google_maps'),
			'options' => __('Map options', 'agm_google_maps'),
			'drop_marker' => __('Drop Marker', 'agm_google_maps'),
			'map_associate' => __('Associate map with this post', 'agm_google_maps'),
			'already_associated_width' => __('This post is already associated with these', 'agm_google_maps'),
			'association_help' => __('Associating a map with a post allows for using this map in advanced ways - to show it dynamically in the sidebar widget, or in an advanced mashup', 'agm_google_maps'),
			'map_size' => __('Map size', 'agm_google_maps'),
			'use_default_size' => __('Use default size', 'agm_google_maps'),
			'map_appearance' => __('Map Appearance', 'agm_google_maps'),
			'show_map' => __('Show map', 'agm_google_maps'),
			'show_markers' => __('Show marker list', 'agm_google_maps'),
			'images_strip' => __('Images strip settings', 'agm_google_maps'),
			'show_images' => __('Show images strip', 'agm_google_maps'),
			'image_size' => __('Use image size', 'agm_google_maps'),
			'image_limit' => __('Show this many images', 'agm_google_maps'),
			'add_location' => __('Enter the location(s) you want to display:', 'agm_google_maps'),
			'add_location_info' => __('You can display as many as you want', 'agm_google_maps'),
		));
	}

	/**
	 * Includes required styles.
	 */
	function css_load_styles () {
		wp_enqueue_style('thickbox');
		wp_enqueue_style('agm_google_maps_admin_style', AGM_PLUGIN_URL . '/css/google_maps_admin.css');
		wp_enqueue_style('agm_google_maps_jquery_ui_dialog_style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/base/jquery-ui.css');
	}

	/**
	 * Handles map listing requests.
	 */
	function json_list_maps () {
		$maps = $this->model->get_maps();
		header('Content-type: application/json');
		echo json_encode($maps);
		exit();
	}

	/**
	 * Handles loading a particular map requests.
	 */
	function json_load_map () {
		$id = (int) $_POST['id'];
		$map = $this->model->get_map($id);
		header('Content-type: application/json');
		echo json_encode($map);
		exit();
	}

	/**
	 * Handles maps creation requests.
	 * Loads defaults and such.
	 */
	function json_new_map () {
		$count = count($this->model->get_maps());
		header('Content-type: application/json');
		if ($count < 4) {
			$defaults = $this->model->get_map_defaults();
			echo json_encode(array(
				"status" => "ok",
				"_count" => $count,
				"defaults" => $defaults,
			));
		} else {
			echo json_encode(array(
				"status" => "upgrade",
			));
		}
		exit();
	}

	/**
	 * Handles map save requests.
	 */
	function json_save_map () {
		$id = $this->model->save_map($_POST);
		header('Content-type: application/json');
		echo json_encode(array(
			'status' => $id ? 1 : 0,
			'id' => $id,
		));
		exit();
	}

	/**
	 * Handles map delete requests.
	 */
	function json_delete_map () {
		$id = $this->model->delete_map($_POST);
		header('Content-type: application/json');
		echo json_encode(array(
			'status' => $id ? 1 : 0,
			'id' => $id,
		));
		exit();
	}

	/**
	 * Handles icons list requests.
	 */
	function json_list_icons () {
		$icons = glob(AGM_PLUGIN_BASE_DIR . '/img/*.png');
		foreach ($icons as $k=>$v) {
			$icons[$k] = basename($v);
		}
		header('Content-type: application/json');
		echo json_encode($icons);
		exit();
	}

	/**
	 * Loads associated post titles.
	 */
	function json_get_post_titles () {
		$titles = $this->model->get_post_titles($_POST['post_ids']);
		header('Content-type: application/json');
		echo json_encode(array(
			'posts' => $titles,
		));
		exit();
	}

	/**
	 * Adds needed hooks.
	 *
	 * @access private
	 */
	function add_hooks () {
		// Step0: Register options and menu
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_menu', array($this, 'create_admin_menu_entry'));

		// Step1a: Add plugin script core requirements and editor interface
		add_action('admin_print_scripts-post.php', array($this, 'js_plugin_url'));
		add_action('admin_print_scripts-post-new.php', array($this, 'js_plugin_url'));
		add_action('admin_print_scripts-widgets.php', array($this, 'js_plugin_url'));

		add_action('admin_print_scripts-post.php', array($this, 'js_editor_button'));
		add_action('admin_print_scripts-post-new.php', array($this, 'js_editor_button'));
		add_action('admin_print_scripts-widgets.php', array($this, 'js_widget_editor'));

		add_action('admin_print_styles-post.php', array($this, 'css_load_styles'));
		add_action('admin_print_styles-post-new.php', array($this, 'css_load_styles'));
		add_action('admin_print_styles-widgets.php', array($this, 'css_load_styles'));


		// Step1b: Add Google Maps dependencies
		add_action('admin_print_scripts-post.php', array($this, 'js_google_maps_api'));
		add_action('admin_print_scripts-post-new.php', array($this, 'js_google_maps_api'));
		add_action('admin_print_scripts-widgets.php', array($this, 'js_google_maps_api'));

		// Step2: Add AJAX request handlers
		add_action('wp_ajax_agm_list_maps', array($this, 'json_list_maps'));
		add_action('wp_ajax_agm_load_map', array($this, 'json_load_map'));
		add_action('wp_ajax_agm_new_map', array($this, 'json_new_map'));
		add_action('wp_ajax_agm_save_map', array($this, 'json_save_map'));
		add_action('wp_ajax_agm_delete_map', array($this, 'json_delete_map'));
		add_action('wp_ajax_agm_list_icons', array($this, 'json_list_icons'));
		add_action('wp_ajax_agm_get_post_titles', array($this, 'json_get_post_titles'));
	}
}