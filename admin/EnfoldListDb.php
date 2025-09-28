<?php
class ECF_ListDb {
    public $results = array();

    function all() {
      global $wpdb;

			$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ecf");

			return $this->results = $results;
    }

    function postTitle($title) {
      global $wpdb;

			$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ecf WHERE `page` = '{$title}'");

			return $this->results = $results;
    }
}
?>
