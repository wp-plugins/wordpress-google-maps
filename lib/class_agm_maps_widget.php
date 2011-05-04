<?php

/**
 * Sidebar widget for Google Maps Plugin.
 */
class AgmMapsWidget extends WP_Widget {
	function AgmMapsWidget() {
		parent::WP_Widget(false, $name = 'Google Maps Widget');
		$this->model = new AgmMapModel();
	}

	function form($instance) {
		$title = esc_attr($instance['title']);
		$height = esc_attr($instance['height']);
		$width = esc_attr($instance['width']);
		$query = esc_attr($instance['query']);
		$query_custom = esc_attr($instance['query_custom']);
		$map_id = esc_attr($instance['map_id']);
		$show_map = esc_attr($instance['show_map']);
		$show_markers = esc_attr($instance['show_markers']);
		$show_images = esc_attr($instance['show_images']);

		// Set defaults
		$height = $height ? $height : 200;
		$width = $width ? $width : 200;
		$query_custom = ('custom' == $query) ? $query_custom : '';
		$show_map = (isset($instance['show_map'])) ? $show_map : 1;
		$show_markers = (isset($instance['show_markers'])) ? $show_markers : 1;
		$show_images = $show_images ? $show_images : 0;

		// Load map titles/ids
		$maps = $this->model->get_maps();

		include(AGM_PLUGIN_BASE_DIR . '/lib/forms/widget_settings.php');
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['height'] = strip_tags($new_instance['height']);
		$instance['width'] = strip_tags($new_instance['width']);
		$instance['query'] = strip_tags($new_instance['query']);
		$instance['query_custom'] = strip_tags($new_instance['query_custom']);
		$instance['map_id'] = strip_tags($new_instance['map_id']);
		$instance['show_map'] = (int)$new_instance['show_map'];
		$instance['show_markers'] = (int)$new_instance['show_markers'];
		$instance['show_images'] = (int)$new_instance['show_images'];
		return $instance;
	}

	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$height = (int)$instance['height'];
		$height = $height ? $height : 200; // Apply default
		$width = (int)$instance['width'];
		$width = $width ? $width : 200; // Apply default
		$query = $instance['query'];
		$query_custom = $instance['query_custom'];
		$map_id = $instance['map_id'];
		$show_map = $instance['show_map'];
		$show_markers = $instance['show_markers'];
		$show_images = $instance['show_images'];

		$maps = $this->get_maps($query, $query_custom, $map_id);

		echo $before_widget;
		if ($title) echo $before_title . $title . $after_title;
		if (is_array($maps)) foreach ($maps as $map) {
			$selector = 'agm_widget_map_' . md5(microtime() . rand());
			$map['height'] = $height;
			$map['width'] = $width;
			$map['show_map'] = $show_map;
			$map['show_markers'] = $show_markers;
			$map['show_images'] = $show_images;
			echo '<div id="' . $selector . '"></div>';
			echo '<script type="text/javascript">_agmMaps[_agmMaps.length] = {selector: "#' . $selector . '", data: ' . json_encode($map) . '};</script>';
		}

		echo $after_widget;
	}

	function get_maps ($query, $custom, $map_id) {
		switch ($query) {
			case 'current':
				return $this->model->get_current_maps();
			case 'all_posts':
				return $this->model->get_all_posts_maps();
			case 'random':
				return $this->model->get_random_map();
			case 'custom':
				return $this->model->get_custom_maps($custom);
			case 'id':
				return array($this->model->get_map($map_id));
			default:
				return false;
		}
	}

}