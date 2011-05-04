<?php

/**
 * Mediates all database interactions.
 *
 * This is where all the map data is saved and loaded.
 */
class AgmMapModel {

	/**
	 * Name of the table where map data is located.
	 * No table prefix.
	 *
	 * @access private
	 */
	var $_table_name = 'agm_maps';

	/**
	 * PHP4 compatibility constructor.
	 */
	function AgmMapModel () {
		$this->__construct();
	}

	function __construct () {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Returns the name of maps database table.
	 *
	 * @access public
	 * @return string Name of the table with prefix.
	 */
	function get_table_name () {
		return $this->wpdb->prefix . $this->_table_name;
	}

	/**
	 * Fetches maps associated with current WP posts.
	 *
	 * @return mixed Maps array on success, false on failure
	 */
	function get_current_maps () {
		global $wp_query;
		$table = $this->get_table_name();
		$posts = $wp_query->get_posts();
		$where_string = $this->prepare_query_string($posts);
		if (!$where_string) return false;
		$maps = $this->wpdb->get_results("SELECT * FROM {$table} {$where_string}", ARRAY_A);
		if (is_array($maps)) foreach ($maps as $k=>$v) {
			$maps[$k] = $this->prepare_map($v);
		}
		return $maps;
	}

	/**
	 * Fetches all maps associated with any posts.
	 *
	 * @return mixed Maps array on success, false on failure
	 */
	function get_all_posts_maps () {
		$table = $this->get_table_name();
		$maps = $this->wpdb->get_results("SELECT * FROM {$table} WHERE post_ids <> 'a:0:{}'", ARRAY_A);
		if (is_array($maps)) foreach ($maps as $k=>$v) {
			$maps[$k] = $this->prepare_map($v);
		}
		return $maps;
	}

	/**
	 * Fetches a random map.
	 *
	 * @return mixed Map array on success, false on failure
	 */
	function get_random_map () {
		$table = $this->get_table_name();
		$map = $this->wpdb->get_row("SELECT * FROM {$table} ORDER BY RAND() LIMIT 1", ARRAY_A);
		return $map ? array($this->prepare_map($map)) : false;
	}

	/**
	 * Fetches maps associated with posts found with custom WP posts query.
	 *
	 * @param string Custom WP posts query
	 * @return mixed Maps array on success, false on failure
	 */
	function get_custom_maps ($query) {
		$table = $this->get_table_name();
		$wpq = new Wp_Query($query);
		$posts = $wpq->get_posts();
		$where_string = $this->prepare_query_string($posts);
		if (!$where_string) return false;
		$maps = $this->wpdb->get_results("SELECT * FROM {$table} {$where_string}", ARRAY_A);
		if (is_array($maps)) foreach ($maps as $k=>$v) {
			$maps[$k] = $this->prepare_map($v);
		}
		return $maps;
	}

	/**
	 * Fetches a list of post titles.
	 *
	 * @param array List of post IDs to fetch titles for
	 * @return mixed Post titles/IDs array on success, false on failure
	 */
	function get_post_titles ($ids) {
		if (!is_array($ids)) return false;
		$table = $this->wpdb->prefix . 'posts';
		foreach ($ids as $k=>$v) $ids[$k] = (int)$v;
		$ids_string = join(', ', $ids);
		return $this->wpdb->get_results("SELECT id, post_title FROM {$table} WHERE ID IN ({$ids_string})", ARRAY_A);
	}

	/**
	 * Fetches a list of existing maps ids/titles.
	 *
	 * @return mixed Map id/title array on success, false on failure
	 */
	function get_maps () {
		$table = $this->get_table_name();
		return $this->wpdb->get_results("SELECT id, title FROM {$table}", ARRAY_A);
	}

	/**
	 * Gets a particular map.
	 *
	 * @param int Map id
	 * @return mixed Map array on success, false on failure
	 */
	function get_map ($id) {
		$id = (int)$id;
		$table = $this->get_table_name();
		$map = $this->wpdb->get_row("SELECT * FROM {$table} WHERE id={$id}", ARRAY_A);

		return $this->prepare_map($map);
	}

	/**
	 * Returns a list of map default options.
	 *
	 * @return mixed Maps defaults array
	 */
	function get_map_defaults () {
		$defaults = get_option('agm_google_maps');
		if (!isset($defaults['image_limit'])) $defaults['image_limit'] = 10;
		return $defaults;
	}

	/**
	 * Saves a map.
	 *
	 * @param array Map data to save.
	 * @return mixed Id on success, false on failure
	 */
	function save_map ($data) {
		$id = (int)$data['id'];
		$table = $this->get_table_name();
		$data = $this->prepare_for_save($data);
		$ret = false;

		if ($id) {
			$result = $this->wpdb->update($table, $data, array('id'=>$id));
			$ret = ($result) ? $id : false;
		} else {
			$result = $this->wpdb->insert($table, $data);
			$ret = ($result) ? $this->wpdb->insert_id : false;
		}
		return $ret;
	}

	/**
	 * Removes a map from the database.
	 *
	 * @param array Array containing 'id' key
	 * @return mixed Deleted id on success, false on failure
	 */
	function delete_map ($data) {
		$id = (int)$data['id'];
		$table = $this->get_table_name();

		$result = $this->wpdb->query("DELETE FROM {$table} WHERE id={$id}");
		return $result ? $id : false;
	}

	/**
	 * Prepares a complex query string.
	 * Used to find maps associated to posts.
	 *
	 * @param array A list of posts
	 * @return mixed Maps array on success, false on failure
	 */
	function prepare_query_string ($posts) {
		$where = array();
		if (!is_array($posts)) return false;
		foreach ($posts as $post) {
			$id = (int)$post->ID;
			$len = strlen($post->ID);
			$where[] = "'%s:{$len}:\"{$id}\";%'";
		}
		return 'WHERE post_ids LIKE ' . join(' OR post_ids LIKE ', $where);
	}

	/**
	 * Prepares map array for serving to front end.
	 *
	 * @param array Map array
	 * @return array Prepared map array
	 */
	function prepare_map ($map) {
		$markers = unserialize(@$map['markers']);
		$options = unserialize(@$map['options']);
		$post_ids =  unserialize(@$map['post_ids']);
		$defaults = get_option('agm_google_maps');

		// Data is force-escaped by WP, so compensate for that
		return array_map('stripslashes_deep', array(
			"markers" => $markers,
			"defaults" => $defaults,
			"post_ids" => $post_ids,
			"id" => @$map['id'],
			"title" => @$map['title'],
			"height" => @$options['height'],
			"width" =>  @$options['width'],
			"zoom" =>  @$options['zoom'],
			"map_type" =>  @$options['map_type'],
			"show_links" =>  @$options['show_links'],
			"show_map" =>  @$options['show_map'],
			"show_markers" =>  @$options['show_markers'],
			"show_images" =>  @$options['show_images'],
			"image_size" =>  @$options['image_size'],
			"image_limit" =>  $options['image_limit'],
		));
	}

	/**
	 * Prepares raw map data for saving to the database.
	 *
	 * @param array Raw map data array
	 * @return array Map array prepared for storage
	 */
	function prepare_for_save ($data) {
		// Normalize marker contents
		if (is_array($data['markers'])) foreach ($data['markers'] as $k=>$v) {
			$data['markers'][$k]['icon'] = basename($v['icon']);
			if (!current_user_can('unfiltered_html')) {
				$data['markers'][$k]['body'] = wp_filter_post_kses($v['body']);
			}
		}
		$post_ids = is_array(@$data['post_ids']) ? array_unique($data['post_ids']) : array();
		// Pack options
		$map_options = array (
			"height" => $data['height'],
			"width" => $data['width'],
			"zoom" => $data['zoom'],
			"map_type" => strtoupper($data['map_type']),
			"show_links" => (int)$data['show_links'],
			"show_map" => (int)$data['show_map'],
			"show_markers" => (int)$data['show_markers'],
			"show_images" => (int)$data['show_images'],
			"image_size" => $data['image_size'],
			"image_limit" => (int)$data['image_limit'],
		);
		// Return prepped data array
		return array (
			"title" => $data['title'],
			"markers" => serialize($data['markers']),
			"post_ids" => serialize($post_ids),
			"options" => serialize($map_options),
		);
	}
}