<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	die('Un-authorized access!');
}

//Get all contact form list here
function lse_get_the_form_list($fid = '')
{

	global $wpdb;

	$result = $wpdb->get_results(
    "SELECT page AS post_title, MIN(id) AS ID
    FROM {$wpdb->prefix}ecf
    GROUP BY page",
    ARRAY_A
  );

	if (sizeof($result)) {
		// New function Added to sort the array by CF7 Name
		usort($result, "cmp_sort_post_title");
	}

	return $result;
}//Close function

/*
 * Sorting the contact forms to asc order by CF7 name
 */
function cmp_sort_post_title($a, $b)
{
	//return $a->name > $b->name;
	return strcmp($a['post_title'], $b['post_title']);

}

/*
 * $data: rows from database
 */
function lse_sortdata($data)
{
	$data_sorted = array();
	//Set submitted id wise form information
	foreach ($data as $k => $v) {
		if (!isset($data_sorted[$v->id])) {
			$data_sorted[$v->id] = array();
		}
		//defined property: stdClass::$name i

		$field_data = maybe_unserialize(base64_decode($v->complete));
		foreach ($field_data as $k2 => $v2) {
			$data_sorted[$v->id][$k2] = trim(wp_unslash($field_data[$k2]));
		}
	}

	return $data_sorted;
}

function lse_get_the_title($fid) {
	global $wpdb;
	$fid = (int) $fid;
	$page_title = $wpdb->get_var($wpdb->prepare("SELECT `page` FROM {$wpdb->prefix}ecf WHERE id = %d", $fid));
	return $page_title;
}

// Pull fields out of form settings
function lse_get_db_fields($fid, $filter = true)
{

	global $wpdb;
	$fid = (int) $fid;
	$page_title = lse_get_the_title($fid);
	$sql = $wpdb->get_results($wpdb->prepare("SELECT `complete`, `contact_time` FROM {$wpdb->prefix}ecf WHERE page = %s", $page_title));
	$data = $sql;
	//Set each field value in array
	$fields = array();
	if (!empty($data)) {
		foreach ($data as $k => $v) {
			$field_data = maybe_unserialize(base64_decode($v->complete));
			foreach ($field_data as $k2 => $v2) {
				$fields[$k2] = htmlspecialchars_decode($k2);
			}
		}
	}

	return $fields;
}//Close function