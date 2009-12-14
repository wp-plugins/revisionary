<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

/**
 * revisions.php
 * 
 * Revisions Manager for Revisionary plugin, derived and heavily expanded from WP 2.8.4 core
 *
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */
 
include_once( 'revision-ui_rvy.php' ); 

//wp_reset_vars( array('revision', 'left', 'right', 'action', 'revision_status') );

if ( ! empty($_GET['revision']) )
	$revision_id = absint($_GET['revision']);

if ( ! empty($_GET['left']) )
	$left = absint($_GET['left']);
else
	$left = '';

if ( ! empty($_GET['right']) )
	$right = absint($_GET['right']);
else
	$right = '';

if ( ! empty($_GET['revision_status']) )
	$revision_status = $_GET['revision_status'];
else
	$revision_status = '';
	
if ( ! empty($_GET['action']) )
	$action = $_GET['action'];
else
	$action = '';

if ( ! empty($_GET['restored_post'] ) )
	$revision_id = $_GET['restored_post'];

if ( ! $revision_id && ! $left && ! $right ) {
	echo( '<div><br />' );
	_e( 'No revision specified.', 'revisionary');
	echo( '</div>' );
	return;
}

$revision_status_captions = array( 'inherit' => __( 'Past', 'revisionary' ), 'pending' => __awp('Pending', 'revisionary'), 'future' => __awp( 'Scheduled', 'revisionary' ) );

switch ( $action ) :
case 'diff' :
	if ( !$left_revision  = get_post( $left ) )
		break;
	if ( !$right_revision = get_post( $right ) )
		break;

	// actual status of compared objects overrides any revision_Status arg passed in
	if ( 'revision' == $left_revision->post_type )
		$revision_status = $left_revision->post_status;
	else
		$revision_status = $right_revision->post_status;
		
	if ( !current_user_can( 'read_post', $left_revision->ID ) || !current_user_can( 'read_post', $right_revision->ID ) )
		break;

	if ( $left_revision->ID == $right_revision->post_parent ) // right is a revision of left
		$rvy_post = $left_revision;
	elseif ( $left_revision->post_parent == $right_revision->ID ) // left is a revision of right
		$rvy_post = $right_revision;
	elseif ( $left_revision->post_parent == $right_revision->post_parent ) // both are revisions of common parent
		$rvy_post = get_post( $left_revision->post_parent );
	else
		break; // Don't diff two unrelated revisions

	if (
		// They're the same
		$left_revision->ID == $right_revision->ID
	||
		// Neither is a revision
		( !wp_get_post_revision( $left_revision->ID ) && !wp_get_post_revision( $right_revision->ID ) )
	)
		break;

	$post_title = "<a href='post.php?action=edit&post=$rvy_post->ID'>$rvy_post->post_title</a>";

	$h2 = sprintf( __( '%1$s Revisions for &#8220;%2$s&#8221;', 'revisionary' ), $revision_status_captions[$revision_status], $post_title );

	$left  = $left_revision->ID;
	$right = $right_revision->ID;

	break;
case 'view' :
default :
	$left = 0;
	$right = 0;
	$h2 = '';
	
	if ( ! $revision = wp_get_post_revision( $revision_id ) ) {
		// Support published post/page in revision argument
		if ( ! $rvy_post = get_post( $revision_id) )
			break;

		if ( ! in_array( $rvy_post->post_type, array( 'post', 'page' ) ) ) {
			$rvy_post = '';  // todo: is this necessary?
			break;
		}

		// revision_id is for a published post.  List all its revisions - either for type specified or default to past
		if ( ! $revision_status )
			$revision_status = 'inherit';
			
		if ( !current_user_can( 'read_post', $rvy_post->ID ) )
			break;
			
	} else {
		if ( !$rvy_post = get_post( $revision->post_parent ) )
			break;

		// actual status of compared objects overrides any revision_Status arg passed in
		$revision_status = $revision->post_status;	
		
		if ( !current_user_can( 'read_post', $revision->ID ) || !current_user_can( 'read_post', $rvy_post->ID ) )
			break;
	}
		
	// Sets up the diff radio buttons
	$right = $rvy_post->ID;

	// temporarily remove filter so we don't change it into a revisions.php link
	global $revisionary;
	remove_filter( 'get_edit_post_link', array($revisionary->admin, 'flt_edit_post_link'), 10, 3 );
		
	if ( $revision ) {
		$left = $revision_id;
		$post_title = "<a href='post.php?action=edit&post=$rvy_post->ID'>$rvy_post->post_title</a>";

		$revision_title = wp_post_revision_title( $revision, false );
		
		$caption = ( strpos($revision->post_name, '-autosave' ) ) ? '' : $revision_status_captions[$revision_status];
		
		// TODO: combine this code with captions for front-end preview approval bar
		switch ( $revision_status ) :
		case 'inherit':
			if ( strpos( $revision->post_name, '-autosave' ) )
				$h2 = sprintf( __( 'Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			else
				$h2 = sprintf( __( 'Past Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			break;
		case 'pending':
			$h2 = sprintf( __( 'Pending Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			break;
		case 'future':
			$h2 = sprintf( __( 'Scheduled Revision of &#8220;%1$s&#8221;', 'revisionary' ), $post_title);
			break;
		endswitch;

		if ( ('diff' != $action) && ($rvy_post->ID != $revision->ID) ) {
			if ( agp_user_can( "edit_{$rvy_post->post_type}", $rvy_post->ID, '', array( 'skip_revision_allowance' => true ) ) ) {
				switch( $revision->post_status ) :
				case 'future' :
					$caption = str_replace( ' ', '&nbsp;', __('Publish Now', 'revisionary') );
					$link = wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'diff' => false, 'action' => 'restore' ) ), "restore-post_$rvy_post->ID|$revision->ID" );
					break;
				case 'pending' :
					if ( strtotime($revision->post_date_gmt) > agp_time_gmt() ) {
						$caption = str_replace( ' ', '&nbsp;', __('Schedule Now', 'revisionary') );
					} else {
						$caption = str_replace( ' ', '&nbsp;', __('Publish Now', 'revisionary') );
					}
					
					$link = wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'diff' => false, 'action' => 'approve' ) ), "approve-post_$rvy_post->ID|$revision->ID" );
					break;
				default :
					$caption = str_replace( ' ', '&nbsp;', __('Restore Now', 'revisionary') );
					$link = wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'diff' => false, 'action' => 'restore' ) ), "restore-post_$rvy_post->ID|$revision->ID" );
				endswitch;
		
				$restore_link = '<a href="' . $link . '">' .$caption . "</a> ";
			} else
				$restore_link = '';
		}
		
	} else {
		$revision = $rvy_post;	

		$link = apply_filters( 'get_edit_post_link', admin_url("{$rvy_post->post_type}.php?action=edit&post=$revision_id"), $revision_id, '' );

		$post_title = "<a href='post.php?action=edit&post=$rvy_post->ID'>$rvy_post->post_title</a>";
		
		$revision_title = wp_post_revision_title( $revision, false );
		$h2 = sprintf( __( '&#8220;%1$s&#8221; (Current Revision)' ), $post_title );
	}

	add_filter( 'get_edit_post_link', array($revisionary->admin, 'flt_edit_post_link'), 10, 3 );
	
	// pending revisions are newer than current revision
	if ( 'pending' == $revision_status ) {
		$buffer_left = $left;
		$left  = $right;
		$right = $buffer_left;
	}

	break;
endswitch;


if ( empty($revision) && empty($right_revision) && empty($left_revision) ) {
	echo( '<div><br />' );
	_e( 'The requested revision does not exist.', 'revisionary');
	echo( '</div>' );
	return;
}

if ( ! $revision_status )
	$revision_status = 'inherit'; 	// default to showing past revisions
?>

<div class="wrap">

<form name="post" action="" method="post" id="post">

<?php
global $current_user;

$can_edit = ('revision' == $revision->post_type ) && (
    ( ( $revision->post_author == $current_user->ID ) && ( 'pending' == $revision->post_status ) ) 
	|| agp_user_can( "edit_{$rvy_post->post_type}", $rvy_post->ID, '', array( 'skip_revision_allowance' => true ) ) );

if ( $can_edit ) {
	wp_nonce_field('update-revision_' .  $revision->ID);

	echo "<input type='hidden' id='revision_ID' name='revision_ID' value='" . esc_attr($revision->ID) . "' />";
}
?>

<table style="width: 100%;clear: both;margin: 0 0 1em 0;padding: 0">
<tr><td style="vertical-align:top">
<h2 style="margin:0"><?php 
echo $h2; 
if ( ! empty($restore_link) )
	echo "<span class='rs-revision_top_action' style='margin-left: 2em'> $restore_link</span>";	
?></h2>

<?php
	$msg = '';

	if ( ! empty($_GET['deleted']) )
		$msg = __('The revision was deleted.', 'revisionary');

	elseif ( isset($_GET['bulk_deleted']) )
		$msg = sprintf( _n( '%s revision was deleted', '%s revisions were deleted', $_GET['bulk_deleted'] ), number_format_i18n( $_GET['bulk_deleted'] ) );
		
	elseif ( ! empty($_GET['rvy_updated']) )
		$msg = __('The revision was updated.', 'revisionary');
		
	elseif ( ! empty($_GET['restored_post'] ) )
		$msg = __('The revision was restored.', 'revisionary');
		
	elseif ( ! empty($_GET['scheduled'] ) )
		$msg = __('The revision was scheduled for publication.', 'revisionary');

	elseif ( ! empty($_GET['published_post'] ) )
		$msg = __('The revision was published.', 'revisionary');

	elseif ( ! empty($_GET['delete_request']) ) {
		if ( current_user_can( "delete_{$rvy_post->post_type}", $rvy_post->ID ) || ( ( 'pending' == $revision->post_status ) && ( $revision->post_author == $current_user->ID ) ) )
			$msg = __('To delete the revision, click the link below.', 'revisionary');
		else
			$msg = __('You do not have permission to delete that revision.', 'revisionary');

	} elseif ( ! empty($_GET['unscheduled'] ) )
		$msg = __('The revision was unscheduled.', 'revisionary');

	
	if ( $msg ) {
		echo '<div id="message" class="updated fade clear" style="margin-bottom: 0"><p>';
		echo $msg;
		echo '</p></div><br />';	
	}
?>
</td>
<?
if ( ( ! $action || ( 'view' == $action ) ) && ( $revision ) ) {
echo '<td style="text-align:right;padding-top:1em;">';
	
	// date stuff
	// translators: Publish box date formt, see http://php.net/date
	$datef = __awp( 'M j, Y @ G:i' );

	if ( in_array( $revision->post_status, array( 'publish', 'private' ) ) )
		$stamp = __('Published on: <strong>%1$s</strong>');
	elseif ( 'future' == $revision->post_status )
		$stamp = __('Scheduled for: <strong>%1$s</strong>');
	elseif ( 'pending' == $revision->post_status ) {
		if ( strtotime($revision->post_date_gmt) > agp_time_gmt() )
			$stamp = __('Requested Publish Date: <strong>%1$s</strong>');
		else
			$stamp = __('Requested Publish Date: <strong>Immediate</strong>');
	} else
		$stamp = __('Modified on: <strong>%1$s</strong>');

	$use_date = ( 'inherit' == $revision->post_status ) ? $revision->post_modified : $revision->post_date;
	
	$date = agp_date_i18n( $datef, strtotime( $use_date ) );
	
	echo '<div id="rvy_time" class="curtime clear"><span id="saved_timestamp">';
	printf($stamp, $date);
	echo '</span>';
	
	if ( $can_edit && in_array( $revision->post_status, array( 'pending', 'future' ) ) ) {
		echo '&nbsp;<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" tabindex="4">';
		echo __awp('Edit');
		echo '</a>';
	}
	
	echo '<div id="selected_timestamp_div" style="display:none;">';
	echo '<span id="selected_timestamp"></span>';
	echo '</div>';
	
	if ( $can_edit && in_array( $revision->post_status, array( 'pending', 'future' ) ) ) {
		echo '<div id="timestampdiv" class="hide-if-js clear">';
		
		global $post;	// touch_time function requires this as of WP 2.8
		$buffer_post = $post;
		$post = $revision;
		touch_time(($action == 'edit'),1,4);
		$post = $buffer_post;
		
		echo '</div>';
		
		?>
		<div id="rvy_revision_edit_secondary_div" style="display:none;float:right;margin:0.5em 0 1em 0">
		<input name="rvy_revision_edit" type="submit" class="button-primary" id="rvy_revision_edit_secondary" tabindex="5" accesskey="p" value="<?php esc_attr_e('Update Revision', 'revisionary') ?>" />
		</div>
		<?php
	}
	echo '</div>';

echo '</td></tr>';
echo '</table>';
	
	echo '
	<div id="poststuff" class="metabox-holder" style="margin-top:-2em">
	<div id="post-body">
	<div id="post-body-content">
	';
	
	// title stuff
	echo '
	<div id="titlediv" style="clear:both">
	<div id="titlewrap">
		<label class="screen-reader-text" for="title">';
		
	echo( __awp('Title') );
	$disabled = ( $can_edit ) ? '' : 'disabled="disabled"';
	
	echo '
	</label><input type="text" name="post_title" size="30" tabindex="1" value="';
	
	echo esc_attr( htmlspecialchars( $rvy_post->post_title ) );
	
	echo '" id="title" ' . $disabled . '/></div></div>';

		
	// post content
	$id = ( user_can_richedit() ) ? 'postdivrich' : 'postdiv';
	echo "<div id='$id' class='postarea' style='clear: both'>";
	$content = apply_filters( "_wp_post_revision_field_post_content", $revision->post_content, 'post_content' );
		
	the_editor($content, 'content', 'title', false);
	echo '</div>';
	
	if ( $can_edit ) {
?>
<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update Revision', 'revisionary') ?>" />
<input name="rvy_revision_edit" type="submit" class="button-primary" id="rvy_revision_edit" tabindex="5" accesskey="p" value="<?php esc_attr_e('Update Revision', 'revisionary') ?>" />
</div>
<?php
	}
		
	echo '
	</div>
	</div>
	</div>
	';
} else 
	echo '</tr></table>';
?>

</form>

<div class="ie-fixed">
<?php if ( 'diff' == $action ) : ?>
<?php
if ( strtotime($left_revision->post_modified) > strtotime($right_revision->post_modified) ) {
	$temp = $left_revision;
	$left_revision = $right_revision;
	$right_revision = $temp;
}

$title_left = sprintf( __('Older: modified %s', 'scoper'), RevisionaryAdmin::convert_link( rvy_post_revision_title( $left_revision, true, 'post_modified' ), 'revision', 'manage' ) );

$title_right = sprintf( __('Newer: modified %s', 'scoper'), RevisionaryAdmin::convert_link( rvy_post_revision_title( $right_revision, true, 'post_modified' ), 'revision', 'manage' ) );

endif;


$identical = true;
foreach ( _wp_post_revision_fields() as $field => $field_title ) :
	if ( ( 'post_content' == $field ) && ( ! $action || ( 'view' == $action ) ) )
		continue;
		
	if ( 'diff' == $action ) {
		$left_content = apply_filters( "_wp_post_revision_field_$field", $left_revision->$field, $field );
		$right_content = apply_filters( "_wp_post_revision_field_$field", $right_revision->$field, $field );
		
		if ( rvy_get_option('diff_display_strip_tags') ) {
			$left_content = strip_tags($left_content);
			$right_content = strip_tags($right_content);
		}
		
		if ( !$content = wp_text_diff( $left_content, $right_content, array( 'title_left' => $title_left, 'title_right' => $title_right ) ) )
			continue; // There is no difference between left and right
		$identical = false;
	} elseif ( $revision ) {
		if ( $revision && ( 'post_title' == $field ) ) {
			if ( 'revision' != $revision->post_type )	// no need to redisplay title
				continue;
			
			if ( $revision->post_title == $rvy_post->post_title )
				continue;
		}
		
		$content = apply_filters( "_wp_post_revision_field_$field", $revision->$field, $field );
	}
	
	if ( ! empty($content) ) :?>
	<div id="revision-field-<?php echo $field; ?>">
		<p style="margin: 2em 0 0 0"><strong>
		<?php 
		echo esc_html( $field_title ); 
		?>
		</strong></p>
		
		<div class="pre clear"><?php echo $content; ?></div>
	</div>
	<?php endif;

	$title_left = '';
	$title_right = '';
	
endforeach;

if ( 'diff' == $action && $identical ) :
	?>

	<div class="updated"><p><?php _e( 'These revisions are identical.' ); ?></p></div>

	<?php

endif;

?>

</div>

<br class="clear" /><br />

<?php
if ( $is_administrator = is_content_administrator_rvy() ) {
	global $wpdb;
	$results = $wpdb->get_results( "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent = '$rvy_post->ID' GROUP BY post_status" );
	
	$num_revisions = array( 'inherit' => 0, 'pending' => 0, 'future' => 0 );
	foreach( $results as $row )
		$num_revisions[$row->post_status] = $row->num_posts;
		
	$num_revisions = (object) $num_revisions;
}

$status_links = '<ul class="subsubsub">';
foreach ( $revision_status_captions as $_revision_status => $status_caption ) {
	$post_id = ( ! empty($rvy_post->ID) ) ? $rvy_post->ID : $revision_id;
	$link = "admin.php?page=rvy-revisions&amp;revision={$post_id}&amp;revision_status=$_revision_status";
	$class = ( $revision_status == $_revision_status ) ? ' class="current" style="font-size: 150%"' : '';
	
	if ( $is_administrator ) {
		$label = __( '%1$s Revisions<span class="count"> (%2$s)</span>', 'revisionary' );
		$status_links .= "<li><a href='$link' $class><span style='margin-right: 1em'>" . sprintf( _nx( $label, $label, $num_revisions->$_revision_status, $label ), $status_caption, number_format_i18n( $num_revisions->$_revision_status ) ) . '</span></a></li>';
	} else {
		$label = __( '%1$s Revisions', 'revisionary' );
		$status_links .= "<li><a href='$link' $class><span style='margin-right: 1em'>" . sprintf( $label, $status_caption ) . '</span></a></li>';
	}
}
$status_links .= '</ul>';

echo $status_links;

// we temporarily removed this above
add_filter( "_wp_post_revision_field_$field", 'htmlspecialchars' );
	
$args = array( 'format' => 'form-table', 'parent' => true, 'right' => $right, 'left' => $left, 'current_id' => $revision_id );

$count = rvy_list_post_revisions( $rvy_post, $revision_status, $args );
if ( $count < 2 ) {
	echo( '<br class="clear" /><p>' );
	printf( __( 'no %s revisions available.', 'revisionary'), strtolower($revision_status_captions[$revision_status]) );
	echo( '</p>' );
}

?>

</div>