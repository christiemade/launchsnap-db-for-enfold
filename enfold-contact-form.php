<?php
/*
Plugin Name: LaunchSnap Form DB for Enfold
Version: 1.0.1
Description: Save All Entries from Enfold Forms
Requires at least: 5.3
Requires PHP: 8.1
Text Domain: launchsnap-db-for-enfold
Author: Christie Wood
Author URI: https://christiemade.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

function launchsnap_db_activate() {
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

function launchsnap_db_uninstall() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall must remove the plugin-owned custom table.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ecf" );
}

register_activation_hook(	__FILE__,	'launchsnap_db_activate'  );
register_uninstall_hook(	__FILE__,	'launchsnap_db_uninstall'  );

include 'admin/EnfoldListDb.php';
$launchsnap_db_list = new Launchsnap_Db_List();

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
function launchsnap_db_run() {

	$plugin = new Launchsnap_Db();
	$plugin->run();

}
launchsnap_db_run();

add_theme_support('avia_template_builder_custom_css');

add_filter( 'wpcf7_posted_data', 'launchsnap_db_save_cf7_form_data' );
add_filter('avf_form_send', 'launchsnap_db_save_enfold_form_data', 10, 4);

function launchsnap_db_save_enfold_form_data($data, $new_post, $form_params, $avia_form)
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
function launchsnap_db_save_cf7_form_data($form_elements)
{
	global $wpdb;
	$contact_value = array();
	foreach ($form_elements as $key=>$element)
	{
		if(is_array($element)) $element = json_encode($element);
		$contact_value[$key] = $element;
	}

	$contact_form = wpcf7_get_current_contact_form();

  $page_title = $contact_form
    ? $contact_form->title()
    : __( 'Contact Form 7 submission', 'launchsnap-db-for-enfold' );

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
