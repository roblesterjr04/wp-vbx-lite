<?php
	
class WPRLVBX_Applet_Hangup extends WPRLVBX_Applet {
	
	public $name = 'Hangup';
	public $icon_class = 'dashicons-dismiss';
	
	public function twiml() {
		
		$this->twiml->Hangup();
		$this->response();
		
	}
	
	public function admin_content() {
		
		echo '<h1>' . __('Hangup the Phone.', WPRLVBX_TD) . '</h1>';
		
	}
	
}