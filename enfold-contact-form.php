<?php
/*
Plugin Name: Launchsnap DB for Enfold
Version: 2.0.2
Description: Save All Entries from Enfold Forms
Author: Christie Wood
Author URI: https://launchsnap.com
*/

function ecf_load_textdomain()
{
	$plugin_rel_path = basename( dirname( __FILE__ ) ) . '/languages';
	load_plugin_textdomain( 'ecf', false, $plugin_rel_path );
}
add_action('plugins_loaded', 'ecf_load_textdomain');


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



function ecf_add_option_page(){
    add_options_page('ECF DB', 'Form Entries', 'administrator', 'ecf-db-page', 'ecf_index');
}
add_action('admin_menu', 'ecf_add_option_page');

add_theme_support('avia_template_builder_custom_css');

add_filter('avf_form_send', 'ecf_saveFormData', 10, 4);

function ecf_saveFormData($bool, $new_post, $form_params, $avia_form)
{
	global $wpdb;

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
	$contact_value = base64_encode(maybe_serialize($contact_value));

	$contact_time = date('Y-m-d H:i:s e');
	$wpdb->get_results("INSERT INTO {$wpdb->prefix}ecf SET page='{$page_title}', complete='{$contact_value}', contact_time ='{$contact_time}'");

  return true;
}

?>
