=== BlogNewsletter ===
Contributors: veganist
Tags: email, newsletter
Requires at least: 3.5
Tested up to: 4.2.4
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin automatically sends an email when you publish a post.

== Description ==

This plugin automatically sends an email when you publish a post to a list of people. This also works for scheduled posts.
The email contains the title, excerpt, thumbnail and a link to the latest post.
You may also add your logo and some text of your choice to the email.

You may specify the list of receivers on the plugin's options page by copy-pasting a comma separated list of email addresses.
People will receive the blog newsletter in Bcc, they will not see the other receivers.

You may also specify a sender address on the options page. If you do not specify an address, the sender address defaults to admin's email address.

== Installation ==

1. Unzip and upload `/blognewsletter/` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the list of email addresses in the settings menu

== Frequently Asked Questions ==

= When I unpublish and then publish my post again, is an email being sent a second time? =
Yes.

= Can I customize the look and feel of the email? =
No. It's a very simple HTML email being sent using Wordpress' email function. You can simply add your logo.

= Does it work for custom post types? =
No. But you can add a simple line to the plugin to make it work. If your custom post type is called "product", for example, you'd simply add:
`add_action( 'publish_product', 'mail_blog_post', 10, 2);`

= Can i customize the email for each receiver? =
No, you can't have a "Hello Person XYZ" as greeting if that is what you are looking for.

= I'm using Google's SMTP instead of Wordpress' email function and no mail is being sent. =
Google's SMTP allows only for 99 emails per day to be sent. So if you added more addresses, it simply won't work.
You could create a list or an alias and send your blognewsletter to that address instead.

== Changelog ==

= 1.1 =
* Show post excerpt in email.
* Show post thumbnail in email.
* Allow adding a logo URL to show in email.

= 1.0 =
* Initial release
