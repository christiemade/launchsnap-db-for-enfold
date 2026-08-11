<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Launchsnap_Db_List {
    public $results = array();

    function all() {
      global $wpdb;

			$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ecf");

			return $this->results = $results;
    }

    public function postTitle( $fid ) {
      global $wpdb;

      $fid = absint( $fid );

      if ( 0 === $fid ) {
        return $this->results = array();
      }

      $results = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}ecf
          WHERE page = (
            SELECT page
            FROM {$wpdb->prefix}ecf
            WHERE id = %d
          )",
          $fid
        )
      );

      return $this->results = $results;
    }
}
?>
