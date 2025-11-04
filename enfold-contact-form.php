<?php
/*
Plugin Name: Launchsnap DB for Enfold
Version: 2.0.2
Description: Save All Entries from Enfold Forms
Author: Christie Wood
Author URI: https://launchsnap.com
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if(!defined('LSE_TEXT_DOMAIN')){
	define('LSE_TEXT_DOMAIN','launchsnap-db-for-enfold');
}

define('LSE_DATA_ENTRY_TABLE_NAME', $wpdb->prefix.'ecf');

require_once(ABSPATH . 'wp-admin/includes/file.php');

function ecf_activated() {

   	global $wpdb;
  	 $wpdb->prefix . 'ecf';

		if($wpdb->get_var("show tables like '{$wpdb->prefix}ecf'") != $wpdb->prefix . 'ecf')
		{
			$wpdb->get_results("CREATE TABLE {$wpdb->prefix}ecf ( id mediumint(9) NOT NULL PRIMARY KEY AUTO_INCREMENT, page varchar(512) NOT NULL, complete BLOB, contact_time TIMESTAMP) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		}

}
function ecf_deactivate()
{
	global $wpdb;
	/**
	* @deactivated_plugin
	*/
}
function ecf_uninstall()
{
	global $wpdb;
	$wpdb->get_results("DROP TABLE {$wpdb->prefix}ecf");
}
register_activation_hook(	__FILE__,	'ecf_activated'  );
register_deactivation_hook(	__FILE__,	'ecf_deactivate' );
register_uninstall_hook(	__FILE__,	'ecf_uninstall'  );

include 'admin/EnfoldListDb.php';
$EnfoldListDb = new ECF_ListDb();

include 'admin/ecf_index.php';

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-launchsnap-db-for-enfold.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_launchsnap_db() {

	$plugin = new Launchsnap_Db();
	$plugin->run();

}
run_launchsnap_db();

add_theme_support('avia_template_builder_custom_css');

add_filter( 'wpcf7_posted_data', 'ecf_cf7_saveFormData' );
add_filter('avf_form_send', 'ecf_saveFormData', 10, 4);

function ecf_saveFormData($data, $new_post, $form_params, $avia_form)
{
	global $wpdb;

	//info@bocillaislandsconservancy.org
	$form_elements = $avia_form->form_elements;
	$parameters = array_values($new_post);
	foreach ($form_elements as $name => $element)
	{
		if($element['type'] == 'decoy' || $element['type'] == 'captcha' || $name == 'av_privacy_agreement')
		{
			unset($form_elements[$name]);
		}
	}
	$contact_value = [];
	$i = 0;
	foreach ($form_elements as $element)
	{
		$contact_value[$element['label']] = urldecode($parameters[$i]);
		$i++;
	}
	$page_title = get_the_title(url_to_postid($form_params['action']));
	if(isset($form_elements['page_title'])) {
		$page_title = ucwords($form_elements['page_title']['value']);
	}
	$contact_value = base64_encode(maybe_serialize($contact_value));

	$contact_time = date('Y-m-d H:i:s e');
	$wpdb->get_results("INSERT INTO {$wpdb->prefix}ecf SET page='{$page_title}', complete='{$contact_value}', contact_time ='{$contact_time}'");

  return true;
}



// Save submissions from CF7
function ecf_cf7_saveFormData($form_elements)
{
	global $wpdb;
	$contact_value = array();
	foreach ($form_elements as $key=>$element)
	{
		if(is_array($element)) $element = json_encode($element);
		$contact_value[$key] = $element;
	}
	error_log(json_encode($contact_value));
	$page_title = $contact_value['page_title'];
	error_log("Element (".gettype($element)."): ".json_encode($element));
	//$page_title = get_the_title(url_to_postid($form_params['action']));
	$contact_value = base64_encode(maybe_serialize($contact_value));

	$contact_time = date('Y-m-d H:i:s e');
	$wpdb->get_results("INSERT INTO {$wpdb->prefix}ecf SET page='{$page_title}', complete='{$contact_value}', contact_time ='{$contact_time}'");

  return $form_elements;
}

?>
