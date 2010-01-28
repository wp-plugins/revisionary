<?php
// menu icons by Jonas Rask: http://www.jonasraskdesign.com/
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

define ('RVY_URLPATH', WP_CONTENT_URL . '/plugins/' . RVY_FOLDER);


class RevisionaryAdmin
{
	var $tinymce_readonly;

	var $revision_save_in_progress;
	var $impose_pending_rev;
	
	function RevisionaryAdmin() {
		add_action('admin_head', array(&$this, 'admin_head'));
		
		if ( ! defined('XMLRPC_REQUEST') && ! strpos($_SERVER['SCRIPT_NAME'], 'p-admin/async-upload.php' ) ) {
			add_action('admin_menu', array(&$this,'build_menu'));
			
			if ( strpos($_SERVER['SCRIPT_NAME'], 'p-admin/plugins.php') ) {
				if ( awp_ver( '2.8' ) )
					add_filter( 'plugin_row_meta', array(&$this, 'flt_plugin_action_links'), 10, 2 );
				else
					add_filter( 'plugin_action_links', array(&$this, 'flt_plugin_action_links'), 10, 2 );
			}
		}
		
		add_action('admin_footer-edit.php', array(&$this, 'act_hide_quickedit_for_revisions') );
		add_action('admin_footer-edit-pages.php', array(&$this, 'act_hide_quickedit_for_revisions') );
		
		add_action('admin_head', array(&$this, 'act_hide_admin_divs') );
		
		
		$script_name = $_SERVER['SCRIPT_NAME'];							// possible todo: separate file for term edit
		$item_edit_scripts = apply_filters( 'item_edit_scripts_rvy', array('p-admin/post-new.php', 'p-admin/post.php', 'p-admin/page.php', 'p-admin/page-new.php', 'p-admin/categories.php') );
		$item_edit_scripts []= 'p-admin/admin-ajax.php';

		foreach( $item_edit_scripts as $edit_script ) {
			if ( strpos( $script_name, $edit_script ) ) {
				global $revisionary;
				
				require_once( 'filters-admin-ui-item_rvy.php' );
				$revisionary->filters_admin_item_ui = new RevisionaryAdminFiltersItemUI();
				break;
			}
		}
		
		if ( ! defined( 'SCOPER_VERSION' ) || defined( 'USE_RVY_RIGHTNOW' ) )
			require_once( 'admin-dashboard_rvy.php' );	
		
		// log this action so we know when to ignore the save_post action
		add_action('inherit_revision', array(&$this, 'act_log_revision_save') );

		add_action('pre_post_type', array(&$this, 'flt_detect_revision_save') );
		
	
		if ( rvy_get_option( 'pending_revisions' ) ) {
			if ( strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/edit.php') 
			|| strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/edit-pages.php')
			|| ( strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/post.php') )
			|| ( strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/page.php') )
			|| false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-revisions')
			) {
				add_filter( 'the_title', array(&$this, 'flt_post_title'), 10, 2 );
				add_filter( 'get_edit_post_link', array(&$this, 'flt_edit_post_link'), 10, 3 );
				add_filter( 'post_link', array(&$this, 'flt_preview_post_link'), 10, 2 );
			}
			
			// special filtering to support Contrib editing of published posts/pages to revision
			add_filter('pre_post_status', array(&$this, 'flt_pendingrev_post_status') );
			add_action('pre_post_update', array(&$this, 'act_impose_pending_rev'), 2 );
		}
		
		if ( rvy_get_option('scheduled_revisions') ) {
			// users who have edit_published capability for post/page can create a scheduled revision by modifying post date to a future date (without setting "future" status explicitly)
			add_filter( 'wp_insert_post_data', array(&$this, 'flt_insert_post_data'), 99, 2 );
			add_action('pre_post_update', array(&$this, 'act_create_scheduled_rev'), 3 );  // other filters will have a chance to apply at actual publish time
		}
		

		$script_name = $_SERVER['SCRIPT_NAME'];
		
		// ===== Special early exit if this is a plugin install script
		if ( strpos($script_name, 'p-admin/plugins.php') || strpos($script_name, 'p-admin/plugin-install.php') || strpos($script_name, 'p-admin/plugin-editor.php') )
			return; // no further filtering on WP plugin maintenance scripts
		
		// low-level filtering for miscellaneous admin operations which are not well supported by the WP API
		$hardway_uris = array(
		'p-admin/index.php',		'p-admin/revision.php',			'admin.php?page=rvy-revisions',
		'p-admin/post.php', 		'p-admin/post-new.php', 		'p-admin/page.php', 		'p-admin/page-new.php', 
		'p-admin/link-manager.php', 'p-admin/edit.php', 			'p-admin/edit-pages.php', 	'p-admin/edit-comments.php', 
		'p-admin/categories.php', 	'p-admin/link-category.php', 	'p-admin/edit-link-categories.php', 'p-admin/upload.php',
		'p-admin/edit-tags.php', 	'p-admin/profile.php',			'p-admin/link-add.php',	'p-admin/admin-ajax.php' );

		$hardway_uris = apply_filters('rvy_admin_hardway_uris', $hardway_uris);

		$uri = urldecode($_SERVER['REQUEST_URI']);
		foreach ( $hardway_uris as $uri_sub ) {	// index.php can only be detected by index.php, but 3rd party-defined hooks may include arguments only present in REQUEST_URI
			if ( defined('XMLRPC_REQUEST') || strpos($script_name, $uri_sub) || strpos($uri, $uri_sub) ) {
				require_once(RVY_ABSPATH . '/hardway/hardway-admin_rvy.php');
				break;
			}
		}
		
		if ( strpos( $_SERVER['REQUEST_URI'], 'edit.php' ) || strpos( $_SERVER['REQUEST_URI'], 'edit-pages.php' ) )
			add_filter( 'get_post_time', array(&$this, 'flt_get_post_time'), 10, 3 );
	}
	
	function act_log_revision_save() {
		$this->revision_save_in_progress = true;
	}
	
	function flt_detect_revision_save( $post_type ) {
		if ( 'revision' == $post_type )
			$this->revision_save_in_progress = true;
	
		return $post_type;
	}
	
	// adds an Options link next to Deactivate, Edit in Plugins listing
	function flt_plugin_action_links($links, $file) {
		if ( $file == RVY_BASENAME ) {
			if ( awp_ver('2.8') )
				$links[] = "<a href='http://agapetry.net/forum/'>" . __awp('Support Forum') . "</a>";
			
			$page = ( IS_MU_RVY ) ? 'rvy-site_options' : 'rvy-options';
			$links[] = "<a href='admin.php?page=$page'>" . __awp('Options') . "</a>";
		}
			
		return $links;
	}
	
	function admin_head() {
		echo '<link rel="stylesheet" href="' . RVY_URLPATH . '/admin/revisionary.css" type="text/css" />'."\n";

		if ( false !== strpos(urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-about') )
			echo '<link rel="stylesheet" href="' . RVY_URLPATH . '/admin/about/about.css" type="text/css" />'."\n";

		add_filter( 'contextual_help_list', array(&$this, 'flt_contextual_help_list'), 10, 2 );
			
		if( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-revisions' ) ) {
			
			// add Ajax goodies we need for fancy publish date editing in Revisions Manager and role duration/content date limit editing Bulk Role Admin
			
			//wp_print_scripts( array( 'post' ) );	 // WP 2.9 broke this for Revisionary usage; manually insert pertinent scripts below instead
			echo "\n" . "<script type='text/javascript' src='" . RVY_URLPATH . "/admin/revision-edit.js'></script>";
			
			if ( ( empty( $_GET['action'] ) || 'view' == $_GET['action'] ) && ! empty( $_GET['revision'] ) ) {
				if ( $revision =& get_post( $_GET['revision'] ) ) {
					if ( ( 'revision' != $revision->post_type ) || $post =& get_post( $revision->post_parent ) ) {
				
						// determine if tinymce textarea should be editable for displayed revision
						global $current_user;

						if ( 'revision' != $revision->post_type ) // we retrieved the parent (current revision) that corresponds to requested revision
							$read_only = true;

						elseif ( ( 'pending' == $revision->post_status ) && ( $revision->post_author == $current_user->ID ) )
							$read_only = false;
						else
							$read_only = ! current_user_can( "edit_{$post->post_type}", $revision->post_parent );
						
						$this->tinymce_readonly = $read_only;
						
						require_once( 'revision-ui_rvy.php' );
						
						add_filter( 'tiny_mce_before_init', 'rvy_tiny_mce_params', 98 );
					
						if ( $read_only )
							add_filter( 'tiny_mce_before_init', 'rvy_tiny_mce_readonly', 99 );
							
						wp_tiny_mce();
					}
				}
			}
			
			// need this for editor swap from visual to html
			if ( empty($read_only) )
				wp_print_scripts( 'editor', 'quicktags' );
			else {
				wp_print_scripts( 'editor' );
				
// if the revision is read-only, also disable the HTML editing area and kill the toolbar which the_editor() forces in
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$('#ed_toolbar').hide();
	$('#content').attr('disabled', 'disabled');
});
/* ]]> */
</script>
<?php	
			} // endif read_only
	
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$('#rvy-rev-checkall').click(function() {
		$('.rvy-rev-chk').attr( 'checked', this.checked );
	});
});
/* ]]> */
</script>
<?php	
			require_once( 'revision-ui_rvy.php' );
			rvy_revisions_js();
		}
		
		// all required JS functions are present in Role Scoper JS; TODO: review this for future version changes as necessary
		// TODO: replace some of this JS with equivalent JQuery
		if ( ! defined('SCOPER_VERSION') )
			echo "\n" . "<script type='text/javascript' src='" . RVY_URLPATH . "/admin/revisionary.js'></script>";
	}
	
	function flt_contextual_help_list ($help, $screen) {
		if ( in_array( $screen, array( 'edit', 'edit-pages', 'page', 'post', 'settings_page_rvy-revisions', 'settings_page_rvy-options' ) ) ) {
			if ( ! isset($help[$screen]) )
				$help[$screen] = '';

			//$help[$screen] .= sprintf(__('%1$s Revisionary Documentation%2$s', 'revisionary'), "<a href='http://agapetry.net/downloads/Revisionary_UsageGuide.htm#$link_section' target='_blank'>", '</a>')
			$help[$screen] .= ' ' . sprintf(__('%1$s Revisionary Support Forum%2$s', 'revisionary'), "<a href='http://agapetry.net/forum/' target='_blank'>", '</a>');
			
			if ( current_user_can( 'manage_options' ) )
				$help[$screen] .= ', ' . sprintf(__('%1$s About Revisionary%2$s', 'revisionary'), "<a href='admin.php?page=rvy-about' target='_blank'>", '</a>');
		}

		return $help;
	}
	
	
			
	function build_menu() {
		$path = RVY_ABSPATH;
	
		// For Revisions Manager access, satisfy WordPress' demand that all admin links be properly defined in menu
		if ( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-revisions' ) ) {
			add_options_page( __('Revisions', 'revisionary'), __('Revisions', 'revisionary'), 'read', 'rvy-revisions');
			
			$func = "include_once('$path' . '/admin/revisions.php');";
			add_action( 'settings_page_rvy-revisions' , create_function( '', $func ) );
		}

		if ( ! current_user_can( 'manage_options' ) )
			return;
		
		if ( false !== strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-about' ) ) {	
			add_options_page( __('About Revisionary', 'revisionary'), __('About Revisionary', 'revisionary'), 'read', 'rvy-about');
			
			$func = "include_once('$path' . '/admin/about.php');";
			add_action( 'settings_page_rvy-about' , create_function( '', $func ) );
		}
			
		// WP MU site options
		if ( IS_MU_RVY ) {
			// RS Site Options
			add_submenu_page('wpmu-admin.php', __('Revisionary Options', 'revisionary'), __('Revisionary Options', 'revisionary'), 'read', 'rvy-site_options');
			
			$func = "include_once('$path' . '/admin/options.php');rvy_options( true );";
			add_action('wpmu-admin_page_rvy-site_options', create_function( '', $func ) );	

			global $rvy_default_options, $rvy_options_sitewide;
			
			// omit Option Defaults menu item if all options are controlled sitewide
			if ( empty($rvy_default_options) )
				rvy_refresh_default_options();
			
			if ( count($rvy_options_sitewide) != count($rvy_default_options) ) {
				// RS Default Options (for per-blog settings)
				add_submenu_page('wpmu-admin.php', __('Revisionary Option Defaults', 'revisionary'), __('Revisionary Defaults', 'revisionary'), 'read', 'rvy-default_options');
				
				$func = "include_once('$path' . '/admin/options.php');rvy_options( false, true );";
				add_action('wpmu-admin_page_rvy-default_options', create_function( '', $func ) );	
			}
		}
		
		// omit Blog-Specific Options menu item if all options are controlled sitewide
		if ( ! IS_MU_RVY || ( count($rvy_options_sitewide) != count($rvy_default_options) ) ) {
			add_options_page( __('Revisionary Options', 'revisionary'), __('Revisionary', 'revisionary'), 'read', 'rvy-options');

			$func = "include_once('$path' . '/admin/options.php');rvy_options( false );";
			add_action('settings_page_rvy-options', create_function( '', $func ) );	
		}
	}
	
	function act_hide_quickedit_for_revisions() {
		global $rvy_any_listed_revisions;
		
		if ( $rvy_any_listed_revisions )
			echo "<div id='rs_hide_quickedit'></div>";
	}

	
	function act_hide_admin_divs() {
		// Hide unrevisionable elements if editing for revisions, regardless of Limited Editing Element settings
		//
		// TODO: allow revisioning of slug, menu order, comment status, ping status ?
		// TODO: leave Revisions metabox for links to user's own pending revisions
		if ( rvy_get_option( 'pending_revisions' ) ) {
			$object_type = ( strpos( $_SERVER['REQUEST_URI'], 'page' ) ) ? 'page' : 'post';
						
			//global $scoper;
			//$object_id = $scoper->data_sources->detect( 'id', $context->source );
			$object_id = rvy_detect_post_id();
			
			$can_edit = agp_user_can( "edit_{$object_type}", $object_id, '', array( 'skip_revision_allowance' => true ) );

			if ( $object_id && ! $can_edit ) {
				if ( 'page' == $object_type )
					$unrevisable_css_ids = array( 'pageparentdiv', 'authordiv', 'pageauthordiv', 'postcustom', 'pagecustomdiv', 'pageslugdiv', 'commentstatusdiv', 'pagecommentstatusdiv', 'password-span', 'visibility', 'edit-slug-box' );
			 	else
					$unrevisable_css_ids = array( 'categorydiv', 'authordiv', 'postcustom', 'customdiv', 'slugdiv', 'commentstatusdiv', 'password-span', 'trackbacksdiv',  'tagsdiv-post_tag', 'visibility', 'edit-slug-box' );
					
				echo( "\n<style type='text/css'>\n<!--\n" );
					
				foreach ( $unrevisable_css_ids as $id ) {
					// TODO: determine if id is a metabox or not
					
					// thanks to piemanek for tip on using remove_meta_box for any core admin div
					remove_meta_box($id, $object_type, 'normal');
					remove_meta_box($id, $object_type, 'advanced');
					
					// also hide via CSS in case the element is not a metabox
					echo "#$id { display: none !important; }\n";  // this line adapted from Clutter Free plugin by Mark Jaquith
				}
					
				echo "-->\n</style>\n";
				
				// display the current status, but hide edit link
				echo "\n<style type='text/css'>\n<!--\n.edit-post-status { display: none !important; }\n-->\n</style>\n";  // this line adapted from Clutter Free plugin by Mark Jaquith
			}
		}	
	}

	function convert_link( $link, $topic, $operation, $args = '' ) {
		$defaults = array ( 'object_type' => '', 'id' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		if ( 'revision' == $topic ) {
			if ( 'manage' == $operation ) {
				if ( strpos( $link, 'revision.php' ) ) {
					$link = str_replace( 'revision.php', 'admin.php?page=rvy-revisions', $link );
					$link = str_replace( '?revision=', "&amp;revision=", $link );
				}
			
			} elseif ( 'preview' == $operation ) {
				$link .= "&post_type=revision&preview=1";

			} elseif ( 'delete' == $operation ) {
				if ( $object_type && $id ) {
					$link = str_replace( "$object_type.php", 'admin.php?page=rvy-revisions', $link );
					$link = str_replace( '?post=', "&amp;revision=", $link );
					$link = wp_nonce_url( $link, 'delete-revision_' . $id );
				}
			} 
		}
		
		return $link;
	}
	
	function flt_edit_post_link( $link, $id, $context ) {
		if ( $post = &get_post( $id ) )
			if ( 'revision' == $post->post_type ) {
				$link = RevisionaryAdmin::convert_link( $link, 'revision', 'manage' );
			
				global $rvy_any_listed_revisions;
				$rvy_any_listed_revisions = true;
			}
		return $link;
	}
	
	function flt_preview_post_link( $link, $post ) {
		if ( 'revision' == $post->post_type )
			$link = RevisionaryAdmin::convert_link( $link, 'revision', 'preview' );

		return $link;
	}
	
	
	function flt_post_title ( $title, $id = '' ) {
		if ( $id )
			if ( $post =& get_post( $id ) )
				if ( 'revision' == $post->post_type )
					$title = sprintf( _x( '%s (revision)', 'post_title (revision)', 'revisionary' ), $post->post_title );

		return $title;
	}
	
	// only added for edit.php and edit-pages.php
	function flt_get_post_time( $time, $format, $gmt ) {
		if ( function_exists('get_the_ID') && $post_id = get_the_ID() ) {
			if ( $post = get_post( $post_id ) ) {
				if ( ( 'revision' == $post->post_type ) && ( 'pending' == $post->post_status ) ) {
					if ( $gmt )
						$time = mysql2date($format, $post->post_modified_gmt, $gmt);
					else
						$time = mysql2date($format, $post->post_modified, $gmt);
				}
			}		
		}
		
		return $time;
	}
	
	
	// If Scheduled Revisions are enabled, don't allow WP to force current post status to future based on publish date
	function flt_insert_post_data( $data, $postarr ) {
		if ( ( 'future' == $data['post_status'] ) && ( 'publish' == $postarr['post_status'] ) )
			$data['post_status'] = 'publish';
			
		return $data;
	}
	
	
	function flt_pendingrev_post_status($status) {
		if ( is_content_administrator_rvy() )
			return $status;
		
		if ( isset($_POST['wp-preview']) && ( 'dopreview' == $_POST['wp-preview'] ) )
			return $status;
			
		if ( isset($_POST['post_ID']) && isset($_POST['post_type']) ) {
			$post_id = $_POST['post_ID'];

			$can_edit = agp_user_can( "edit_{$_POST['post_type']}", $post_id, '', array( 'skip_revision_allowance' => true ) );
			
			if ( ! $can_edit )
				$this->impose_pending_rev = $post_id;
		}
		
		return $status;
	}
	
	
	function act_impose_pending_rev() {
		if ( isset($_POST['wp-preview']) && ( 'dopreview' == $_POST['wp-preview'] ) )
			return;

		if ( ! empty($this->impose_pending_rev) ) {
			
			// todo: can we just return instead?
			if ( isset($_POST['action']) && ( 'autosave' == $_POST['action'] ) )
				wp_die( 'Autosave disabled when editing a published post/page to create a pending revision.' );
			
			$object_id = $this->impose_pending_rev;
			$post_arr = $_POST;
			
			$object_type = isset($post_arr['post_type']) ? $post_arr['post_type'] : '';
		
			$post_arr['post_type'] = 'revision';
			$post_arr['post_status'] = 'pending';
			$post_arr['post_parent'] = $this->impose_pending_rev;  // side effect: don't need to filter page parent selection because parent is set to published revision
			$post_arr['parent_id'] = $this->impose_pending_rev;
			$post_arr['post_ID'] = 0;
			$post_arr['ID'] = 0;
			$post_arr['guid'] = '';
			
			if ( defined('SCOPER_VERSION') ) {
				if ( isset($post_arr['post_category']) )	// todo: also filter other post taxonomies
					$post_arr['post_category'] = $this->scoper->filters_admin->flt_pre_object_terms($post_arr['post_category'], 'category');
			}
					
			global $current_user, $wpdb;
			$post_arr['post_author'] = $current_user->ID;		// store current user as revision author (but will retain current post_author on restoration)
				
			$post_arr['post_modified'] = current_time( 'mysql' );
			$post_arr['post_modified_gmt'] = current_time( 'mysql', 1 );

			$date_clause = ", post_modified = '" . current_time( 'mysql' ) . "', post_modified_gmt = '" . current_time( 'mysql', 1 ) . "'";  // make sure actual modification time is stored to revision
			
			if ( $revision_id = wp_insert_post($post_arr) ) {
				$future_date = ( ! empty($post_arr['post_date']) && ( strtotime($post_arr['post_date_gmt'] ) > agp_time_gmt() ) );
				
				$wpdb->query("UPDATE $wpdb->posts SET post_status = 'pending', post_parent = '$this->impose_pending_rev' $date_clause WHERE ID = '$revision_id'");
	
				if ( 'page' == $object_type ) {
					$manage_uri = 'edit-pages.php';
					$manage_caption = __( 'Return to Edit Pages', 'revisionary' );
				} else {
					$manage_uri = 'edit.php';
					$manage_caption = __( 'Return to Edit Posts', 'revisionary' );
				}
				
				if ( $future_date )
					$msg = __('Your modification has been saved for editorial review.  If approved, it will be published on the date you specified.', 'revisionary') . ' ';
				else
					$msg = __('Your modification has been saved for editorial review.', 'revisionary') . ' ';
				
				$msg .= '<ul><li>';
				$msg .= sprintf( '<a href="%s">' . __('View it in Revisions Manager', 'revisionary') . '</a>', "admin.php?page=rvy-revisions&amp;revision=$revision_id&amp;action=view" );
				$msg .= '<br /><br /></li><li>';
				
				if ( $future_date ) {
					$msg .= sprintf( '<a href="%s">' . __('Go back to Submit Another revision (possibly for a different publish date).', 'revisionary') . '</a>', "javascript:back();" );
					$msg .= '<br /><br /></li><li>';
				}
				$msg .= sprintf( '<a href="%s">' . $manage_caption . '</a>', admin_url($manage_uri) );
				$msg .= '</li></ul>';

			} else {
				$msg = __('Sorry, an error occurred while attempting to save your modification for editorial review!', 'revisionary') . ' ';	
			}
			
			
			$admin_notify = rvy_get_option( 'pending_rev_notify_admin' );
			$author_notify = rvy_get_option( 'pending_rev_notify_author' );
			if ( $admin_notify || $author_notify ) {
				$type_caption = ( 'page' == $object_type ) ? __('page') : __('post');
				
				$title = sprintf(__('[%s] Pending Revision Notification', 'revisionary'), get_option('blogname'));
				
				$message = sprintf( __('A pending revision to the %1$s "%2$s" has been submitted.', 'revisionary'), $type_caption, $post_arr['post_title'] ) . "\r\n\r\n";
				
				if ( defined('SCOPER_VERSION') ) {
					if ( $author = new WP_Scoped_User( $post_arr['post_author'], '', array( 'disable_user_roles' => true, 'disable_group_roles' => true, 'disable_wp_roles' => true ) ) )
						$message .= sprintf( __('It was submitted by %1$s.', 'revisionary' ), $author->display_name ) . "\r\n\r\n";
				} else {
					if ( $author = new WP_User( $post_arr['post_author'], '' ) )
						$message .= sprintf( __('It was submitted by %1$s.', 'revisionary' ), $author->display_name ) . "\r\n\r\n";	
				}
				
				if ( $revision_id )
					$message .= __( 'Review it here: ', 'revisionary' ) . admin_url("admin.php?page=rvy-revisions&action=view&revision={$revision_id}") . "\r\n";
				
					
				// establish the publisher recipients
				if ( $admin_notify && ('always' != $admin_notify ) && ! empty($post_arr['prev_cc_user']) ) {
					if ( defined( 'SCOPER_VERSION' ) && ! defined( 'SCOPER_DEFAULT_MONITOR_GROUPS' ) ) {
						global $scoper;
						
						require_once( SCOPER_ABSPATH . '/admin/admin_lib_rs.php');
						
						if ( $group = ScoperAdminLib::get_group_by_name( '[Pending Revision Monitors]' ) ) {
							$monitor_ids = ScoperAdminLib::get_group_members( $group->ID, COL_ID_RS, true );
							
							$post_publisher_ids = $scoper->users_who_can( "edit_{$object_type}", COL_ID_RVY, 'post', $this->impose_pending_rev );
							$monitor_ids = array_intersect( $monitor_ids, $post_publisher_ids );
						} else
							$monitor_ids = array();
					} else {
						require_once(ABSPATH . 'wp-admin/includes/user.php');
						$admin_search = new WP_User_Search( '', 0, 'administrator' );
						$monitor_ids = $admin_search->results;
						
						$editor_search = new WP_User_Search( '', 0, 'editor' );
						$monitor_ids = array_merge( $monitor_ids, $editor_search->results );	
					}
					
					// intersect default recipients with selected recipients						
					$monitor_ids = array_intersect( $post_arr['prev_cc_user'], $monitor_ids );
				} else
					$monitor_ids = array();
				

				if ( $author_notify ) {
					if ( $post = get_post( $this->impose_pending_rev ) ) {
						if ( ( 'always' == $author_notify ) || ( isset($post_arr['prev_cc_user']) && is_array($post_arr['prev_cc_user']) && in_array( $post->post_author, $post_arr['prev_cc_user'] ) ) )
							$monitor_ids []= $post->post_author;	
					}
				}
				
				if ( $monitor_ids ) {
					global $wpdb;
					$to_addresses = $wpdb->get_col( "SELECT user_email FROM $wpdb->users WHERE ID IN ('" . implode( "','", $monitor_ids ) . "')" );
				} else
					$to_addresses = array();

					
				if ( $to_addresses ) {
					if ( ini_get( 'allow_url_fopen' ) && rvy_get_option('async_email') ) {					
						$pending_mail = (array) get_option( 'pending_mail_rvy' );
						$pending_mail []= array( 'to' => $to_addresses, 'title' => $title, 'message' => $message );	
						update_option( 'pending_mail_rvy', $pending_mail );
	
						// asynchronous secondary site call to avoid delays
						rvy_log_async_request('process_mail');
						$url = site_url( 'index.php?action=process_mail' );
						wp_remote_post( $url, array('timeout' => 0.1, 'blocking' => false, 'sslverify' => apply_filters('https_local_ssl_verify', true)) );
					} else {
						foreach ( $to_addresses as $address )
							rvy_mail($address, $title, $message);
					}
				}
			}
			
			unset($this->impose_pending_rev);
			
			wp_die( $msg, __('Pending Revision Created', 'revisionary'), array( 'response' => 0 ) );
		}
	}
	
	
	function act_create_scheduled_rev() {
		if ( isset($_POST['wp-preview']) && ( 'dopreview' == $_POST['wp-preview'] ) )
			return;
		
		if ( isset($_POST['action']) && ( 'autosave' == $_POST['action'] ) )
			return;
			
		$post_arr = $_POST;
		
		if ( ! empty($post_arr['post_date_gmt']) && ( strtotime($post_arr['post_date_gmt'] ) > agp_time_gmt() ) ) {
			$parent_id = $post_arr['ID'];
			
			// a future publish date was selected
			$date_clause = ", post_modified = '" . current_time( 'mysql' ) . "', post_modified_gmt = '" . current_time( 'mysql', 1 ) . "'";  // If WP forces modified time up to post time, force it back

			$post_arr['post_modified'] = current_time( 'mysql' );
			$post_arr['post_modified_gmt'] = current_time( 'mysql', 1 );

			$object_type = isset($post_arr['post_type']) ? $post_arr['post_type'] : '';
		
			$post_arr['post_type'] = 'revision';
			$post_arr['post_status'] = 'future';
			$post_arr['post_parent'] = $post_arr['ID'];
			$post_arr['post_ID'] = 0;
			$post_arr['ID'] = 0;
			$post_arr['guid'] = '';
	
			if ( defined('SCOPER_VERSION') ) {
				global $scoper;
				
				if ( isset($post_arr['post_category']) )	// todo: also filter other post taxonomies
					$post_arr['post_category'] = $scoper->filters_admin->flt_pre_object_terms($post_arr['post_category'], 'category');
			}
					
			global $current_user;
			$post_arr['post_author'] = $current_user->ID;		// store current user as revision author (but will retain current post_author on restoration)

			if ( $revision_id = wp_insert_post($post_arr) ) {
				global $wpdb;
				$wpdb->query("UPDATE $wpdb->posts SET post_status = 'future', post_parent = '$parent_id' WHERE ID = '$revision_id'");
			}
			
			require_once('revision-action_rvy.php');
			rvy_update_next_publish_date();

			if ( 'page' == $object_type ) {
				$manage_uri = 'edit-pages.php';
				$manage_caption = __( 'Return to Edit Pages', 'revisionary' );
			} else {
				$manage_uri = 'edit.php';
				$manage_caption = __( 'Return to Edit Posts', 'revisionary' );
			}
			
			$msg = __('Your modification was saved as a Scheduled Revision.', 'revisionary') . ' ';
			
			$msg .= '<ul><li>';
			$msg .= sprintf( '<a href="%s">' . __('View it in Revisions Manager', 'revisionary') . '</a>', "admin.php?page=rvy-revisions&amp;revision=$revision_id&amp;action=view" );
			$msg .= '<br /><br /></li><li>';
			$msg .= sprintf( '<a href="%s">' . __('Go back to Schedule Another revision.', 'revisionary') . '</a>', "javascript:back();" );
			$msg .= '<br /><br /></li><li>';
			$msg .= sprintf( '<a href="%s">' . $manage_caption . '</a>', admin_url($manage_uri) );
			$msg .= '</li></ul>';
			
			wp_die( $msg, __('Scheduled Revision Created', 'revisionary'), array( 'response' => 0 ) );
		}
	}
	
	
} // end class RevisionaryAdmin
?>