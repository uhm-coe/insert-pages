<?php
/**
 * Plugin Name: Insert Pages
 * Plugin URI: https://github.com/uhm-coe/insert-pages
 * Description: Insert Pages lets you embed any WordPress content (e.g., pages, posts, custom post types) into other WordPress content using the Shortcode API.
 * Author: Paul Ryan
 * Text Domain: insert-pages
 * Domain Path: /languages
 * License: GPL2
 * Version: 3.4.2
 *
 * @package insert-pages
 */

/*
Copyright 2011 Paul Ryan (email: prar@hawaii.edu)

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

/**
 * Shortcode Format:
 * [insert page='{slug}|{id}' display='title|link|excerpt|excerpt-only|content|post-thumbnail|all|{custom-template.php}' class='any-classes']
 */

if ( ! class_exists( 'InsertPagesPlugin' ) ) {
	/**
	 * Class InsertPagesPlugin
	 */
	class InsertPagesPlugin {
		/**
		 * Page ID being inserted.
		 *
		 * @var int
		 */
		protected $page_id;

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Include the code that generates the options page.
			require_once dirname( __FILE__ ) . '/options.php';
		}

		/**
		 * Getter for page_id.
		 *
		 * @return int Page ID being inserted.
		 */
		public function get_page_id() {
			return $this->page_id;
		}

		/**
		 * Setter for page_id.
		 *
		 * @param int $id Page ID being inserted.
		 */
		public function set_page_id( $id ) {
			$this->page_id = $id;

			return $this->page_id;
		}

		/**
		 * Action hook: WordPress 'init'.
		 *
		 * @return void
		 */
		public function insert_pages_init() {
			add_shortcode( 'insert', array( $this, 'insert_pages_handle_shortcode_insert' ) );
		}

		/**
		 * Action hook: WordPress 'admin_init'.
		 *
		 * @return void
		 */
		public function insert_pages_admin_init() {
			// Get options set in WordPress dashboard (Settings > Insert Pages).
			$options = get_option( 'wpip_settings' );
			if ( false === $options || ! is_array( $options ) || ! array_key_exists( 'wpip_format', $options ) || ! array_key_exists( 'wpip_wrapper', $options ) || ! array_key_exists( 'wpip_insert_method', $options ) || ! array_key_exists( 'wpip_tinymce_filter', $options ) ) {
				$options = wpip_set_defaults();
			}

			// Register the TinyMCE toolbar button script.
			wp_enqueue_script(
				'wpinsertpages',
				plugins_url( '/js/wpinsertpages.js', __FILE__ ),
				array( 'wpdialogs' ),
				'20180702'
			);
			wp_localize_script(
				'wpinsertpages',
				'wpInsertPagesL10n',
				array(
					'update' => __( 'Update', 'insert-pages' ),
					'save' => __( 'Insert Page', 'insert-pages' ),
					'noTitle' => __( '(no title)', 'insert-pages' ),
					'noMatchesFound' => __( 'No matches found.', 'insert-pages' ),
					'l10n_print_after' => 'try{convertEntities(wpInsertPagesL10n);}catch(e){};',
					'format' => $options['wpip_format'],
					'private' => __( 'Private' ),
				)
			);

			// Register the TinyMCE toolbar button styles.
			wp_enqueue_style(
				'wpinsertpagescss',
				plugins_url( '/css/wpinsertpages.css', __FILE__ ),
				array( 'wp-jquery-ui-dialog' ),
				'20180702'
			);

			/**
			 * Register TinyMCE plugin for the toolbar button in normal mode (register
			 * TinyMCE plugin filters below before plugins_loaded in compatibility
			 * mode, to work around a SiteOrigin PageBuilder bug).
			 *
			 * @see  https://wordpress.org/support/topic/button-in-the-toolbar-of-tinymce-disappear-conflict-page-builder/
			 */
			if ( 'normal' === $options['wpip_tinymce_filter'] ) {
				add_filter( 'mce_external_plugins', array( $this, 'insert_pages_handle_filter_mce_external_plugins' ) );
				add_filter( 'mce_buttons', array( $this, 'insert_pages_handle_filter_mce_buttons' ) );
			}

			load_plugin_textdomain(
				'insert-pages',
				false,
				plugin_basename( dirname( __FILE__ ) ) . '/languages'
			);

		}


		/**
		 * Shortcode hook: Replace the [insert ...] shortcode with the inserted page's content.
		 *
		 * @param  array  $atts    Shortcode attributes.
		 * @param  string $content Content to replace shortcode.
		 * @return string          Content to replace shortcode.
		 */
		public function insert_pages_handle_shortcode_insert( $atts, $content = null ) {
			global $wp_query, $post, $wp_current_filter;

			// Shortcode attributes.
			$attributes = shortcode_atts( array(
				'page' => '0',
				'display' => 'all',
				'class' => '',
				'inline' => false,
				'public' => false,
				'querystring' => '',
			), $atts, 'insert' );

			// Validation checks.
			if ( '0' === $attributes['page'] ) {
				return $content;
			}

			// Short circuit if trying to embed same page in itself.
			if (
				! is_null( $post ) && property_exists( $post, 'ID' ) &&
				(
					( intval( $attributes['page'] ) > 0 && intval( $attributes['page'] ) === $post->ID ) ||
					$attributes['page'] === $post->post_name
				)
			) {
				return $content;
			}

			// Get options set in WordPress dashboard (Settings > Insert Pages).
			$options = get_option( 'wpip_settings' );
			if ( false === $options || ! is_array( $options ) || ! array_key_exists( 'wpip_format', $options ) || ! array_key_exists( 'wpip_wrapper', $options ) || ! array_key_exists( 'wpip_insert_method', $options ) || ! array_key_exists( 'wpip_tinymce_filter', $options ) ) {
				$options = wpip_set_defaults();
			}

			$attributes['inline'] = ( false !== $attributes['inline'] && 'false' !== $attributes['inline'] ) || array_search( 'inline', $atts, true ) === 0 || ( array_key_exists( 'wpip_wrapper', $options ) && 'inline' === $options['wpip_wrapper'] );
			/**
			 * Filter the flag indicating whether to wrap the inserted content in inline tags (span).
			 *
			 * @param bool $use_inline_wrapper Indicates whether to wrap the content in span tags.
			 */
			$attributes['inline'] = apply_filters( 'insert_pages_use_inline_wrapper', $attributes['inline'] );
			$attributes['wrapper_tag'] = $attributes['inline'] ? 'span' : 'div';

			$attributes['public'] = ( false !== $attributes['public'] && 'false' !== $attributes['public'] ) || array_search( 'public', $atts, true ) === 0 || is_user_logged_in();

			/**
			 * Filter the querystring values applied to every inserted page. Useful
			 * for admins who want to provide the same querystring value to all
			 * inserted pages sitewide.
			 *
			 * @since 3.2.9
			 *
			 * @param string $querystring The querystring value for the inserted page.
			 */
			$attributes['querystring'] = apply_filters( 'insert_pages_override_querystring',
				str_replace( '{', '[', str_replace( '}', ']', htmlspecialchars_decode( $attributes['querystring'] ) ) )
			);

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
			 * @param bool $apply_nesting_check Indicates whether to apply deep nesting check.
			 */
			$attributes['should_apply_nesting_check'] = apply_filters( 'insert_pages_apply_nesting_check', $attributes['should_apply_nesting_check'] );

			/**
			 * Filter the chosen display method, where display can be one of:
			 * title, link, excerpt, excerpt-only, content, post-thumbnail, all, {custom-template.php}
			 * Useful for admins who want to restrict the display sitewide.
			 *
			 * @since 3.2.7
			 *
			 * @param string $display The display method for the inserted page.
			 */
			$attributes['display'] = apply_filters( 'insert_pages_override_display', $attributes['display'] );

			// Don't allow inserted pages to be added to the_content more than once (prevent infinite loops).
			if ( $attributes['should_apply_nesting_check'] ) {
				$done = false;
				foreach ( $wp_current_filter as $filter ) {
					if ( 'the_content' === $filter ) {
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
					array( $this, 'is_post_type_insertable' )
				);
				$inserted_page = get_page_by_path( $attributes['page'], OBJECT, $insertable_post_types );

				// If get_page_by_path() didn't find the page, check to see if the slug
				// was provided instead of the full path (useful for hierarchical pages
				// that are nested under another page).
				if ( is_null( $inserted_page ) ) {
					global $wpdb;
					$page = $wpdb->get_var( $wpdb->prepare(
						"SELECT ID FROM $wpdb->posts WHERE post_name = %s AND (post_status = 'publish' OR post_status = 'private') LIMIT 1", $attributes['page']
					) );
					if ( $page ) {
						$inserted_page = get_post( $page );
					}
				}

				$attributes['page'] = $inserted_page ? $inserted_page->ID : $attributes['page'];
			} else {
				$inserted_page = get_post( intval( $attributes['page'] ) );
			}

			// If inserted page's status is private, don't show to anonymous users
			// unless 'public' option is set.
			if ( 'private' === $inserted_page->post_status && ! $attributes['public'] ) {
				$inserted_page = null;
			}

			// Set any querystring params included in the shortcode.
			parse_str( $attributes['querystring'], $querystring );
			$original_get = $_GET;
			$original_request = $_REQUEST;
			foreach ( $querystring as $param => $value ) {
				$_GET[ $param ] = $value;
				$_REQUEST[ $param ] = $value;
			}

			// Use "Normal" insert method (get_post()).
			if ( 'legacy' !== $options['wpip_insert_method'] ) {

				// If we couldn't retrieve the page, fire the filter hook showing a not-found message.
				if ( null === $inserted_page ) {
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

				// If Beaver Builder, SiteOrigin Page Builder, Elementor, or WPBakery
				// Page Builder (Visual Composer) are enabled, load any cached styles
				// associated with the inserted page.
				// Note: Temporarily set the global $post->ID to the inserted page ID,
				// since both builders rely on the id to load the appropriate styles.
				if (
					class_exists( 'FLBuilder' ) ||
					class_exists( 'SiteOrigin_Panels' ) ||
					class_exists( '\Elementor\Post_CSS_File' ) ||
					defined( 'VCV_VERSION' )
				) {
					// If we're not in The Loop (i.e., global $post isn't assigned),
					// temporarily populate it with the post to be inserted so we can
					// retrieve generated styles for that post. Reset $post to null
					// after we're done.
					if ( is_null( $post ) ) {
						$old_post_id = null;
						$post = $inserted_page;
					} else {
						$old_post_id = $post->ID;
						$post->ID = $inserted_page->ID;
					}

					if ( class_exists( 'FLBuilder' ) ) {
						FLBuilder::enqueue_layout_styles_scripts( $inserted_page->ID );
					}

					if ( class_exists( 'SiteOrigin_Panels' ) ) {
						$renderer = SiteOrigin_Panels::renderer();
						$renderer->add_inline_css( $inserted_page->ID, $renderer->generate_css( $inserted_page->ID ) );
					}

					if ( class_exists( '\Elementor\Post_CSS_File' ) ) {
						$css_file = new \Elementor\Post_CSS_File( $inserted_page->ID );
						$css_file->enqueue();
					}

					// Enqueue custom style from WPBakery Page Builder (Visual Composer).
					if ( defined( 'VCV_VERSION' ) ) {
						$bundle_url = get_post_meta( $inserted_page->ID, 'vcvSourceCssFileUrl', true );
						if ( $bundle_url ) {
							$version = get_post_meta( $inserted_page->ID, 'vcvSourceCssFileHash', true );
							if ( ! preg_match( '/^http/', $bundle_url ) ) {
								if ( ! preg_match( '/assets-bundles/', $bundle_url ) ) {
									$bundle_url = '/assets-bundles/' . $bundle_url;
								}
							}
							if ( preg_match( '/^http/', $bundle_url ) ) {
								$bundle_url = set_url_scheme( $bundle_url );
							} elseif ( defined( 'VCV_TF_ASSETS_IN_UPLOADS' ) && constant( 'VCV_TF_ASSETS_IN_UPLOADS' ) ) {
								$upload_dir = wp_upload_dir();
								$bundle_url = set_url_scheme( $upload_dir['baseurl'] . '/' . VCV_PLUGIN_ASSETS_DIRNAME . '/' . ltrim( $bundle_url, '/\\' ) );
							} else {
								$bundle_url = content_url() . '/' . VCV_PLUGIN_ASSETS_DIRNAME . '/' . ltrim( $bundle_url, '/\\' );
							}
							wp_enqueue_style(
								'vcv:assets:source:main:styles:' . sanitize_title( $bundle_url ),
								$bundle_url,
								array(),
								VCV_VERSION . '.' . $version
							);
						}
					}

					if ( is_null( $old_post_id ) ) {
						$post = null;
					} else {
						$post->ID = $old_post_id;
					}
				}

				/**
				 * Show either the title, link, content, everything, or everything via a
				 * custom template.
				 *
				 * Note: if the sharing_display filter exists, it means Jetpack is
				 * installed and Sharing is enabled; this plugin conflicts with Sharing,
				 * because Sharing assumes the_content and the_excerpt filters are only
				 * getting called once. The fix here is to disable processing of filters
				 * on the_content in the inserted page.
				 *
				 * @see https://codex.wordpress.org/Function_Reference/the_content#Alternative_Usage
				 */
				switch ( $attributes['display'] ) {
					case 'title':
						$title_tag = $attributes['inline'] ? 'span' : 'h1';
						echo "<$title_tag class='insert-page-title'>";
						echo get_the_title( $inserted_page->ID );
						echo "</$title_tag>";
						break;

					case 'link':
						?><a href="<?php echo esc_url( get_permalink( $inserted_page->ID ) ); ?>"><?php echo get_the_title( $inserted_page->ID ); ?></a>
						<?php
						break;

					case 'excerpt':
						?><h1><a href="<?php echo esc_url( get_permalink( $inserted_page->ID ) ); ?>"><?php echo get_the_title( $inserted_page->ID ); ?></a></h1>
						<?php
						echo $this->insert_pages_trim_excerpt( get_post_field( 'post_excerpt', $inserted_page->ID ), $inserted_page->ID, $attributes['should_apply_the_content_filter'] );
						break;

					case 'excerpt-only':
						echo $this->insert_pages_trim_excerpt( get_post_field( 'post_excerpt', $inserted_page->ID ), $inserted_page->ID, $attributes['should_apply_the_content_filter'] );
						break;

					case 'content':
						// If Elementor is installed, try to render the page with it. If there is no Elementor content, fall back to normal rendering.
						if ( class_exists( '\Elementor\Plugin' ) ) {
							$elementor_content = \Elementor\Plugin::$instance->frontend->get_builder_content( $inserted_page->ID );
							if ( strlen( $elementor_content ) > 0 ) {
								echo $elementor_content;
								break;
							}
						}

						// Render the content normally.
						$content = get_post_field( 'post_content', $inserted_page->ID );
						if ( $attributes['should_apply_the_content_filter'] ) {
							$content = apply_filters( 'the_content', $content );
						}
						echo $content;
						break;

					case 'post-thumbnail':
						?><a href="<?php echo esc_url( get_permalink( $inserted_page->ID ) ); ?>"><?php echo get_the_post_thumbnail( $inserted_page->ID ); ?></a>
						<?php
						break;

					case 'all':
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
						/**
						 * Meta.
						 *
						 * @see https://core.trac.wordpress.org/browser/tags/4.4/src/wp-includes/post-template.php#L968
						 */
						$keys = get_post_custom_keys( $inserted_page->ID );
						if ( $keys ) {
							echo "<ul class='post-meta'>\n";
							foreach ( (array) $keys as $key ) {
								$keyt = trim( $key );
								if ( is_protected_meta( $keyt, 'post' ) ) {
									continue;
								}
								$value = get_post_custom_values( $key, $inserted_page->ID );
								if ( is_array( $value ) ) {
									$values = array_map( 'trim', $value );
									$value = implode( $values, ', ' );
								}

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

					default: // Display is either invalid, or contains a template file to use.
						/**
						 * Legacy/compatibility code: In order to use custom templates,
						 * we use query_posts() to provide the template with the global
						 * state it requires for the inserted page (in other words, all
						 * template tags will work with respect to the inserted page
						 * instead of the parent page / main loop). Note that this may
						 * cause some compatibility issues with other plugins.
						 *
						 * @see https://codex.wordpress.org/Function_Reference/query_posts
						 */
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
							// Only allow templates that don't have any directory traversal in
							// them (to prevent including php files that aren't in the active
							// theme directory or the /wp-includes/theme-compat/ directory).
							$path_in_theme_or_childtheme_or_compat = (
								// Template is in current theme folder.
								0 === strpos( realpath( $template ), realpath( get_stylesheet_directory() ) ) ||
								// Template is in current or parent theme folder.
								0 === strpos( realpath( $template ), realpath( get_template_directory() ) ) ||
								// Template is in theme-compat folder.
								0 === strpos( realpath( $template ), realpath( ABSPATH . WPINC . '/theme-compat/' ) )
							);
							if ( strlen( $template ) > 0 && $path_in_theme_or_childtheme_or_compat ) {
								include $template; // Execute the template code.
							} else { // Couldn't find template, so fall back to printing a link to the page.
								the_post();
								?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								<?php
							}
						}
						wp_reset_query();
				}

				// Save output buffer contents.
				$content = ob_get_clean();

			} else { // Use "Legacy" insert method (query_posts()).

				// Construct query_posts arguments.
				if ( is_numeric( $attributes['page'] ) ) {
					$args = array(
						'p' => intval( $attributes['page'] ),
						'post_type' => get_post_types(),
						'post_status' => $attributes['public'] ? array( 'publish', 'private' ) : array( 'publish' ),
					);
				} else {
					$args = array(
						'name' => esc_attr( $attributes['page'] ),
						'post_type' => get_post_types(),
						'post_status' => $attributes['public'] ? array( 'publish', 'private' ) : array( 'publish' ),
					);
				}
				$posts = query_posts( $args );
				if ( have_posts() ) {
					// Start output buffering so we can save the output to string.
					ob_start();

					// If Beaver Builder, SiteOrigin Page Builder, Elementor, or WPBakery
					// Page Builder (Visual Composer) are enabled, load any cached styles
					// associated with the inserted page.
					// Note: Temporarily set the global $post->ID to the inserted page ID,
					// since both builders rely on the id to load the appropriate styles.
					if (
						class_exists( 'FLBuilder' ) ||
						class_exists( 'SiteOrigin_Panels' ) ||
						class_exists( '\Elementor\Post_CSS_File' ) ||
						defined( 'VCV_VERSION' )
					) {
						// If we're not in The Loop (i.e., global $post isn't assigned),
						// temporarily populate it with the post to be inserted so we can
						// retrieve generated styles for that post. Reset $post to null
						// after we're done.
						if ( is_null( $post ) ) {
							$old_post_id = null;
							$post = $inserted_page;
						} else {
							$old_post_id = $post->ID;
							$post->ID = $inserted_page->ID;
						}

						if ( class_exists( 'FLBuilder' ) ) {
							FLBuilder::enqueue_layout_styles_scripts( $inserted_page->ID );
						}

						if ( class_exists( 'SiteOrigin_Panels' ) ) {
							$renderer = SiteOrigin_Panels::renderer();
							$renderer->add_inline_css( $inserted_page->ID, $renderer->generate_css( $inserted_page->ID ) );
						}

						if ( class_exists( '\Elementor\Post_CSS_File' ) ) {
							$css_file = new \Elementor\Post_CSS_File( $inserted_page->ID );
							$css_file->enqueue();
						}

						// Enqueue custom style from WPBakery Page Builder (Visual Composer).
						if ( defined( 'VCV_VERSION' ) ) {
							$bundle_url = get_post_meta( $inserted_page->ID, 'vcvSourceCssFileUrl', true );
							if ( $bundle_url ) {
								$version = get_post_meta( $inserted_page->ID, 'vcvSourceCssFileHash', true );
								if ( ! preg_match( '/^http/', $bundle_url ) ) {
									if ( ! preg_match( '/assets-bundles/', $bundle_url ) ) {
										$bundle_url = '/assets-bundles/' . $bundle_url;
									}
								}
								if ( preg_match( '/^http/', $bundle_url ) ) {
									$bundle_url = set_url_scheme( $bundle_url );
								} elseif ( defined( 'VCV_TF_ASSETS_IN_UPLOADS' ) && constant( 'VCV_TF_ASSETS_IN_UPLOADS' ) ) {
									$upload_dir = wp_upload_dir();
									$bundle_url = set_url_scheme( $upload_dir['baseurl'] . '/' . VCV_PLUGIN_ASSETS_DIRNAME . '/' . ltrim( $bundle_url, '/\\' ) );
								} else {
									$bundle_url = content_url() . '/' . VCV_PLUGIN_ASSETS_DIRNAME . '/' . ltrim( $bundle_url, '/\\' );
								}
								wp_enqueue_style(
									'vcv:assets:source:main:styles:' . sanitize_title( $bundle_url ),
									$bundle_url,
									array(),
									VCV_VERSION . '.' . $version
								);
							}
						}

						if ( is_null( $old_post_id ) ) {
							$post = null;
						} else {
							$post->ID = $old_post_id;
						}
					}

					/**
					 * Show either the title, link, content, everything, or everything via a
					 * custom template.
					 *
					 * Note: if the sharing_display filter exists, it means Jetpack is
					 * installed and Sharing is enabled; this plugin conflicts with Sharing,
					 * because Sharing assumes the_content and the_excerpt filters are only
					 * getting called once. The fix here is to disable processing of filters
					 * on the_content in the inserted page.
					 *
					 * @see https://codex.wordpress.org/Function_Reference/the_content#Alternative_Usage
					 */
					switch ( $attributes['display'] ) {
						case 'title':
							the_post();
							$title_tag = $attributes['inline'] ? 'span' : 'h1';
							echo "<$title_tag class='insert-page-title'>";
							the_title();
							echo "</$title_tag>";
							break;
						case 'link':
							the_post();
							?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							<?php
							break;
						case 'excerpt':
							the_post();
							?><h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
							<?php
							if ( $attributes['should_apply_the_content_filter'] ) {
								the_excerpt();
							} else {
								echo get_the_excerpt();
							}
							break;
						case 'excerpt-only':
							the_post();
							if ( $attributes['should_apply_the_content_filter'] ) {
								the_excerpt();
							} else {
								echo get_the_excerpt();
							}
							break;
						case 'content':
							// If Elementor is installed, try to render the page with it. If there is no Elementor content, fall back to normal rendering.
							if ( class_exists( '\Elementor\Plugin' ) ) {
								$elementor_content = \Elementor\Plugin::$instance->frontend->get_builder_content( $inserted_page->ID );
								if ( strlen( $elementor_content ) > 0 ) {
									echo $elementor_content;
									break;
								}
							}
							// Render the content normally.
							the_post();
							if ( $attributes['should_apply_the_content_filter'] ) {
								the_content();
							} else {
								echo get_the_content();
							}
							break;
						case 'post-thumbnail':
							?><a href="<?php echo esc_url( get_permalink( $inserted_page->ID ) ); ?>"><?php echo get_the_post_thumbnail( $inserted_page->ID ); ?></a>
							<?php
							break;
						case 'all':
							the_post();
							$title_tag = $attributes['inline'] ? 'span' : 'h1';
							echo "<$title_tag class='insert-page-title'>";
							the_title();
							echo "</$title_tag>";
							if ( $attributes['should_apply_the_content_filter'] ) {
								the_content();
							} else {
								echo get_the_content();
							}
							the_meta();
							break;
						default: // Display is either invalid, or contains a template file to use.
							$template = locate_template( $attributes['display'] );
							// Only allow templates that don't have any directory traversal in
							// them (to prevent including php files that aren't in the active
							// theme directory or the /wp-includes/theme-compat/ directory).
							$path_in_theme_or_childtheme_or_compat = (
								// Template is in current theme folder.
								0 === strpos( realpath( $template ), realpath( get_stylesheet_directory() ) ) ||
								// Template is in current or parent theme folder.
								0 === strpos( realpath( $template ), realpath( get_template_directory() ) ) ||
								// Template is in theme-compat folder.
								0 === strpos( realpath( $template ), realpath( ABSPATH . WPINC . '/theme-compat/' ) )
							);
							if ( strlen( $template ) > 0 && $path_in_theme_or_childtheme_or_compat ) {
								include $template; // Execute the template code.
							} else { // Couldn't find template, so fall back to printing a link to the page.
								the_post();
								?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								<?php
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
			 *   public: Boolean indicating anonymous users can see private inserted pages.
			 *   querystring: Extra querystring values provided to the custom template.
			 *   should_apply_nesting_check: Whether to disable nested inserted pages.
			 *   should_apply_the_content_filter: Whether to apply the_content filter to post contents and excerpts.
			 *   wrapper_tag: Tag to use for the wrapper element (e.g., div, span).
			 */
			$content = apply_filters( 'insert_pages_wrap_content', $content, $inserted_page, $attributes );

			// Unset any querystring params included in the shortcode.
			$_GET = $original_get;
			$_REQUEST = $original_request;

			return $content;
		}

		/**
		 * Default filter for insert_pages_wrap_content.
		 *
		 * @param  string $content    Content of shortcode.
		 * @param  array  $posts      Post data of inserted page.
		 * @param  array  $attributes Shortcode attributes.
		 * @return string             Content to replace shortcode.
		 */
		public function insert_pages_wrap_content( $content, $posts, $attributes ) {
			return "<{$attributes['wrapper_tag']} data-post-id='{$attributes['page']}' class='insert-page insert-page-{$attributes['page']} {$attributes['class']}'>{$content}</{$attributes['wrapper_tag']}>";
		}

		/**
		 * Filter hook: Add a button to the TinyMCE toolbar for our insert page tool.
		 *
		 * @param  array $buttons TinyMCE buttons.
		 * @return array          TinyMCE buttons with Insert Pages button.
		 */
		public function insert_pages_handle_filter_mce_buttons( $buttons ) {
			if ( ! in_array( 'wpInsertPages_button', $buttons, true ) ) {
				array_push( $buttons, 'wpInsertPages_button' );
			}
			return $buttons;
		}

		/**
		 * Filter hook: Load the javascript for our custom toolbar button.
		 *
		 * @param  array $plugins TinyMCE plugins.
		 * @return array          TinyMCE plugins with Insert Pages plugin.
		 */
		public function insert_pages_handle_filter_mce_external_plugins( $plugins ) {
			if ( ! array_key_exists( 'wpInsertPages', $plugins ) ) {
				$plugins['wpInsertPages'] = plugins_url( '/js/wpinsertpages_plugin.js', __FILE__ );
			}
			return $plugins;
		}

		/**
		 * Helper function to generate an excerpt (outside of the Loop) for a given
		 * ID (based on wp_trim_excerpt()).
		 *
		 * @param  string  $text                     Excerpt.
		 * @param  integer $post_id                  Post ID of excerpt.
		 * @param  boolean $apply_the_content_filter Whether to apply `the_content`.
		 * @return string                            Excerpt.
		 */
		public function insert_pages_trim_excerpt( $text = '', $post_id = 0, $apply_the_content_filter = true ) {
			$post_id = intval( $post_id );
			if ( $post_id < 1 ) {
				return '';
			}

			$raw_excerpt = $text;
			if ( '' === $text ) {
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
				$excerpt_more = apply_filters( 'excerpt_more', ' [&hellip;]' );
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
		public function insert_pages_wp_tinymce_dialog() {
			// If wp_editor() is being called outside of an admin context,
			// required dependencies for Insert Pages will be missing (e.g.,
			// wp-admin/includes/template.php will not be loaded, admin_head
			// action will not be fired). If that's the case, just skip loading
			// the Insert Pages tinymce button.
			if ( ! is_admin() || ! function_exists( 'page_template_dropdown' ) ) {
				return;
			}

			// Get ID of post currently being edited.
			$post_id = array_key_exists( 'post', $_REQUEST ) && intval( $_REQUEST['post'] ) > 0 ? intval( $_REQUEST['post'] ) : '';

			// display: none is required here, see #WP27605.
			?><div id="wp-insertpage-backdrop" style="display: none"></div>
			<div id="wp-insertpage-wrap" class="wp-core-ui<?php
			if ( 1 === intval( get_user_setting( 'wpinsertpage', 0 ) ) ) :
				?> options-panel-visible<?php
			endif; ?>" style="display: none">
			<form id="wp-insertpage" tabindex="-1">
			<?php wp_nonce_field( 'internal-inserting', '_ajax_inserting_nonce', false ); ?>
			<input type="hidden" id="insertpage-parent-page-id" value="<?php echo esc_attr( $post_id ); ?>" />
			<div id="insertpage-modal-title">
				<?php esc_html_e( 'Insert page', 'insert-pages' ); ?>
				<div id="wp-insertpage-close" tabindex="0"></div>
			</div>
			<div id="insertpage-selector">
				<div id="insertpage-search-panel">
					<div class="insertpage-search-wrapper">
						<label>
							<span class="search-label"><?php esc_html_e( 'Search', 'insert-pages' ); ?></span>
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
						<div class="query-notice"><em><?php esc_html_e( 'No search term specified. Showing recent items.', 'insert-pages' ); ?></em></div>
						<ul></ul>
						<div class="river-waiting">
							<span class="spinner"></span>
						</div>
					</div>
				</div>
				<p class="howto" id="insertpage-options-toggle"><?php esc_html_e( 'Options', 'insert-pages' ); ?></p>
				<div id="insertpage-options-panel">
					<div class="insertpage-options-wrapper">
						<label for="insertpage-slug-field">
							<span><?php esc_html_e( 'Slug or ID', 'insert-pages' ); ?></span>
							<input id="insertpage-slug-field" type="text" autocomplete="off" />
							<input id="insertpage-page-id" type="hidden" />
						</label>
					</div>
					<div class="insertpage-format">
						<label for="insertpage-format-select">
							<?php esc_html_e( 'Display', 'insert-pages' ); ?>
							<select name="insertpage-format-select" id="insertpage-format-select">
								<option value='title'><?php esc_html_e( 'Title', 'insert-pages' ); ?></option>
								<option value='link'><?php esc_html_e( 'Link', 'insert-pages' ); ?></option>
								<option value='excerpt'><?php esc_html_e( 'Excerpt with title', 'insert-pages' ); ?></option>
								<option value='excerpt-only'><?php esc_html_e( 'Excerpt only (no title)', 'insert-pages' ); ?></option>
								<option value='content'><?php esc_html_e( 'Content', 'insert-pages' ); ?></option>
								<option value='post-thumbnail'><?php esc_html_e( 'Post Thumbnail', 'insert-pages' ); ?></option>
								<option value='all'><?php esc_html_e( 'All (includes custom fields)', 'insert-pages' ); ?></option>
								<option value='template'><?php esc_html_e( 'Use a custom template', 'insert-pages' ); ?> &raquo;</option>
							</select>
							<select name="insertpage-template-select" id="insertpage-template-select" disabled="true">
								<option value='all'><?php esc_html_e( 'Default Template', 'insert-pages' ); ?></option>
								<?php page_template_dropdown(); ?>
							</select>
						</label>
					</div>
					<div class="insertpage-extra">
						<label for="insertpage-extra-classes">
							<?php esc_html_e( 'Extra Classes', 'insert-pages' ); ?>
							<input id="insertpage-extra-classes" type="text" autocomplete="off" />
						</label>
						<label for="insertpage-extra-inline">
							<?php esc_html_e( 'Inline?', 'insert-pages' ); ?>
							<input id="insertpage-extra-inline" type="checkbox" />
						</label>
						<label for="insertpage-extra-querystring">
							<?php esc_html_e( 'Querystring', 'insert-pages' ); ?>
							<input id="insertpage-extra-querystring" type="text" autocomplete="off" />
						</label>
						<br>
						<label for="insertpage-extra-public">
							<input id="insertpage-extra-public" type="checkbox" />
							<?php esc_html_e( 'Anonymous users can see this inserted even if its status is private', 'insert-pages' ); ?>
						</label>
					</div>
				</div>
			</div>
			<div class="submitbox">
				<div id="wp-insertpage-update">
					<input type="submit" value="<?php esc_attr_e( 'Insert Page', 'insert-pages' ); ?>" class="button button-primary" id="wp-insertpage-submit" name="wp-insertpage-submit">
				</div>
				<div id="wp-insertpage-cancel">
					<a class="submitdelete deletion" href="#"><?php esc_html_e( 'Cancel', 'insert-pages' ); ?></a>
				</div>
			</div>
			</form>
			</div>
			<?php
		}

		/**
		 * Modified from:
		 * Internal linking functions.
		 *
		 * @package WordPress
		 * @subpackage Administration
		 * @since 3.1.0
		 */
		public function insert_pages_insert_page_callback() {
			check_ajax_referer( 'internal-inserting', '_ajax_inserting_nonce' );
			$args = array();
			if ( isset( $_POST['search'] ) ) {
				$args['s'] = wp_unslash( $_POST['search'] );
			}
			$args['pagenum'] = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
			$args['pageID'] = ! empty( $_POST['pageID'] ) ? absint( $_POST['pageID'] ) : 0;

			// Change search to slug or post ID if we're not doing a plaintext
			// search (e.g., if we're editing an existing shortcode and the
			// search field is populated with the post's slug or ID).
			if ( array_key_exists( 'type', $_POST ) && 'slug' === $_POST['type'] ) {
				$args['name'] = $args['s'];
				unset( $args['s'] );
			} elseif ( array_key_exists( 'type', $_POST ) && 'post_id' === $_POST['type'] ) {
				$args['p'] = $args['s'];
				unset( $args['s'] );
			}

			$results = $this->insert_pages_wp_query( $args );

			// Fail if our query didn't work.
			if ( ! isset( $results ) ) {
				die( '0' );
			}

			echo wp_json_encode( $results );
			echo "\n";
			die();
		}

		/**
		 * Modified from:
		 * Performs post queries for internal linking.
		 *
		 * @since 3.1.0
		 * @param  array $args Optional. Accepts 'pagenum' and 's' (search) arguments.
		 * @return array Results.
		 */
		private function insert_pages_wp_query( $args = array() ) {
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
				'post_status' => array( 'publish', 'private' ),
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
			$get_posts = new WP_Query();
			$posts = $get_posts->query( $query );
			// Check if any posts were found.
			if ( ! $get_posts->post_count ) {
				return false;
			}

			// Build results.
			$results = array();
			foreach ( $posts as $post ) {
				if ( 'post' === $post->post_type ) {
					$info = mysql2date( 'Y/m/d', $post->post_date );
				} else {
					$info = $pts[ $post->post_type ]->labels->singular_name;
				}
				$results[] = array(
					'ID' => $post->ID,
					'title' => trim( esc_html( strip_tags( get_the_title( $post ) ) ) ),
					'permalink' => get_permalink( $post->ID ),
					'slug' => $post->post_name,
					'path' => get_page_uri( $post ),
					'info' => $info,
					'status' => get_post_status( $post ),
				);
			}
			return $results;
		}

		/**
		 * Add Insert Page quicktag button to Text editor.
		 *
		 * @return void
		 */
		public function insert_pages_add_quicktags() {
			if ( wp_script_is( 'quicktags' ) ) : ?>
				<script type="text/javascript">
					QTags.addButton( 'ed_insert_page', '[insert page]', "[insert page='your-page-slug' display='title|link|excerpt|excerpt-only|content|post-thumbnail|all']\n", '', '', 'Insert Page', 999 );
				</script>
			<?php
			endif;
		}

		/**
		 * Indicates whether a particular post type is able to be inserted.
		 *
		 * @param  boolean $type Post type.
		 * @return boolean       Whether post type is insertable.
		 */
		private function is_post_type_insertable( $type ) {
			return ! in_array( $type, array( 'nav_menu_item', 'attachment', 'revision', 'customize_changeset', 'oembed_cache' ), true );
		}

		/**
		 * Registers the theme widget for inserting a page into an area.
		 *
		 * @return void
		 */
		public function insert_pages_widgets_init() {
			register_widget( 'InsertPagesWidget' );
		}

	}
}

// Initialize InsertPagesPlugin object.
if ( class_exists( 'InsertPagesPlugin' ) ) {
	$insert_pages_plugin = new InsertPagesPlugin();
}

// Actions and Filters handled by InsertPagesPlugin class.
if ( isset( $insert_pages_plugin ) ) {
	// Get options set in WordPress dashboard (Settings > Insert Pages).
	$options = get_option( 'wpip_settings' );
	if ( false === $options || ! is_array( $options ) || ! array_key_exists( 'wpip_format', $options ) || ! array_key_exists( 'wpip_wrapper', $options ) || ! array_key_exists( 'wpip_insert_method', $options ) || ! array_key_exists( 'wpip_tinymce_filter', $options ) ) {
		$options = wpip_set_defaults();
	}

	// Register shortcode [insert ...].
	add_action( 'init', array( $insert_pages_plugin, 'insert_pages_init' ), 1 );
	// Register shortcode [insert ...] when TinyMCE is included in a frontend ACF form.
	add_action( 'acf_head-input', array( $insert_pages_plugin, 'insert_pages_init' ), 1 ); // ACF 3.
	add_action( 'acf/input/admin_head', array( $insert_pages_plugin, 'insert_pages_init' ), 1 ); // ACF 4.

	// Add TinyMCE button for shortcode.
	add_action( 'admin_head', array( $insert_pages_plugin, 'insert_pages_admin_init' ), 1 );

	// Add quicktags button for shortcode.
	add_action( 'admin_print_footer_scripts', array( $insert_pages_plugin, 'insert_pages_add_quicktags' ) );

	// Preload TinyMCE popup.
	add_action( 'before_wp_tiny_mce', array( $insert_pages_plugin, 'insert_pages_wp_tinymce_dialog' ), 1 );

	// Ajax: Populate page search in TinyMCE button popup.
	add_action( 'wp_ajax_insertpage', array( $insert_pages_plugin, 'insert_pages_insert_page_callback' ) );

	// Use internal filter to wrap inserted content in a div or span.
	add_filter( 'insert_pages_wrap_content', array( $insert_pages_plugin, 'insert_pages_wrap_content' ), 10, 3 );

	/**
	 * Register TinyMCE plugin for the toolbar button if in compatibility mode.
	 * (to work around a SiteOrigin PageBuilder bug).
	 *
	 * @see  https://wordpress.org/support/topic/button-in-the-toolbar-of-tinymce-disappear-conflict-page-builder/
	 */
	if ( 'compatibility' === $options['wpip_tinymce_filter'] ) {
		add_filter( 'mce_external_plugins', array( $insert_pages_plugin, 'insert_pages_handle_filter_mce_external_plugins' ) );
		add_filter( 'mce_buttons', array( $insert_pages_plugin, 'insert_pages_handle_filter_mce_buttons' ) );
	}

	// Register Insert Pages shortcode widget.
	require_once dirname( __FILE__ ) . '/widget.php';
	add_action( 'widgets_init', array( $insert_pages_plugin, 'insert_pages_widgets_init' ) );
}
