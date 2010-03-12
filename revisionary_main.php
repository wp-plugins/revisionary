<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
	
/**
 * Revisionary PHP class for the WordPress plugin Revisionary
 * revisionary_main.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */
class Revisionary
{		
	var $admin;					// object ref - RevisionaryAdmin
	var $filters_admin_item_ui; // object ref - RevisionaryAdminFiltersItemUI
	var $skip_revision_allowance = false;
	
	// minimal config retrieval to support pre-init usage by WP_Scoped_User before text domain is loaded
	function Revisionary() {
		rvy_refresh_options_sitewide();
		
		// NOTE: $_GET['preview'] and $_GET['post_type'] arguments are set by rvy_init() at response to ?p= request when the requested post is a revision.
		if ( ! is_admin() && ( ! empty( $_GET['preview'] ) || ! empty( $_GET['mark_current_revision'] ) ) && empty($_GET['preview_id']) ) { // preview_id indicates a regular preview via WP core, based on autosave revision
			require_once( 'front_rvy.php' );
			$this->front = new RevisionaryFront();
		}
			
		if ( ! is_content_administrator_rvy() ) {
			add_filter( 'user_has_cap', array( &$this, 'flt_user_has_cap' ), 98, 3 );
			add_filter( 'posts_where', array( &$this, 'flt_posts_where' ), 1 );
		}
			
		if ( is_admin() ) {
			require_once('admin/admin_rvy.php');
			$this->admin = new RevisionaryAdmin();
		}	
		
		add_action( 'wpmu_new_blog', array( &$this, 'act_new_blog'), 10, 2 );
		
		do_action( 'rvy_init' );
	}
	
	function act_new_blog( $blog_id, $user_id ) {
		rvy_add_revisor_role( $blog_id );
	}
	
	function flt_user_has_cap($wp_blogcaps, $reqd_caps, $args)	{
		if ( ! rvy_get_option('pending_revisions') )
			return $wp_blogcaps;
		
		$post_id = rvy_detect_post_id();

		$script_name = $_SERVER['SCRIPT_NAME'];
		
		if ( ! $this->skip_revision_allowance ) {
			// Allow Contributors / Revisors to edit published post/page, with change stored as a revision pending review
			$replace_caps = array('edit_published_posts', 'edit_private_posts', 'publish_posts');
			if ( array_intersect( $reqd_caps, $replace_caps) ) {	// don't need to fudge the capreq for post.php unless existing post has public/private status
				if ( is_preview() || strpos($script_name, 'p-admin/edit.php') || strpos($script_name, 'p-admin/edit-pages.php') || strpos($script_name, 'p-admin/widgets.php') || ( in_array( get_post_field('post_status', $post_id ), array('publish', 'private') ) ) ) {

					if ( strpos($script_name, 'p-admin/page.php') || strpos($script_name, 'p-admin/edit-pages.php') )
						$use_cap_req = 'edit_pages';
					else
						$use_cap_req = 'edit_posts';
				
					if ( ! empty( $wp_blogcaps[$use_cap_req] ) )
						foreach ( $replace_caps as $replace_cap_name )
							$wp_blogcaps[$replace_cap_name] = true;
				}
			}
			
			$replace_caps = array('edit_published_pages', 'edit_private_pages', 'publish_pages');
			if ( array_intersect( $reqd_caps, $replace_caps) ) {	// don't need to fudge the capreq for page.php unless existing page has public/private status
				if ( is_preview() || strpos($script_name, 'p-admin/edit.php') || strpos($script_name, 'p-admin/edit-pages.php') || strpos($script_name, 'p-admin/widgets.php') || ( in_array( get_post_field('post_status', $post_id ), array('publish', 'private') ) ) ) {
					$use_cap_req = 'edit_pages';
					
					if ( ! empty( $wp_blogcaps[$use_cap_req] ) )
						foreach ( $replace_caps as $replace_cap_name )
							$wp_blogcaps[$replace_cap_name] = true;
				}
			}
		}
		
		if ( in_array( 'edit_others_posts', $reqd_caps ) && ( strpos($script_name, 'p-admin/page.php') || strpos($script_name, 'p-admin/page-new.php') ) ) {
			
			// Allow contributors to edit published post/page, with change stored as a revision pending review
			if ( ! rvy_metaboxes_started('page') && ! strpos($script_name, 'p-admin/revision.php') && false === strpos(urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rvy-revisions' )  ) // don't enable contributors to view/restore revisions
				$use_cap_req = 'edit_pages';
			else
				$use_cap_req = 'edit_published_pages';
				
			if ( ! empty( $wp_blogcaps[$use_cap_req] ) )
				$wp_blogcaps['edit_others_posts'] = true;
		}
		
		// TODO: possible need to redirect revision cap check to published parent post/page ( RS cap-interceptor "maybe_revision" )
		return $wp_blogcaps;			
	}
	
	function flt_posts_where( $where ) {
		if ( ( is_preview() || is_admin() ) && ! is_content_administrator_rvy() ) {
			global $current_user;
			
			if ( ! $this->skip_revision_allowance ) {
				if ( $pos = strpos( $where, "wp_trunk_posts.post_author = $current_user->id AND" ) ) {  
					if ( strpos( $_SERVER['REQUEST_URI'], 'page' ) )
						$cap = 'edit_others_pages';
					else
						$cap = 'edit_others_posts';
					
					if ( current_user_can( $cap ) ) {
						$where = str_replace( "wp_trunk_posts.post_author = $current_user->id AND", '', $where );	// current syntax as of WP 2.8.4
						$where = str_replace( "wp_trunk_posts.post_author = '$current_user->id' AND", '', $where );
					}
				}
			}
		}
			
		return $where;
	}
	
	
} // end Revisionary class
?>