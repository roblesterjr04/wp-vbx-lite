<?php

use Twilio\Twiml;
use Twilio\Rest\Client;

/**
 * WP_VBX class.
 */
class WPRLVBX {
	
	public $vbx_admin;
	public $vbx_voicemail;
	public $vbx_flow;
	public $vbx_number;
	public $vbx_logs;
	
	private static $currentPlugin;
	private static $applets;
	public static $client;
	public static $twiml;
	private $type;
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct() {
		
		$this->vbx_voicemail = new WPRLVBX_VoiceMail();
		$this->vbx_flow = new WPRLVBX_CallFlow();
		$this->vbx_number = new WPRLVBX_Numbers();
		
		add_action('init', array($this, 'init'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		
		add_action('admin_head', array($this, 'admin_head'));
		
		add_filter('post_type_link', array($this, 'filter_link'), 10, 2);
		add_filter('user_contactmethods', array($this, 'add_number_to_profile'));
		
		self::$client = $this->register_client();
		self::$twiml = new Twiml();
				
	}
	
	/**
	 * register_client function.
	 * 
	 * @access private
	 * @return void
	 */
	private function register_client() {
		
		$options = get_option('wp-vbx-twilio-keys');
		
		if (isset($options['twilio_sid']) && isset($options['twilio_secret'])
				&& $options['twilio_sid'] != '' && $options['twilio_secret'] != '') {
		
			$client = new Client($options['twilio_sid'], $options['twilio_secret']);
	
			$this->type = $client->accounts($options['twilio_sid'])->fetch()->type;
			if ($this->type == 'Trial') {
				add_action('admin_notices', array($this, 'trial_mode'));
			}
			
			return $client;
		} else {
			add_action('admin_notices', array($this, 'activate_twilio_notice'));
			return false;
		}
	}
	
	/**
	 * activate_twilio_notice function.
	 * 
	 * @access public
	 * @return void
	 */
	public function activate_twilio_notice() {
		
		?>
	    <div class="notice notice-error">
	        <p><?php _e( 'Twilio is not activated. Please go to the settings page and update your API keys.', WPRLVBX_TD ); ?></p>
	    </div>
	    <?php
		
	}
	
	public function trial_mode() {
		
		?>
	    <div class="notice notice-error">
	        <p><?php _e( 'Twilio account is in trial mode. You must upgrade to a full account for the plugin to work.', WPRLVBX_TD ); ?></p>
	    </div>
	    <?php
		
	}
	
	/**
	 * add_number_to_profile function.
	 * 
	 * @access public
	 * @param mixed $methods
	 * @return void
	 */
	public function add_number_to_profile($methods) {
		
		$methods['vbx_phone_number'] = __('WP VBX Device Number', WPRLVBX_TD);
		
		return $methods;
		
	}
	
	/**
	 * filter_link function.
	 * 
	 * @access public
	 * @param mixed $url
	 * @param mixed $post
	 * @param bool $index (default: false)
	 * @return void
	 */
	public function filter_link( $url, $post, $index = false ) {
		
		if ($post->post_type == 'wp-vbx-flows') {
			
			return admin_url( 'admin-ajax.php' ) . '?action=vbx_flow&flow=' . $post->ID . '&index=' . ($index ?: 'WPRLVBX_Applet_Start-0');
			
		}
		
		return $url;
		
	}
	
	/**
	 * admin_head function.
	 * 
	 * @access public
	 * @return void
	 */
	public function admin_head() {
		$url = site_url() . '/wp-admin/admin-ajax.php';
		echo "<script> var site_url = '$url'; </script>";
	}
	
	/**
	 * init function.
	 * 
	 * @access public
	 * @return void
	 */
	public function init() {
		
		if ($this->type == 'Full') {
			
			register_post_status( 'vbx-unheard', array(
				'label'                     => _x( 'New', 'wp-vbx-voicemails' ),
				'public'                    => true,
				//'internal'					=> true,
				//'private'					=> true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'New <span class="count">(%s)</span>', 'New <span class="count">(%s)</span>' ),
			) );
			
						
			$vms = get_posts(array('post_status'=>'vbx-unheard', 'post_type'=>'wp-vbx-voicemails', 'posts_per_page'=>100));
			$vmcount = count($vms);
						
			$this->register_post_type( 'wp-vbx-voicemails', array(
				'name'=>__('Voicemails', WPRLVBX_TD),
				'menu_name'=>__('Voicemails', WPRLVBX_TD) . "<span class='update-plugins count-$vmcount' title=''><span class='update-count'>" . number_format_i18n($vmcount) . "</span></span>", 
				'singular_name'=>__('Voicemail', WPRLVBX_TD), 
				'edit_item'=>__('Listen to Voicemail' , WPRLVBX_TD)
			), array(
				'register_meta_box_cb'=>array($this->vbx_voicemail, 'meta_boxes'),
				'supports'=>array('author'),
				'capabilities'=>array(
					'create_posts'=>false,
				)
			) );
			
			$user = wp_get_current_user();
			if ( in_array( 'administrator', (array) $user->roles ) ) {
			    
			    $this->register_post_type( 'wp-vbx-flows', array(
					'name'=>__('Call Flows', WPRLVBX_TD), 
					'singular_name'=>__('Call flow', WPRLVBX_TD), 
					'add_new_item'=>__('Add New Call flow', WPRLVBX_TD),
				), array(
					'supports'=>array('title'),
					'register_meta_box_cb'=>array($this->vbx_flow, 'meta_boxes'),
					'show_in_admin_bar'=>true,
					
				) );
						
				$this->register_post_type( 'wp-vbx-numbers', array(
					'name'=>__('Phone Numbers', WPRLVBX_TD), 
					'singular_name'=>__('Phone Number', WPRLVBX_TD),
					'add_new_item'=>__('Buy New Number', WPRLVBX_TD),
					'add_new'=>__('Buy Number', WPRLVBX_TD)
				), array(
					'register_meta_box_cb'=>array($this->vbx_number, 'meta_boxes'),
					
				) );
			    
			}
			
			if (isset($_GET['vbx_error'])) {
				if ($_GET['vbx_error'] == 'voice') {
					self::$twiml->Say('There has been an error. Please try again later.');
					echo self::$twiml;
					exit;
				} 
				if ($_GET['vbx_error'] == 'sms') {
					self::$twiml->Message('There has been an error. Please try again later.');
					echo self::$twiml;
					exit;
				}
			}
		}
		
	}
	
	/**
	 * register_post_type function.
	 * 
	 * @access private
	 * @param mixed $slug
	 * @param mixed $labels
	 * @param bool $overrides (default: false)
	 * @return void
	 */
	private function register_post_type($slug, $labels, $overrides = false) {
		
		$args = array(
			'labels'				=>	$labels,
			'public'				=>	false,
			'show_in_nav_menus'		=>	true,
			'show_ui'				=>	true,
			'show_in_admin_bar'		=>	false,
			'show_in_menu'			=>	'phone-system',
			'supports'				=>	false,
			'map_meta_cap'			=>	true,
		);
		
		$args = apply_filters('wp-vbx-post-type-args', $args, $slug, $labels, $overrides);
		
		if ($overrides) {
			foreach ($overrides as $key => $value) {
				$args[$key] = $value;
			}
		}
		
		register_post_type( $slug, $args );
		
	}
	
	/**
	 * enqueue_scripts function.
	 * 
	 * @access public
	 * @return void
	 */
	public function enqueue_scripts() {
				
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_media();
		wp_enqueue_script( 'wp-vbx-admin', plugin_dir_url( __FILE__ ) . '../assets/js/admin.js');
			
		wp_enqueue_style('wp-vbx-styles', plugin_dir_url(__FILE__) . '../assets/css/admin.css');
	}
	
	/**
	 * get_applets function.
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public static function get_applets() {
		$extends = array();
	    foreach(get_declared_classes() as $class) {
		    
	        if(is_subclass_of($class,'WPRLVBX_Applet')) {
		        $extends[$class] = $class;
		    }
	    }
	    return $extends;
	}
	
}
new WPRLVBX;