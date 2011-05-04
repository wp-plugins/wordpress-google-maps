<?php

/**
 * Handles public maps interface.
 */
class AgmUserMaps {

	/**
	 * Entry method.
	 *
	 * Creates and handles the Userland interface for the Plugin.
	 *
	 * @access public
	 * @static
	 */
	static function serve () {
		$me = new AgmUserMaps();
		$me->add_hooks();
		$me->model = new AgmMapModel();
	}

	/**
	 * Include Google Maps dependencies.
	 */
	function js_google_maps_api () {
		wp_enqueue_script('google_maps_api', AGM_PLUGIN_URL . '/js/google_maps_loader.js');

		wp_enqueue_script('agm_google_user_maps', AGM_PLUGIN_URL . '/js/google_maps_user.js', array('jquery'));
		wp_localize_script('agm_google_user_maps', 'l10nStrings', array(
			'close' => __('Close', 'agm_google_maps'),
			'get_directions' => __('Get Directions', 'agm_google_maps'),
			'geocoding_error' => __('There was an error geocoding your location. Check the address and try again', 'agm_google_maps'),
			'missing_waypoint' => __('Please, enter values for both point A and point B', 'agm_google_maps'),
			'directions' => __('Directions', 'agm_google_maps'),
			'oops_no_directions' => __('Oops, we couldn\'t calculate the directions', 'agm_google_maps'),
		));
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
	 * Introduces global list of maps to be initialized.
	 */
	function js_maps_global () {
		echo '<script type="text/javascript">_agmMaps = [];</script>';
	}

	/**
	 * Includes required styles.
	 */
	function css_load_styles () {
		wp_enqueue_style('agm_google_maps_user_style', AGM_PLUGIN_URL . '/css/google_maps_user.css');
	}

	/**
	 * Adds needed hooks.
	 *
	 * @access private
	 */
	function add_hooks () {
		// Step1a: Add root dependencies
		add_action('wp_print_scripts', array($this, 'js_maps_global'));
		add_action('wp_print_scripts', array($this, 'js_plugin_url'));
		add_action('wp_print_styles', array($this, 'css_load_styles'));

		// Step1b: Add Google Maps dependencies
		add_action('wp_print_scripts', array($this, 'js_google_maps_api'));

		// Step3: Process map tags
		$rpl = AgmMarkerReplacer::register();
		//add_filter('the_content', array($rpl, 'process_tags'), 99);
	}
}