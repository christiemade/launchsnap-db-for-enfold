<?php
class ECF_ListDb {
    public $results = array();

    function all() {
      global $wpdb;

			$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ecf");

			return $this->results = $results;
    }

    function postTitle($fid) {
      global $wpdb;

			$results = $wpdb->get_results("SELECT *
        FROM {$wpdb->prefix}ecf
        WHERE page = (
            SELECT page
            FROM {$wpdb->prefix}ecf
            WHERE id = $fid
        )");

			return $this->results = $results;
    }
}
?>
