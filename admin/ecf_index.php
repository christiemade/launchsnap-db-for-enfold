<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	die('Un-authorized access!');
}

/**
 * Detect plugin. For use in Admin area only.
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

function ecf_index(){  
  global $wpdb;
	wp_enqueue_script('launchsnap_db_admin_js');

	//Get all existing contact form list
	$form_list = lse_get_the_form_list();
	$fid = '';
	
	$nonce = wp_create_nonce('lse-action-nonce');

	if(!wp_verify_nonce( $nonce, 'lse-action-nonce')){
		echo esc_html('You have no permission to access this page');
		return;
	}

	//Get selected Form Page Id value
	if(isset($_GET['fp_id']) && !empty($_GET['fp_id'])){
		$fid = intval(sanitize_text_field($_GET['fp_id']));
		$results = $GLOBALS['EnfoldListDb']->postTitle(get_the_title($fid));
	} else {
		$results = $GLOBALS['EnfoldListDb']->all(); 
		$fid = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '".$results[0]->page."'" );
	}

	//Get all form names which entry store in DB
	
	//Get table name for data entry

	?><div class="wrap">
		<h2><?php
			esc_html_e('View Form Information',LSE_TEXT_DOMAIN);
		?></h2>
	</div>
	<div class="wrap select-specific">
		<table class="form-table inner-row">
			<tr class="form-field form-required select-form">
				<th><?php esc_html_e('Select Form name',LSE_TEXT_DOMAIN);  ?></th>
				<td><?php $fid = (string)trim($fid) ?>
					<form name="fp_name" id="fp_name" action="<?php menu_page_url('form-contacts');?>" method="">
						<select name="fp_id" id="fp_id" onchange="submit_lse()">
							<option value=""><?php esc_html_e('Select Form name',LSE_TEXT_DOMAIN);  ?></option><?php
							//Display all existing form list here
							if(!empty($form_list)){

								foreach($form_list as $formInfo){
									$exist_entry_flag = true;
									if(!empty($fid) && $fid === $formInfo['ID'])
										print '<option value="'.$formInfo['ID'].'" selected>'.esc_html($formInfo['post_title']).'</option>';
									else
										print '<option value="'.$formInfo['ID'].'" >'.esc_html($formInfo['post_title']).'</option>';
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
	$page = isset($_REQUEST['cpage']) && !empty($_REQUEST['cpage']) 
    ? absint(sanitize_text_field($_REQUEST['cpage'])) 
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
							do_action('lse_after_bulkaction_btn', $fid);
							?><div class="tablenav-pages">
								<span class="displaying-num"><?php echo (($total == 1) ?
								'1 ' . esc_html('item') :
								$total . ' ' . esc_html('items')) ?></span>

								<span class="pagination-links"><?php
									//Setup pagination structure
									// Copy the current query vars
									$query_args = $_GET;

									// Overwrite 'cpage' with the pagination placeholder
									$query_args['cpage'] = '%#%';

									// Build the base URL with all preserved args
									$base = add_query_arg( 'cpage', '%#%', admin_url( 'admin.php?page=form-contacts' ) );

									echo paginate_links( array(
										'base'      => $base,
										'format'    => '', // leave empty, we're already handling it in base
										'prev_text' => __('&laquo;'),
										'next_text' => __('&raquo;'),
										'total'     => ceil($total / $items_per_page),
										'current'   => $page,
									) );


								?></span>
							</div>
						</div>
						<br class="clear">
					</div>
				</div>
				<?php 
	error_log(json_encode($data_sorted)); ?>
				<div class="span12 table-structure">
					<div class="table-inner-structure">
						<table class="wp-list-table widefat fixed striped posts">
							<thead>
								<tr><?php
									//Define table header section here
									$fields = maybe_unserialize(base64_decode($data_sorted[0]->complete));
									$fields = array_keys($fields);
							
									foreach ($fields as $k => $v){
										echo '<th class="manage-column" data-key="'.esc_html($v).'">'.$v.'</th>';
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

											echo '<td data-head="">'. $v2. '</td>';
										}//Close foreach
										echo '</tr>';
									}//Close foreach
								}
								else{
									?><tr><?php
										$span = count($fields) + 2;
										?><td colspan="<?php echo esc_html($span); ?>">
											<?php esc_html_e('No records found.',LSE_TEXT_DOMAIN);  ?>
										</td><?php
									?></tr><?php
								}
							?></tbody>
							<tfoot>
								<tr><?php
									//Setup header section in table footer area
									echo '<td class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-2" /></td>';
									foreach ($fields as $k => $v){
                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										echo '<th class="manage-column" data-key="'.esc_html($v).'">'.$v.'</th>';
									}
								?></tr>
							</tfoot>
						</table>
					</div>
				</div>

				<input type="hidden" name="cpage" value="<?php echo intval($page);?>" id="cpage">
				<input type="hidden" name="totalPage" value="<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  print ceil($total / $items_per_page);?>" id="totalPage">
				<?php $list_nonce = wp_create_nonce( 'lse-form-list-nonce' ); ?>
				<input type="hidden" name="lse_form_list_nonce"  value="<?php esc_html_e($list_nonce); ?>" />
				
			</form>
			<?php else: ?>
					No entries.
			<?php endif; ?>
<?php } ?>
