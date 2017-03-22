<?php
	
class WPRLVBX_CallFlow {
	
	private $flow;
	
	public function __construct() {
		
		add_action('wp_ajax_get_applet_ui', array($this, 'get_applet_ui'));
		add_action('wp_ajax_vbx_flow', array($this, 'execute_flow'));
		add_action('wp_ajax_nopriv_vbx_flow', array($this, 'execute_flow'));
		add_action('save_post', array($this, 'save_flow'));
		
	}
	
	public function meta_boxes() {
		
		add_meta_box( 'applet-select', 'Applets', array($this, 'applet_meta_box'), 'wp-vbx-flows', 'side');
		
		$flow = unserialize(get_post_meta( get_the_ID(), '_call_flow', true ));
		
		$flow = apply_filters( 'wp-vbx-flow-data', $flow, get_the_ID() );
			
		if ($flow) {
			
			foreach ($flow as $index=>$flowstep) {
				
				if (isset($flowstep['this'])) {
					if ($flowstep['this'] != 'WPRLVBX_Applet_Start-0')
						add_filter('postbox_classes_wp-vbx-flows_applet-' . $flowstep['this'], array($this, 'meta_classes'));
					$class_parts = explode('-', $flowstep['this']);
					if (class_exists($class_parts[0])) {
						$applet = new $class_parts[0]($index);
						$applet->meta_box();
					}
				}
				
			}
			
		} else {
		
			$start = new WPRLVBX_Applet_Start(0, get_the_ID());
			$start->meta_box();
		
		}
	}
	
	public function meta_classes($classes = array()) {
		
		$classes[] = apply_filters('wp-vbx-flow-meta-box-class', 'step-hidden');
		return $classes;
		
	}
	
	public function get_applet_ui() {
				
		$id = $_POST['data']['wp_applet_id'];
		$index = $_POST['data']['wp_index'];
		$postid = $_POST['data']['wp_post'];
			
		if (!class_exists($id)) exit;	
		$applet = new $id($index, $postid);
		$applet->ajax_box();
		
		exit;
		
	}
	
	public function applet_meta_box( $post ) {
		
		$applets = WPRLVBX::get_applets();
		
		do_action( 'wp-vbx-applets-loaded', $applets);
		
		?>
		
		<div class="applets ui-draggable" id="menu-to-edit"> 
			<?php foreach ($applets as $key=>$value) : $applet = new $value(); ?>
			<?php if ($applet->id == 'WPRLVBX_Applet_Start') continue; ?>
			<?php $applet->item() ?>
			<?php endforeach ?>
		</div>
		
		<?php
		
	}
	
	public function execute_flow() {
		
		header('Content-type: text/xml');
		
		$id = $_GET['flow'];
		$index = $_GET['index'];
		
		$flowpost = get_post($id);
		
		do_action( 'wp-vbx-before-execute-applet', $flowpost );
		
		if ($flowpost->post_status == 'publish') {
		
			$this->flow = unserialize(get_post_meta( $id, '_call_flow', true ));
			
			$start = $this->get_applet($index);
	
			if ($start !== null) {
				$start->twiml();
				exit;
			}
		
		}
		
		WPRLVBX::$twiml->Hangup();
		echo WPRLVBX::$twiml;
		
		do_action( 'wp-vbx-failed-execute-applet' );
		exit;		
	}
	
	private function get_applet($applet_name) {

		foreach ($this->flow as $flow) {

			if ($flow['this'] == $applet_name) {
				$class_parts = explode('-', $flow['this']);
				do_action( 'wp-vbx-get-applet-' . $applet_name );
				return new $class_parts[0]($class_parts[1], $_GET['flow']);
			}
			
		}
		return null;
		
	}
	
	public function save_flow( $post_id ) {
		
		if (!isset($_POST['flow'])) return;
		
		$flow = $_POST['flow'];
		
		$post_type = get_post_type($post_id);
		if ( "wp-vbx-flows" != $post_type ) return;
		
		//if ($flow[0]['next'] == '') $flow = array();
		
		update_post_meta( $post_id, '_call_flow', esc_sql(serialize($flow)) );
				
	}
}