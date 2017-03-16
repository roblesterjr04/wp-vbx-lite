<?php
	
class WPRLVBX_Applet_VoiceMail extends WPRLVBX_Applet {
	
	public $name = 'VoiceMail';
	public $icon_class = 'dashicons-microphone';
	
	public function admin_content() {
		
		echo '<h3>' . __('Assign to user', WPRLVBX_TD) . '</h3>';
		$users = get_users();
		$sel_user = $this->value('voicemail_user');
		
		?>
		<select name="<? $this->field_name('voicemail_user') ?>">
				<?php foreach ($users as $user) : ?>
				<option value="<?php echo $user->ID ?>" <?php $this->selected($user->ID, $sel_user) ?>><?php echo $user->display_name ?> (<?php echo $user->user_email ?>)</option>
				<?php endforeach ?>
			</select><hr><?php

		
		$this->prompt_message();
			
	}
	
	public function twiml() {
		
		$recording = $this->request('RecordingUrl');
				
		if ($recording) {
			
			$filename = basename( $recording );
			
			$sel_user = $this->value('voicemail_user');
			
			$uploads = wp_upload_dir()['basedir'] . '/voicemail';
			$uploads_url = wp_upload_dir()['baseurl'] . '/voicemail';
			if (!file_exists($uploads)) mkdir($uploads);
			
			$destination = $uploads . '/' . $filename . '.mp3';
			$dest_url = $uploads_url . '/' . $filename . '.mp3';
			
			copy($recording, $destination);
												
			$voicemail = array();
			$voicemail['post_type'] = 'wp-vbx-voicemails';
			$voicemail['post_name'] = date('m-d-Y-H:i:s');
			$voicemail['post_title'] = date('m-d-Y-H:i:s');
			$voicemail['post_status'] = 'vbx-unheard';
			$voicemail['post_author'] = $sel_user;
			$pid = wp_insert_post( $voicemail );
			
			update_post_meta( $pid, '_voicemail_post_data', $_POST );	
			update_post_meta( $pid, '_voicemail_recording', $dest_url );
			update_post_meta( $pid, '_voicemail_twilio_recording', $recording);
			
			do_action( 'wp-vbx-voicemail-received', get_post($pid), $dest_url, $this );
						
			$this->twiml->Hangup();
			$this->response();
			
		} else {
			
			$default = $this->request('To') . ' is not available. Please leave a message after the tone.';
			$this->prompt_output(false, false, $default);
				
			$this->twiml->Record(array('playBeep'=>true, 'finishOnKey'=>'#'));
			$this->response();
		
		}
		
	}
	
}