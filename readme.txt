=== Insert Pages ===
Contributors: figureone, the_magician
Tags: insert, pages, shortcode, embed
Requires at least: 3.0.1
Tested up to: 4.5.3
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Insert Pages lets you embed any WordPress content (e.g., pages, posts, custom post types) into other WordPress content using the Shortcode API. It also includes a widget for inserting pages into any widget area.

== Description ==

Insert Pages lets you embed any WordPress content (e.g., pages, posts, custom post types) into other WordPress content using the Shortcode API. It also includes a widget for inserting pages into any widget area.

The real power of Insert Pages comes when you start creating custom post types, either [programmatically in your theme](http://codex.wordpress.org/Post_Types), or using another plugin like [Custom Post Type UI](http://wordpress.org/plugins/custom-post-type-ui/). You can then abstract away common data types (like videos, quizzes, due dates) into their own custom post types, and then show those pieces of content within your normal pages and posts by Inserting them as a shortcode.

Here are two quick example use cases:

### Normal Use
Say you teach a course and you're constantly referring to an assignment due date in your course website. The next semester the due date changes, and you have to go change all of the locations you referred to it. Instead, you'd rather just change the date once! With Insert Pages, you can do the following:

1. Create a custom post type called **Due Date**.
1. Create a new *Due Date* called **Assignment 1 Due Date** with **Fri Nov 22, 2013** as its content.
1. Edit all the pages where the due date occurs and use the *Insert Pages* toolbar button to insert a reference to the *Due Date* you just created. Be sure to set the *Display* to **Content** so *Fri Nov 22, 2013* shows wherever you insert it. The shortcode you just created should look something like this: `[insert page='assignment-1-due-date' display='content']`
1. That's it! Now, when you want to change the due date, just edit the *Assignment 1 Due Date* custom post you created, and it will automatically be updated on all the pages you inserted it on.

### Advanced Use
Say your site has a lot of video content, and you want to include video transcripts and video lengths along with the videos wherever you show them. You could just paste the transcripts into the page content under the video, but then you'd have to do this on every page the video showed on. (It's also just a bad idea, architecturally!) With Insert Pages, you can use a custom post type and create a custom theme template to display your videos+transcripts+lengths just the way you want!

1. Create a custom post type called **Video**.
1. Use a plugin like [Advanced Custom Fields](http://wordpress.org/plugins/advanced-custom-fields/) to add extra fields to your new *Video* custom post type. Add a **Video URL** field, a **Transcript** field, and a **Video Length** field.
1. Create a new *Video* called **My Awesome Video** with the following values in its fields:
	* *Video URL*: **http://www.youtube.com/watch?v=oHg5SJYRHA0**
	* *Transcript*: **We're no strangers to love, You know the rules and so do I...**
	* *Video Length*: **3:34**
1. Create a template in your theme so we can display the video content as we want. I won't cover this step here since it's pretty involved, but you can find more help in the [WordPress Codex](http://codex.wordpress.org/Theme_Development#Custom_Page_Templates). Let's assume you created a template called **Video with transcript** (video-with-transcript.php) that shows the youtube video in a [fancybox](http://fancybox.net/), and includes a button that shows the text transcript when a user clicks on it.
1. Edit the pages where you want the video to show up and use the *Insert Pages* toolbar button to insert a reference to the *Video* you just created. Be sure to set the *Display* to **Use a custom template**, and select your new template **Video with transcript**. The shortcode you just created should look something like this: `[insert page='my-awesome-video' display='video-with-transcript.php']`
1. That's it! Now you can create all sorts of video content and know that it's being tracked cleanly in the database as its own custom post type, and you can place videos all over your site and not worry about lots of duplicate content.

The possibilities are endless!

== Installation ==

1. Upload "insert-pages" to the "/wp-content/plugins/" directory.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Use the toolbar button while editing any page to insert any other page.

== Frequently Asked Questions ==

= How do I limit the list of pages in the dialog to certain post types? =

You can hook into the 'insert_pages_available_post_types' filter to limit the post types displayed in the dialog. Here's an example filter that just shows Posts:

`/**
 * Filter the list of post types to show in the insert pages dialog.
 *
 * @param $post_types Array of post type names to include in the insert pages list.
 */
function only_insert_posts( $post_types ) {
    return array( 'post' );
}
add_filter( 'insert_pages_available_post_types', 'only_insert_posts' );`

= Do I have to use the toolbar button to Insert Pages? =

No! You can type out the shortcode yourself if you'd like, it's easy. Here's the format:

`[insert page='{slug}|{id}' display='title|link|content|all|{custom-template.php}']`

Examples:

* `[insert page='your-page-slug' display='link']`
* `[insert page='your-page-slug' display='your-custom-template.php']`
* `[insert page='123' display='all']`

= Anything I should be careful of? =

Just one! The plugin prevents you from embedding a page in itself, but you can totally create a loop that will prevent a page from rendering. Say on page A you embed page B, but on page B you also embed page A. As the plugin tries to render either page, it will keep going down the rabbit hole until your server runs out of memory. Future versions should have a way to prevent this behavior!

== Screenshots ==

1. Insert Pages toolbar button.
2. Insert Pages browser.
3. Insert Pages shortcode example.

== Changelog ==

= 3.1.5 =
* Fix for php warning when inserting page outside of The Loop while using Beaver Builder. Props @jeffreytanuwidjaja for the report.

= 3.1.4 =
* Compatibility for for php versions lower than 5.3. Replace closure with create_function().

= 3.1.3 =
* Prevent menu items and page attachments from being insertable; this fixes problems with inserting pages via slug when there is a menu item with the same slug as a page/post. Props @k7f7 for tracking this one down!

= 3.1.2 =
* Fix for custom template dropdown not enabling when configuring the widget on the theme customizer page (customize.php). Props @aassouad for finding this!

= 3.1.1 =
* Fix: Add compatibility for PHP 5.2 in the widget registration code. See https://codex.wordpress.org/Widgets_API

= 3.1 =
* Feature: Insert Page widget. Go to Appearance > Widgets to add the Insert Page widget to any of your widget areas. Specify a page slug or ID in the widget, and that page will get displayed in the widget area.

= 3.0.2 =
* Hotfix: Inserting posts with custom paths using legacy insert method.

= 3.0.1 =
* Hotfix: Version 3 broke some plugin compatibility (most notably with Beaver Builder and Page Builder by SiteOrigin). This update should restore functionality.
* Hotfix: Version 3 broke some page displays (e.g., content, all). This update should restore functionality.

= 3.0 =
* Hotfix: 2.9.1 broke extra classes added to the inserted page wrapper. Props @philipsacht!
* Feature: Expose extra classes and inline status in tinymce dialog.
* One more API change to insert_pages_wrap_content_filter (2nd parameter is a WP_Post now instead of an array of WP_Posts, since we only ever insert one page).
Example 1:
`/**
 * Enable nested shortcodes by hooking into insert_pages_wrap_content.
 *
 * @param string $content The post content of the inserted page.
 * @param array $inserted_page The post object returned from querying the inserted page.
 * @param array $attributes Extra parameters modifying the inserted page.
 *   page: Page ID or slug of page to be inserted.
 *   display: Content to display from inserted page.
 *   class: Extra classes to add to inserted page wrapper element.
 *   inline: Boolean indicating wrapper element should be a span.
 *   should_apply_nesting_check: Whether to disable nested inserted pages.
 *   should_apply_the_content_filter: Whether to apply the_content filter to post contents and excerpts.
 *   wrapper_tag: Tag to use for the wrapper element (e.g., div, span).
 */
function your_custom_wrapper_function( $content, $inserted_page, $attributes ) {
    return do_shortcode( $content );
}
add_filter( 'insert_pages_wrap_content', 'your_custom_wrapper_function', 9, 3 );`
Example 2:
`/**
 * Completely modify markup generated by Insert Pages by hooking into insert_pages_wrap_content.
 *
 * @param string $content The post content of the inserted page.
 * @param array $inserted_page The post object returned from querying the inserted page.
 * @param array $attributes Extra parameters modifying the inserted page.
 *   page: Page ID or slug of page to be inserted.
 *   display: Content to display from inserted page.
 *   class: Extra classes to add to inserted page wrapper element.
 *   inline: Boolean indicating wrapper element should be a span.
 *   should_apply_nesting_check: Whether to disable nested inserted pages.
 *   should_apply_the_content_filter: Whether to apply the_content filter to post contents and excerpts.
 *   wrapper_tag: Tag to use for the wrapper element (e.g., div, span).
 */
function your_custom_wrapper_function( $content, $inserted_page, $attributes ) {
    // Remove the default filter that wraps the content in a div or span.
    remove_all_filters( 'insert_pages_wrap_content', 10 );
    // Return your custom wrapper around the content.
    return "<section class='my-section {$attributes['class']}'>$content</section>";
}
add_filter( 'insert_pages_wrap_content', 'your_custom_wrapper_function', 9, 3 );`

= 2.9.1 =
* API Change: modify insert_pages_wrap_content filter. Props @heiglandreas.

= 2.9 =
* Add filter for altering the markup generated by Insert Pages. This filter is used internally at priority 10, so if you want to modify $content, do it earlier (priority 1-9); if you want to reconstruct the generated markup using the supplied parameters, do it after (priority 11+). Props @heiglandreas!

= 2.8 =
* Feature: Add options page with option to insert page IDs instead of page slugs (users of WPML will need this feature if translated pages all share the same page slug).
* Feature: Inserted pages with Beaver Builder enabled now embed correctly.
* Fix: TinyMCE toolbar button states (active, disabled) have been fixed.
* Fix: TinyMCE cursor detection inside an existing shortcode has been fixed.
* Fix: Expanded options in Insert Pages popup now correctly remembers last choice.
* Fix: Restore missing spinners in search dialog.
* Fix: prevent PHP warning when rendering wp_editor() outside of edit context. Props Jerry Benton.

= 2.7.2 =
* Add shortcode attribute to wrap inserted content in an inline element (span) instead of a block level element (div). Example usage:
`Lorem ipsum [insert page='my-page' display='content' inline] dolor sit amet.`
* Add filter to wrap inserted content in an inline element (span) instead of a block level element (div). Example usage:
`function theme_init() {
    // Wrap all inserted content in inline elements (span).
    add_filter( 'insert_pages_use_inline_wrapper', function ( $should_use_inline_wrapper ) { return true; } );
}
add_action( 'init', 'theme_init' );`

= 2.7.1 =
* Add filter to show a message when an inserted page cannot be found. Example usage:
`function theme_init() {
    // Show a message in place of an inserted page if that page cannot be found.
    add_filter( 'insert_pages_not_found_message', function ( $content ) { return 'Page could not be found.'; } );
}
add_action( 'init', 'theme_init' );`

= 2.7 =
* Fix: Prevent Insert Pages from breaking tinymce if wp_editor() is called outside of an admin context.

= 2.6 =
* Fix: Query data wasn't getting reset properly when viewing a category archive containing a post with an inserted page, causing date and author information in post footers in the twentyfifteen theme to show incorrect information. This has been resolved.

= 2.5 =
* Maintenance release: prevent infinite loops when using a custom template that doesn't call the_post().

= 2.4 =
* Add insert_pages_apply_nesting_check filter. Use it to disable the deep nesting check which prevents inserted pages from being embedded within other inserted pages. Example usage:
`function theme_init() {
    // Disable nesting check to allow inserted pages within inserted pages.
    add_filter( 'insert_pages_apply_nesting_check', function ( $should_apply ) { return false; } );
}
add_action( 'init', 'theme_init' );`

= 2.3 =
* Remove insertPages_Content id from div wrapper to allow multiple pages to be embedded; replace with insert-page class. Example: `<div data-post-id='123' class='insert-page insert-page-123'>...</div>`
* New shortcode attribute: class. You can now add custom classes to the div wrapper around your inserted page:
`[insert page='123' display='all' class='my-class another-class']`
This results in:
`<div data-post-id='123' class='insert-page insert-page-123 my-class another-class'>...</div>`

= 2.2 =
* Revert previous fix for conflict with Jetpack's Sharing widget (affected other users negatively).
* New fix for conflict with Jetpack's Sharing widget. Use it in your theme like so:
`// If Jetpack Sharing widget is enabled, disable the_content filter for inserted pages.
function theme_init() {
    if ( has_filter( 'the_content', 'sharing_display' ) ) {
        add_filter( 'insert_pages_apply_the_content_filter', function ( $should_apply ) { return false; } );
    }
}
add_action( 'init', 'theme_init' );`

= 2.1 =
* Add quicktag button for Insert Pages to the Text Editor.
* Fix conflict with Jetpack's Sharing widget.
* Add stronger infinite loop protection (will stop expanding shortcodes nested within an embedded page).
* Fix potential infinite loop if custom template can't be found.

= 2.0 =
* Add insert_pages_available_post_types filter to limit post types shown in insert pages dialog (see FAQ for example filter hook). Props @noahj for the feature request.
* Add excerpt-only display to output the excerpt without the title above it. Props @kalico for the feature request.

= 1.9 =
* Add data-post-id attribute to div container for each inserted page, now you can reference via jQuery with .data( 'postId' ). Props to Robert Payne for the pull request, thanks!

= 1.8 =
* Fix for custom post types marked as exclude_from_search not inserting correctly.

= 1.7 =
* Tested and works on WordPress 4.1;
* New display format: excerpt. Props to bitbucket user grzegorzdrozd for the pull request. https://github.com/uhm-coe/insert-pages/commit/0f6402c98058858f76f3f865bb3f8c5aba4cda65

= 1.6 =
* Fix for long page template names causing Display field to wrap in the tinymce popup;
* Marked as WordPress 4.0 compatible.

= 1.5 =
* Fix for options button toggle in tinymce popup;
* Fix popup display on small screen sizes (mobile-friendly).

= 1.4 =
* Update for WordPress 3.9 (update to work under tinymce4);
* Can now edit existing shortcodes (click inside them, then click the toolbar button).

= 1.3 =
* Better documentation.

= 1.2 =
* Add retina toolbar icon.

= 1.1 =
* Minor changes to documentation.

= 1.0 =
* Initial release.

= 0.5 =
* Development release.

== Upgrade Notice ==

= 2.3 =
Warning: If you apply CSS rules to #insertPages_Content, this update will require you to modify those styles. The element id "insertPages_Content" was removed so multiple pages can be embedded on a single page. You may apply styles instead to the "insert-page" class.

= 1.2 =
Added retina toolbar icon.

= 1.0 =
Upgrade to v1.0 to get the first stable version.
