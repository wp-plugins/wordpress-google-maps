<?php

/**
 * Handles rendering of form elements for plugin Options page.
 */
class AgmAdminFormRenderer {

	function _create_small_text_box ($name, $value) {
		return "<input type='text' disabled='disabled' name='agm_google_maps[{$name}]' id='{$name}' size='3' value='{$value}' />";
	}

	function create_height_box () {
		$opt = get_option('agm_google_maps');
		echo $this->_create_small_text_box('height', @$opt['height']) . 'px';
	}

	function create_width_box () {
		$opt = get_option('agm_google_maps');
		echo $this->_create_small_text_box('width', @$opt['width']) . 'px';
	}

	function create_image_limit_box () {
		$opt = get_option('agm_google_maps');
		$limit = (isset($opt['image_limit'])) ? $opt['image_limit'] : 10;
		echo $this->_create_small_text_box('image_limit', $limit);
	}

	function  create_map_type_box () {
		$opt = get_option('agm_google_maps');
		$items = array(
			'ROADMAP',
			'SATELLITE',
			'HYBRID',
			'TERRAIN'
		);
		echo "<select disabled='disabled' id='map_type' name='agm_google_maps[map_type]'>";
		foreach($items as $item) {
			$selected = ($opt['map_type'] == $item) ? 'selected="selected"' : '';
			echo "<option value='$item' $selected>$item</option>";
		}
		echo "</select>";
	}

	function  create_image_size_box () {
		$opt = get_option('agm_google_maps');
		$items = array(
			'small',
			'medium',
			'thumbnail',
			'square',
			'mini_square',
		);
		echo "<select disabled='disabled' id='image_size' name='agm_google_maps[image_size]'>";
		foreach($items as $item) {
			$selected = ($opt['image_size'] == $item) ? 'selected="selected"' : '';
			echo "<option value='$item' $selected>$item</option>";
		}
		echo "</select>";
	}
}
