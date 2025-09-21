<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://launchsnap.com
 * @since      1.0.0
 *
 * @package    Launchsnap_Db
 * @subpackage Launchsnap_Db/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Launchsnap_Db
 * @subpackage Launchsnap_Db/admin
 * @author     Christie Wood <christie@christiemade.com>
 */
class Launchsnap_Db_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_register_script('launchsnap_db_admin_js', plugin_dir_url(__FILE__) . 'js/launchsnap-db-for-enfold-admin.js', array('jquery'), $this->version, false);
	}

	/**
	 * Defining the extra menus to be added
	 * admin screens for Contact form Db and Import CSV
	 */
	function ls_enfold_plugin_menu()
	{

		///// Menu pages for contact form DB
		$user_id = get_current_user_id();
		$subject = new WP_User($user_id);
		$cap = 'edit_posts';

		//check current user view capability access
		if (in_array("administrator", $subject->roles, true)) {
			add_menu_page("Form Entries", "Form Entries", 'manage_options', 'form-contacts', 'ecf_index', 'dashicons-visibility', 45);

		}
	}


	/**
	 * Action callback function of 'lse_after_bulkaction_btn'
	 * Populate Export option box on form listing screen
	 * @param $fid
	 */
	function lse_after_bulkaction_btn_callback($fid)
	{
		$fid = (int) $fid;
		if (empty($fid)) {
			return 'Select at least one form';
		}

		?><!-- Display Export functionality button here-->
		<select id="vsz-cf7-export" name="vsz-cf7-export" data-fid="<?php echo esc_html($fid); ?>">
			<option value="-1"><?php esc_html_e('Export to...', LSE_TEXT_DOMAIN); ?></option>
			<option value="csv"><?php esc_html_e('CSV', LSE_TEXT_DOMAIN); ?></option>
			<option value="excel"><?php esc_html_e('Excel', LSE_TEXT_DOMAIN); ?></option>
		</select>
		<button class="button action" title="<?php esc_html_e('Export', LSE_TEXT_DOMAIN); ?>" type="submit"
			name="btn_export"><?php esc_html_e('Export', LSE_TEXT_DOMAIN); ?></button>
		<?php
	}

	/**
	 * Export options callback
	 */
	public function lse_save_setting_callback()
	{
		global $wpdb;

		//Setup export functionality here
		if (isset($_POST['btn_export'])) {

			//Get form ID
			$fid = (int) sanitize_text_field($_POST['fid']);

			//Get export id related information
			$ids_export = ((isset($_POST['del_id']) && !empty($_POST['del_id'])) ? implode(',', array_map('intval', $_POST['del_id'])) : '');

			///Get export type related information
			$type = sanitize_text_field($_POST['vsz-cf7-export']);
			//Check type name and execute type related CASE
			switch ($type) {
				case 'csv':
					lse_export_to_csv($fid, $ids_export);
					break;
				case 'excel':
					lse_export_to_excel($fid, $ids_export);
					break;
				case '-1':
					return;
					break;
				default:
					return;
					break;
			}//Close switch
		}//Close if for export
	}//Close admin_init hook function
}

/**
 * Generate CSV file here
 */
function lse_export_to_csv($fid, $ids_export = '')
{

	global $wpdb;

	if (!isset($_POST['_wpnonce']) || (isset($_POST['_wpnonce']) && empty($_POST['_wpnonce']))) {
		return esc_html('You do not have the permission to export the data');
	}

	//Get nonce value
	$nonce = sanitize_text_field($_POST['_wpnonce']);

	//Verify nonce value
	if (!wp_verify_nonce($nonce, 'lse-action-nonce')) {
		return esc_html('You do not have the permission to export the data');
	}

	$fid = intval($fid);
	if (empty($fid)) {
		return esc_html('You do not have the permission to export the data');
	}
	$fields = lse_get_db_fields($fid);

	//get current form title
	$form_title = get_the_title($fid);

	//Get export data
	$data = create_lse_export_query($fid, $ids_export);

	if (!empty($data)) {
		//Setup export data
		$data_sorted = wp_unslash(lse_sortdata($data));

		//Generate CSV file
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment;filename="' . $form_title . '.csv";');
		$fp = fopen('php://output', 'w');
		fputs($fp, "\xEF\xBB\xBF");
		fputcsv($fp, array_values(array_map('sanitize_text_field', $fields)));
		foreach ($data_sorted as $k => $v) {
			$temp_value = array();
			foreach ($fields as $k2 => $v2) {
				$temp_value[] = ((isset($v[$k2])) ? html_entity_decode($v[$k2]) : '');
			}
			fputcsv($fp, $temp_value);
		}

		fclose($fp);
		exit();
	}
}

/**
 * Generate excel file here
 */
function lse_export_to_excel($fid, $ids_export)
{

	global $wpdb;

	require_once __DIR__ . '/../vendor/autoload.php';

	if (!isset($_POST['_wpnonce']) || (isset($_POST['_wpnonce']) && empty($_POST['_wpnonce']))) {
		return esc_html('You do not have the permission to export the data');
	}

	//Get nonce value
	$nonce = sanitize_text_field($_POST['_wpnonce']);
	//Verify nonce value
	if (!wp_verify_nonce($nonce, 'lse-action-nonce')) {
		return esc_html('You do not have the permission to export the data');
	}

	$fid = intval($fid);
	if (empty($fid)) {
		return esc_html('You do not have the permission to export the data');
	}

	$fields = lse_get_db_fields($fid);

	//get current form title
	$form_title = get_the_title($fid);

	//Get export data
	$data = create_lse_export_query($fid, $ids_export);
	if (!empty($data)) {
		//Setup export data
		$data_sorted = wp_unslash(lse_sortdata($data));

		// Convert number to Excel column letter (1 -> A, 2 -> B, etc.)
		function colLetter($c)
		{
			$c = intval($c);
			if ($c <= 0)
				return '';
			$letter = '';
			while ($c != 0) {
				$p = ($c - 1) % 26;
				$c = intval(($c - $p) / 26);
				$letter = chr(65 + $p) . $letter;
			}
			return $letter;
		}

		$arrHeader = array_values(array_map('sanitize_text_field', $fields));
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		// 1. Insert headers into first row
		$col = 1;
		foreach ($arrHeader as $colName) {
			$cell = colLetter($col) . '1';
			$sheet->setCellValue($cell, $colName);
			$col++;
		}

		// 2. Insert data starting from row 2
		$row = 2;
		foreach ($data_sorted as $entry) {
			$col = 1;
			foreach ($fields as $key => $fieldName) {
				$colVal = isset($entry[$key]) ? html_entity_decode($entry[$key]) : '';
				$cell = colLetter($col) . $row;
				$sheet->setCellValue($cell, $colVal);
				$col++;
			}
			$row++;
		}

		// 3. Set filename
		$filename = sanitize_file_name($form_title) . '.xlsx';

		// 4. Send headers for browser download
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');

		// 5. Write file to output
		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');
		exit;
	}
}


// Setup export query here
function create_lse_export_query($fid, $ids_export)
{

	global $wpdb;
	$fid = intval($fid);
	$page_title = get_the_title($fid);

	$query = "SELECT * FROM `" . LSE_DATA_ENTRY_TABLE_NAME . "` WHERE `page` = '" . $page_title . "' AND id IN(
						SELECT * FROM (
							SELECT id FROM `" . LSE_DATA_ENTRY_TABLE_NAME . "` WHERE 1 = 1 AND `page` = '" . $page_title . "' " . ((!empty($ids_export)) ? " AND id IN(" . $ids_export . ")" : '') . "
								GROUP BY `id` ORDER BY id ASC
							)
						temp_table)
						ORDER BY id ASC";

	//Execuste query
	$data = $wpdb->get_results($query);

	//Return result set
	return $data;
}//Close export query function

