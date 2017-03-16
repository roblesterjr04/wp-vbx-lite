<?php
	
class WPRLVBX_Applet_Dial extends WPRLVBX_Applet {
	
	public $name = 'Dial';
	public $icon_class = 'dashicons-phone';
		
	public function admin_content() {
		
		$users = get_users(array('meta_query'=>array(
			array(
			'key'=>'vbx_phone_number',
			'compare'=>'!=',
			'value'=>''
			)
		)));
		$groups = $this->get_editable_roles();
				
		$vbx_dial_type = $this->value('vbx_dial_type');
		if (!$vbx_dial_type) $vbx_dial_type = 'user';
		
		$sel_user = $this->value('vbx_dial_user');
		$sel_group = $this->value('vbx_dial_group');
		
		?>
		<h3><?php _e('Dial a group, a user, or a number?', WPRLVBX_TD) ?></h3>
		<label>
			<input type="radio" name="<? $this->field_name('vbx_dial_type') ?>" value="user" <?php echo ($vbx_dial_type == 'user' ? 'checked' : '') ?>> <?php _e('User',WPRLVBX_TD) ?>
			&mdash;&nbsp;<select name="<? $this->field_name('vbx_dial_user') ?>">
				<?php foreach ($users as $user) : ?>
				<option value="<?php echo $user->ID ?>" <?php $this->selected($user->ID, $sel_user) ?>><?php echo $user->display_name ?> (<?php echo $user->user_email ?>)</option>
				<?php endforeach ?>
			</select>
		</label>
		<label>
			<input type="radio" name="<? $this->field_name('vbx_dial_type') ?>" value="group" <?php echo ($vbx_dial_type == 'group' ? 'checked' : '') ?>> <?php _e('Group', WPRLVBX_TD) ?>
			&mdash;&nbsp;<select name="<? $this->field_name('vbx_dial_group') ?>">
				<?php foreach ($groups as $key=>$group) : ?>
				<option value="<?php echo $key ?>" <?php $this->selected($key, $sel_group) ?>><?php echo $group['name'] ?>s</option>
				<?php endforeach ?>
			</select>
		</label>
		<label>
			<input type="radio" name="<? $this->field_name('vbx_dial_type') ?>" value="number" <?php echo ($vbx_dial_type == 'number' ? 'checked' : '') ?>> <?php _e('Number(s):', WPRLVBX_TD) ?>
			&mdash;&nbsp;<input type="text" name="<? $this->field_name('vbx_dial_number') ?>" value="<? echo $this->value('vbx_dial_number') ?>">
		</label>
		
		<h3><?php _e("If there's no answer...", WPRLVBX_TD) ?></h3>
		<?php
			$this->drop_zone('no-answer'); ?>
		<h3><?php _e('When the call is disconnected from the caller...', WPRLVBX_TD) ?></h3>
		<?php
			$this->drop_zone('call-ended'); 
		
				
	}
	
	private function get_editable_roles() {
	    global $wp_roles;
	
	    $all_roles = $wp_roles->roles;
	    $editable_roles = apply_filters('editable_roles', $all_roles);
	
	    return $editable_roles;
	}
	
	public function twiml() {
		
		$status = $this->request('DialCallStatus');
		$number = $this->value('vbx_dial_number');
		$type = $this->value('vbx_dial_type');
		$user = $this->value('vbx_dial_user');
		$group = $this->value('vbx_dial_group');
		
		$dial_stack = array();
		
		if ($status == 'no-answer' || $status == 'busy') {
			$this->run('no-answer');
		} else if ($status == 'completed') {
			$this->run('call-ended');
		} else {
			
			switch($type) {
				case 'user':
					$numbers = explode(',', get_user_meta($user, 'vbx_phone_number', true));
					$dial_stack = array_merge($numbers, $dial_stack);
					break;
				case 'group':
					$group_users = get_users(array('role'=>$group, 'meta_query'=>array(
						array(
						'key'=>'vbx_phone_number',
						'compare'=>'!=',
						'value'=>''
						)
					)));
					foreach ($group_users as $user) {
						$numbers = explode(',', get_user_meta($user->ID, 'vbx_phone_number', true));
						$dial_stack = array_merge($numbers, $dial_stack);
					}
					break;
				case 'number':
					$numbers = explode(',', $number);
					$dial_stack = array_merge($numbers, $dial_stack);
					break;
				default:
					break;
				}
			
			$dial_stack = array_unique($dial_stack);
			
			$dial = $this->twiml->Dial(array('action'=>$this->action));
			foreach($dial_stack as $n) {
				$dial->Number($n);
			}
			
			$this->response();
			
		}
		
	}
	
}