<?php
	
class WPRLVBX_Applet_Menu extends WPRLVBX_Applet {
	
	public $name = 'Menu';
	public $icon_class = 'dashicons-networking';
	//public $icon_url = WP_VBX_URL . 'assets/images/menu.png';
	
	public function admin_content() {
		
		$options = $this->value('menu_options');
		
		?>
		<div class="group-container">
		<h3><?php _e('Set Greeting...', WPRLVBX_TD) ?></h3>
		<p><a href="#" class="handle">Settings...</a></p>
		<?php
		$this->prompt_message('greeting');
		?></div>
		<h3>Menu Options:</h3>
		<div class="vbx-menu-options">
			
			<?php if ($options) : ?>
			
			<?php foreach ($options as $i=>$option) : ?>
				
				<label data-index="<?php echo $i ?>">
				<?php _e('Keys:', WPRLVBX_TD) ?> <input name="<?php $this->field_name('menu_options') ?>[<?php echo $i ?>]" value="<?php echo $option ?>" type="text">
				<?php $this->drop_zone('menu_option_' . $i) ?>
				<?php $this->menu_option_controls($i) ?>
				</label>
			
			<?php endforeach ?>
			
			<?php else : ?>
			
			<label data-index="0">
				<?php _e('Keys:', WPRLVBX_TD) ?> <input name="<?php $this->field_name('menu_options') ?>[0]" value="" type="text">
				<?php $this->drop_zone('menu_option_0') ?>
				<?php $this->menu_option_controls(0) ?>
			</label>
			<label data-index="1">
				<?php _e('Keys:', WPRLVBX_TD) ?> <input name="<?php $this->field_name('menu_options') ?>[1]" value="" type="text">
				<?php $this->drop_zone('menu_option_1') ?>
				<?php $this->menu_option_controls(1) ?>
			</label>
			
			<?php endif ?>
			
		</div>
		<div class="group-container">		
		<h3><?php _e('Set Invalid Message...', WPRLVBX_TD) ?></h3>
		<p><a href="#" class="handle">Settings...</a></p>
		<?php
		$this->prompt_message('invalid');
		?></div>
		<label><?php _e('Menu Timeout (seconds):', WPRLVBX_TD) ?> <input type="text" name="<?php $this->field_name('menu_timeout') ?>" value="<?php echo $this->value('menu_timeout') ?>"></label>
		<h3>After the menu times out, do this...</h3>
		<?php
			
		$this->drop_zone();
		
	}
	
	public function twiml() {
		
		$options = $this->value('menu_options');
		$timeout = $this->value('menu_timeout');
		
		if (isset($_POST['Digits'])) {
			$digit = $_POST['Digits'];
			foreach ($options as $key=>$option) {
				if ($option == $digit) {
					$this->run('menu_option_' . $key);
					return;
				}
			}
			$this->prompt_output('invalid');
			$this->response();
			
		} else {
			$args = apply_filters('wp-vbx-menu-gather-arguments', array('timeout'=>$timeout ?: 5), $this);
			$gather = $this->twiml->Gather($args);
			$this->prompt_output('greeting', $gather);
			$this->run();
		}
		
		
		
	}
	
	private function menu_option_controls($index = 0) {
		?>
		<div class="menu_option_controls">
			<?php if ($index > 0) : ?>
			<a href="#" class="remove"><span class="dashicons dashicons-minus"></span></a>
			<?php endif; ?>
			<a href="#" class="add"><span class="dashicons dashicons-plus"></span></a>
		</div>
		<?php
	}
	
}