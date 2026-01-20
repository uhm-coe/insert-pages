=== Insert Pages ===
Contributors: figureone, the_magician
Tags: insert, pages, shortcode, embed
Tested up to: 6.9
Stable tag: 3.11.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Insert Pages lets you embed any WordPress content (e.g., pages, posts, custom post types) into other WordPress content using the Shortcode API.

== Description ==

Insert Pages lets you embed any WordPress content (e.g., pages, posts, custom post types) into other WordPress content using the Shortcode API. It also includes a widget for inserting pages into any widget area.

The real power of Insert Pages comes when you start creating custom post types, either [programmatically in your theme](http://codex.wordpress.org/Post_Types), or using another plugin like [Custom Post Type UI](http://wordpress.org/plugins/custom-post-type-ui/). You can then abstract away common data types (like videos, quizzes, due dates) into their own custom post types, and then show those pieces of content within your normal pages and posts by Inserting them as a shortcode.

### Advanced Tutorial

Contributor Wes Modes has graciously written an updated tutorial for the Gutenberg era, focused on creating a custom post type with custom fields and a custom template for rendering content. Read it here: [https://medium.com/@wesmodes/using-wordpress-insert-pages-plugin-with-your-custom-post-types-and-custom-templates-535c141f9635](https://medium.com/@wesmodes/using-wordpress-insert-pages-plugin-with-your-custom-post-types-and-custom-templates-535c141f9635)

### Example: Normal Use Case
Say you teach a course and you're constantly referring to an assignment due date in your course website. The next semester the due date changes, and you have to go change all of the locations you referred to it. Instead, you'd rather just change the date once! With Insert Pages, you can do the following:

1. Create a custom post type called **Due Date**.
1. Create a new *Due Date* called **Assignment 1 Due Date** with **Fri Nov 22, 2013** as its content.
1. Edit all the pages where the due date occurs and use the *Insert Pages* toolbar button to insert a reference to the *Due Date* you just created. Be sure to set the *Display* to **Content** so *Fri Nov 22, 2013* shows wherever you insert it. The shortcode you just created should look something like this: `[insert page='assignment-1-due-date' display='content']`
1. That's it! Now, when you want to change the due date, just edit the *Assignment 1 Due Date* custom post you created, and it will automatically be updated on all the pages you inserted it on.

### Example: Advanced Use Case
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

= How do I create a custom template for use by Insert Pages?

A basic template would look like the following. This would be a file on your theme directory, e.g., `your-custom-template.php`:

`<?php
/**
 * Template Name: Name of your custom template
 */
?>
<div id="your-wrapper-div">
  <?php while ( have_posts() ) : the_post(); ?>
    <div id="your-container-div-for-each-post">
      <?php the_content(); ?>
      <?php the_post_thumbnail(); ?>
    </div>
  <?php endwhile; ?>
</div>`

You can use whatever template tags that you'd like, check out the WordPress documentation.

* [https://developer.wordpress.org/themes/basics/template-tags/](https://developer.wordpress.org/themes/basics/template-tags/)
* [https://developer.wordpress.org/themes/references/list-of-template-tags/](https://developer.wordpress.org/themes/references/list-of-template-tags/)

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

= 3.11.2 =
* Fix for Divi redirecting to inserted page when an Insert Pages shortcode exists in the classic editor. Props airflo for the [report](https://github.com/uhm-coe/insert-pages/issues/65)!

= 3.11.1 =
* Fix inserting pages with parentheses in the page slug/title. Props @lineuponline for the [report](https://wordpress.org/support/topic/cant-get-away-with-title-rather-than-slug-or-page-id-anymore/)!

= 3.11.0 =
* NOTICE: If you intentionally insert unpublished content (e.g., future, draft, pending, or private), go to Insert Pages Settings after installing this update and enable that functionality in "Allow anonymous users to see inserted pages with these statuses." This version changes the plugin behavior to only allow inserting the "publish" post status by default (more secure).
* Add plugin option to choose post statuses other than "published" that can be publicly viewable.
* Remove the "Reveal Private Pages?" block attribute (and the "public" shortcode attribute) that allowed inserted private pages to be publicly viewable, since it can be controlled by lower-privilege users. Use the new plugin option above to set this behavior.
* Prevent inserting pages with trash or revision post statuses (security).

= 3.10.0 =
* Fix URLInput in Insert Pages block (render URLInput in a Popover component to prevent overflow issues).
* Migrate Insert Pages block to API version 3 (WordPress 6.3 or greater). Allows [block editor to be iframed](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-api-versions/).
* Tested up to WordPress 6.9 (6.9-RC2).

= 3.9.3 =
* Support Otter Blocks in inserted pages. Props @joeb2880 for the [request](https://wordpress.org/support/topic/otter-accordions-not-displaying-properly-when-inserted/)!

= 3.9.2 =
* Fix error when tinymce is used outside of admin context (e.g., in other plugins like Gravity Forms).
* Tested up to WordPress 6.8.1.

= 3.9.1 =
* Additional fixes for error in some Woocommerce contexts.
* Fix for legacy widgets with custom css from builder plugins having the css escaped and printed.

= 3.9.0 =
* Fix error in some Woocommerce contexts. Props @osositno for the [report](https://wordpress.org/support/topic/fatal-error-4761)!
* Fix block layout styles not appearing in the block editor inside an insert page block. Props @davidpotter for the [report](https://wordpress.org/support/topic/block-properties-not-always-honored-in-the-gutenberg-editor/)!
* Fix issues reported by Plugin Check, including securing output data (escaping).
* Minor fixes to adhere to WordPress Coding Standards.

= 3.8.1 =
* Fix WPML compatibility: inserted pages will now match the language of the parent page.

= 3.8 =
* Add `display='title-content'` to render both Title and Content in the same shortcode or block. Props dregad for the [request](https://github.com/dregad)!
* Update block dev dependencies and rebuild block.
* Fix: Verify search param exists before checking.
* Tested up to WordPress 6.5.

= 3.7.7 =
* Update block dev dependencies and rebuild block.
* Tested up to WordPress 6.2.
* Update readme.

= 3.7.6 =
* Allow full URLs in insert page shortcode. Props @woodhall2k for the suggestion.

= 3.7.5 =
* Security: fix improper escaping of class attribute. Lower-privileged users like Contributors can potentially insert javascript into the Insert Pages shortcode that can run when an Administrator previews their post, creating a vector for cross-site scripting. We recommend updating to this version immediately. Props @wpscan for the report.
* Add size attribute to change post-thumbnail size. Example: `[insert page='sample-page' display='post-thumbnail' size='large']`
* Fix for shortcodes with extra spaces breaking classic editor toolbar button highlight.
* Don’t reparse querystring during a rest request (gutenberg block refresh). Props @robbymacdonell for finding the bug!
* Support GoodLayers page builder. Props @rehanahmed38 for the request.
* Update gutenberg block dev dependencies.

= 3.7.4 =
* Fixes quicktag button missing in WordPress 6.0 (also fixes "QTags is not defined) javascript error while editing posts).
* Add Simple WP Membership integration (content protected with Simple WP Membership will only be shown to authorized users or admins when inserted).

= 3.7.3 =
* Fix missing Visual Composer script/style enqueues.
* Tested up to WordPress 6.0.
* Bump gutenberg npm development dependencies.

= 3.7.2 =
* Add custom CSS/JS enqueue for inserted pages with blocks from the Ultimate Addons for Gutenberg plugin.
* Default to "normal" insert method instead of "legacy."
* Bump gutenberg development dependencies.

= 3.7.1 =
* Fix Insert Pages block styles affecting other blocks. Props @drsdre for the report!
* Fix gutenberg block deprecation notices.
* Tested up to WordPress 5.9.

= 3.7.0 =
* Security: Prevent unprivileged users from inserting private posts by others.
* Security: Filter out possible XSS in post meta using wp_kses_post() when using display=all.
* New Setting: Only show Authors and Contributors their own content in the TinyMCE Insert Pages popup.

= 3.6.1 =
* Fix TinyMCE dialog not closing properly. Props @astaryne for the report!

= 3.6.0 =
* Add `insert_pages_tinymce_state` filter to set TinyMCE modal field defaults. [Details](https://wordpress.org/support/topic/customise-modal-content/)
* Add `insert_pages_available_templates` filter to customize the list of allowed custom templates. [Details](https://wordpress.org/support/topic/customise-modal-content/)
* Update TinyMCE plugin to match changes in current wp-link dialog in core.
* Fix TinyMCE modal height on mobile.
* Ensure scripts/styles are loaded before adding TinyMCE plugin.

= 3.5.10 =
* Allow Insert Pages TinyMCE widget to run in a front-end wp_editor().

= 3.5.9 =
* Fix jQuery deprecation notices in WordPress 5.7.
* Tested up to WordPress 5.7.

= 3.5.8 =
* Allow adding query vars for the inserted page (for example, to insert a specific tab of the WooCommerce My Account page: `[insert page=‘my-account’ display=‘content’ querystring=‘pagename=my-account&downloads’]`).
* Tested up to WordPress 5.6.1.

= 3.5.7 =
* Prevent Flamingo (Contact Forms 7 plugin) inbound messages with the same slug as an existing inserted page from showing up.

= 3.5.6 =
* Tested up to WordPress 5.5.1.
* Update gutenberg block loading method to newer version.
* Fix warning about Gutenberg block being registered multiple times if ACF is installed.
* Fix Gutenberg devDependency security warnings.
* Fix being unable to insert the same page twice if the first insert uses a custom template.

= 3.5.5 =
* Save user’s selected display and template in the TinyMCE dialog for restoring next time they insert a page. Props @ladygeekgeek for the idea!
* Tested up to WordPress 5.4.2.
* Bump lodash from 4.17.15 to 4.17.19 (dev dependency only).

= 3.5.4 =
* Support custom scripts and styles in inserted pages created with Visual Composer version 26.0.

= 3.5.3.2 =
* Fix for loop detection: we had accidentally prevented pages from inserting another page multiple times (not in a loop).

= 3.5.3.1 =
* Revert change affecting Elementor using legacy insert method. Props @progameinc for reporting it so quickly!

= 3.5.3 =
* Update Gutenberg block (replace deprecated calls).
* Add automatic loop detection; nested inserts now work by default.
* Deprecate `insert_pages_apply_nesting_check` filter since it's now unnecessary.

= 3.5.2 =
* Add FAQ for creating a custom template. [Details](https://wordpress.org/support/topic/suggestion-for-faq-documentation/)
* Parse `<!--nextpage-->` separators in Content and All displays on legacy insert mode. [Details](https://wordpress.org/support/topic/not-working-with-insert-page/)
* Add support for custom templates for Insert Pages within Elementor and Beaver Builder. [Details](https://wordpress.org/support/topic/use-a-custom-template-doesnt-show-custom-template-filename-in-builder/)

= 3.5.1 =
* Fix Gutenberg block assets (js, css) loading on front end.
* Add option to disable Insert Pages Gutenberg block.

= 3.5.0 =
* Add Gutenberg block.
* Fix for [WSOD](https://wordpress.org/support/topic/app-v3-4-7-not-compatible-with-wp-v4-9-6) in WordPress versions before 5.0 (unintentionally included Gutenberg block in last release).
* Fix PHP warnings in some contexts.

= 3.4.7 =
* Larger Insert Page modal height in Classic Editor (accommodate larger WP 5.3 form elements).
* Update npm packages (gutenberg build dependencies).
* Tested up to WordPress 5.3.

= 3.4.6 =
* Respect <!--more--> quicktag in excerpt and excerpt-only displays (in normal insert method).
* Add a custom <!--crop--> quicktag in excerpt and excerpt-only displays (in normal insert method). Excerpt can be trimmed to anything between <!--crop--> and <!--more--> quicktags.

= 3.4.5 =
* Fix for nested inserted pages all using custom templates. Props @masterbip for discovering the issue!

= 3.4.4 =
* Fix incorrect link in excerpt's 'Continue reading' link. Props @bogyo74 for discovering the bug!
* Fix empty id attribute rendering if no custom ID provided. Props @theschappy for finding and fixing this bug!

= 3.4.3 =
* Add 'id' shortcode param that sets the html id attribute on the wrapper element for an inserted page. Useful for anchor links. Props @Seb33300 for the suggestion!
* Fix for WPBakery Visual Composer inline styles in inserted pages. Props @Seb33300 for the pull request!

= 3.4.2 =
* Add 'public' shortcode param that lets private inserted pages be visible to anonymous users. Props @ahtcx for the suggestion.
* Fix for inserted pages shown on BuddyPress profiles. Props @IdleWanderer for the report!
* Fix for querystring option missing from widget settings.

= 3.4.1 =
* Fix for Elementor rendering in legacy mode.
* Fix for Post Thumbnail display in legacy mode.

= 3.4.0 =
* Add integration with WPBakery Page Builder (Visual Composer).
* Fix error messages about deprecated functions in PHP 7.2.
* Adhere to WordPress Coding Standards.

= 3.3.0 =
* Fix custom field values coming from parent post in certain contexts. Props @chrisneward for catching it!
* Add post-thumbnail display to output just the featured image of a post. Props @pereztroff for the feature request.

= 3.2.9 =
* Add querystring parameter to the shortcode to pass custom querystring values to any custom templates.

  Example: [insert page='your-page' display='your-custom-template.php' querystring='foo=bar&baz=qux']

  Note: If you need to use arrays in your querystring variables, use braces {} instead of brackets [], since WordPress shortcodes cannot have brackets inside them. The plugin will convert the braces internally. Example: querystring='foo[]=bar&foo[]=baz'

= 3.2.8 =
* Add support for inserting pages/posts built with Elementor.

= 3.2.7 =
* Add insert_pages_override_display filter so site admins can enforce a specific display on all inserted pages.

= 3.2.6 =
* Fix for custom templates issues on certain platforms (e.g., Windows).

= 3.2.5 =
* Support looking up hierarchical pages by slug; insert hierarchical pages by path (not slug).
* Fix for php warning when displaying meta values that are strings instead of arrays.

= 3.2.4 =
* Restrict custom template paths to theme directory (prevent directory traversal attacks).

= 3.2.3 =
* Fix for loading inline CSS from SiteOrigin Page Builder version 2.5 or later. Props @alexgso for the pull request!

= 3.2.2 =
* Revert TinyMCE filter hook to 3.1.9 method due to continued Divi theme compatibility issues.
* Add configurable option to load TinyMCE filter in a different location to support SiteOrigin PageBuilder users.
* Fix missing JS on front-end ACF forms with WYSIWYG editors.

= 3.2.1 =
* Revert TinyMCE filter move for SiteOrigin PageBuilder since it breaks compatibility with Divi theme. Instead, hook into the filter multiple times, and make sure the Insert Pages button is registered each time. Props @trevorp for the report.

= 3.2.0 =
* Fix for toolbar button disappearing when the SiteOrigin PageBuilder Widgets Bundle plugin is enabled. Props @JarkkoLaine for figuring that one out! Ref: https://wordpress.org/support/topic/button-in-the-toolbar-of-tinymce-disappear-conflict-page-builder/

= 3.1.9 =
* Support `shortcode_atts_insert` filter for filtering the shortcode's default attributes. Props @gtrout for the pull request!

= 3.1.8 =
* Fix for widget being used in the Beaver Builder widget interface.

= 3.1.7 =
* Plugin is now translatable (internationalization). Props @maxgx for getting the ball rolling and creating a translation.

= 3.1.6 =
* Fix for TinyMCE toolbar button not appearing for authors and contributors. Props @fernandosalvato for the report.
* Fix for deprecation warning in PHP 7. Props @christer_f for the report.

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

= 3.11.0 =
If you intentionally insert unpublished content (e.g., future, draft, pending, or private), go to Insert Pages Settings after installing this update and enable that functionality in "Allow anonymous users to see inserted pages with these statuses." This version changes the plugin behavior to only allow inserting the "publish" post status by default (more secure).

= 3.7.0 =
If you insert private pages/posts, please review the post authors of the pages containing the inserted page and confirm they have the capability to read the private content. This upgrade enforces private page visibility based on the role of the author of the page that inserts any private content.

= 3.2.4 =
Security: fixes a directory traversal attack where an Editor role or higher could include any php file by specifying it as a custom template in the Insert Pages shortcode. Example: [insert page='your-page' display='../../../../../../../../xampp/apache/logs/access.log']

= 2.3 =
Warning: If you apply CSS rules to #insertPages_Content, this update will require you to modify those styles. The element id "insertPages_Content" was removed so multiple pages can be embedded on a single page. You may apply styles instead to the "insert-page" class.

= 1.2 =
Added retina toolbar icon.

= 1.0 =
Upgrade to v1.0 to get the first stable version.
