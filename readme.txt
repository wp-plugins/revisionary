=== Plugin Name ===
Contributors: kevinB
Donate link: http://agapetry.net/news/introducing-role-scoper/#role-scoper-download
Tags: revision, access, permissions, cms, user, groups, members, admin, pages, posts, page, Post
Requires at least: 2.6
Tested up to: 2.9
Stable Tag: 1.0.RC1

Enables qualified users to submit changes to currently published posts or pages.  These changes, if approved by an Editor, can be published immediately or scheduled for future publication.

== Description ==
Have you ever wanted to allow certain users to submit changes to published content, with an editor reviewing those changes before publication?

Doesn't it seem like setting a published post/page to a future date should schedule your changes to be published on that date, instead of unpublishing it until that date?

Revisionary enables qualified users to submit changes to currently published posts or pages.  Contributors also gain the ability to submit revisions to their own published content.  These changes, if approved by an Editor, can be published immediately or scheduled for future publication.

= Partial Feature List =
* Pending Revisions allow designated users to suggest changes to a currently published post/page
* Scheduled Revisions allow you to specify future changes to published content
* Enchanced Revision Management Form
* Front-end preview display of Pending / Scheduled Revisions with "Publish Now" link
* New WordPress role, "Revisor" is a moderated Editor
* Works with blog-wide WordPress Roles, or in conjunction with Role Scoper

= Support =
* Most Bug Reports and Plugin Compatibility issues addressed promptly following your <a href="http://agapetry.net/forum/">support forum</a> submission.
* Author is available for professional consulting to meet your configuration, troubleshooting and customization needs .


== Installation ==
Revisionary can be installed automatically via the Plugins tab in your blog administration panel.

= To install manually instead: =
1. Upload `revisionary&#95;?.zip` to the `/wp-content/plugins/` directory
1. Extract `revisionary&#95;?.zip` into the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Screenshots ==

1. Pending Revision Creation
2. Pending Revision Confirmation
3. Pending Revision Email Notification
4. Dashboard Right Now Count
5. Pending Revisions in Edit Pages Listing
6. Editor / Administrator views submission in Revisions Manager
7. Difference Display in Revisions Manager
8. Editor / Administrator views preview of Pending Revision


== Changelog ==

**1.0.RC1 - 12 Dec 2009**
Initial release.  Feature Changes and Bug Fixes are vs. Pending Revisions function in Role Scoper 1.0.8

=General:=
* Feature : Scheduled Revisions - submitter can specify a desired publication date for a revision
* Feature : Any user with the delete_published_ and edit_published capabilities for a post/page can administer its revisions (must include those caps in RS Editor definitions and assign that role)
* Feature : Scheduled Publishing and Email notification is processed asynchronously

=Revisions Manager:=
* Feature : Dedicated Revisions Manager provides more meaningful captions, classified by Past / Pending / Scheduled
* Feature : RS Revision Manager form displays visually via TinyMCE, supports editing of content, title and date
* Feature : Revisions Manager supports individual or bulk deletion
* Feature : Users can view their own Pending and Scheduled Revisions
* Feature : Users can delete their own Pending Revisions until approval

=Preview:=
* Feature : Preview a Pending Revision, with top link to publish / schedule it
* Feature : Preview a Scheduled Revision, with top link fo publish it now
* Feature : Preview a Past Revision, with top link for restore it

=WP Admin:=
* Feature : Pending and Scheduled revisions are included in Edit Posts / Pages list for all qualified users
* Feature : Delete, View links on revisions in Edit Posts / Pages list redirect to RS Revisions Manager
* Feature : Add pending posts and pages total to Dashboard Right Now list (includes both new post submissions and Pending Revisions)
* Feature : Metaboxes in Edit Post/Page form for Pending / Scheduled Revisions
* BugFix : Multiple Pending Revions created by autosave
* BugFix : Users cannot preview their changes before submitting a Pending Revision on a published post/page
* BugFix : Pending Post Revisions were not visible to Administrator in Edit Posts list
* BugFix : Both Pending Page Revisions and Pending Post Revisions were visible to Administator in Edit Pages list
* BugFix : Pending Revisions were not included in list for restoration
* BugFix : Bulk Deletion attempt failed when pending / scheduled revisions were included in selection 

=Notification:=
* Feature : Optional email (to editors or post author) on Pending Revision submission
* Feature : Optional email (to editors, post author, or revisor) on Pending Revision approval
* Feature : Optional email (to editors, post author, or revisor) on Scheduled Revision publication
* Feature : If Role Scoper is active, Editors notification group can be customized via User Group

