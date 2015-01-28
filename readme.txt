=== Batch Comment Spam Deletion ===
Plugin URI: http://pippinsplugins.com/batch-comment-spam-deletion
Author URI: http://pippinsplugins.com
Contributors: mordauk
Donate link: http://pippinsplugins.com/support-the-site
Tags: comments, spam, comment
Requires at least: 3.6
Tested up to: 4.1
Stable Tag: 1.0.5

Modifies the Empty Spam action in WordPress to process the spam deletion in batches instead of all at once.

== Description ==

Modifies the Empty Spam action in WordPress to process the spam deletion in batches, allowing you to delete thousands or even hundreds of thousands of spam comments at once without killing your server.

Have you found a bug or have a suggestion or improvement you'd like to submit? This plugin is available on [Github](https://github.com/pippinsplugins/batch-comment-spam-deletion) and pull requests are welcome!

== Installation ==

1. Activate the plugin
2. Go to Comments > Spam
3. Use the "Empty Spam" button to clear out all spam comments

== Frequently Asked Questions ==

= Can I change the number of comments deleted per batch? =

Yes, there are two ways to do it.

1. You can modify the number via a filter, like this:

`
<?php
function pw_bcsd_per_batch( $per_batch ) {
	
	$per_batch = 50;

	return $per_batch;
}
add_filter( 'pw_bcpd_comments_per_batch', 'pw_bcsd_per_batch' );
`

2. You can modify it by adding a constant to your `wp-config.php` file:

`define( 'PW_BCPD_PER_BATCH', 50 );`

== Changelog ==

= 1.0.5 =

* Fix: missing closing tag

= 1.0.4 =

* Fix: Fatal error during batch processing

= 1.0.3 =

* New: added the ability to change the per-batch number via the PW_BCPD_PER_BATCH constant

= 1.0.2 =

* Fix: Undefined index error

= 1.0.1 =

* Fix: The Empty Trash button was accidentally removed from the Trash page

= 1.0 =

* First release!
