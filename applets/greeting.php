<?php
	
class WPRLVBX_Applet_Greeting extends WPRLVBX_Applet {
	
	public $name = 'Greeting';
	public $icon_class = 'dashicons-controls-volumeon';
	public $description = 'Welcome the caller with a greeting.';
	
	public function admin_content() {
		
		?>
		<h3><?php _e('Speak something to the caller', WPRLVBX_TD) ?></h3>
		<?php $this->prompt_message() ?>
		<h3><?php _e('After speaking, do...', WPRLVBX_TD) ?></h3>
		<?php
			
		$this->drop_zone();
		
	}
	
	public function twiml() {
		
		$this->prompt_output();
		$this->run('next');
		
	}
	
}