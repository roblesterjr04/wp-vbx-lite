<?php
	
class WPRLVBX_VoiceMail {
	
	private $data;
	
	public function __construct() {
		
		add_filter( 'dashboard_glance_items', array($this, 'voicemail_status'));
		add_filter( 'display_post_states', array($this, 'voicemail_state') );
		
	}
	
	/**
	 * voicemail_status function.
	 * 
	 * @access public
	 * @param mixed $items
	 * @return void
	 */
	public function voicemail_status( $items ) {
		
		$voicemails = get_posts(array(
			'post_status'=>'vbx-unheard',
			'post_type'=>'wp-vbx-voicemails'
		));
		
		if (count($voicemails)) {
		
			$items[] = '<strong><a class="vbx-vm-count" href="edit.php?post_type=wp-vbx-voicemails">'.count($voicemails).' '.(count($voicemails) > 1 ? 'Voicemails' : 'Voicemail').'</a></strong>';
		
		}
		
		return $items;
	}
	
	/**
	 * meta_boxes function.
	 * 
	 * @access public
	 * @return void
	 */
	public function meta_boxes() {
		
		add_meta_box( 'voicemail-recording', 'Recording', array($this, 'recording_box'), 'wp-vbx-voicemails');
		add_meta_box( 'voicemail-meta', 'Call Data', array($this, 'call_data'), 'wp-vbx-voicemails' );
		
	}
	
	/**
	 * recording_box function.
	 * 
	 * @access public
	 * @param mixed $post
	 * @return void
	 */
	public function recording_box( $post ) {
		
		$recording = get_post_meta( $post->ID, '_voicemail_recording', true );
		
		do_action('wp_vbx_voicemail_before_recording', $post);
		
		?>
		<audio class="prompt-audio" controls>
			<source src="<?php echo $recording ?>">
		</audio>
		<?php
			
		do_action('wp_vbx_voicemail_after_recording', $post);
		
		$post->post_status = 'publish';
		wp_update_post( $post );
		
	}
	
	public function voicemail_state( $states ) {
		global $post;
		$arg = get_query_var( 'post_status' );
		if($arg != 'vbx-unheard'){
			if($post->post_status == 'vbx-unheard'){
				return array('New');
			}
		}
		return $states;
	}
	
	/**
	 * call_data function.
	 * 
	 * @access public
	 * @param mixed $post
	 * @return void
	 */
	public function call_data( $post ) {
		
		$this->data = get_post_meta( $post->ID, '_voicemail_post_data', true );
		
		do_action('wp_vbx_voicemail_before_call_data', $post);
		
		ob_start();
		?>
		
		<h3><?php _e('From:',WPRLVBX_TD) ?> <?php echo $this->_d('From') ?></h3>
		<h3><?php _e('To:',WPRLVBX_TD) ?> <?php echo $this->_d('To') ?></h3>
		<p><?php _e('Location of caller:', WPRLVBX_TD) ?> <?php echo $this->_d('FromCity') ?>, <?php echo $this->_d('FromState') ?></p>
		
		<?php
		echo apply_filters( 'wp-vbx-voicemail-call-data', ob_get_clean(), $post, $this );
			
		do_action('wp_vbx_voicemail_after_call_data', $post);
			
	}
	
	/**
	 * d function.
	 * 
	 * @access private
	 * @param mixed $key
	 * @return void
	 */
	private function _d($key) {
		if (isset($this->data[$key])) return $this->data[$key];
		else return '';
	}
	
}