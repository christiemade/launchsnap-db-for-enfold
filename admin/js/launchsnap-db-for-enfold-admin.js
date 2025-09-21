//Select form name then call
function submit_lse(){
	var url = jQuery('#fp_name').attr('action');
	var fp_id = parseInt(jQuery('#fp_id').val());
	console.log(fp_id);
	if(!isNaN(fp_id)){
		url = url+"&fp_id="+fp_id;
	}
	window.location = url;
}