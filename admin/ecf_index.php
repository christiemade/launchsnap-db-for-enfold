<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	die('Un-authorized access!');
}

/**
 * Detect plugin. For use in Admin area only.
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

function launchsnap_db_admin_page(){  
  global $wpdb;
	wp_enqueue_script('launchsnap_db_admin_js');

	//Get all existing contact form list
	$form_list = launchsnap_db_get_form_list();
	$fid = '';

	//Get selected Form Page Id value
	if(isset($_GET['fp_id']) && !empty($_GET['fp_id'])){
		$fid = absint( wp_unslash( $_GET['fp_id'] ) );
		$results = $GLOBALS['launchsnap_db_list']->postTitle($fid);
	} else {
		$results = $GLOBALS['launchsnap_db_list']->all(); 
		if ( ! empty( $results ) ) {
      $fid = (int) $wpdb->get_var(
        $wpdb->prepare(
          "SELECT id FROM {$wpdb->prefix}ecf WHERE page = %s ORDER BY id ASC LIMIT 1",
          $results[0]->page
        )
      );
    }
	}

	//Get all form names which entry store in DB
	
	//Get table name for data entry

	?><div class="wrap">
		<h2><?php
			esc_html_e('View Form Information', 'launchsnap-db-for-enfold');
		?></h2>
	</div>
	<div class="wrap select-specific">
		<table class="form-table inner-row">
			<tr class="form-field form-required select-form">
				<th><?php esc_html_e('Select Form name','launchsnap-db-for-enfold');  ?></th>
				<td><?php $fid = (string)trim($fid) ?>
					<form name="fp_name" id="fp_name" action="<?php menu_page_url('form-contacts');?>" method="">
						<select name="fp_id" id="fp_id" onchange="submit_lse()">
							<option value=""><?php esc_html_e('Select Form name','launchsnap-db-for-enfold');  ?></option>
							<?php
							//Display all existing form list here
							if(!empty($form_list)){

								foreach($form_list as $formInfo){
									$exist_entry_flag = true;
									?><option value="<?php echo esc_html( $formInfo['ID'] ); ?>" <?php
									if(!empty($fid) && $fid === $formInfo['ID'])
										print ' selected';
									?> ><?php echo esc_html( $formInfo['post_title'] ); ?></option><?php
								}//close for each
							}//close if
						?></select>
					</form>
				</td>
			</tr>
		</table>
	</div><?php

	//Define bulk action array
	$items_per_page = 30;

	//Get current page information from  query
	$page = isset( $_GET['cpage'] )
    ? max( 1, absint( wp_unslash( $_GET['cpage'] ) ) )
    : 1;

	//Setup offset related value here
	$offset = ($page - 1) * $items_per_page;

	$total = sizeof($results);

	$data_sorted = array();

	if($total) {
		$data_sorted = array_splice($results, $offset, $items_per_page);
	}

		//Form listing design structure start here
		?><div class="wrap our-class">
			<?php if($total > 0): ?>
			<form class="lse-listing row" action="<?php menu_page_url('form-contacts');?>" method="post" id="lse-admin-action-frm" >
				<input type="hidden" name="page" value="form-contacts">
				<input type="hidden" name="fid" value="<?php echo esc_html($fid); ?>">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_html(wp_create_nonce('lse-action-nonce')); ?>">
				
					<div class="span12 bulk-actions">
					<div class="tablenav top">
						<div class="actions bulkactions">
		
							<?php
							//Display Export button option values
							do_action('render_export_controls', $fid);
							?><div class="tablenav-pages">
								<span class="displaying-num"><?php echo (($total == 1) ?
								'1 ' . esc_html('item','launchsnap-db-for-enfold') :
								 esc_html( $total,'launchsnap-db-for-enfold') . ' ' . esc_html('items')) ?></span>

								<span class="pagination-links"><?php
									// Setup pagination structure
									// Build the base URL with all preserved args
									$base = add_query_arg( 'cpage', '%#%', admin_url( 'admin.php?page=form-contacts' ) );

									$nav = paginate_links( array(
										'base'      => $base,
										'format'    => '', // leave empty, we're already handling it in base
										'prev_text' => __('&laquo;', 'launchsnap-db-for-enfold'),
										'next_text' => __('&raquo;', 'launchsnap-db-for-enfold'),
										'total'     => ceil($total / $items_per_page),
										'current'   => $page,
									) );

									echo wp_kses_post( $nav );


								?></span>
							</div>
						</div>
						<br class="clear">
					</div>
				</div>

				<div class="span12 table-structure">
					<div class="table-inner-structure">
						<table class="wp-list-table widefat fixed striped posts">
							<thead>
								<tr><?php
									//Define table header section here
									$fields = maybe_unserialize(base64_decode($data_sorted[0]->complete));
									$fields = array_keys($fields);
									$fields[] = __('Time', 'launchsnap-db-for-enfold');
							
									foreach ($fields as $k => $v){
										echo '<th class="manage-column" data-key="'.esc_html($v,'launchsnap-db-for-enfold').'">'.esc_html($v,'launchsnap-db-for-enfold').'</th>';
									}
								?></tr>
							</thead>
							<tbody><?php

								//Get all fields related information
								if(sizeof($data_sorted)){
									foreach ($data_sorted as $k => $v) {
										$k = (int)$k;
										echo '<tr>';
										$fieldnames = maybe_unserialize(base64_decode($v->complete));
										foreach ($fieldnames as $k2 => $v2) {

											echo '<td data-head="">'. esc_html($v2,'launchsnap-db-for-enfold'). '</td>';
										}//Close foreach
										echo '<td data-head="">'. esc_html($v->contact_time,'launchsnap-db-for-enfold'). '</td>';
										echo '</tr>';
									}//Close foreach
								}
								else{
									?><tr><?php
										$span = count($fields) + 2;
										?><td colspan="<?php echo esc_html($span); ?>">
											<?php esc_html_e('No records found.','launchsnap-db-for-enfold');  ?>
										</td><?php
									?></tr><?php
								}
							?></tbody>
							<tfoot>
								<tr><?php
									foreach ($fields as $k => $v){
                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										echo '<th class="manage-column" data-key="'.esc_html($v,'launchsnap-db-for-enfold').'">'.esc_html($v,'launchsnap-db-for-enfold').'</th>';
									}
								?></tr>
							</tfoot>
						</table>
					</div>
				</div>

				<input type="hidden" name="cpage" value="<?php echo intval($page);?>" id="cpage">
				<input type="hidden" name="totalPage" value="" id="totalPage">
			</form>
			<?php else: ?>
					No entries.
			<?php endif; ?>
<?php } ?>
