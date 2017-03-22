<?php
	
class WPRLVBX_Applet_Start extends WPRLVBX_Applet {
	
	public $name = 'Start';
	
	public function admin_content() {
		
		$flow = get_post_meta( get_the_ID(), '_call_flow', true );
		$flow = unserialize($flow);
		
		?>
		<input type="hidden" id="index" value="<?php echo count($flow) ?>">
		<h1><?php _e('Start Here', WPRLVBX_TD) ?>!</h1>
		<p><?php _e('Drop the first applet here that you want to execute when a caller dials in.', WPRLVBX_TD) ?></p>
		<?php
		$this->drop_zone();
		
	}
	
	public function twiml() {
		
		$class = $this->instance('next');
		
		if ($class == '') {
			$this->twiml->Hangup();
			$this->response();
		}
		
		$class_parts = explode('-', $class);
		$app = new $class_parts[0]($class_parts[1], $this->post);
		
		do_action( 'wp-vbx-applet-run', 'next', $app );
		
		$app->twiml();
		
	}
	
}