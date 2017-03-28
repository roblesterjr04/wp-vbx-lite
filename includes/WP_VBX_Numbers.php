<?php

use Twilio\Twiml;
use Twilio\Rest\Client;

class WPRLVBX_Numbers {
	
	public function __construct() {
		
		add_filter('gettext', array($this, 'publish_button_text'), 10, 2);
		add_action('wp_ajax_search_numbers', array($this, 'twilio_number_search'));
		add_action('save_post', array($this, 'save_number'));
		//add_action('before_delete_post', array($this, 'delete_number'));
		add_filter('post_row_actions',array($this, 'release_action_link'), 10, 2);
		add_filter('the_title', array($this, 'the_title'), 10, 2);
		add_action('admin_init', array($this, 'release_number'));
		
	}
	
	public function the_title($title, $post) {
		
		$number = get_post_meta( $post, '_twilio_display_number', true );
		return $number ?: $title;
		
	}
	
	public function release_action_link($actions, $post) {
		if ($post->post_type == 'wp-vbx-numbers' && $post->post_status == 'trash') {
			$link = wp_nonce_url('post.php?post_type=wp-vbx-numbers&action=release&post='.$post->ID, 'release', 'wp-vbx-release');
			$actions['release'] = '<a href="'.$link.'">Delete Permanently & Release</a>';
		}
		return $actions;
	}

	public function release_number() {
				
		if (isset($_GET['post']) 
			&& isset($_GET['action']) 
			&& $_GET['action'] == 'release'
			&& isset($_GET['post_type'])
			&& $_GET['post_type'] == 'wp-vbx-numbers'
			&& isset($_GET['wp-vbx-release'])
		) {

			if (!wp_verify_nonce($_GET['wp-vbx-release'], 'release')) die('Unauthorized');
			
			$postid = $_GET['post'];
			
			if ( wp_is_post_revision( $postid ) )
			return;
		
			$post_type = get_post_type($postid);
			if ( "wp-vbx-numbers" != $post_type ) return;
			
			$sid = get_post_meta( $postid, '_twilio_number_sid', true);
			
			twilio()->incomingPhoneNumbers($sid)->delete();
			
			wp_delete_post( $postid, true);
			
			wp_redirect( admin_url('edit.php?post_type=' . $_GET['post_type'] ) );
			
			do_action( 'wp-vbx-release-number' );
			exit;
			
		}
					
	}
	
	public function save_number( $postid ) {
				
		if ( wp_is_post_revision( $postid ) )
			return;
		
		$post_type = get_post_type($postid);
		if ( "wp-vbx-numbers" != $post_type ) return;
		
		if (!isset( $_POST['wp_vbx'] ) 
			|| !wp_verify_nonce( $_POST['wp_vbx'], 'save_number' )) return;
			
		if (isset($_POST['selected_number'])) {
			update_post_meta( $postid, '_twilio_number', $_POST['selected_number'] );
			
			// Debug code for buying numbers. If debug is set to true, test creds are used and no number is purchased.
			$client = twilio();
			
			$number = $client->incomingPhoneNumbers
			    ->create(
			        array(
				        "voiceUrl" => get_permalink( $_POST['_call_flow'] ),
				        "smsUrl"=>get_permalink( $_POST['_message_flow'] ),
			            "phoneNumber" => $_POST['selected_number']
			        )
			    );
			    
			update_post_meta( $postid, '_twilio_number_sid', $number->sid ); 
			update_post_meta( $postid, '_twilio_display_number', $number->friendlyName );  
		} else {
			$sid = get_post_meta( $postid, '_twilio_number_sid', true );
			$client = twilio();
			$number = $client->incomingPhoneNumbers($sid)->update(array(
				'voiceUrl'=>$_POST['_call_flow'] ? get_permalink( $_POST['_call_flow'] ) : '',
				'smsUrl'=>$_POST['_message_flow'] ? get_permalink( $_POST['_message_flow'] ) : '',
				'voiceFallbackUrl'=>$_POST['_call_flow'] ? site_url() . '?vbx_error=voice' : '',
				'smsFallbackUrl'=>$_POST['_message_flow'] ? site_url() . '?vbx_error=sms' : ''
			));
		}
		
		update_post_meta( $postid, '_call_flow', $_POST['_call_flow'] );
		update_post_meta( $postid, '_message_flow', $_POST['_message_flow'] );
				
	}
	
	public function meta_boxes() {
		
		global $post;
		
		add_meta_box( 'vbx-number-flow', 'Execute Call Flow', array($this, 'flow_box'), 'wp-vbx-numbers' );
		
		if ($post->post_status != 'publish') {
			add_meta_box( 'vbx-new-number', 'Search for Number', array($this, 'number_box'), 'wp-vbx-numbers' );
		} else {
			
		}
		
	}
	
	public function flow_box( $post ) {
		
		$call_flow = get_post_meta( $post->ID, '_call_flow', true);
		$mess_flow = get_post_meta( $post->ID, '_message_flow', true);
		$flows = get_posts( array('post_type'=>'wp-vbx-flows', 'post_status'=>'publish') );
		$number = get_post_meta( $post->ID, '_twilio_display_number', true );
		?>
		<?php wp_nonce_field( 'save_number', 'wp_vbx' ) ?>
		<h1><?php echo $number ?></h1>
		<br>
		<h3>Call Flow</h3>
		<select name="_call_flow" class="widefat">
			<option value="">Do Nothing</option>
			<?php foreach ($flows as $flow) : ?>
			<option value="<?php echo $flow->ID ?>" <?php echo ($flow->ID == $call_flow ? 'selected' : '') ?>><?php echo $flow->post_title ?></option>
			<?php endforeach ?>
		</select>
		<h3>Message Flow</h3>
		<select name="_message_flow" class="widefat">
			<option value="">Do Nothing</option>
			<?php foreach ($flows as $flow) : ?>
			<option value="<?php echo $flow->ID ?>" <?php echo ($flow->ID == $mess_flow ? 'selected' : '') ?>><?php echo $flow->post_title ?></option>
			<?php endforeach ?>
		</select>
		<input type="hidden" name="time_force" value="<?php echo time() ?>">
		<?php
		
	}
	
	public function number_box( $post ) {
		?>
		<?php wp_nonce_field( 'save_number', 'wp_vbx' ) ?>
		<h1><?php _e('Search Criteria', WPRLVBX_TD) ?>:</h1>
		<input class="widefat" type="text" name="vbx_search" placeholder="<?php _e('Ex: 123; LAW; HELP; 332-33**', WPRLVBX_TD) ?>">
		<hr>
		Region: <select name="vbx_state">
			<?php foreach($this->states() as $key=>$state) : ?>
				<option value="<?php echo $key ?>"><?php echo $state ?></option>
			<?php endforeach ?>
			
		</select>
		<p><button href="#" class="button button-primary button-large vbx-search" style="float:right"><?php _e('Search', WPRLVBX_TD) ?></button></p>
		<div style="clear: both;"></div>
		<div class="search-results" style="display: none"></div>
		<?php
	}
	
	public function publish_button_text( $translation, $text ) {
		
		if (!isset($_GET['post_type'])) return $translation;
		if ($text == 'Publish' && $_GET['post_type'] == 'wp-vbx-numbers') return __('Purchase', WPRLVBX_TD);
		return $translation;
		
	}
	
	public function twilio_number_search() {
		
		$search = $_POST['search'];
		$state = $_POST['state'];
		
		$client = twilio();
		
		try {
			$numbers = $client->availablePhoneNumbers('US')->local->read(
			    array("contains" => $search, 'inRegion'=>$state)
			);
		} catch(Exception $e) {
			$numbers = array();
		}
				
		$numbers = apply_filters( 'wp-vbx-available-numbers-results', $numbers, $search, $state, $client );
		
		ob_start();
		
		if (count($numbers)) :
		
		?>
		<h3><?php echo count($numbers) ?> <?php _e('Available', WPRLVBX_TD) ?></h3>
		<table class="widefat">
			<thead>
				<tr>
					<th></th>
					<th><?php _e('Number', WPRLVBX_TD) ?></th>
					<th><?php _e('City/Town', WPRLVBX_TD) ?></th>
					<th><?php _e('State', WPRLVBX_TD) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($numbers as $number) : do_action('wp-vbx-available-number', $number); ?>
				<tr>
					<td><input name="selected_number" type="radio" value="<?php echo $number->phoneNumber ?>"></td>
					<td><?php echo $number->friendlyName ?></td>
					<td><?php echo $number->rateCenter ?></td>
					<td><?php echo $number->region ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
		
		<?php
		
		else :
		
		?><h3>No numbers found with that criteria.</h3><?php
		
		endif;
			
		do_action( 'wp-vbx-available-numbers-search' );
			
		echo apply_filters( 'wp-vbx-available-numbers-ajax-table', ob_get_clean(), $numbers, $search, $state, $client );
		
		exit;
	}
	
	public function states() {
		
		$states = array(
		    'AL'=>'Alabama',
		    'AK'=>'Alaska',
		    'AZ'=>'Arizona',
		    'AR'=>'Arkansas',
		    'CA'=>'California',
		    'CO'=>'Colorado',
		    'CT'=>'Connecticut',
		    'DE'=>'Delaware',
		    'DC'=>'District of Columbia',
		    'FL'=>'Florida',
		    'GA'=>'Georgia',
		    'HI'=>'Hawaii',
		    'ID'=>'Idaho',
		    'IL'=>'Illinois',
		    'IN'=>'Indiana',
		    'IA'=>'Iowa',
		    'KS'=>'Kansas',
		    'KY'=>'Kentucky',
		    'LA'=>'Louisiana',
		    'ME'=>'Maine',
		    'MD'=>'Maryland',
		    'MA'=>'Massachusetts',
		    'MI'=>'Michigan',
		    'MN'=>'Minnesota',
		    'MS'=>'Mississippi',
		    'MO'=>'Missouri',
		    'MT'=>'Montana',
		    'NE'=>'Nebraska',
		    'NV'=>'Nevada',
		    'NH'=>'New Hampshire',
		    'NJ'=>'New Jersey',
		    'NM'=>'New Mexico',
		    'NY'=>'New York',
		    'NC'=>'North Carolina',
		    'ND'=>'North Dakota',
		    'OH'=>'Ohio',
		    'OK'=>'Oklahoma',
		    'OR'=>'Oregon',
		    'PA'=>'Pennsylvania',
		    'RI'=>'Rhode Island',
		    'SC'=>'South Carolina',
		    'SD'=>'South Dakota',
		    'TN'=>'Tennessee',
		    'TX'=>'Texas',
		    'UT'=>'Utah',
		    'VT'=>'Vermont',
		    'VA'=>'Virginia',
		    'WA'=>'Washington',
		    'WV'=>'West Virginia',
		    'WI'=>'Wisconsin',
		    'WY'=>'Wyoming',
		);
		
		return $states;
		
	}
	
}