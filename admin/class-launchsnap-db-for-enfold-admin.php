<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://christiemade.com
 * @since      1.0.1
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
	 * @since    1.0.1
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.1
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
	 * @since    1.0.1
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
		add_menu_page(
      __( 'Form Entries', 'launchsnap-db-for-enfold' ),
      __( 'Form Entries', 'launchsnap-db-for-enfold' ),
      'manage_options',
      'form-contacts',
      'launchsnap_db_admin_page',
      'dashicons-visibility',
      45
    );
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
			<option value="-1"><?php esc_html_e('Export to...', 'launchsnap-db-for-enfold'); ?></option>
			<option value="csv"><?php esc_html_e('CSV', 'launchsnap-db-for-enfold'); ?></option>
			<option value="excel"><?php esc_html_e('Excel', 'launchsnap-db-for-enfold'); ?></option>
		</select>
		<button class="button action" title="<?php esc_html_e('Export', 'launchsnap-db-for-enfold'); ?>" type="submit"
			name="btn_export"><?php esc_html_e('Export', 'launchsnap-db-for-enfold'); ?></button>
		<?php
	}

	/**
	 * Export options callback
	 */
	public function lse_save_setting_callback()
	{
    if ( ! isset( $_POST['_wpnonce'] ) ) {
      return;
    }

    $nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );

    if ( ! wp_verify_nonce( $nonce, 'lse-action-nonce' ) ) {
      return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
      return;
    }

		global $wpdb;

		//Setup export functionality here
		if (isset($_POST['btn_export'])) {

			//Get form ID
			$fid = isset( $_POST['fid'] )
        ? absint( wp_unslash( $_POST['fid'] ) )
        : 0;

      if ( 0 === $fid ) {
        return;
      }

			//Get export id related information
			$ids_export = '';

      if ( isset( $_POST['del_id'] ) && is_array( $_POST['del_id'] ) ) {
        $selected_ids = array_filter(
          array_map(
            'absint',
            wp_unslash( $_POST['del_id'] )
          )
        );

        $ids_export = implode( ',', $selected_ids );
      }

			///Get export type related information
			$type = isset( $_POST['vsz-cf7-export'] )
        ? sanitize_key( wp_unslash( $_POST['vsz-cf7-export'] ) )
        : '';

      if ( ! in_array( $type, array( 'csv', 'excel' ), true ) ) {
        return;
      }

			//Check type name and execute type related CASE
			switch ($type) {
				case 'csv':
					launchsnap_db_export_csv($fid, $ids_export);
					break;
				case 'excel':
					launchsnap_db_export_excel($fid, $ids_export);
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

function launchsnap_db_safe_csv_value( $value ) {
	$value = (string) $value;

	if ( preg_match( '/^[=+\-@\t\r]/', ltrim( $value ) ) ) {
		return "'" . $value;
	}

	return $value;
}

/**
 * Generate CSV file here
 */
function launchsnap_db_export_csv($fid, $ids_export = '')
{

	global $wpdb;

	if (!isset($_POST['_wpnonce']) || (isset($_POST['_wpnonce']) && empty($_POST['_wpnonce']))) {
		return esc_html('You do not have the permission to export the data');
	}

	//Get nonce value
	$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );

	//Verify nonce value
	if (!wp_verify_nonce($nonce, 'lse-action-nonce')) {
		return esc_html('You do not have the permission to export the data');
	}

	$fid = intval($fid);
	if (empty($fid)) {
		return esc_html('You do not have the permission to export the data');
	}

	$fields = launchsnap_db_get_form_fields($fid);

	//get current form title
	$form_title = launchsnap_db_get_entry_title($fid);

	//Get export data
	$data = launchsnap_db_create_export_query($fid, $ids_export);

	if (!empty($data)) {
		//Setup export data
		$data_sorted = wp_unslash(launchsnap_db_sort_entry_data($data));

		//Generate CSV file
		header('Content-Type: text/csv; charset=UTF-8');
		$filename = sanitize_file_name( $form_title );

    if ( '' === $filename ) {
      $filename = 'form-entries';
    }

    $filename .= '.csv';

    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		$fp = fopen('php://output', 'w');
		echo "\xEF\xBB\xBF";
		fputcsv($fp, array_values(array_map('sanitize_text_field', $fields)),",","\"","\\");
		foreach ($data_sorted as $k => $v) {
			$temp_value = array();
			foreach ($fields as $k2 => $v2) {
				$temp_value[] = isset( $v[$k2] )
          ? launchsnap_db_safe_csv_value( html_entity_decode( $v[$k2] ) )
          : '';
			}
			fputcsv($fp, $temp_value,",","\"","\\");
		}

		exit();
	}
}

/**
 * Generate excel file here
 */
function launchsnap_db_export_excel($fid, $ids_export)
{

	global $wpdb;

	require_once __DIR__ . '/../vendor/autoload.php';

	if (!isset($_POST['_wpnonce']) || (isset($_POST['_wpnonce']) && empty($_POST['_wpnonce']))) {
		return esc_html('You do not have the permission to export the data');
	}

	//Get nonce value
  $nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );

	//Verify nonce value
	if (!wp_verify_nonce($nonce, 'lse-action-nonce')) {
		return esc_html('You do not have the permission to export the data');
	}

	$fid = intval($fid);
	if (empty($fid)) {
		return esc_html('You do not have the permission to export the data');
	}

	$fields = launchsnap_db_get_form_fields($fid);

	//get current form title
	$form_title = launchsnap_db_get_entry_title($fid);

	//Get export data
	$data = launchsnap_db_create_export_query($fid, $ids_export);
	if (!empty($data)) {
		//Setup export data
		$data_sorted = wp_unslash(launchsnap_db_sort_entry_data($data));

		// Convert number to Excel column letter (1 -> A, 2 -> B, etc.)
		function launchsnap_db_column_letter($c)
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
			$cell = launchsnap_db_column_letter($col) . '1';
			$sheet->setCellValueExplicit(
        $cell,
        (string) $colName,
        DataType::TYPE_STRING
      );
			$col++;
		}

		// 2. Insert data starting from row 2
		$row = 2;
		foreach ($data_sorted as $entry) {
			$col = 1;
			foreach ($fields as $key => $fieldName) {
				$colVal = isset($entry[$key]) ? html_entity_decode($entry[$key]) : '';
				$cell = launchsnap_db_column_letter($col) . $row;
				$sheet->setCellValueExplicit(
          $cell,
          (string) $colVal,
          DataType::TYPE_STRING
        );
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
function launchsnap_db_create_export_query( $fid, $ids_export = '' ) {
	global $wpdb;

	$page_title = launchsnap_db_get_entry_title( absint( $fid ) );

	if ( empty( $page_title ) ) {
		return array();
	}

	$ids = array_filter(
		array_map(
			'absint',
			explode( ',', (string) $ids_export )
		)
	);

	if ( empty( $ids ) ) {
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ecf
				WHERE page = %s
				ORDER BY id ASC",
				$page_title
			)
		);
	}

	$ids_csv = implode( ',', $ids );

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ecf
			WHERE page = %s
			AND FIND_IN_SET( id, %s )
			ORDER BY id ASC",
			$page_title,
			$ids_csv
		)
	);
}