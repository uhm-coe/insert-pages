<?php

/*
Plugin Name: Insert Pages
Plugin URI: https://github.com/uhm-coe/insert-pages
Description: Insert Pages lets you embed any WordPress content (e.g., pages, posts, custom post types) into other WordPress content using the Shortcode API.
Author: Paul Ryan
Version: 3.1.5
Author URI: http://www.linkedin.com/in/paulrryan
License: GPL2
*/

/*  Copyright 2011 Paul Ryan (email: prar@hawaii.edu)

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

/*  Shortcode Format:
	[insert page='{slug}|{id}' display='title|link|excerpt|excerpt-only|content|all|{custom-template.php}' class='any-classes']
*/

// Define the InsertPagesPlugin class (variables and functions)
if ( !class_exists( 'InsertPagesPlugin' ) ) {
	class InsertPagesPlugin {
		// Save the id of the page being edited
		protected $pageID;

		// Constructor
		public function InsertPagesPlugin() {
			// Include the code that generates the options page.
			require_once( dirname( __FILE__ ) . '/options.php' );
		}

		// Getter/Setter for pageID
		function getPageID() {
			return $this->pageID;
		}
		function setPageID( $id ) {
			return $this->pageID = $id;
		}

		// Action hook: Wordpress 'init'
		function insertPages_init() {
			add_shortcode( 'insert', array( $this, 'insertPages_handleShortcode_insert' ) );
		}

		// Action hook: Wordpress 'admin_init'
		function insertPages_admin_init() {
			// Get options set in WordPress dashboard (Settings > Insert Pages).
			$options = get_option( 'wpip_settings' );
			if ( $options === FALSE || ! is_array( $options ) || ! array_key_exists( 'wpip_format', $options ) || ! array_key_exists( 'wpip_wrapper', $options ) || ! array_key_exists( 'wpip_insert_method', $options ) ) {
				$options = wpip_set_defaults();
			}

			// Add TinyMCE toolbar button filters only if current user has permissions
			if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) && get_user_option( 'rich_editing' )=='true' ) {

				// Register the TinyMCE toolbar button script
				wp_enqueue_script(
					'wpinsertpages',
					plugins_url( '/js/wpinsertpages.js', __FILE__ ),
					array( 'wpdialogs' ),
					'20151230'
				);
				wp_localize_script(
					'wpinsertpages',
					'wpInsertPagesL10n',
					array(
						'update' => __( 'Update' ),
						'save' => __( 'Insert Page' ),
						'noTitle' => __( '(no title)' ),
						'noMatchesFound' => __( 'No matches found.' ),
						'l10n_print_after' => 'try{convertEntities(wpLinkL10n);}catch(e){};',
						'format' => $options['wpip_format'],
					)
				);

				// Register the TinyMCE toolbar button styles
				wp_enqueue_style(
					'wpinsertpagescss',
					plugins_url( '/css/wpinsertpages.css', __FILE__ ),
					array( 'wp-jquery-ui-dialog' ),
					'20151230'
				);

				add_filter( 'mce_external_plugins', array( $this, 'insertPages_handleFilter_mceExternalPlugins' ) );
				add_filter( 'mce_buttons', array( $this, 'insertPages_handleFilter_mceButtons' ) );

				//load_plugin_textdomain('insert-pages', false, dirname(plugin_basename(__FILE__)).'/languages/');
			}

		}


		// Shortcode hook: Replace the [insert ...] shortcode with the inserted page's content
		function insertPages_handleShortcode_insert( $atts, $content = null ) {
			global $wp_query, $post, $wp_current_filter;

			// Shortcode attributes.
			$attributes = shortcode_atts( array(
				'page' => '0',
				'display' => 'all',
				'class' => '',
				'inline' => false,
			), $atts );

			// Validation checks.
			if ( $attributes['page'] === '0' ) {
				return $content;
			}

			// Trying to embed same page in itself.
			if (
				! is_null( $post ) && property_exists( $post, 'ID' ) &&
				( $attributes['page'] == $post->ID || $attributes['page'] == $post->post_name )
			) {
				return $content;
			}

			// Get options set in WordPress dashboard (Settings > Insert Pages).
			$options = get_option( 'wpip_settings' );
			if ( $options === FALSE || ! is_array( $options ) || ! array_key_exists( 'wpip_format', $options ) || ! array_key_exists( 'wpip_wrapper', $options ) || ! array_key_exists( 'wpip_insert_method', $options ) ) {
				$options = wpip_set_defaults();
			}

			$attributes['inline'] = ( $attributes['inline'] !== false && $attributes['inline'] !== 'false' ) || array_search( 'inline', $atts ) === 0 || ( array_key_exists( 'wpip_wrapper', $options ) && $options['wpip_wrapper'] === 'inline' );
			/**
			 * Filter the flag indicating whether to wrap the inserted content in inline tags (span).
			 *
			 * @param bool $use_inline_wrapper Indicates whether to wrap the content in span tags.
			 */
			$attributes['inline'] = apply_filters( 'insert_pages_use_inline_wrapper', $attributes['inline'] );
			$attributes['wrapper_tag'] = $attributes['inline'] ? 'span' : 'div';

			$attributes['should_apply_the_content_filter'] = true;
			/**
			 * Filter the flag indicating whether to apply the_content filter to post
			 * contents and excerpts that are being inserted.
			 *
			 * @param bool $apply_the_content_filter Indicates whether to apply the_content filter.
			 */
			$attributes['should_apply_the_content_filter'] = apply_filters( 'insert_pages_apply_the_content_filter', $attributes['should_apply_the_content_filter'] );

			// Disable the_content filter if using inline tags, since wpautop
			// inserts p tags and we can't have any inside inline elements.
			if ( $attributes['inline'] ) {
				$attributes['should_apply_the_content_filter'] = false;
			}

			$attributes['should_apply_nesting_check'] = true;
			/**
			 * Filter the flag indicating whether to apply deep nesting check
			 * that can prevent circular loops. Note that some use cases rely
			 * on inserting pages that themselves have inserted pages, so this
			 * check should be disabled for those individuals.
			 *
			 * @param bool $apply_the_content_filter Indicates whether to apply the_content filter.
			 */
			$attributes['should_apply_nesting_check'] = apply_filters( 'insert_pages_apply_nesting_check', $attributes['should_apply_nesting_check'] );

			// Don't allow inserted pages to be added to the_content more than once (prevent infinite loops).
			if ( $attributes['should_apply_nesting_check'] ) {
				$done = false;
				foreach ( $wp_current_filter as $filter ) {
					if ( 'the_content' == $filter ) {
						if ( $done ) {
							return $content;
						} else {
							$done = true;
						}
					}
				}
			}

			// Get the WP_Post object from the provided slug or ID.
			if ( ! is_numeric( $attributes['page'] ) ) {
				// Get list of post types that can be inserted (page, post, custom
				// types), excluding builtin types (nav_menu_item, attachment).
				$insertable_post_types = array_filter(
					get_post_types(),
					create_function( '$type', 'return ! in_array( $type, array( "nav_menu_item", "attachment" ) );' )
				);
				$inserted_page = get_page_by_path( $attributes['page'], OBJECT, $insertable_post_types );
				$attributes['page'] = $inserted_page ? $inserted_page->ID : $attributes['page'];
			} else {
				$inserted_page = get_post( intval( $attributes['page'] ) );
			}

			// Use "Normal" insert method (get_post()).
			if ( $options['wpip_insert_method'] !== 'legacy' ) {

				// If we couldn't retrieve the page, fire the filter hook showing a not-found message.
				if ( $inserted_page === null ) {
					/**
					 * Filter the html that should be displayed if an inserted page was not found.
					 *
					 * @param string $content html to be displayed. Defaults to an empty string.
					 */
					$content = apply_filters( 'insert_pages_not_found_message', $content );

					// Short-circuit since we didn't find the page.
					return $content;
				}

				// Start output buffering so we can save the output to a string.
				ob_start();

				// If Beaver Builder plugin is enabled, load any cached styles associated with the inserted page.
				// Note: Temporarily set the global $post->ID to the inserted page ID,
				// since Beaver Builder relies on it to load the appropriate styles.
				if ( class_exists( 'FLBuilder' ) ) {
					// If we're not in The Loop (i.e., global $post isn't assigned),
					// temporarily populate it with the post to be inserted so we can
					// retrieve Beaver Builder styles for that post. Reset $post to null
					// after we're done.
					if ( is_null( $post ) ) {
						$old_post_id = null;
						$post = $inserted_page;
					} else {
						$old_post_id = $post->ID;
						$post->ID = $inserted_page->ID;
					}

					FLBuilder::enqueue_layout_styles_scripts( $inserted_page->ID );

					if ( is_null( $old_post_id ) ) {
						$post = null;
					} else {
						$post->ID = $old_post_id;
					}
				}

				// Show either the title, link, content, everything, or everything via a custom template
				// Note: if the sharing_display filter exists, it means Jetpack is installed and Sharing is enabled;
				// This plugin conflicts with Sharing, because Sharing assumes the_content and the_excerpt filters
				// are only getting called once. The fix here is to disable processing of filters on the_content in
				// the inserted page. @see https://codex.wordpress.org/Function_Reference/the_content#Alternative_Usage
				switch ( $attributes['display'] ) {

				case "title":
					$title_tag = $attributes['inline'] ? 'span' : 'h1';
					echo "<$title_tag class='insert-page-title'>";
					echo get_the_title( $inserted_page->ID );
					echo "</$title_tag>";
					break;

				case "link":
					?><a href="<?php echo esc_url( get_permalink( $inserted_page->ID ) ); ?>"><?php echo get_the_title( $inserted_page->ID ); ?></a><?php
					break;

				case "excerpt":
					?><h1><a href="<?php echo esc_url( get_permalink( $inserted_page->ID ) ); ?>"><?php echo get_the_title( $inserted_page->ID ); ?></a></h1><?php
					echo $this->insertPages_trim_excerpt( get_post_field( 'post_excerpt', $inserted_page->ID ), $inserted_page->ID, $attributes['should_apply_the_content_filter'] );
					break;

				case "excerpt-only":
					echo $this->insertPages_trim_excerpt( get_post_field( 'post_excerpt', $inserted_page->ID ), $inserted_page->ID, $attributes['should_apply_the_content_filter'] );
					break;

				case "content":
					$content = get_post_field( 'post_content', $inserted_page->ID );
					if ( $attributes['should_apply_the_content_filter'] ) {
						$content = apply_filters( 'the_content', $content );
					}
					echo $content;
					break;

				case "all":
					// Title.
					$title_tag = $attributes['inline'] ? 'span' : 'h1';
					echo "<$title_tag class='insert-page-title'>";
					echo get_the_title( $inserted_page->ID );
					echo "</$title_tag>";
					// Content.
					$content = get_post_field( 'post_content', $inserted_page->ID );
					if ( $attributes['should_apply_the_content_filter'] ) {
						$content = apply_filters( 'the_content', $content );
					}
					echo $content;
					// Meta.
					// @ref https://core.trac.wordpress.org/browser/tags/4.4/src/wp-includes/post-template.php#L968
					if ( $keys = get_post_custom_keys( $inserted_page->ID ) ) {
						echo "<ul class='post-meta'>\n";
						foreach ( (array) $keys as $key ) {
							$keyt = trim( $key );
							if ( is_protected_meta( $keyt, 'post' ) ) {
								continue;
							}
							$values = array_map( 'trim', get_post_custom_values( $key ) );
							$value = implode( $values, ', ' );

							/**
							 * Filter the HTML output of the li element in the post custom fields list.
							 *
							 * @since 2.2.0
							 *
							 * @param string $html  The HTML output for the li element.
							 * @param string $key   Meta key.
							 * @param string $value Meta value.
							 */
							echo apply_filters( 'the_meta_key', "<li><span class='post-meta-key'>$key:</span> $value</li>\n", $key, $value );
						}
						echo "</ul>\n";
					}
					break;

				default: // display is either invalid, or contains a template file to use
					// Legacy/compatibility code: In order to use custom templates,
					// we use query_posts() to provide the template with the global
					// state it requires for the inserted page (in other words, all
					// template tags will work with respect to the inserted page
					// instead of the parent page / main loop). Note that this may
					// cause some compatibility issues with other plugins.
					// @ref https://codex.wordpress.org/Function_Reference/query_posts
					if ( is_numeric( $attributes['page'] ) ) {
						$args = array(
							'p' => intval( $attributes['page'] ),
							'post_type' => get_post_types(),
						);
					} else {
						$args = array(
							'name' => esc_attr( $attributes['page'] ),
							'post_type' => get_post_types(),
						);
					}
					$inserted_page = query_posts( $args );
					if ( have_posts() ) {
						$template = locate_template( $attributes['display'] );
						if ( strlen( $template ) > 0 ) {
							include $template; // execute the template code
						} else { // Couldn't find template, so fall back to printing a link to the page.
							the_post();
							?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php
						}
					}
					wp_reset_query();

				}

				// Save output buffer contents.
				$content = ob_get_clean();

			// Use "Legacy" insert method (query_posts()).
			} else {

				// Construct query_posts arguments.
				if ( is_numeric( $attributes['page'] ) ) {
					$args = array(
						'p' => intval( $attributes['page'] ),
						'post_type' => get_post_types(),
					);
				} else {
					$args = array(
						'name' => esc_attr( $attributes['page'] ),
						'post_type' => get_post_types(),
					);
				}
				$posts = query_posts( $args );
				if ( have_posts() ) {
					// Start output buffering so we can save the output to string
					ob_start();

					// If Beaver Builder plugin is enabled, load any cached styles associated with the inserted page.
					// Note: Temporarily set the global $post->ID to the inserted page ID,
					// since Beaver Builder relies on it to load the appropriate styles.
					if ( class_exists( 'FLBuilder' ) ) {
						// If we're not in The Loop (i.e., global $post isn't assigned),
						// temporarily populate it with the post to be inserted so we can
						// retrieve Beaver Builder styles for that post. Reset $post to null
						// after we're done.
						if ( is_null( $post ) ) {
							$old_post_id = null;
							$post = $inserted_page;
						} else {
							$old_post_id = $post->ID;
							$post->ID = $inserted_page->ID;
						}

						FLBuilder::enqueue_layout_styles_scripts( $inserted_page->ID );

						if ( is_null( $old_post_id ) ) {
							$post = null;
						} else {
							$post->ID = $old_post_id;
						}
					}

					// Show either the title, link, content, everything, or everything via a custom template
					// Note: if the sharing_display filter exists, it means Jetpack is installed and Sharing is enabled;
					// This plugin conflicts with Sharing, because Sharing assumes the_content and the_excerpt filters
					// are only getting called once. The fix here is to disable processing of filters on the_content in
					// the inserted page. @see https://codex.wordpress.org/Function_Reference/the_content#Alternative_Usage
					switch ( $attributes['display'] ) {
					case "title":
						the_post();
						$title_tag = $attributes['inline'] ? 'span' : 'h1';
						echo "<$title_tag class='insert-page-title'>";
						the_title();
						echo "</$title_tag>";
						break;
					case "link":
						the_post();
						?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php
						break;
					case "excerpt":
						the_post();
						?><h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1><?php
						if ( $attributes['should_apply_the_content_filter'] ) the_excerpt(); else echo get_the_excerpt();
						break;
					case "excerpt-only":
						the_post();
						if ( $attributes['should_apply_the_content_filter'] ) the_excerpt(); else echo get_the_excerpt();
						break;
					case "content":
						the_post();
						if ( $attributes['should_apply_the_content_filter'] ) the_content(); else echo get_the_content();
						break;
					case "all":
						the_post();
						$title_tag = $attributes['inline'] ? 'span' : 'h1';
						echo "<$title_tag class='insert-page-title'>";
						the_title();
						echo "</$title_tag>";
						if ( $attributes['should_apply_the_content_filter'] ) the_content(); else echo get_the_content();
						the_meta();
						break;
					default: // display is either invalid, or contains a template file to use
						$template = locate_template( $attributes['display'] );
						if ( strlen( $template ) > 0 ) {
							include $template; // execute the template code
						} else { // Couldn't find template, so fall back to printing a link to the page.
							the_post();
							?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php
						}
						break;
					}
					// Save output buffer contents.
					$content = ob_get_clean();
				} else {
					/**
					 * Filter the html that should be displayed if an inserted page was not found.
					 *
					 * @param string $content html to be displayed. Defaults to an empty string.
					 */
					$content = apply_filters( 'insert_pages_not_found_message', $content );
				}
				wp_reset_query();
			}

			/**
			 * Filter the markup generated for the inserted page.
			 *
			 * @param string $content The post content of the inserted page.
			 * @param object $inserted_page The post object returned from querying the inserted page.
			 * @param array $attributes Extra parameters modifying the inserted page.
			 *   page: Page ID or slug of page to be inserted.
			 *   display: Content to display from inserted page.
			 *   class: Extra classes to add to inserted page wrapper element.
			 *   inline: Boolean indicating wrapper element should be a span.
			 *   should_apply_nesting_check: Whether to disable nested inserted pages.
			 *   should_apply_the_content_filter: Whether to apply the_content filter to post contents and excerpts.
			 *   wrapper_tag: Tag to use for the wrapper element (e.g., div, span).
			 */
			$content = apply_filters( 'insert_pages_wrap_content', $content, $inserted_page, $attributes );

			return $content;
		}

		// Default filter for insert_pages_wrap_content.
		function insertPages_wrap_content( $content, $posts, $attributes ) {
			return "<{$attributes['wrapper_tag']} data-post-id='{$attributes['page']}' class='insert-page insert-page-{$attributes['page']} {$attributes['class']}'>{$content}</{$attributes['wrapper_tag']}>";
		}

		// Filter hook: Add a button to the TinyMCE toolbar for our insert page tool
		function insertPages_handleFilter_mceButtons( $buttons ) {
			array_push( $buttons, 'wpInsertPages_button' ); // add a separator and button to toolbar
			return $buttons;
		}

		// Filter hook: Load the javascript for our custom toolbar button
		function insertPages_handleFilter_mceExternalPlugins( $plugins ) {
			$plugins['wpInsertPages'] = plugins_url( '/js/wpinsertpages_plugin.js', __FILE__ );
			return $plugins;
		}

		// Helper function to generate an excerpt (outside of the Loop) for a given ID.
		// @ref wp_trim_excerpt()
		function insertPages_trim_excerpt( $text = '', $post_id = 0, $apply_the_content_filter = true ) {
			$post_id = intval( $post_id );
			if ( $post_id < 1 ) {
				return '';
			}

			$raw_excerpt = $text;
			if ( '' == $text ) {
				$text = get_post_field( 'post_content', $post_id );

				$text = strip_shortcodes( $text );

				/** This filter is documented in wp-includes/post-template.php */
				if ( $apply_the_content_filter ) {
					$text = apply_filters( 'the_content', $text );
				}
				$text = str_replace( ']]>', ']]&gt;', $text );

				/**
				 * Filter the number of words in an excerpt.
				 *
				 * @since 2.7.0
				 *
				 * @param int $number The number of words. Default 55.
				 */
				$excerpt_length = apply_filters( 'excerpt_length', 55 );
				/**
				 * Filter the string in the "more" link displayed after a trimmed excerpt.
				 *
				 * @since 2.9.0
				 *
				 * @param string $more_string The string shown within the more link.
				 */
				$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
				$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );
			}
			/**
			 * Filter the trimmed excerpt string.
			 *
			 * @since 2.8.0
			 *
			 * @param string $text        The trimmed text.
			 * @param string $raw_excerpt The text prior to trimming.
			 */
			return apply_filters( 'wp_trim_excerpt', $text, $raw_excerpt );
		}

		/**
		 * Modified from /wp-admin/includes/internal-linking.php, function wp_link_dialog()
		 * Dialog for internal linking.
		 *
		 * @since 3.1.0
		 */
		function insertPages_wp_tinymce_dialog() {
			// If wp_editor() is being called outside of an admin context,
			// required dependencies for Insert Pages will be missing (e.g.,
			// wp-admin/includes/template.php will not be loaded, admin_head
			// action will not be fired). If that's the case, just skip loading
			// the Insert Pages tinymce button.
			if ( ! is_admin() || ! function_exists( 'page_template_dropdown' ) ) {
				return;
			}

			$options_panel_visible = '1' == get_user_setting( 'wpinsertpage', '0' ) ? ' options-panel-visible' : '';

			// Get ID of post currently being edited.
			$post_id = array_key_exists( 'post', $_REQUEST ) && intval( $_REQUEST['post'] ) > 0 ? intval( $_REQUEST['post'] ) : '';

			// display: none is required here, see #WP27605
			?><div id="wp-insertpage-backdrop" style="display: none"></div>
			<div id="wp-insertpage-wrap" class="wp-core-ui<?php echo $options_panel_visible; ?>" style="display: none">
			<form id="wp-insertpage" tabindex="-1">
			<?php wp_nonce_field( 'internal-inserting', '_ajax_inserting_nonce', false ); ?>
			<input type="hidden" id="insertpage-parent-pageID" value="<?php echo $post_id; ?>" />
			<div id="insertpage-modal-title">
				<?php _e( 'Insert page' ) ?>
				<div id="wp-insertpage-close" tabindex="0"></div>
			</div>
			<div id="insertpage-selector">
				<div id="insertpage-search-panel">
					<div class="insertpage-search-wrapper">
						<label>
							<span class="search-label"><?php _e( 'Search' ); ?></span>
							<input type="search" id="insertpage-search-field" class="insertpage-search-field" autocomplete="off" />
							<span class="spinner"></span>
						</label>
					</div>
					<div id="insertpage-search-results" class="query-results">
						<ul></ul>
						<div class="river-waiting">
							<span class="spinner"></span>
						</div>
					</div>
					<div id="insertpage-most-recent-results" class="query-results">
						<div class="query-notice"><em><?php _e( 'No search term specified. Showing recent items.' ); ?></em></div>
						<ul></ul>
						<div class="river-waiting">
							<span class="spinner"></span>
						</div>
					</div>
				</div>
				<p class="howto" id="insertpage-options-toggle"><?php _e( 'Options' ); ?></p>
				<div id="insertpage-options-panel">
					<div class="insertpage-options-wrapper">
						<label for="insertpage-slug-field">
							<span><?php _e( 'Slug or ID' ); ?></span>
							<input id="insertpage-slug-field" type="text" autocomplete="off" />
							<input id="insertpage-pageID" type="hidden" />
						</label>
					</div>
					<div class="insertpage-format">
						<label for="insertpage-format-select">
							<?php _e( 'Display' ); ?>
							<select name="insertpage-format-select" id="insertpage-format-select">
								<option value='title'>Title</option>
								<option value='link'>Link</option>
								<option value='excerpt'>Excerpt with title</option>
								<option value='excerpt-only'>Excerpt only (no title)</option>
								<option value='content'>Content</option>
								<option value='all'>All (includes custom fields)</option>
								<option value='template'>Use a custom template &raquo;</option>
							</select>
							<select name="insertpage-template-select" id="insertpage-template-select" disabled="true">
								<option value='all'><?php _e( 'Default Template' ); ?></option>
								<?php page_template_dropdown(); ?>
							</select>
						</label>
					</div>
					<div class="insertpage-extra">
						<label for="insertpage-extra-classes">
							<?php _e( 'Extra Classes' ); ?>
							<input id="insertpage-extra-classes" type="text" autocomplete="off" />
						</label>
						<label for="insertpage-extra-inline">
							<?php _e( 'Inline?' ); ?>
							<input id="insertpage-extra-inline" type="checkbox" />
						</label>
					</div>
				</div>
			</div>
			<div class="submitbox">
				<div id="wp-insertpage-update">
					<input type="submit" value="<?php esc_attr_e( 'Insert Page' ); ?>" class="button button-primary" id="wp-insertpage-submit" name="wp-insertpage-submit">
				</div>
				<div id="wp-insertpage-cancel">
					<a class="submitdelete deletion" href="#"><?php _e( 'Cancel' ); ?></a>
				</div>
			</div>
			</form>
			</div>
			<?php
		}

		/** Modified from:
		 * Internal linking functions.
		 *
		 * @package WordPress
		 * @subpackage Administration
		 * @since 3.1.0
		 */
		function insertPages_insert_page_callback() {
			check_ajax_referer( 'internal-inserting', '_ajax_inserting_nonce' );
			$args = array();
			if ( isset( $_POST['search'] ) ) {
				$args['s'] = stripslashes( $_POST['search'] );
			}
			$args['pagenum'] = !empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
			$args['pageID'] =  !empty( $_POST['pageID'] ) ? absint( $_POST['pageID'] ) : 0;

			// Change search to slug or post ID if we're not doing a plaintext
			// search (e.g., if we're editing an existing shortcode and the
			// search field is populated with the post's slug or ID).
			if ( array_key_exists( 'type', $_POST ) && $_POST['type'] === 'slug' ) {
				$args['name'] = $args['s'];
				unset( $args['s'] );
			} else if ( array_key_exists( 'type', $_POST ) && $_POST['type'] === 'post_id' ) {
				$args['p'] = $args['s'];
				unset( $args['s'] );
			}

			$results = $this->insertPages_wp_query( $args );

			// Fail if our query didn't work.
			if ( ! isset( $results ) ) {
				die( '0' );
			}

			echo json_encode( $results );
			echo "\n";
			die();
		}

		/** Modified from:
		 * Performs post queries for internal linking.
		 *
		 * @since 3.1.0
		 * @param array   $args Optional. Accepts 'pagenum' and 's' (search) arguments.
		 * @return array Results.
		 */
		function insertPages_wp_query( $args = array() ) {
			$pts = get_post_types( array( 'public' => true ), 'objects' );
			$post_types = array_keys( $pts );

			/**
			 * Filter the post types that appear in the list of pages to insert.
			 *
			 * By default, all post types will apear.
			 *
			 * @since 2.0
			 *
			 * @param array   $post_types Array of post type names to include.
			 */
			$post_types = apply_filters( 'insert_pages_available_post_types', $post_types );

			$query = array(
				'post_type' => $post_types,
				'suppress_filters' => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'post_status' => 'publish',
				'order' => 'DESC',
				'orderby' => 'post_date',
				'posts_per_page' => 20,
				'post__not_in' => array( $args['pageID'] ),
			);

			$args['pagenum'] = isset( $args['pagenum'] ) ? absint( $args['pagenum'] ) : 1;
			$query['offset'] = $args['pagenum'] > 1 ? $query['posts_per_page'] * ( $args['pagenum'] - 1 ) : 0;

			// Search post content and post title.
			if ( isset( $args['s'] ) ) {
				$query['s'] = $args['s'];
			}

			// Search post_name (post slugs).
			if ( isset( $args['name'] ) ) {
				$query['name'] = $args['name'];
			}

			// Search post ids.
			if ( isset( $args['p'] ) ) {
				$query['p'] = $args['p'];
			}

			// Do main query.
			$get_posts = new WP_Query;
			$posts = $get_posts->query( $query );
			// Check if any posts were found.
			if ( ! $get_posts->post_count ) {
				return false;
			}

			// Build results.
			$results = array();
			foreach ( $posts as $post ) {
				if ( 'post' == $post->post_type ) {
					$info = mysql2date( __( 'Y/m/d' ), $post->post_date );
				} else {
					$info = $pts[ $post->post_type ]->labels->singular_name;
				}
				$results[] = array(
					'ID' => $post->ID,
					'title' => trim( esc_html( strip_tags( get_the_title( $post ) ) ) ),
					'permalink' => get_permalink( $post->ID ),
					'slug' => $post->post_name,
					'info' => $info,
				);
			}
			return $results;
		}

		function insertPages_add_quicktags() {
			if ( wp_script_is( 'quicktags' ) ) : ?>
				<script type="text/javascript">
					QTags.addButton( 'ed_insert_page', '[insert page]', "[insert page='your-page-slug' display='title|link|excerpt|excerpt-only|content|all']\n", '', '', 'Insert Page', 999 );
				</script>
			<?php endif;
		}

	}
}

// Initialize InsertPagesPlugin object
if ( class_exists( 'InsertPagesPlugin' ) ) {
	$insertPages_plugin = new InsertPagesPlugin();
}

// Actions and Filters handled by InsertPagesPlugin class
if ( isset( $insertPages_plugin ) ) {
	// Register shortcode [insert ...].
	add_action( 'init', array( $insertPages_plugin, 'insertPages_init' ), 1 );

	// Add TinyMCE button for shortcode.
	add_action( 'admin_head', array( $insertPages_plugin, 'insertPages_admin_init' ), 1 );

	// Add quicktags button for shortcode.
	add_action( 'admin_print_footer_scripts', array( $insertPages_plugin, 'insertPages_add_quicktags' ) );

	// Preload TinyMCE popup.
	add_action( 'before_wp_tiny_mce', array( $insertPages_plugin, 'insertPages_wp_tinymce_dialog' ), 1 );

	// Ajax: Populate page search in TinyMCE button popup.
	add_action( 'wp_ajax_insertpage', array( $insertPages_plugin, 'insertPages_insert_page_callback' ) );

	// Use internal filter to wrap inserted content in a div or span.
	add_filter( 'insert_pages_wrap_content', array( $insertPages_plugin, 'insertPages_wrap_content' ), 10, 3 );

	// Register Insert Pages shortcode widget.
	require_once( dirname( __FILE__ ) . '/widget.php' );
	add_action( 'widgets_init', create_function( '', 'return register_widget( "InsertPagesWidget" );' ) );
}
