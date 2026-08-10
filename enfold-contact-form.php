<?php
/*
Plugin Name: LaunchSnap Form DB for Enfold
Version: 1.0.1
Description: Save All Entries from Enfold Forms
Author: Christie Wood
Author URI: https://christiemade.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define('LSE_DATA_ENTRY_TABLE_NAME', $wpdb->prefix.'ecf');

require_once(ABSPATH . 'wp-admin/includes/file.php');

function ecf_activated() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table_name      = $wpdb->prefix . 'ecf';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (
		id mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
		page varchar(512) NOT NULL,
		complete longblob,
		contact_time datetime NOT NULL,
		PRIMARY KEY  (id)
	) {$charset_collate};";

	dbDelta( $sql );
}

function ecf_deactivate()
{
	global $wpdb;
	/**
	* @deactivated_plugin
	*/
}
function ecf_uninstall() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall must remove the plugin-owned custom table.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ecf" );
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
 * @since    1.0.1
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

	$contact_time = current_time( 'mysql' );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Form submissions are stored in the plugin-owned custom table.
  $wpdb->insert(
    $wpdb->prefix . 'ecf',
    array(
      'page'         => $page_title,
      'complete'     => $contact_value,
      'contact_time' => $contact_time,
    ),
    array(
      '%s',
      '%s',
      '%s',
    )
  );

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

	$page_title = $contact_value['page_title'];
	$contact_value = base64_encode(maybe_serialize($contact_value));

	$contact_time = current_time( 'mysql' );

  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Form submissions are stored in the plugin-owned custom table.
	$wpdb->insert(
    $wpdb->prefix . 'ecf',
    array(
      'page'         => $page_title,
      'complete'     => $contact_value,
      'contact_time' => $contact_time,
    ),
    array(
      '%s',
      '%s',
      '%s',
    )
  );

  return $form_elements;
}

?>
