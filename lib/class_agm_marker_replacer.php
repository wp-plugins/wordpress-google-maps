<?php

/**
 * Handles quicktags replacement in text.
 */
class AgmMarkerReplacer {

	/**
	 * PHP4 compatibility constructor.
	 */
	function AgmMarkerReplacer () {
		$this->__construct();
	}

	function __construct() {
		$this->model = new AgmMapModel();
	}

	/**
	 * Creates a replacer and registers shortcodes.
	 *
	 * @access public
	 * @static
	 */
	static function register () {
		$me = new AgmMarkerReplacer();
		$me->register_shortcodes();
	}

	/**
	 * Registers shortcodes for processing.
	 *
	 * @access private
	 */
	function register_shortcodes () {
		add_shortcode('map', array($this, 'process_tags'));
	}

	/**
	 * Creates markup to insert a single map.
	 *
	 * @access private
	 */
	function create_tag ($map) {
		if (!$map['id']) return '';
		$elid = 'map-' . md5(microtime() . rand());
		$rpl = '<div id="' . $elid . '"></div>';
		$rpl .= '<script type="text/javascript">_agmMaps[_agmMaps.length] = {selector: "#' . $elid . '", data: ' . json_encode($map) . '};</script>';
		return $rpl;
	}

	/**
	 * Creates markup to insert multiple maps.
	 *
	 * @access private
	 */
	function create_tags ($maps) {
		if (!is_array($maps)) return '';
		$ret = '';
		foreach ($maps as $map) {
			$ret .= $this->create_tag($map);
		}
		return $ret;
	}

	/**
	 * Inserts a map for tags with ID attribute set.
	 *
	 * @access private
	 */
	function process_map_id_tag ($map_id) {
		return $this->create_tag($this->model->get_map($map_id));
	}

	/**
	 * Inserts a map for tags with query attribute set.
	 *
	 * @access private
	 */
	function process_map_query_tag ($query) {
		if ('random' == $query) return $this->create_tags($this->model->get_random_map());
		return $this->create_tags($this->model->get_custom_maps($query));
	}

	/**
	 * Processes text and replaces recognized tags.
	 *
	 * @access public
	 */
	function process_tags ($atts, $content=null) {
		$body = false;
		$atts = shortcode_atts(array(
			'id' => false,
			'query' => false,
		), $atts);
		if ($atts['id']) $body = $this->process_map_id_tag($atts['id']);
		else if ($atts['query']) $body = $this->process_map_query_tag($atts['query']);
		return $body ? $body : $content;
	}
}