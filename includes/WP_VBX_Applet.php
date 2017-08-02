<?php
	
abstract class WPRLVBX_Applet {
	
	public $id = '-';
	public $name = 'Not Set';
	public $icon_class = 'dashicons-marker';
	public $fields = array();
	protected $action;
	public $description;
	
	private $request;
	
	protected $index;
	protected $instance; 
	
	protected $post;
	
	public $twiml;
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param int $index (default: 0)
	 * @param bool $postid (default: false)
	 * @return void
	 */
	public function __construct($index = 0, $postid = false) {
		if (!$postid) $postid = get_the_ID();
		$this->post = $postid;
		$this->index = $index;
		$this->instance = $this->instance();
		$this->id = get_class($this);
		foreach ($_REQUEST as $key=>$val) {
			$this->request[$key] = $val;
		}
		
		if (defined('DOING_AJAX')) {
			$this->action = vbx_permalink($postid, get_class($this) . '-' .$index);
		}
		$this->twiml = apply_filters('wp-vbx-twiml', WPRLVBX::$twiml, $this);
		do_action('wp-vbx-applet', $this);
	}
	
	/**
	 * box_content function.
	 * 
	 * @access public
	 * @return void
	 */
	final public function box_content() {
		
		ob_start();
		
		?><div class="applet-content"><?php
		$this->admin_content();
		do_action( 'wp-vbx-applet-admin-content' , $this);
		?>
		<span class="index"><?php echo $this->index + 1 ?></span>
		<input class="this" type="hidden" name="flow[<?php echo $this->index ?>][this]" value="<?php echo $this->id ?>-<?php echo $this->index ?>">
		</div>
		<?php
			
		echo apply_filters( 'wp-vbx-applet-box-content', ob_get_clean(), $this );
	}
	
	/**
	 * admin_content function.
	 * 
	 * @access public
	 * @return void
	 */
	public function admin_content() {
		?>
		<h1><?php _e(apply_filters( 'wp-vbx-applet-no-settings', 'No Settings', $this ), WPRLVBX_TD) ?></h1>
		<?
	}
	
	
	/**
	 * drop_zone function.
	 * 
	 * @access protected
	 * @final
	 * @param string $name (default: 'next')
	 * @return void
	 */
	final protected function drop_zone($name = 'next') {
		
		ob_start();
				
		?>
		<div class="dropzone" data-index="<?php echo $this->index ?>">
		<input class="drop" type="hidden" name="flow[<?php echo $this->index ?>][<?php echo $name ?>]" value="<?php echo isset($this->instance[$name]) ? $this->instance[$name] : '' ?>">
		
		<div class="flow ui-droppable" style="">
			<?php
				if (isset($this->instance[$name]) && strlen($this->instance[$name])) {
					$class_parts = explode('-', $this->instance[$name]);
					if (class_exists($class_parts[0])) {
						$app = new $class_parts[0]($class_parts[1]);
						$app->item();
					}
				}
			?>
		</div>
		<div class="backdrop"><p>Drop Applet Here</p></div>
		</div>
		<?php
			
		echo apply_filters( 'wp-vbx-applet-drop-zone', ob_get_clean(), $name, $this );
			
	}
	
	final public function prompt_message($name = '') {
		
		ob_start();
		
		if ($name != '') $name .= '_';
		
		$selected = $this->value($name . 'prompt_type') ?: 'text';
		$voice = $this->value($name . 'prompt_voice') ?: 'man';
		$language = $this->value('text_language');
		
		?>
		<div class="prompt-message collapse">
			
			<label>
				<input type="radio" value="text" name="<?php $this->field_name($name . 'prompt_type') ?>" <?php $this->checked( $selected, 'text') ?>>
					<?php _e('Text to speech', WPRLVBX_TD) ?>
				<textarea class="widefat" name="<?php $this->field_name($name . 'prompt_text') ?>"><?php echo $this->value($name . 'prompt_text') ?></textarea>
				<label><input type="radio" value="man" name="<?php $this->field_name($name . 'prompt_voice') ?>" <?php $this->checked( $voice, 'man') ?>> <?php _e('Man', WPRLVBX_TD) ?></label>
				<label><input type="radio" value="woman" name="<?php $this->field_name($name . 'prompt_voice') ?>" <?php $this->checked( $voice, 'woman') ?>> <?php _e('Woman', WPRLVBX_TD) ?></label>
				<label>
					&nbsp;|&nbsp;&nbsp;Language:&nbsp;
					<select name="<?php $this->field_name('text_language') ?>">
						<option value=""><?php _e('Wordpress Default', WPRLVBX_TD) ?></option>
						<option value="en" <?php $this->selected( $language, 'en') ?>><?php _e('English US', WPRLVBX_TD) ?></option>
						<option value="en_GB" <?php $this->selected( $language, 'en_GB') ?>><?php _e('English GB', WPRLVBX_TD) ?></option>
						<option value="es" <?php $this->selected( $language, 'es') ?>><?php _e('Spanish', WPRLVBX_TD) ?></option>
						<option value="fr" <?php $this->selected( $language, 'fr') ?>><?php _e('French', WPRLVBX_TD) ?></option>
						<option value="de" <?php $this->selected( $language, 'de') ?>><?php _e('German', WPRLVBX_TD) ?></option>
					</select>
				</label>
			</label>
			<label>
				<input type="radio" value="recording" name="<?php $this->field_name($name . 'prompt_type') ?>" <?php $this->checked( $selected, 'recording') ?>>
					<?php _e('Play recorded message', WPRLVBX_TD) ?>
				<?php $this->media_picker($name . 'prompt_recording', 'Select Recording', array('audio')) ?>
			</label>
		</div>
		
		<?php
			
		echo apply_filters( 'wp-vbx-applet-prompt-message', ob_get_clean(), $selected, $this);
		
	}
	
	final public function media_picker($name, $title = 'Select Media', $type = array('image')) {
		
		$seltype = $this->value($name . '_mime');
		
		?>
		<div class="media-picker <?php echo $seltype ?>">
		<input class="media-url" type="hidden" value="<?php echo $this->value($name) ?>" name="<?php $this->field_name($name) ?>">
		<input class="media-mime" type="hidden" value="<?php echo $this->value($name . '_mime') ?>" name="<?php $this->field_name($name . '_mime') ?>">
		<button class="button select-media" data-type="['<?php echo implode('\',\'', $type) ?>']"><?php echo $title ?></button>
		<p class="media-filename"><?php echo ($this->value($name) ? basename($this->value($name)) : 'Nothing Selected') ?></p>
		<img src="<?php echo $this->value($name) ?>" class="media-display image" />
		<audio class="media-display audio" controls src="<?php echo $this->value($name) ?>"></audio>
		<video class="media-display video" controls src="<?php echo $this->value($name) ?>"></video>
		</div>
		<?php
		
	}
	
	final public function prompt_output($name = '', $twiml = false, $default = false) {
		
		$language_supported = array('en', 'en_GB', 'es', 'fr', 'de');
		
		$language = $this->value('text_language') ?: get_locale();
		
		if ($language != 'en_GB') $language = substr($language, 0, 2);
		
		if (!in_array($language, $language_supported)) $language = 'en';
		if ($language == 'en_GB') $language = 'en-gb';
		
		if ($name != '') $name .= '_';
		
		$type = $this->value($name.'prompt_type');
		$voice = $this->value($name . 'prompt_voice');
		
		if ($twiml === false) $twiml = $this->twiml;
				
		$args = array('voice'=>$voice, 'language'=>$language);
				
		if ($type == 'text') {
			$twiml->Say($this->value($name.'prompt_text') ?: $default, $args);
		} else {
			$twiml->Play($this->value($name.'prompt_recording'));
		}
		
		return $args;
		
	}
	
	/**
	 * selected function.
	 * 
	 * @access protected
	 * @param mixed $val
	 * @param mixed $sel
	 * @return void
	 */
	final protected function selected($val, $sel) {
		if ($val == $sel) echo 'selected';
		else echo '';
	}
	
	/**
	 * checked function.
	 * 
	 * @access protected
	 * @param mixed $val
	 * @param mixed $sel
	 * @return void
	 */
	final protected function checked($val, $sel) {
		if ($val == $sel) echo 'checked';
		else echo '';
	}
	
	
	/**
	 * meta_box function.
	 * 
	 * @access public
	 * @return void
	 */
	final public function meta_box() {
		$name = '<span class="dashicons ' . $this->icon_class . '"></span> ' . $this->name;
		
		add_meta_box( 'applet-' . $this->id . '-' . $this->index, $name, array($this, 'box_content'), 'wp-vbx-flows');
		
	}
	
	
	/**
	 * ajax_box function. Returns new settings box for dropped applets.
	 * 
	 * @access public
	 * @return void
	 */
	public function ajax_box() {
		
		$name = '<span class="dashicons ' . $this->icon_class . '"></span> ' . $this->name;
		
		ob_start();
		
		?>
		<div id="applet-<?php echo $this->id ?>-<?php echo $this->index ?>" class="postbox ">
			<button type="button" class="handlediv button-link" aria-expanded="true"><span class="screen-reader-text">Toggle panel: <?php echo $this->name ?></span><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class="hndle ui-sortable-handle"><span><?php echo $name ?></span></h2>
			<div class="inside">
				<?php $this->box_content() ?>		
			</div>
		</div>
		<?php
			
		echo apply_filters('wp-vbx-applet-ajax-box', ob_get_clean(), $this);
		
	}
	
	
	/**
	 * instance function. Return the instance of the applet
	 * 
	 * @access protected
	 * @param bool $key (default: false)
	 * @return void
	 */
	protected function instance($key = false) {
		
		$flow = unserialize(get_post_meta( $this->post, '_call_flow', true)) ?: array();
		if (empty($flow)) return array();
		
		if ($key) return $flow[$this->index][$key];
		return $flow[$this->index];
		
	}
	
	
	/**
	 * value function. Returns the value of a field in the given applet instance.
	 * 
	 * @access protected
	 * @param mixed $field
	 * @return void
	 */
	final public function value($field) {
		$value = apply_filters('wp-vbx-applet-value', isset($this->instance[$field]) ? $this->instance[$field] : '', $field);
		return $value;
	}
	
	
	/**
	 * item function. Outputs the draggable applet.
	 * 
	 * @access public
	 * @return void
	 */
	public function item() {
		$handle = $this->id . ($this->index > 0 ? '-'.$this->index : '');
		$desc = $this->description ?: $this->name . ' Applet';
		
		ob_start();
		?>
		<div id="<?php echo $handle ?>" class="applet" title="<?php echo $desc ?>">
			<div class="menu-item-bar">
				<div class="menu-item-handle" style="width: auto;">
					<span class="item-title" style="margin: 0">
						<?php if (isset($this->icon_url)) : ?>
							<img src="<?php echo $this->icon_url ?>"/>&nbsp;
						<?php else : ?>
							<span class="dashicons <?php echo $this->icon_class ?>"></span>&nbsp;
						<?php endif ?>
						<span class="menu-item-title"><?php echo $this->name ?></span>
						<a href="#" class="remove-button"><span class="dashicons dashicons-trash"></span></a>
					</span>
				</div>
			</div>
		</div>
			
		<?php
			
		echo apply_filters('wp-vbx-applet-item', ob_get_clean(), $this);
		
	}
	
	
	/**
	 * field_name function. Outputs the sanitized field name. Used when developing applets.
	 * 
	 * @access protected
	 * @param mixed $field
	 * @param bool $echo (default: true)
	 * @return void
	 */
	protected function field_name($field, $echo = true) {
		$name = "flow[{$this->index}][$field]";
		if (!$echo) return $name;
		else echo $name;
	}
	
	
	/**
	 * run function. Executes a dropzone redirect. Hangup if dropzone isn't set.
	 * 
	 * @access protected
	 * @param mixed $name
	 * @return void
	 */
	final protected function run($name = 'next') {
		
		do_action( 'wp-vbx-applet-run', $name, $this );
		
		if (isset($this->instance[$name]) && $this->instance[$name] != '') {
			$this->twiml->Redirect( vbx_permalink($_GET['flow'], $this->instance[$name]) );
			$this->response();

		} else {
			
			if ($this->request('CallSid')) $this->twiml->Hangup();
			$this->response();
			
		}
		
	}
	
	
	/**
	 * twiml function. Base twiml function. Should be overriden by an applet.
	 * 
	 * @access public
	 * @return void
	 */
	public function twiml() {
		$this->twiml->Hangup();
		$this->response();
	}
	
	
	/**
	 * request function. Retrieves a request value. 
	 * 
	 * @access protected
	 * @param mixed $key
	 * @return void
	 */
	final public function request($key) {
		if (isset($this->request[$key])) return apply_filters('wp-vbx-applet-request', $this->request[$key], $key);
		return false;
	}
	
	
	/**
	 * response function. Outputs the TWIML assembly.
	 * 
	 * @access protected
	 * @return void
	 */
	final public function response() {
		
		do_action( 'wp-vbx-applet-before-response', $this->twiml, $this );
		echo $this->twiml;
		do_action( 'wp-vbx-applet-after-response', strval($this->twiml), $this );
		exit;
		
	} 
	
}