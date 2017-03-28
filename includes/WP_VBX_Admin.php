<?php
	
use Twilio\Twiml;
use Twilio\Rest\Client;
	
class WPRLVBX_Admin {
	
	private $options;
	
	public function __construct() {
		
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_menu', array($this, 'admin_menu'));
		
		add_filter('pre_update_option_wp-vbx-twilio-keys', array($this, 'settings_updated'), 10, 2);
		add_action('admin_notices', array($this, 'numbers_imported'));
		
		session_start();
	}
	
	public function settings_updated($new_value, $old_value) {
		
		if (isset($new_value['twilio_import'])) {
			
			$this->import_numbers($new_value);
			
			unset($new_value['twilio_import']);
		}

		return $new_value;
	}
	
	public function numbers_imported() {
		
		if (!isset($_SESSION['imported_numbers'])) return;
		$nums = $_SESSION['imported_numbers'];
		
		?>
	    <div class="notice notice-success">
	        <p><?php _e( $nums . ' numbers successfully imported.', WPRLVBX_TD ); ?></p>
	    </div>
	    <?php
		    
		unset($_SESSION['imported_numbers']);
		
	}
	
	private function import_numbers($keys) {
		
		if (!isset($keys['twilio_sid']) || !isset($keys['twilio_secret'])) return;
		
		$client = new Client($keys['twilio_sid'], $keys['twilio_secret']);
		
		$numbers = $client->incomingPhoneNumbers->read();
		$successes = 0;
		
		foreach ($numbers as $number) {
			
			$posts = get_posts(array(
				'meta_key'=>'_twilio_number_sid',
				'meta_value'=>$number->sid,
				'post_type'=>'wp-vbx-numbers',
			));
			
			if (count($posts)) continue;
			
			$number_post = array();
			$number_post['post_type'] = 'wp-vbx-numbers';
			$number_post['post_name'] = $number->phoneNumber;
			$number_post['post_title'] = $number->friendlyName;
			$number_post['post_status'] = 'publish';
			$postid = wp_insert_post( $number_post );
			
			update_post_meta( $postid, '_twilio_number', $number->phoneNumber );
			update_post_meta( $postid, '_twilio_number_sid', $number->sid ); 
			update_post_meta( $postid, '_twilio_display_number', $number->friendlyName );
			
			$successes++;
		}
		
		$_SESSION['imported_numbers'] = $successes;
		
	}
	
	public function admin_init() {
		
		register_setting(WPRLVBX_S, 'wp-vbx-twilio-keys' );
		
		add_settings_section(
            'twilio_settings', // ID
            __('Twilio API Keys', WPRLVBX_TD), // Title
            array( $this, 'twilio_info' ), // Callback
            'phone-system-settings' // Page
        );
        
        add_settings_field(
            'twilio_sid', // ID
            __('Twilio Account SID', WPRLVBX_TD), // Title 
            array( $this, 'twilio_sid_callback' ), // Callback
            'phone-system-settings', // Page
            'twilio_settings' // Section           
        );      

        add_settings_field(
            'twilio_secret', 
            __('Twilio Secret KEY', WPRLVBX_TD), 
            array( $this, 'twilio_secret_callback' ), 
            'phone-system-settings', 
            'twilio_settings'
        );
        
        add_settings_field(
	        'twilio_import',
	        __('Import Existing Phone Numbers', WPRLVBX_TD),
	        array( $this, 'twilio_import_numbers' ),
	        'phone-system-settings',
	        'twilio_settings'
        );
				
	}
	
	public function twilio_info() {
		_e('Enter your settings below:', WPRLVBX_TD);
	}
	
	public function twilio_import_numbers() {
		echo '<label><input type="checkbox" id="twilio_import" name="wp-vbx-twilio-keys[twilio_import]" value="1" /> '.__('Import Numbers from Twilio', WPRLVBX_TD).'</label><p class="description">This is for users who already own numbers through Twilio.</p>';
	}
	
	public function twilio_sid_callback()
    {
        printf(
            '<input type="text" id="twilio_sid" name="wp-vbx-twilio-keys[twilio_sid]" value="%s" />', 
            isset( $this->options['twilio_sid'] ) ? esc_attr( $this->options['twilio_sid']) : ''
        );
    }
    
    public function twilio_secret_callback()
    {
        printf(
            '<input type="text" id="twilio_secret" name="wp-vbx-twilio-keys[twilio_secret]" value="%s" />', 
            isset( $this->options['twilio_secret'] ) ? esc_attr( $this->options['twilio_secret']) : ''
        );
    }
	
	public function admin_menu() {
		
		add_menu_page( 'Phone System', 'Phone System', 'read', 'phone-system', null, 'dashicons-phone', 20);
		add_submenu_page( 'phone-system', 'Phone System Settings', 'Settings', 'manage_options', 'phone-system-settings', array($this, 'admin_page'));
		add_submenu_page( 'phone-system', 'Get Started', 'Get Started', 'manage_options', 'vbx-get-started', array($this, 'get_started'));

	}
	
	public function admin_page() {
		
		$this->options = get_option( 'wp-vbx-twilio-keys' );
		
		?>
		<div class="wrap">
			<h1><?php _e('WP VBX Phone System Settings', WPRLVBX_TD) ?></h1>
		
			<form method="post" action="options.php">
				
				<?php settings_fields( WPRLVBX_S ); ?>
				
				<?php do_settings_sections( 'phone-system-settings' ); ?>
				
				<?php submit_button(); ?>
				
			</form>
		
		</div>
		<?php
		
	}
	
	public function get_started() {
		
		$this->options = get_option( 'wp-vbx-twilio-keys' );
		?>
		<style>
			ol.get-started {
				font-size: 24px;
			}	
			ol.get-started li {
				padding: 10px 25px;
			}
		</style>
		<div class="wrap">
			<h1><?php _e('Getting Started with Wordpress VBX', WPRLVBX_TD) ?></h1>
		
			<h2>Get Started Now</h2>
			<?php if (!isset($this->options['twilio_sid'])) : ?>
			<ol class="get-started">
				<li>Sign up for free at Twilio.com. <a href="https://www.twilio.com/try-twilio" target="_blank">Click Here...</a></li>
				<li>Enter your account ID and secret key below</li>
				<li>Create your first call flow</li>
				<li>Buy a number</li>
			</ol>
			
			<form method="post" action="options.php">
				
				<?php settings_fields( WPRLVBX_S ); ?>
				
				<?php do_settings_sections( 'phone-system-settings' ); ?>
				
				<?php submit_button(); ?>
				
			</form>
			<?php endif ?>
			<hr>
			<h2>How to create a call flow</h2>
			<iframe width="560" height="315" src="https://www.youtube.com/embed/sqX7PjGJ7aw" frameborder="0" allowfullscreen></iframe>
		</div>
		<?php
		
	}
	
}
new WPRLVBX_Admin;