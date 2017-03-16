<?php
	
class WPRLVBX_Applet_Sms extends WPRLVBX_Applet {
	
	public $name = 'SMS Reply';
	public $icon_class = 'dashicons-admin-comments';
	
	public function admin_content() {
		
		?>
		<h3>Send SMS to caller</h3>
		<p>The text here will be sent to the caller as an SMS text message.</p>
		<textarea class="widefat" name="<?php $this->field_name('sms_text') ?>"><?php echo $this->value('sms_text') ?></textarea>
		<p>Attach some media (optional)</p>
		<?php $this->media_picker('sms_media', 'Select Media', array('image', 'video')); ?>
		<h3>After message is sent, do this...</h3><?php
			
			$this->drop_zone();
		
	}
	
	public function twiml() {
		
		$sms = $this->value('sms_text');
		$media = $this->value('sms_media');

		if ($sms || $media) {
			if ($this->request('CallSid')) {
				$this->twiml->Sms($sms);
				$this->run();
			} else if ($this->request('MessageSid')) {
				$message = $this->twiml->Message();
				if ($sms) {
					$message->Body($sms);
				}
				if ($media) {
					$message->Media($media);
				}
				$this->run();
			} else {
				$this->run();
			}
		} else {
			$this->run();
		}
				
	}
	
}