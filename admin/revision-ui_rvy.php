<?php
/**
 * revision-ui_rvy.php
 * 
 * UI library for Revisions Manager, heavily expanded from WP 2.8.4 core
 *
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */

 // clear TinyMCE plugin conflicts (this is only applied for the Revision Manager url)
function rvy_clear_mce_plugins( $mce_plugins ) {
	if ( is_array( $mce_plugins ) ) {
		$mce_offenders = array( 'cforms' );
		$mce_plugins = array_diff_key( $mce_plugins, array_fill_keys( $mce_offenders, true ) );
	}
		
	return $mce_plugins;
}
add_filter( 'mce_external_plugins', 'rvy_clear_mce_plugins', 99 );
 
 
function rvy_metabox_notification_list( $topic ) {

	if ( 'pending_revision' == $topic ) {	
		if ( ! rvy_get_option('pending_rev_notify_admin') )
			return;
		
		$object_type = awp_post_type_from_uri();
		$object_id = rvy_detect_post_id();

		$id_prefix = 'prev_cc';

		$post_publishers = array();
		$default_ids = array();

		if ( defined('SCOPER_VERSION') && ! defined('SCOPER_DEFAULT_MONITOR_GROUPS') ) {
			global $scoper;

			if ( $group = ScoperAdminLib::get_group_by_name( '[Pending Revision Monitors]' ) ) {

				$default_ids = ScoperAdminLib::get_group_members( $group->ID, COL_ID_RS, true );

				$post_publishers = $scoper->users_who_can( "edit_{$object_type}", COLS_ALL_RVY, 'post', $object_id, array( 'force_refresh' => true ) );

				$can_publish_post = array();
				foreach ( $post_publishers as $key => $user ) {
					$can_publish_post []= $user->ID;
					
					if ( ! in_array( $user->ID, $default_ids ) )
						unset(  $post_publishers[$key] );
				}
	
				$default_ids = array_intersect( $default_ids, $can_publish_post );
				$default_ids = array_fill_keys( $default_ids, true );
			}
		
		} else {
			// If RS is not active, default to sending to all Administrators and Editors
			require_once(ABSPATH . 'wp-admin/includes/user.php');
			
			$use_wp_roles = ( defined( 'SCOPER_MONITOR_ROLES' ) ) ? SCOPER_MONITOR_ROLES : 'administrator,editor';
			
			$use_wp_roles = str_replace( ' ', '', $use_wp_roles );
			$use_wp_roles = explode( ',', $use_wp_roles );
			
			$recipient_ids = array();

			foreach ( $use_wp_roles as $role_name ) {
				$search = new WP_User_Search( '', 0, $role_name );
				$recipient_ids = array_merge( $recipient_ids, $search->results );
			}

			foreach ( $recipient_ids as $userid ) {
				$user = new WP_User($userid);
				$post_publishers []= $user;
				$default_ids [$user->ID] = true;
			}
		}		
		
		require_once('agents_checklist_rvy.php');
		
		echo("<div id='rvy_cclist_$topic'>");
		RevisionaryAgentsChecklist::agents_checklist( 'user', $post_publishers, $id_prefix, $default_ids );
		echo('</div>');
	}
}


function rvy_metabox_revisions( $status ) {
	global $revisionary;

	$property_name = $status . '_revisions';
	if ( ! empty( $revisionary->filters_admin_item_ui->$property_name ) )
		echo $revisionary->filters_admin_item_ui->$property_name;
	
	elseif ( ! empty( $_GET['post'] ) ) {
		$args = array( 'format' => 'list', 'parent' => false );
		rvy_list_post_revisions( $_GET['post'], $status, $args );
	}
}


// Work around conflict with WP Super Edit and any other plugins which wipe out default TinyMCE parameters
function rvy_log_tiny_mce_params( $initArray ) {
	global $rvy_tiny_mce_params;
	$rvy_tiny_mce_params = $initArray;
	return $initArray;
}


// adjust TinyMCE parameters for Revision viewing / edit
function rvy_tiny_mce_params( $initArray ) {
	global $rvy_tiny_mce_params;
	if ( ! empty($rvy_tiny_mce_params) && is_array($initArray) )	// Restore default TinyMCE parameters in case another plugin wiped them.  This is only done for the Revision Management form.
		$initArray = array_merge($rvy_tiny_mce_params, $initArray);
	else
		$initArray = $rvy_tiny_mce_params;
		
	$mce_buttons_1 = apply_filters('mce_buttons', array('bold', 'italic', 'strikethrough', '|', 'bullist', 'numlist', 'blockquote', '|', 'justifyleft', 'justifycenter', 'justifyright', '|', 'link', 'unlink', 'wp_more', '|', 'spellchecker', 'fullscreen', 'wp_adv' ));
	$mce_buttons_1 = implode($mce_buttons_1, ',');

	$mce_buttons_2 = apply_filters('mce_buttons_2', array('formatselect', 'underline', 'justifyfull', 'forecolor', '|', 'pastetext', 'pasteword', 'removeformat', '|', 'media', 'charmap', '|', 'outdent', 'indent', '|', 'undo', 'redo', 'wp_help' ));
	$mce_buttons_2 = implode($mce_buttons_2, ',');

	$mce_buttons_3 = apply_filters('mce_buttons_3', array());
	$mce_buttons_3 = implode($mce_buttons_3, ',');

	$mce_buttons_4 = apply_filters('mce_buttons_4', array());
	$mce_buttons_4 = implode($mce_buttons_4, ',');
	
	$mce_locale = ( '' == get_locale() ) ? 'en' : strtolower( substr(get_locale(), 0, 2) ); // only ISO 639-1
	
	// note custom save_callback since WP 2.9-beta-1 removed default callback method from SwitchEditors
	$arr = array (
		'mode' => 'specific_textareas',
		'editor_selector' => 'theEditor',
		'width' => '100%',
		'theme' => 'advanced',
		'skin' => 'wp_theme',
		'language' => "$mce_locale",
		'theme_advanced_toolbar_location' => 'top',
		'theme_advanced_toolbar_align' => 'left',
		'theme_advanced_statusbar_location' => 'bottom',
		'theme_advanced_resizing' => true,
		'theme_advanced_resize_horizontal' => false,
		'dialog_type' => 'modal',
		'apply_source_formatting' => false,
		'remove_linebreaks' => true,
		'gecko_spellcheck' => true,
		'entities' => '38,amp,60,lt,62,gt',
		'accessibility_focus' => true,
		'tabfocus_elements' => 'major-publishing-actions',
		'media_strict' => false,
		'save_callback' => 'tmCallbackRvy',
		'wpeditimage_disable_captions' => true,
		'plugins' => '',
		'theme_advanced_buttons1' => $mce_buttons_1,
		'theme_advanced_buttons2' => $mce_buttons_2,
		'theme_advanced_buttons3' => $mce_buttons_3,
		'theme_advanced_buttons4' => $mce_buttons_4
	);
	
	foreach ( $arr as $key => $val ) {
		if ( ! isset($initArray[$key]) )
			$initArray[$key] = $val;
	}

	//$url = parse_url( RVY_URLPATH . '/admin/revisions-rs.css' );
	//$initArray['content_css'] = $url['path'];
	
	//$initArray['skin'] = 'rvy_view_revision'; 
	
	return $initArray;
}


function rvy_tiny_mce_readonly( $initArray ) {
	$initArray[ 'readonly'] = 'readonly';
	
	return $initArray;
}


/**
 * Retrieve formatted date timestamp of a revision (linked to that revisions's page).
 *
 * @package WordPress
 * @subpackage Post_Revisions
 * @since 2.6.0
 *
 * @uses date_i18n()
 *
 * @param int|object $revision Revision ID or revision object.
 * @param bool $link Optional, default is true. Link to revisions's page?
 * @return string i18n formatted datetimestamp or localized 'Current Revision'.
 */
function rvy_post_revision_title( $revision, $link = true, $date_field = 'post_date' ) {
	if ( ! is_object($revision) )
		if ( !$revision = get_post( $revision ) )
			return $revision;

	$public_types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
	$public_types []= 'revision';
	
	if ( ! in_array( $revision->post_type, $public_types ) )
		return false;
	
	/* translators: revision date format, see http://php.net/date */
	$datef = _x( 'j F, Y @ G:i', 'revision date format', 'revisionary' );
	
	$date = agp_date_i18n( $datef, strtotime( $revision->$date_field ) );

	// note: RS filter (un-requiring edit_published/private cap) will be applied to this cap check
	
	if ( $link ) { //&& current_user_can( 'edit_post', $revision->ID ) ) {    // revisions are listed in the Editor even if not editable / restorable / approvable
		
		$link = "admin.php?page=rvy-revisions&amp;action=view&amp;revision=$revision->ID";
		$date = "<a href='$link'>$date</a>";
	}

	if ( 'revision' != $revision->post_type ) {
		$currentf  = __( '%1$s (Current Revision)', 'revisionary' );
		$date = sprintf( $currentf, $date );
		
	} elseif ( "{$revision->post_parent}-autosave" === $revision->post_name ) {
		$autosavef = __( '%1$s (Autosave)', 'revisionary' );
		$date = sprintf( $autosavef, $date );
	}
	
	return $date;
}



/**
 * Display list of a post's revisions (modified by Kevin Behrens to include view links).
 *
 * Can output either a UL with edit links or a TABLE with diff interface, and
 * restore action links.
 *
 * Second argument controls parameters:
 *   (bool)   parent : include the parent (the "Current Revision") in the list.
 *   (string) format : 'list' or 'form-table'.  'list' outputs UL, 'form-table'
 *                     outputs TABLE with UI.
 *   (int)    right  : what revision is currently being viewed - used in
 *                     form-table format.
 *   (int)    left   : what revision is currently being diffed against right -
 *                     used in form-table format.
 *
 * @uses wp_get_post_revisions()
 * @uses wp_post_revision_title()
 * @uses get_edit_post_link()
 * @uses get_the_author_meta()
 *
 * @todo split into two functions (list, form-table) ?
 *
 * @param int|object $post_id Post ID or post object.
 * @param string|array $args See description {@link wp_parse_args()}.
 * @return null
 */
function rvy_list_post_revisions( $post_id = 0, $status = '', $args = null ) {
	if ( !$post = get_post( $post_id ) )
		return;
		
	$defaults = array( 'parent' => false, 'right' => false, 'left' => false, 'format' => 'list', 'type' => 'all', 'echo' => true, 'date_field' => '', 'current_id' => 0 );
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

	// link to publish date in Edit Form metaboxes, but modification date in Revisions Manager table
	if ( ! $date_field  ) {
		if ( 'list' == $format ) {
			$date_field = ( 'inherit' == $status ) ? 'post_modified' : 'post_date';
			$sort_field = $date_field;
		} else {
			$date_field = 'post_modified';
			$sort_field = ( 'inherit' == $status ) ? 'post_modified' : 'post_date';
		}
	} else {
		if ( ! $sort_field )
			$sort_field = $date_field;	
	}
			
	global $current_user;
	
	switch ( $type ) {
	case 'autosave' :
		if ( !$autosave = wp_get_post_autosave( $post->ID ) )
			return;
		$revisions = array( $autosave );
		break;
	case 'revision' : // just revisions - remove autosave later
	case 'all' :
	default :
		if ( !$revisions = rvy_get_post_revisions( $post->ID, $status, array( 'orderby' => $sort_field ) ) )
			return;
		break;
	}
	
	/* translators: post revision: 1: when, 2: author name */
	$titlef = _x( '%1$s by %2$s', 'post revision' );

	if ( $parent )
		array_unshift( $revisions, $post );

	$rows = '';
	$class = false;
	
	$type_obj = get_post_type_object( $post->post_type );
	$can_edit_post = agp_user_can( $type_obj->cap->edit_post, $post->ID, '', array( 'skip_revision_allowance' => true ) );

	$count = 0;
	$left_checked_done = false;
	$right_checked_done = false;
	$can_delete_any = false;
	
	$delete_msg = __( "The revision will be deleted. Are you sure?", 'revisionary' );
	$js_delete_call = "javascript:if (confirm('$delete_msg')) {return true;} else {return false;}";
	
	// buffer listed revision IDs for capability checks
	if ( defined('SCOPER_VERSION') ) {
		global $scoper;
		foreach( $revisions as $revision )
			$scoper->listed_ids['post'][$revision->post_parent] = true;
	}
	
	foreach ( $revisions as $revision ) {
		if ( $status && ( $status != $revision->post_status ) ) 		 // support arg to display only past / pending / future revisions
			if ( 'revision' == $revision->post_type )					// but always display current rev
				continue;
					
		if ( 'revision' === $type && wp_is_post_autosave( $revision ) )
			continue;

		// todo: set up buffering to restore this in case we (or some other plugin) impose revision-specific read capability
		//if ( ! current_user_can( "read_{$post->post_type}", $revision->ID ) )
		//	continue;

		$date = rvy_post_revision_title( $revision, true, $date_field );

		$name = get_the_author_meta( 'display_name', $revision->post_author );

		if ( 'form-table' == $format ) {
			if ( ! $left_checked_done ) {
				if ( $left )
					$left_checked = ( $left == $revision->ID ) ? ' checked="checked"' : '';
				else
					$left_checked = ( $right == $revision->ID ) ? '' : ' checked="checked"';
			}
					
			if ( ! $right_checked_done ) {
				if ( $right )
					$right_checked = ( $right == $revision->ID ) ? ' checked="checked"' : '';
				else
					$right_checked = $left_checked ? '' : ' checked="checked"';
			}
			
			$actions = '';
			if ( $revision->ID == $current_id )
				$class = " class='rvy-revision-row rvy-current-revision'"; 
			elseif ( $class )
				$class = " class='rvy-revision-row'";
			else
				$class = " class='rvy-revision-row alternate'"; 
			
			$datef = __awp( 'M j, Y @ G:i' );
			
			if ( $post->ID != $revision->ID ) {
				$preview_link = "<a href='" .  site_url("?p={$revision->ID}&amp;post_type=revision&amp;preview=1") . "'>" . __awp( 'Preview' ) . '</a>';

				if ( $can_edit_post 
				|| ( ( 'pending' == $status ) && ( $revision->post_author == $current_user->ID ) )	// allow submitters to delete their own still-pending revisions
				 ) {
					if ( 'future' == $status ) {
						$link = "admin.php?page=rvy-revisions&amp;action=unschedule&amp;revision={$revision->ID}";
						$actions .= '<a href="' . wp_nonce_url( $link, 'unschedule-revision_' . $revision->ID ) . '">' . __('Unschedule') . '</a>&nbsp;|&nbsp;';
					}
				
					$link = "admin.php?page=rvy-revisions&amp;action=delete&amp;revision={$revision->ID}";
					$actions .= '<a href="' . wp_nonce_url( $link, 'delete-revision_' . $revision->ID ) . '" onclick="' . $js_delete_call . '" >' . __awp('Delete') . '</a>';
				}
				
				if ( ( strtotime($revision->post_date_gmt) > agp_time_gmt() ) && ( 'inherit' != $revision->post_status ) )
					$publish_date = '(' . agp_date_i18n( $datef, strtotime($revision->post_date) ) . ')';
				else
					$publish_date = '';
					
			} else {
				$preview_link = '<a href="' . site_url("?p={$revision->ID}&amp;mark_current_revision=1") . '" target="_blank">' . __awp( 'Preview' ) . '</a>';
				
				// wp_post_revision_title() returns edit post link for current rev.  Convert it to a revisions.php link for viewing here like the rest
				if ( $post->ID == $revision->ID ) {
					$date = str_replace( "{$post->post_type}.php", 'revision.php', $date );
					$date = str_replace( 'action=edit', '', $date );
					$date = str_replace( 'post=', 'revision=', $date );
					$date = str_replace( '?&amp;', '?', $date );
					$date = str_replace( '?&', '?', $date );
					$date = RevisionaryAdmin::convert_link( $date, 'revision', 'manage', array( 'object_type' => $post->post_type ) );

					$date = str_replace( '&revision=', "&amp;revision_status=$status&amp;revision=", $date );
					$date = str_replace( '&amp;revision=', "&amp;revision_status=$status&amp;revision=", $date );
				}
				
				$publish_date = agp_date_i18n( $datef, strtotime($revision->post_date) );
			}

			$rows .= "<tr$class>\n";
			$rows .= "\t<th scope='row'><input type='radio' name='left' value='$revision->ID'$left_checked /><input type='radio' name='right' value='$revision->ID'$right_checked /></th>\n";
			$rows .= "\t<td>$date</td>\n";
			$rows .= "\t<td>$publish_date</td>\n";
			$rows .= "\t<td>$preview_link</td>\n";
			$rows .= "\t<td>$name</td>\n";
			$rows .= "\t<td class='action-links'>$actions</td>\n";
			if ( $post->ID != $revision->ID 
			&& ( $can_edit_post || ( ( 'pending' == $status ) && ( $revision->post_author == $current_user->ID ) ) )	// allow submitters to delete their own still-pending revisions
			) {
				$rows .= "\t<td><input class='rvy-rev-chk' type='checkbox' name='delete_revisions[]' value='" . $revision->ID . "' /></td>\n";
				$can_delete_any = true;
			} else
				$rows .= "\t<td></td>\n";
			
			$rows .= "</tr>\n";
			
			if ( $left_checked ) {
				$left_checked = '';
				$left_checked_done = true;
			}
			
			if ( $right_checked ) {
				$right_checked = '';
				$right_checked_done = true;
			}	
			
		} else {
			$title = sprintf( $titlef, $date, $name );
			$rows .= "\t<li>$title</li>\n";
		}
		
		$count++;
	}
	
	if ( 'form-table' == $format ) : 
		if ( $count > 1 ) :
	?>
<form action="" method="post">
<?php
wp_nonce_field( 'rvy-revisions' ); 
?>
<div class="tablenav">
	<div class="alignleft">
		<input type="submit" name="rvy_compare_revs" class="button-secondary" value="<?php _e( 'Compare Selected HTML', 'revisionary' ); ?>" />
	</div>
</div>

<br class="clear" />

<table class="widefat post-revisions" cellspacing="0">
	<col />
	<col class="rvy-col1" />
	<col class="rvy-col2" />
	<col class="rvy-col3" />
	<col class="rvy-col4" />
	<col class="rvy-col5" />
<thead>
<tr>
	<th scope="col"></th>
	<th scope="col"><?php 
switch( $status ) :
case 'inherit' :
	_e( 'Modified Date (click to view/restore)', 'revisionary' ); 
	break;
case 'pending' :
	_e( 'Modified Date (click to view/approve)', 'revisionary' ); 
	break;
case 'future' :
	_e( 'Modified Date (click to view/publish)', 'revisionary' );
	break;
endswitch;
?></th>
	<th scope="col"><?php _e( 'Publish Date', 'revisionary' ); ?></th>
	<th scope="col"></th>
	<th scope="col"><?php echo __awp( 'Author' ); ?></th>
	<th scope="col" class="action-links"><?php _e( 'Actions' ); ?></th>
	<th scope="col"><input id='rvy-rev-checkall' type='checkbox' name='rvy-rev-checkall' value='' /></th>
</tr>
</thead>
<tbody>

<?php echo $rows; ?>

</tbody>
</table>

<?php if( $can_delete_any ):?>
<br />
<div class="alignright actions">
<select name="action">
<option value="" selected="selected"><?php _e('Bulk Actions'); ?></option>
<option value="bulk-delete"><?php _e('Delete'); ?></option>
</select>
<input type="submit" value="<?php _e('Apply'); ?>" name="rvy-action" id="rvy-action" class="button-secondary action" />
</div>
<?php endif; ?>

</form>

<?php
	   endif; // more than one table row displayed
	   
	   // we echoed the table, now return row count
	   return ( $count );
	   
	else :
		// return / echo a simple list
		$output = ( $rows ) ? "<ul class='post-revisions'>\n$rows</ul>" : '';
				
		if ( $echo ) {
			echo $output;
			return $count;	
		} else
			return $output;
	endif; // list or table

} // END FUNCTION rvy_list_post_revisions


function rvy_revisions_js() {
	$ajax_url = site_url( 'wp-admin/admin-ajax.php' );
?>
<script type="text/javascript">
/* <![CDATA[ */
try{convertEntities(wpAjax);}catch(e){};
var wpListL10n = {
	url: "<?php echo $ajax_url?>"
};
var postboxL10n = {
	requestFile: "<?php echo $ajax_url?>"
};
var revL10n = {
	publishOn: "<?php _e('Date as:', 'revisionary')?>",
	publishOnFuture: "<?php _e('Schedule for:', 'revisionary')?>",
	publishOnPast: "<?php _e('Published on:', 'revisionary')?>",
	privatelyPublished: "<?php _e('Privately Published:', 'revisionary')?>",
	published: "<?php _e('Published:', 'revisionary')?>",
	unsavedDate: "<?php _e('Unsaved Date Selection:', 'revisionary')?>"
};
/* ]]> */
</script>
<?php	
} // end function rvy_revisions_js

?>