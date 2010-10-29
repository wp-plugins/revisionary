=== Plugin Name ===
Contributors: kevinB
Donate link: http://agapetry.net/news/introducing-role-scoper/#role-scoper-download
Tags: revision, access, permissions, cms, user, groups, members, admin, pages, posts, page, Post
Requires at least: 3.0
Tested up to: 3.0.1
Stable Tag: 1.0.8

Enables qualified users to submit changes to currently published posts or pages.  These changes, if approved by an Editor, can be published immediately or scheduled for future publication.

== Description ==

Have you ever wanted to allow certain users to submit changes to published content, with an editor reviewing those changes before publication?

Doesn't it seem like setting a published post/page to a future date should schedule your changes to be published on that date, instead of unpublishing it until that date?

Revisionary enables qualified users to submit changes to currently published posts or pages.  Contributors also gain the ability to submit revisions to their own published content.  These changes, if approved by an Editor, can be published immediately or scheduled for future publication.

= Partial Feature List =
* Pending Revisions allow designated users to suggest changes to a currently published post/page
* Scheduled Revisions allow you to specify future changes to published content (either via Pending Revision approval or directly by fully qualified author/editor)
* Enchanced Revision Management Form
* Front-end preview display of Pending / Scheduled Revisions with "Publish Now" link
* New WordPress role, "Revisor" is a moderated Editor
* Works with blog-wide WordPress Roles, or in conjunction with <a href="http://wordpress.org/extend/plugins/role-scoper/">Role Scoper</a>

= Support =
* Most Bug Reports and Plugin Compatibility issues addressed promptly following your <a href="http://agapetry.net/forum/">support forum</a> submission.
* Author is available for professional consulting to meet your configuration, troubleshooting and customization needs.


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

= 1.1.RC3 - 29 Oct 2010 =
* BugFix : Revision preview link returned 404 (since 1.1.RC)
* BugFix : Revision Approval emails were not sent reliably with "Asynchronous Email" option enabled (since 1.0)
* BugFix : Custom taxonomy selection UI was not hidden when submitting a revision
* BugFix : In Quick Edit form, Published option sometimes displayed inappropriately


= 1.1.RC.2 - 11 Oct 2010 =
* BugFix : Listed revisions in Revision Editor were not linked for viewing / editing (since 1.1.RC)


= 1.1.RC - 8 Oct 2010 =
* Feature : Support Custom Post Types
* Change : Better internal support for custom statuses
* BugFix : On Options page, links to "Pending Revision Monitors" and "Scheduled Revision Monitors" were reversed
* Change : Raise minimum WP version to 3.0


= 1.0.7 - 21 June 2010 =
* BugFix : Revisionary prevented the normal scheduling of drafts for first-time publishing


= 1.0.6 - 18 June 2010 =
* Compat : CForms conflict broke TinyMCE edit form in Revisions Manager 


= 1.0.5 - 7 May 2010 =
* Compat : WP 3.0 Multisite menu items had invalid link


= 1.0.4 - 6 May 2010 =
* BugFix : Pending Revision Approval email used invalid permalink if permalink structure changed since original post storage
* BugFix : Schedule Revision Publication email used invalid permalink if permalink structure changed since original post storage


= 1.0.3 - 6 May 2010 =
* Compat : WP 3.0 elimination of page.php, edit-pages.php, page-new.php broke many aspects of page filtering
* BugFix : Trash link did not work for revisions in Edit Posts/Pages listing
* Change : Administrators and Editors now retain Quick Edit link for non-revisions in Edit Pages, Edit Posts listing
* BugFix : "Publishers to Notify" metabox was included even if no eligible recipients are designated


= 1.0.2 - 11 Mar 2010 =
* BugFix : Email notification caused error if Role Scoper was not activated
* BugFix : Database error message (nuisance) in non-MU installations (SELECT meta_key, meta_value FROM WHERE site_id...)
* BugFix : Publish Now link on Scheduled Revision preview did not work
* BugFix : With WP > 2.9, newly published revisions also remained listed as a Pending or Scheduled revision
* BugFix : With WP > 2.9, revision date selection UI showed "undefined" caption next to new date selection
* BugFix : Link for viewing Scheduled Revisions was captioned as "Pending Revisions" (since 1.0.1) 
* Compat : WMPL plugin


= 1.0.1 - 6 Feb 2010 =
* BugFix : 	Submitting a Pending Revision to a published Post failed with Fatal Error
* BugFix : 	PHP short tag caused Parse Error on servers which were not configured to support it
* Compat :  Support TinyMCE Advanced and WP Super Edit for custom editor buttons on Revision Management form
* Feature : Revision preview bar can be styled via CSS file
* Lang 	 : 	Fixed several string formatting issues for better translation support
* Change : 	Use https link for Revisionary css and js files if ssl is being used / forced for the current uri


= 1.0 - 30 Dec 2009 =
* Feature : Use Blog Title and Admin Email as from address in revision notices, instead of "WordPress <wordpress@>"
* BugFix : Revision Approval / Publication Notices used p=ID link instead of normal post permalink
* Compat : Display workaround instructions for FolioPress conflict with visual revision display


**1.0.RC1 - 12 Dec 2009**
Initial release.  Feature Changes and Bug Fixes are vs. Pending Revisions function in Role Scoper 1.0.8

= General: =
* Feature : Scheduled Revisions - submitter can specify a desired publication date for a revision
* Feature : Any user with the delete_published_ and edit_published capabilities for a post/page can administer its revisions (must include those caps in RS Editor definitions and assign that role)
* Feature : Scheduled Publishing and Email notification is processed asynchronously

= Revisions Manager: =
* Feature : Dedicated Revisions Manager provides more meaningful captions, classified by Past / Pending / Scheduled
* Feature : RS Revision Manager form displays visually via TinyMCE, supports editing of content, title and date
* Feature : Revisions Manager supports individual or bulk deletion
* Feature : Users can view their own Pending and Scheduled Revisions
* Feature : Users can delete their own Pending Revisions until approval

= Preview: =
* Feature : Preview a Pending Revision, with top link to publish / schedule it
* Feature : Preview a Scheduled Revision, with top link fo publish it now
* Feature : Preview a Past Revision, with top link for restore it

= WP Admin: =
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

= Notification: =
* Feature : Optional email (to editors or post author) on Pending Revision submission
* Feature : Optional email (to editors, post author, or revisor) on Pending Revision approval
* Feature : Optional email (to editors, post author, or revisor) on Scheduled Revision publication
* Feature : If Role Scoper is active, Editors notification group can be customized via User Group

