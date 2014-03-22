<?php
/*
Plugin Name: BlogNewsletter
Plugin URI: http://curlybracket.net/2013/12/02/blog-newsletter/
Description: Automatically send email on post publication to a specified list of email addresses
Version: 1.0
Author: Ulrike Uhlig
Author URI: http://curlybracket.net
License: GPL2
*/
/*
    Copyright 2013  Ulrike Uhlig  (email : rike@curlybracket.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php

/* checks status of post_ID and if in the future, schedules the sending event */
function mail_blog_post( $post_ID ) {
	$post = get_post($post_ID);
	if( ( $post->post_status == 'publish' ) && ( $post->original_post_status != 'publish' ) ) {
		send_blognewsletter($post_ID);
	} else if ( 'future' == $post->post_status ) {
	    $time = strtotime( $post->post_date_gmt . ' GMT' );
	    if ( $time > time() ) { // Uh oh, someone jumped the gun!
	        wp_clear_scheduled_hook( 'schedule_blognewsletter', array( $post_id ) ); // clear anything else in the system
	        wp_schedule_single_event( $time, 'schedule_blognewsletter', array( $post_ID ) );
	        return;
	    }
	}
}

/* Code inspired by http://codex.wordpress.org/Plugin_API/Action_Reference/publish_post
 * sends the actual email
 */
function send_blognewsletter( $post_ID ) {
	// get options
	$blog_newsletter_options = get_option('blog_newsletter_option_name');
	$from = $blog_newsletter_options['from'];
	if(!is_email($from)) $from = get_option( 'admin_email' ); // fall back to admin email if from !isset
	$subject = $blog_newsletter_options['subject'];
	$recipients = $blog_newsletter_options['recipients'];
	$extracontents = $blog_newsletter_options['extracontents'];

	// send mail
	if(!empty($recipients) AND !empty($from)) {
		$to = $from;
		$blogurl = get_bloginfo('url');
		$blogname =  get_bloginfo('name');
		$posttitle = str_replace("&rsquo;","'", get_the_title($post_ID));
		$posttitle = preg_replace('/[^a-zA-ZÀ-ÿ0-9’\']/', ' ', $posttitle);
		$postlink = get_permalink($post_ID);
		$headers[] = "From: $blogname <$from>";
		$headers[] = "Bcc: $recipients";
		$message = "$extracontents <br /><a href=\"$postlink\">$posttitle</a>.<br />--<br />$blogurl";
		add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
		wp_mail( $to, "[$blogname] $subject $posttitle", $message, $headers );
		return $post_ID;
	}
}
add_action( 'publish_post', 'mail_blog_post');
add_action( 'schedule_blognewsletter','send_blognewsletter', 10, 3 );

class BlogNewsletterSettingsPage {
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'Blog Newsletter',
            'manage_options',
            'blog-newsletter-admin',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'blog_newsletter_option_name' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Paramètres Newsletter</h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'blog_newsletter_option_group' );
                do_settings_sections( 'blog-newsletter-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'blog_newsletter_option_group', // Option group
            'blog_newsletter_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'blog_newsletter_section_general', // ID
            'Blog Newsletter Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'blog-newsletter-admin' // Page
        );

        add_settings_field(
            'from',
            'Sender email',
            array( $this, 'from_callback' ),
            'blog-newsletter-admin',
            'blog_newsletter_section_general'
        );
        add_settings_field(
            'recipients',
            'Comma separated list of receiver email addresses',
            array( $this, 'recipients_callback' ),
            'blog-newsletter-admin',
            'blog_newsletter_section_general'
        );
        add_settings_field(
            'extracontents',
            'Additional contents for the email message (appears before the post link)',
            array( $this, 'extracontents_callback' ),
            'blog-newsletter-admin',
            'blog_newsletter_section_general'
        );
        add_settings_field(
            'subject',
            'Email subject (ie. "New post:")',
            array( $this, 'subject_callback' ),
            'blog-newsletter-admin',
            'blog_newsletter_section_general'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {

        if( !empty( $input['from'] ) )
            $input['from'] = sanitize_email( $input['from'] );

        if( !empty( $input['recipients'] ) ) {
            $input['recipients'] = sanitize_text_field(str_replace( ';', ',', $input['recipients'] ));
            $tmprecipients = explode( ',', $input['recipients'] );
            foreach($tmprecipients as $recipient) {
                $tmp = sanitize_email( $recipient );
                if(!empty($tmp)) {
                    $clean_recipients[] = $tmp;
                }
            }
            $input['recipients'] = implode(',', $clean_recipients);
        }

        if( !empty( $input['subject'] ) )
            $input['subject'] = sanitize_text_field( $input['subject'] );

        if( !empty( $input['extracontents'] ) )
            $input['extracontents'] = sanitize_text_field( $input['extracontents'] );

        return $input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'The newsletter will be sent automatically to all recipients when you publish a post.';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function from_callback()
    {
        printf(
            '<input type="text" id="from" name="blog_newsletter_option_name[from]" value="%s" class="regular-text ltr" />',
            esc_attr( $this->options['from'])
        );
    }

    public function recipients_callback()
    {
        printf(
            '<textarea id="recipients" name="blog_newsletter_option_name[recipients]" class="large-text code">%s</textarea>',
            esc_attr( $this->options['recipients'])
        );
    }

    public function subject_callback()
    {
        printf(
			'<input type="text" id="subject" name="blog_newsletter_option_name[subject]" class="regular-text ltr" value="%s" />',
			esc_attr( $this->options['subject'])
        );
    }
    public function extracontents_callback()
    {
        printf(
            '<textarea id="extracontents" name="blog_newsletter_option_name[extracontents]" class="large-text code">%s</textarea>',
            esc_attr( $this->options['extracontents'])
        );
    }
}

if( is_admin() )
    $my_settings_page = new BlogNewsletterSettingsPage();
