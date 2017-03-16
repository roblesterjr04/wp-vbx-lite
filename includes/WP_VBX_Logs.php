<?php

use Twilio\Values;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPRLVBX_Logs_Table extends WP_List_Table {
	
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Logs', WPRLVBX_TD ), //singular name of the listed records
			'plural'   => __( 'Log', WPRLVBX_TD ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?

		] );

	}
	
	public static function get_calls( $per_page = 20, $page_number = 1 ) {
		
		$calls = array();
		foreach (twilio()->account->calls->page(array(), $per_page, Values::of(array('PageToken')), $page_number - 1) as $call) {
			$calls[] = array(
				'from'=>$call->from,
				'to'=>$call->to,
				'status'=> ucwords($call->status),
				'direction'=> ucwords($call->direction),
				'start_time'=> $call->startTime->format('Y-m-d g:i A'),
				'duration'=> number_format($call->duration / 60, 2) . ' minutes'
			);
		}
		
		return $calls;
		
	}
	
	public static function record_count() {
		
		return 100;
		//return count(twilio()->account->calls->read());
		
	}
	
	public function no_items() {
		_e( 'No Logs avaliable.', WPRLVBX_TD );
	}
	
	public function column_from( $item ) {

		// create a nonce		
		$title = '<strong>' . $item['from'] . '</strong>';
		
		return $title;
	}
	
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
		case 'from':
		case 'to':
		case 'direction':
		case 'status':
		case 'start_time':
		case 'duration':
			return $item[ $column_name ];
		default:
			return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}
	
	public function get_columns() {
		$columns = [
			'start_time'=>__('Time', WPRLVBX_TD),
			'from'=>__('From', WPRLVBX_TD),
			'to'=>__('To', WPRLVBX_TD),
			'duration'=>__('Length', WPRLVBX_TD),
			'status'=>__('Status', WPRLVBX_TD),
			'direction'=>__('Direction', WPRLVBX_TD),
		];
		
		return $columns;
	}
	
	public function prepare_items() {

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$per_page     = $this->get_items_per_page( 'calls_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();
		
		$this->set_pagination_args( [
		'total_items' => $total_items, //WE have to calculate the total number of items
		'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );
		
		
		$this->items = self::get_calls( $per_page, $current_page );
		
	}
	
}
		
class WPRLVBX_Logs {
	
	public function __construct() {
		
		add_action('admin_menu', array($this, 'admin_menu'), 100);
				
	}
	
	public function admin_menu() {
		if (twilio()) add_submenu_page( 'phone-system', 'Phone System Logs', 'Logs', 'manage_options', 'phone-system-logs', array($this, 'table'));
	}
	
	public function table() {
		
		$logs = new WPRLVBX_Logs_Table();
		
		?>
		<div class="wrap">
			<h1>Phone Logs</h1>
			<?php $logs->prepare_items() ?>
			<?php $logs->display(); ?>
		</div>
		<?php
	}
	
	public function logs($posts, $query) {
		
		if ($query->query['post_type'] != 'wp-vbx-logs') return;
				
		foreach (twilio()->account->calls->page(array(), 20, Values::of(array('PageToken')), 0) as $call) {
			$call->post_title = $call->from;
			$posts[] = $call;
		}
				
		return $posts;
	}
	
}
new WPRLVBX_Logs;