<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class RevisionaryAdminFiltersItemUI {

	var $meta_box_ids = array();
	var $pending_revisions = array();
	var $future_revisions = array();
	
	function RevisionaryAdminFiltersItemUI () {
		add_action('admin_menu', array(&$this, 'add_meta_boxes'));
		add_action('do_meta_boxes', array(&$this, 'act_tweak_metaboxes') );
		
		add_action('admin_head', array(&$this, 'add_js') );
	}
	
	function add_js() {
		$src_name = 'post';
		$object_type = ( strpos( $_SERVER['REQUEST_URI'], 'page' ) ) ? 'page' : 'post';
		
		$object_id = rvy_detect_post_id();
		
		if ( ! $object_id || agp_user_can( "edit_{$object_type}", $object_id, '', array( 'skip_revision_allowance' => true ) ) )
			return;
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$('#publish').val('Submit Revision');
});
/* ]]> */
</script>
<?php
	}
	
	function add_meta_boxes() {
		if ( rvy_get_option( 'pending_revisions' ) ) {
			require_once( 'revision-ui_rvy.php' );
			add_meta_box( 'pending_revisions', __( 'Pending Revisions', 'revisionary'), create_function( '', "rvy_metabox_revisions('pending');"), 'post' );
			add_meta_box( 'pending_revisions', __( 'Pending Revisions', 'revisionary'), create_function( '', "rvy_metabox_revisions('pending');"), 'page' );
				
			add_meta_box( 'pending_revision_notify', __( 'Publishers to Notify of Your Revision', 'revisionary'), create_function( '', "rvy_metabox_notification_list('pending_revision');"), 'post' );
			add_meta_box( 'pending_revision_notify', __( 'Publishers to Notify of Your Revision', 'revisionary'), create_function( '', "rvy_metabox_notification_list('pending_revision');"), 'page' );
		}
			
		if ( rvy_get_option( 'scheduled_revisions' ) ) {
			require_once( 'revision-ui_rvy.php' );
			add_meta_box( 'future_revisions', __( 'Scheduled Revisions', 'revisionary'), create_function( '', "rvy_metabox_revisions('future');"), 'post' );
			add_meta_box( 'future_revisions', __( 'Scheduled Revisions', 'revisionary'), create_function( '', "rvy_metabox_revisions('future');"), 'page' );
		}
	}
	
	function act_tweak_metaboxes() {
		static $been_here;
		
		if ( isset($been_here) )
			return;

		$been_here = true;
		
		global $wp_meta_boxes;
		
		if ( empty($wp_meta_boxes) )
			return;
		
		$src_name = 'post';
		$object_type = ( strpos( $_SERVER['REQUEST_URI'], 'page' ) ) ? 'page' : 'post';
		
		if ( empty($wp_meta_boxes[$object_type]) )
			return;
		
		//$object_id = $this->scoper->data_sources->detect('id', $src_name, '', $object_type);
		$object_id = rvy_detect_post_id();
		
		// This block will be moved to separate class
		foreach ( $wp_meta_boxes[$object_type] as $context => $priorities ) {
			foreach ( $priorities as $priority => $boxes ) {
				foreach ( array_keys($boxes) as $box_id ) {
					// Remove Scheduled / Pending Revisions metabox if none will be listed
					// If a listing does exist, buffer it for subsequent display
					if ( 'pending_revisions' == $box_id ) {
						if ( ! $this->pending_revisions = rvy_list_post_revisions( $object_id, 'pending', array( 'format' => 'list', 'parent' => false, 'echo' => false ) ) )
							unset( $wp_meta_boxes[$object_type][$context][$priority][$box_id] );
					
					} elseif ( 'future_revisions' == $box_id ) {
						if ( ! $this->future_revisions = rvy_list_post_revisions( $object_id, 'future', array( 'format' => 'list', 'parent' => false, 'echo' => false ) ) )
							unset( $wp_meta_boxes[$object_type][$context][$priority][$box_id] );
							
					// Remove Revision Notification List metabox if this user is NOT submitting a pending revision
					} elseif ( 'pending_revision_notify' == $box_id ) {
						if ( ! $object_id || agp_user_can( "edit_{$object_type}", $object_id, '', array( 'skip_revision_allowance' => true ) ) )
							unset( $wp_meta_boxes[$object_type][$context][$priority][$box_id] );
					}
				}
			}
		}
				
	}

} // end class

?>