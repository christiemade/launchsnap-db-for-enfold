<?php
class ECF_ListDb {
    public $results = array();

    function all() {
      global $wpdb;

			$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ecf");

			return $this->results = $results;
    }
}
?>
