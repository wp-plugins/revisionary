<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

// TODO: only for edit / write post/page URIs and dashboard ?
add_filter('query', array('RevisionaryAdminHardway', 'flt_include_pending_revisions'), 13 ); // allow regular hardway filter to apply scoping first

if ( ! is_content_administrator_rvy() ) {
	// URIs ending in specified filename will not be subjected to low-level query filtering
	$nomess_uris = apply_filters( 'rvy_skip_lastresort_filter_uris', array( 'p-admin/categories.php', 'p-admin/themes.php', 'p-admin/plugins.php', 'p-admin/profile.php' ) );
	$nomess_uris = array_merge($nomess_uris, array('p-admin/admin-ajax.php'));

	$haystack = $_SERVER['REQUEST_URI'];
	$haystack_length = strlen($haystack);
	$matched = false;
	
	foreach($nomess_uris as $needle) {
		$pos = strpos($haystack, $needle);
		if ( is_numeric($pos) && ( $any_substr_pos || ( $pos == ( $haystack_length - strlen($needle) ) ) ) ) {
			$matched = true;
			break;
		}
	}
	
	if ( ! $matched ) {
		require_once( 'hardway-admin_non-administrator_rvy.php' );
		add_filter('query', array('RevisionaryAdminHardway_Ltd', 'flt_last_resort_query'), 12 );
	}
} 	


/**
 * RevisionaryAdminHardway PHP class for the WordPress plugin Revisionary
 * hardway-admin_rv.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 * Used by Role Role Scoper Plugin as a container for statically-called functions
 *
 */
class RevisionaryAdminHardway {
	
	function flt_include_pending_revisions($query) {
		global $wpdb;
		
		// Require current user to be a blog-wide editor due to complexity of applying scoped roles to revisions
		if ( strpos($query, "FROM $wpdb->posts") && ( strpos($query, ".post_status = 'pending'") || strpos($query, ".post_status = 'future'") || strpos($query, 'GROUP BY post_status') || strpos($query, "GROUP BY $wpdb->posts.post_status") || ( empty($_GET['post_status']) || ( 'all' == $_GET['post_status'] ) ) ) ) {
			
			// counts for edit posts / pages
			if ( strpos($query, "GROUP BY post_status") ) {
				//$query = str_replace("SELECT post_status, COUNT( * ) AS num_posts FROM $wpdb->posts WHERE post_type = 'page'", "SELECT post_status, COUNT( * ) AS num_posts FROM $wpdb->posts WHERE ( post_type = 'page' OR ( post_type = 'revision' AND ( post_status = 'pending' OR post_status = 'future' ) AND post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'page' ) ) )", $query);
				//$query = str_replace("SELECT post_status, COUNT( * ) AS num_posts FROM $wpdb->posts WHERE post_type = 'post'", "SELECT post_status, COUNT( * ) AS num_posts FROM $wpdb->posts WHERE ( post_type = 'post' OR ( post_type = 'revision' AND ( post_status = 'pending' OR post_status = 'future' ) AND post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'post' ) ) )", $query);
			
				$p = ( strpos( $query, 'p.post_type' ) ) ? 'p.' : '';
	
				$query = str_replace("{$p}post_type = 'page'", "( {$p}post_type = 'page' OR ( {$p}post_type = 'revision' AND ( {$p}post_status = 'pending' OR {$p}post_status = 'future' ) AND {$p}post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'page' ) ) )", $query);
				$query = str_replace("{$p}post_type = 'post'", "( {$p}post_type = 'post' OR ( {$p}post_type = 'revision' AND ( {$p}post_status = 'pending' OR {$p}post_status = 'future' ) AND {$p}post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'post' ) ) )", $query);
				
				//$query = str_replace("post_type = 'page'", "( post_type = 'page' OR ( post_type = 'revision' AND ( post_status = 'pending' OR post_status = 'future' ) AND post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'page' ) ) )", $query);
				//$query = str_replace("post_type = 'post'", "( post_type = 'post' OR ( post_type = 'revision' AND ( post_status = 'pending' OR post_status = 'future' ) AND post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'post' ) ) )", $query);
					
			} elseif ( strpos($query, "GROUP BY $wpdb->posts.post_status") && strpos($query, "ELECT $wpdb->posts.post_status," ) ) {
				
				// also post-process the scoped equivalent 
				$query = str_replace(" post_type = 'page'", "( $wpdb->posts.post_type = 'page' OR ( $wpdb->posts.post_type = 'revision' AND $wpdb->posts.post_status IN ('pending', 'future') AND $wpdb->posts.post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'page' ) ) )", $query);
				$query = str_replace(" post_type = 'post'", "( $wpdb->posts.post_type = 'post' OR ( $wpdb->posts.post_type = 'revision' AND $wpdb->posts.post_status IN ('pending', 'future') AND $wpdb->posts.post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'post' ) ) )", $query);
			}
			
			// edit pages / posts listing items
			elseif ( strpos($query, 'ELECT') ) {
				if ( awp_ver('2.7-dev') ) {
					// include pending/scheduled revs in All, Pending or Scheduled list
					$status_clause = '';
					if ( strpos($query, ".post_status = 'pending'") || empty($_GET['post_status']) || ( 'all' == $_GET['post_status'] ) )
						$status_clause = "$wpdb->posts.post_status = 'pending'";
					
					if ( strpos($query, ".post_status = 'future'") || empty($_GET['post_status']) || ( 'all' == $_GET['post_status'] ) ) {
						$or = ( $status_clause ) ? ' OR ' : '';
						$status_clause .= $or . "$wpdb->posts.post_status = 'future'";
					}

					$query = str_replace("$wpdb->posts.post_type = 'page'", "( $wpdb->posts.post_type = 'page' OR ( $wpdb->posts.post_type = 'revision' AND $wpdb->posts.post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'page' ) ) AND ( $status_clause ) )", $query);
					$query = str_replace("$wpdb->posts.post_type = 'post'", "( $wpdb->posts.post_type = 'post' OR ( $wpdb->posts.post_type = 'revision' AND $wpdb->posts.post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'post' ) ) AND ( $status_clause ) )", $query);
					
				} else {
					// WP < 2.7 will not include pending/scheduled revisions in "All" list (todo: is the 2.6 query really different for this purpose?)
					if ( strpos($query, ".post_status = 'pending'") && strpos($query, 'ELECT') ) {
						$query = str_replace("$wpdb->posts.post_type = 'page' AND ($wpdb->posts.post_status = 'pending')", "$wpdb->posts.post_type = 'page' OR ( $wpdb->posts.post_type = 'revision' AND $wpdb->posts.post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'page' ) ) AND ( $wpdb->posts.post_status = 'pending' )", $query);
						$query = str_replace("$wpdb->posts.post_type = 'post' AND ($wpdb->posts.post_status = 'pending')", "$wpdb->posts.post_type = 'post' OR ( $wpdb->posts.post_type = 'revision' AND $wpdb->posts.post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'post' ) ) AND ( $wpdb->posts.post_status = 'pending' )", $query);
					}
					
					if ( strpos($query, ".post_status = 'future'") && strpos($query, 'ELECT') ) {
						$query = str_replace("$wpdb->posts.post_type = 'page' AND ($wpdb->posts.post_status = 'future')", "$wpdb->posts.post_type = 'page' OR ( $wpdb->posts.post_type = 'revision' AND $wpdb->posts.post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'page' ) ) AND ($wpdb->posts.post_status = 'future')", $query);
						$query = str_replace("$wpdb->posts.post_type = 'post' AND ($wpdb->posts.post_status = 'future')", "$wpdb->posts.post_type = 'post' OR ( $wpdb->posts.post_type = 'revision' AND $wpdb->posts.post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = 'post' ) ) AND ($wpdb->posts.post_status = 'future')", $query);
					}
				} // endif WP >= 2.7
			} // endif SELECT query
			
		} // endif query pertains in any way to pending status and/or revisions
		
		return $query;
	}
}
?>