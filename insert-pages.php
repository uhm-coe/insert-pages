<?php
/**
 * Plugin Name: Insert Pages
 * Plugin URI: https://github.com/uhm-coe/insert-pages
 * Description: Insert Pages lets you embed any WordPress content (e.g., pages, posts, custom post types) into other WordPress content using the Shortcode API.
 * Author: Paul Ryan
 * Text Domain: insert-pages
 * Domain Path: /languages
 * License: GPL2
 * Requires at least: 3.0.1
 * Version: 3.7.0
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
 * [insert page='{slug}|{id}' display='title|link|excerpt|excerpt-only|content|post-thumbnail|all|{custom-template.php}' class='any-classes' id='any-id' [inline] [public] querystring='{url-encoded-values}']
 */

if ( ! class_exists( 'InsertPagesPlugin' ) ) {
	/**
	 * Class InsertPagesPlugin
	 */
	class InsertPagesPlugin {
		/**
		 * Stack tracking inserted pages (for loop detection).
		 *
		 * @var  array Array of page ids inserted.
		 */
		protected $inserted_page_ids;

		/**
		 * Flag to only render the TinyMCE plugin dialog once.
		 *
		 * @var boolean
		 */
		private static $link_dialog_printed = false;

		/**
		 * Flag checked when rendering TinyMCE modal to ensure that required scripts
		 * and styles were enqueued (normally done in `admin_init` hook).
		 *
		 * @var boolean
		 */
		private static $is_admin_initialized = false;

		/**
		 * Singleton plugin instance.
		 *
		 * @var object Plugin instance.
		 */
		protected static $instance = null;


		/**
		 * Access this plugin's working instance.
		 *
		 * @return object Object of this class.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}


		/**
		 * Disable constructor to enforce a single plugin instance..
		 */
		protected function __construct() {
		}


		/**
		 * Action hook: WordPress 'init'.
		 *
		 * @return void
		 */
		public function insert_pages_init() {
			$options = get_option( 'wpip_settings' );

			// Register the [insert] shortcode.
			add_shortcode( 'insert', array( $this, 'insert_pages_handle_shortcode_insert' ) );

			// Register the gutenberg block so we can populate it via server side
			// rendering. Note: only register it once (some plugins, like Advanced
			// Custom Fields, create a scenario where this init hook gets called
			// multiple times).
			if (
				function_exists( 'register_block_type' ) &&
				isset( $options['wpip_gutenberg_block'] ) &&
				'enabled' === $options['wpip_gutenberg_block'] &&
				class_exists( 'WP_Block_Type_Registry' ) &&
				! WP_Block_Type_Registry::get_instance()->is_registered( 'insert-pages/block' )
			) {
				// Automatically load dependencies and version.
				$asset_file = include( plugin_dir_path( __FILE__ ) . 'lib/gutenberg-block/build/index.asset.php');

				wp_register_script(
					'insert-pages-gutenberg-block',
					plugins_url( 'lib/gutenberg-block/build/index.js', __FILE__ ),
					$asset_file['dependencies'],
					$asset_file['version']
				);

				wp_register_style(
					'insert-pages-gutenberg-block',
					plugins_url( 'lib/gutenberg-block/build/index.css', __FILE__ ),
					array(),
					$asset_file['version']
				);

				register_block_type(
					'insert-pages/block',
					array(
						'editor_style' => 'insert-pages-gutenberg-block',
						'editor_script' => 'insert-pages-gutenberg-block',
						'attributes' => array(
							'url' => array(
								'type' => 'string',
								'default' => '',
							),
							'page' => array(
								'type' => 'number',
								'default' => 0,
							),
							'display' => array(
								'type' => 'string',
								'default' => 'title',
							),
							'template' => array(
								'type' => 'string',
								'default' => '',
							),
							'class' => array(
								'type' => 'string',
								'default' => '',
							),
							'id' => array(
								'type' => 'string',
								'default' => '',
							),
							'inline' => array(
								'type' => 'bool',
								'default' => false,
							),
							'public' => array(
								'type' => 'bool',
								'default' => false,
							),
							'querystring' => array(
								'type' => 'string',
								'default' => '',
							),
						),
						'render_callback' => array( $this, 'block_render_callback' ),
					)
				);
			}
		}


		/**
		 * Renders the gutenberg block (using legacy server-side rendering).
		 *
		 * @param  array $attr Array of block attributes.
		 * @return string      Rendered inserted page.
		 */
		public function block_render_callback( $attr ) {
			// Display attribute defaults to 'title'; otherwise it is the passed param,
			// and if the display param is 'custom', it is the value of the 'template'
			// param.
			$display = 'title';
			if ( isset( $attr['display'] ) && strlen( $attr['display'] ) > 0 ) {
				$display = esc_attr( $attr['display'] );
			}
			if ( 'custom' === $display && isset( $attr['template'] ) && strlen( $attr['template'] ) > 0) {
				$display = esc_attr( $attr['template'] );
			}

			$shortcode = sprintf(
				'[insert page="%1$s" display="%2$s"%3$s%4$s%5$s%6$s%7$s]',
				isset( $attr['page'] ) && strlen( $attr['page'] ) > 0 ? esc_attr( $attr['page'] ) : '0',
				$display,
				isset( $attr['class'] ) && strlen( $attr['class'] ) > 0 ? ' class="' . esc_attr( $attr['class'] ) . '"' : '',
				isset( $attr['id'] ) && strlen( $attr['id'] ) > 0 ? ' id="' . esc_attr( $attr['id'] ) . '"' : '',
				isset( $attr['querystring'] ) && strlen( $attr['querystring'] ) > 0 ? ' querystring="' . esc_attr( $attr['querystring'] ) . '"' : '',
				isset( $attr['inline'] ) && 'true' === $attr['inline'] ? ' inline' : '',
				isset( $attr['public'] ) && 'true' === $attr['public'] ? ' public' : ''
			);

			return do_shortcode( $shortcode );
		}


		/**
		 * Load gutenberg block resources only when editing (only if Gutenberg block
		 * setting is enabled in Insert Pages settings).
		 *
		 * Action hook: enqueue_block_editor_assets
		 *
		 * @return void
		 */
		public function insert_pages_enqueue_block_editor_assets() {
			$options = get_option( 'wpip_settings' );
			if ( isset( $options['wpip_gutenberg_block'] ) && 'enabled' === $options['wpip_gutenberg_block'] ) {
			}
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
				'20210528',
				false
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
					'tinymce_state' => $this->get_tinymce_state(),
				)
			);

			// Register the TinyMCE toolbar button styles.
			wp_enqueue_style(
				'wpinsertpagescss',
				plugins_url( '/css/wpinsertpages.css', __FILE__ ),
				array( 'wp-jquery-ui-dialog' ),
				'20210408'
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

			// Load the translations.
			load_plugin_textdomain(
				'insert-pages',
				false,
				plugin_basename( dirname( __FILE__ ) ) . '/languages'
			);

			self::$is_admin_initialized = true;
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
			$attributes = shortcode_atts(
				array(
					'page'        => '0',
					'display'     => 'all',
					'class'       => '',
					'id'          => '',
					'querystring' => '',
					'inline'      => false,
					'public'      => false,
				),
				$atts,
				'insert'
			);

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
			$attributes['querystring'] = apply_filters(
				'insert_pages_override_querystring',
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
					$page = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$wpdb->prepare(
							"SELECT ID FROM $wpdb->posts WHERE post_name = %s AND (post_status = 'publish' OR post_status = 'private') LIMIT 1",
							$attributes['page']
						)
					);
					if ( $page ) {
						$inserted_page = get_post( $page );
					}
				}

				$attributes['page'] = $inserted_page ? $inserted_page->ID : $attributes['page'];
			} else {
				$inserted_page = get_post( intval( $attributes['page'] ) );
			}

			// Prevent unprivileged users from inserting private posts from others.
			if ( is_object( $inserted_page ) && 'publish' !== $inserted_page->post_status ) {
				$post_type = get_post_type_object( $inserted_page->post_type );
				$parent_post_author_id = intval( get_the_author_meta( 'ID' ) );
				if ( ! user_can( $parent_post_author_id, $post_type->cap->read_post, $inserted_page->ID ) ) {
					$inserted_page = null;
				}
			}

			// If inserted page's status is private, don't show to anonymous users
			// unless 'public' option is set.
			if ( is_object( $inserted_page ) && 'private' === $inserted_page->post_status && ! $attributes['public'] ) {
				$inserted_page = null;
			}

			// Loop detection: check if the page we are inserting has already been
			// inserted; if so, short circuit here.
			if ( ! is_array( $this->inserted_page_ids ) ) {
				// Initialize stack to the main page that contains inserted page(s).
				$this->inserted_page_ids = array( get_the_ID() );
			}
			if ( isset( $inserted_page->ID ) ) {
				if ( ! in_array( $inserted_page->ID, $this->inserted_page_ids ) ) {
					// Add the page being inserted to the stack.
					$this->inserted_page_ids[] = $inserted_page->ID;
				} else {
					// Loop detected, so exit without rendering this post.
					return $content;
				}
			}

			// Set any querystring params included in the shortcode.
			parse_str( $attributes['querystring'], $querystring );
			$original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification
			$original_request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification
			foreach ( $querystring as $param => $value ) {
				$_GET[ $param ] = $value;
				$_REQUEST[ $param ] = $value;
			}
			$original_wp_query_vars = $GLOBALS['wp']->query_vars;
			if ( ! empty( $querystring ) && isset( $GLOBALS['wp'] ) && method_exists( $GLOBALS['wp'], 'parse_request' ) ) {
				$GLOBALS['wp']->parse_request( $querystring );
			}

			// Use "Normal" insert method (get_post).
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
					defined( 'VCV_VERSION' ) ||
					defined( 'WPB_VC_VERSION' )
				) {
					// If we're not in The Loop (i.e., global $post isn't assigned),
					// temporarily populate it with the post to be inserted so we can
					// retrieve generated styles for that post. Reset $post to null
					// after we're done.
					if ( is_null( $post ) ) {
						$old_post_id = null;
						$post = $inserted_page; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
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
							} elseif ( class_exists( 'VisualComposer\Helpers\AssetsEnqueue' ) ) {
								// These methods should work for Visual Composer 26.0.
								// Enqueue custom css/js stored in vcvSourceAssetsFiles postmeta.
								$vc = new \VisualComposer\Helpers\AssetsEnqueue;
								if ( method_exists( $vc, 'enqueueAssets' ) ) {
									$vc->enqueueAssets($inserted_page->ID);
								}
								// Enqueue custom CSS stored in vcvSourceCssFileUrl postmeta.
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

					// Visual Composer custom CSS.
					if ( defined( 'WPB_VC_VERSION' ) ) {
						// Post custom CSS.
						$post_custom_css = get_post_meta( $inserted_page->ID, '_wpb_post_custom_css', true );
						if ( ! empty( $post_custom_css ) ) {
							$post_custom_css = wp_strip_all_tags( $post_custom_css );
							echo '<style type="text/css" data-type="vc_custom-css">';
							echo $post_custom_css;
							echo '</style>';
						}
						// Shortcodes custom CSS.
						$shortcodes_custom_css = get_post_meta( $inserted_page->ID, '_wpb_shortcodes_custom_css', true );
						if ( ! empty( $shortcodes_custom_css ) ) {
							$shortcodes_custom_css = wp_strip_all_tags( $shortcodes_custom_css );
							echo '<style type="text/css" data-type="vc_shortcodes-custom-css">';
							echo $shortcodes_custom_css;
							echo '</style>';
						}
					}

					if ( is_null( $old_post_id ) ) {
						$post = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
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
						$this->the_meta( $inserted_page->ID );
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
						// We save the previous query state here instead of using
						// wp_reset_query() because wp_reset_query() only has a single stack
						// variable ($GLOBALS['wp_the_query']). This allows us to support
						// pages inserted into other pages (multiple nested pages).
						$old_query = $GLOBALS['wp_query'];
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
						// Restore previous query and update the global template variables.
						$GLOBALS['wp_query'] = $old_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
						wp_reset_postdata();
				}

				// Save output buffer contents.
				$content = ob_get_clean();

			} else { // Use "Legacy" insert method (query_posts).

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

				// We save the previous query state here instead of using
				// wp_reset_query() because wp_reset_query() only has a single stack
				// variable ($GLOBALS['wp_the_query']). This allows us to support
				// pages inserted into other pages (multiple nested pages).
				$old_query = $GLOBALS['wp_query'];
				$posts = query_posts( $args );

				// Prevent unprivileged users from inserting private posts from others.
				if ( have_posts() ) {
					$can_read = true;
					$parent_post_author_id = intval( get_the_author_meta( 'ID' ) );
					foreach ( $posts as $post ) {
						$post_type = get_post_type_object( $post->post_type );
						if ( ! user_can( $parent_post_author_id, $post_type->cap->read_post, $post->ID ) ) {
							$can_read = false;
						}
					}
					if ( ! $can_read ) {
						// Force an empty query so we don't show any posts.
						$posts = query_posts( array( 'post__in' => array( 0 ) ) );
					}
				}

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
						defined( 'VCV_VERSION' ) ||
						defined( 'WPB_VC_VERSION' )
					) {
						// If we're not in The Loop (i.e., global $post isn't assigned),
						// temporarily populate it with the post to be inserted so we can
						// retrieve generated styles for that post. Reset $post to null
						// after we're done.
						if ( is_null( $post ) ) {
							$old_post_id = null;
							$post = $inserted_page; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
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
								} elseif ( class_exists( 'VisualComposer\Helpers\AssetsEnqueue' ) ) {
									// These methods should work for Visual Composer 26.0.
									// Enqueue custom css/js stored in vcvSourceAssetsFiles postmeta.
									$vc = new \VisualComposer\Helpers\AssetsEnqueue;
									if ( method_exists( $vc, 'enqueueAssets' ) ) {
										$vc->enqueueAssets($inserted_page->ID);
									}
									// Enqueue custom CSS stored in vcvSourceCssFileUrl postmeta.
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

						// Visual Composer custom CSS.
						if ( defined( 'WPB_VC_VERSION' ) ) {
							// Post custom CSS.
							$post_custom_css = get_post_meta( $inserted_page->ID, '_wpb_post_custom_css', true );
							if ( ! empty( $post_custom_css ) ) {
								$post_custom_css = wp_strip_all_tags( $post_custom_css );
								echo '<style type="text/css" data-type="vc_custom-css">';
								echo $post_custom_css;
								echo '</style>';
							}
							// Shortcodes custom CSS.
							$shortcodes_custom_css = get_post_meta( $inserted_page->ID, '_wpb_shortcodes_custom_css', true );
							if ( ! empty( $shortcodes_custom_css ) ) {
								$shortcodes_custom_css = wp_strip_all_tags( $shortcodes_custom_css );
								echo '<style type="text/css" data-type="vc_shortcodes-custom-css">';
								echo $shortcodes_custom_css;
								echo '</style>';
							}
						}

						if ( is_null( $old_post_id ) ) {
							$post = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
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
							// Render any <!--nextpage--> pagination links.
							wp_link_pages( array(
								'before' => '<div class="page-links">' . __( 'Pages:', 'twentynineteen' ),
								'after'  => '</div>',
							) );
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
							$this->the_meta();
							// Render any <!--nextpage--> pagination links.
							wp_link_pages( array(
								'before' => '<div class="page-links">' . __( 'Pages:', 'twentynineteen' ),
								'after'  => '</div>',
							) );
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
				// Restore previous query and update the global template variables.
				$GLOBALS['wp_query'] = $old_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				wp_reset_postdata();
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
			 *   id: Optional ID for the inserted page wrapper element.
			 *   inline: Boolean indicating wrapper element should be a span.
			 *   public: Boolean indicating anonymous users can see private inserted pages.
			 *   querystring: Extra querystring values provided to the custom template.
			 *   should_apply_the_content_filter: Whether to apply the_content filter to post contents and excerpts.
			 *   wrapper_tag: Tag to use for the wrapper element (e.g., div, span).
			 */
			$content = apply_filters( 'insert_pages_wrap_content', $content, $inserted_page, $attributes );

			// Unset any querystring params included in the shortcode.
			$_GET = $original_get;
			$_REQUEST = $original_request;
			$GLOBALS['wp']->query_vars = $original_wp_query_vars;

			// Loop detection: remove the page from the stack (so we can still insert
			// the same page multiple times on another page, but prevent it from being
			// inserted multiple times within the same recursive chain).
			if ( isset( $inserted_page->ID ) ) {
				foreach ( $this->inserted_page_ids as $key => $page_id ) {
					if ( $page_id === $inserted_page->ID ) {
						unset( $this->inserted_page_ids[ $key ] );
					}
				}
			} elseif ( is_array( $inserted_page ) && ! empty( $inserted_page ) ) {
				// Legacy template code populates $inserted_page with query_posts()
				// output. Remove each from the stack (should just be a single page).
				foreach ( $inserted_page as $page ) {
					foreach ( $this->inserted_page_ids as $key => $page_id ) {
						if ( $page_id === $page->ID ) {
							unset( $this->inserted_page_ids[ $key ] );
						}
					}
				}
			}

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
			$maybe_id = isset( $attributes['id'] ) && strlen( $attributes['id'] ) > 0 ? ' id="' . esc_attr( $attributes['id'] ) . '"' : '';
			return "<{$attributes['wrapper_tag']} data-post-id='{$attributes['page']}' class='insert-page insert-page-{$attributes['page']} {$attributes['class']}'$maybe_id>{$content}</{$attributes['wrapper_tag']}>";
		}

		/**
		 * Filter hook: Add a button to the TinyMCE toolbar for our insert page tool.
		 *
		 * @param  array $buttons TinyMCE buttons.
		 * @return array          TinyMCE buttons with Insert Pages button.
		 */
		public function insert_pages_handle_filter_mce_buttons( $buttons ) {
			if ( self::$is_admin_initialized && ! in_array( 'wpInsertPages_button', $buttons, true ) ) {
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
			if ( self::$is_admin_initialized && ! array_key_exists( 'wpInsertPages', $plugins ) ) {
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

				// Look for a <!--more--> quicktag and trim the excerpt there if it exists.
				$has_more_quicktag = false;
				if ( preg_match( '/<!--more(.*?)?-->/', $text, $matches ) ) {
					$has_more_quicktag = true;
					$text = explode( $matches[0], $text, 2 );
					$text = $text[0];
					// Look for a custom <!--crop--> quicktag that will trim any text before
					// it out of the excerpt.
					if ( preg_match( '/<!--crop-->/', $text, $matches ) ) {
						$text = explode( $matches[0], $text, 2 );
						$text = $text[1];
					}
				}

				/** This filter is documented in wp-includes/post-template.php */
				if ( $apply_the_content_filter ) {
					$text = apply_filters( 'the_content', $text );
				}
				$text = str_replace( ']]>', ']]&gt;', $text );

				// Only trim excerpt if there wasn't an existing <!--more--> quicktag.
				if ( ! $has_more_quicktag ) {
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
					global $post;
					if ( isset( $post->ID ) ) {
						$old_post_id = $post->ID;
						$post->ID = $post_id;
					}
					$excerpt_more = apply_filters( 'excerpt_more', ' [&hellip;]' );
					if ( isset( $post->ID ) ) {
						$post->ID = $old_post_id;
					}

					$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );
				}
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
		 * Modified from /wp-includes/class-wp-editor.php, function
		 * wp_link_dialog().
		 *
		 * Dialog for internal linking.
		 *
		 * @since 3.1.0
		 */
		public function insert_pages_wp_tinymce_dialog() {
			// Don't run if required scripts and styles weren't enqueued.
			if ( ! self::$is_admin_initialized ) {
				return;
			}

			// Run once.
			if ( self::$link_dialog_printed ) {
				return;
			}

			self::$link_dialog_printed = true;

			$formats = array(
				'title'          => __( 'Title', 'insert-pages' ),
				'link'           => __( 'Link', 'insert-pages' ),
				'excerpt'        => __( 'Excerpt with title', 'insert-pages' ),
				'excerpt-only'   => __( 'Excerpt only (no title)', 'insert-pages' ),
				'content'        => __( 'Content', 'insert-pages' ),
				'post-thumbnail' => __( 'Post Thumbnail', 'insert-pages' ),
				'all'            => __( 'All (includes custom fields)', 'insert-pages' ),
				'template'       => __( 'Use a custom template', 'insert-pages' ) . ' &raquo;',
			);

			$templates = array(
				'all' => __( 'Default Template', 'insert-pages' ),
			);
			foreach ( wp_get_theme()->get_page_templates() as $file => $name ) {
				$templates[ $file ] = $name;
			}

			/**
			 * Filter the available templates shown in the template dropdown.
			 *
			 * @param array $templates Array of template names keyed by their filename.
			 */
			$templates = apply_filters( 'insert_pages_available_templates', $templates );

			// Get default values for the TinyMCE dialog fields. Note: can be
			// overridden by the `insert_pages_tinymce_state` filter.
			$tinymce_state = $this->get_tinymce_state();

			// Get ID of post currently being edited.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id = isset( $_REQUEST['post'] ) && intval( $_REQUEST['post'] ) > 0 ? intval( $_REQUEST['post'] ) : '';

			// display: none is required here, see #WP27605.
			?>
			<div id="wp-insertpage-backdrop" style="display: none"></div>
			<div id="wp-insertpage-wrap" class="wp-core-ui<?php echo 1 === intval( get_user_setting( 'wpinsertpage', 0 ) ) ? ' options-panel-visible' : ''; ?><?php echo empty( $tinymce_state['hide_querystring'] ) ? '' : ' querystring-hidden'; ?><?php echo empty( $tinymce_state['hide_public'] ) ? '' : ' public-hidden'; ?>" style="display: none;" role="dialog" aria-labelledby="insertpage-modal-title">
			<form id="wp-insertpage" tabindex="-1">
			<?php wp_nonce_field( 'internal-inserting', '_ajax_inserting_nonce', false ); ?>
			<input type="hidden" id="insertpage-parent-page-id" value="<?php echo esc_attr( $post_id ); ?>" />
			<h1 id="insertpage-modal-title"><?php _e( 'Insert page', 'insert-pages' ); ?></h1>
			<button type="button" id="wp-insertpage-close"><span class="screen-reader-text"><?php _e( 'Close' ); ?></span></button>
			<div id="insertpage-selector">
				<div id="insertpage-search-panel">
					<div class="insertpage-search-wrapper">
						<label>
							<span class="search-label"><?php _e( 'Search', 'insert-pages' ); ?></span>
							<input type="search" id="insertpage-search-field" class="insertpage-search-field" autocomplete="off" />
							<span class="spinner"></span>
						</label>
					</div>
					<div id="insertpage-search-results" class="query-results" tabindex="0">
						<ul></ul>
						<div class="river-waiting">
							<span class="spinner"></span>
						</div>
					</div>
					<div id="insertpage-most-recent-results" class="query-results" tabindex="0">
						<div class="query-notice" id="insertpage-query-notice-message">
							<em class="query-notice-default"><?php _e( 'No search term specified. Showing recent items.', 'insert-pages' ); ?></em>
							<em class="query-notice-hint screen-reader-text"><?php _e( 'Search or use up and down arrow keys to select an item.' ); ?></em>
						</div>
						<ul></ul>
						<div class="river-waiting">
							<span class="spinner"></span>
						</div>
					</div>
				</div>
				<p class="howto" id="insertpage-options-toggle"><?php _e( 'Options', 'insert-pages' ); ?></p>
				<div id="insertpage-options-panel">
					<div class="insertpage-options-wrapper">
						<label for="insertpage-slug-field">
							<span><?php _e( 'Slug or ID', 'insert-pages' ); ?></span>
							<input id="insertpage-slug-field" type="text" autocomplete="off" />
							<input id="insertpage-page-id" type="hidden" />
						</label>
					</div>
					<div class="insertpage-format">
						<label for="insertpage-format-select">
							<?php _e( 'Display', 'insert-pages' ); ?>
						</label>
						<select name="insertpage-format-select" id="insertpage-format-select">
							<?php foreach ( $formats as $format => $label ) : ?>
								<option value='<?php echo esc_attr( $format ); ?>' <?php selected( $tinymce_state['format'], $format ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<select name="insertpage-template-select" id="insertpage-template-select" disabled="true">
							<?php foreach ( $templates as $template => $label ) : ?>
								<option value='<?php echo esc_attr( $template ); ?>' <?php selected( $tinymce_state['template'], $template ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="insertpage-extra">
						<label for="insertpage-extra-classes">
							<?php _e( 'Extra Classes', 'insert-pages' ); ?>
							<input id="insertpage-extra-classes" type="text" autocomplete="off" value="<?php echo empty( $tinymce_state['class'] ) ? '' : esc_attr( $tinymce_state['class'] ); ?>" />
						</label>
						<label for="insertpage-extra-id">
							<?php _e( 'ID', 'insert-pages' ); ?>
							<input id="insertpage-extra-id" type="text" autocomplete="off" value="<?php echo empty( $tinymce_state['id'] ) ? '' : esc_attr( $tinymce_state['id'] ); ?>" />
						</label>
						<label for="insertpage-extra-inline">
							<?php _e( 'Inline?', 'insert-pages' ); ?>
							<input id="insertpage-extra-inline" type="checkbox" <?php checked( $tinymce_state['inline'] ); ?> />
						</label>
						<br class="<?php echo empty( $tinymce_state['hide_querystring'] ) ? '' : 'hidden'; ?>" />
						<label for="insertpage-extra-querystring" class="<?php echo empty( $tinymce_state['hide_querystring'] ) ? '' : 'hidden'; ?>">
							<?php _e( 'Querystring', 'insert-pages' ); ?>
							<input id="insertpage-extra-querystring" type="text" autocomplete="off" value="<?php echo empty( $tinymce_state['querystring'] ) ? '' : esc_attr( $tinymce_state['querystring'] ); ?>" />
						</label>
						<br class="<?php echo empty( $tinymce_state['hide_public'] ) ? '' : 'hidden'; ?>" />
						<label for="insertpage-extra-public" class="<?php echo empty( $tinymce_state['hide_public'] ) ? '' : 'hidden'; ?>">
							<input id="insertpage-extra-public" type="checkbox" <?php checked( $tinymce_state['public'] ); ?> />
							<?php _e( 'Anonymous users can see this inserted even if its status is private', 'insert-pages' ); ?>
						</label>
					</div>
				</div>
			</div>
			<div class="submitbox">
				<div id="wp-insertpage-cancel">
					<button type="button" class="button"><?php _e( 'Cancel', 'insert-pages' ); ?></button>
				</div>
				<div id="wp-insertpage-update">
					<input type="submit" value="<?php esc_attr_e( 'Insert Page', 'insert-pages' ); ?>" class="button button-primary" id="wp-insertpage-submit" name="wp-insertpage-submit">
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
				$args['s'] = wp_unslash( $_POST['search'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
			$args['pagenum'] = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
			$args['pageID'] = ! empty( $_POST['pageID'] ) ? absint( $_POST['pageID'] ) : 0;

			// Change search to slug or post ID if we're not doing a plaintext
			// search (e.g., if we're editing an existing shortcode and the
			// search field is populated with the post's slug or ID).
			if ( isset( $_POST['type'] ) && 'slug' === $_POST['type'] ) {
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
		 * Save the user's last-selected display or template in the TinyMCE widget
		 * whenever it changes.
		 *
		 * @hook wp_ajax_insertpage_save_presets
		 */
		public function insert_pages_save_presets() {
			check_ajax_referer( 'internal-inserting', '_ajax_inserting_nonce' );
			$args = array();
			if ( isset( $_POST['format'] ) ) {
				$args['format'] = sanitize_key( wp_unslash( $_POST['format'] ) );
			}
			if ( isset( $_POST['template'] ) ) {
				$args['template'] = sanitize_file_name( wp_unslash( $_POST['template'] ) );
			}

			if ( ! empty( $args ) ) {
				$tinymce_state = get_user_meta( get_current_user_id(), 'insert_pages_tinymce_state', true );
				if ( empty( $tinymce_state ) ) {
					$tinymce_state = array(
						'format'   => 'title',
						'template' => 'all',
					);
				}
				$tinymce_state = array_merge( $tinymce_state, $args );
				update_user_meta( get_current_user_id(), 'insert_pages_tinymce_state', $tinymce_state );
			}

			// Fail if our query didn't work.
			if ( ! isset( $results ) ) {
				die( '0' );
			}

			echo wp_json_encode( 'Success' );
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
				// 'post__not_in' => array( $args['pageID'] ), // Remove?
			);

			// Show non-admins only their own posts if the option is enabled.
			$options = get_option( 'wpip_settings' );
			if (
				! empty( $options['wpip_classic_editor_hide_others_posts'] ) &&
				'enabled' === $options['wpip_classic_editor_hide_others_posts'] &&
				! current_user_can( 'edit_others_posts' )
			) {
				$query['author'] = get_current_user_id();
			}

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
				// Prevent unprivileged users (e.g., Contributors) from seeing and
				// inserting other user's private posts.
				$post_type = get_post_type_object( $post->post_type );
				if ( 'publish' !== $post->post_status && ! current_user_can( $post_type->cap->read_post, $post->ID ) ) {
					continue;
				}

				if ( 'post' === $post->post_type ) {
					$info = mysql2date( 'Y/m/d', $post->post_date );
				} else {
					$info = $pts[ $post->post_type ]->labels->singular_name;
				}
				$results[] = array(
					'ID' => $post->ID,
					'title' => trim( esc_html( wp_strip_all_tags( get_the_title( $post ) ) ) ),
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
			return ! in_array(
				$type,
				array(
					'nav_menu_item',
					'attachment',
					'revision',
					'customize_changeset',
					'oembed_cache',
					// Exclude Flamingo messages (created via Contact Form 7 submissions).
					// See: https://wordpress.org/support/topic/plugin-hacked-14/
					'flamingo_inbound',
				),
				true
			);
		}

		/**
		 * Fetch the default values for the TinyMCE modal fields.
		 */
		private function get_tinymce_state() {
			// Get user's previously selected display and template to restore (if any).
			$tinymce_state = get_user_meta( get_current_user_id(), 'insert_pages_tinymce_state', true );
			if ( empty( $tinymce_state ) ) {
				$tinymce_state = array();
			}

			// Merge user's format and template defaults with global defaults.
			$tinymce_state = wp_parse_args(
				$tinymce_state,
				array(
					'format'           => 'title',
					'template'         => 'all',
					'class'            => '',
					'id'               => '',
					'querystring'      => '',
					'inline'           => false,
					'public'           => false,
					'hide_querystring' => false,
					'hide_public'      => false,
				)
			);

			/**
			 * Filter the TinyMCE dialog field defaults.
			 *
			 * @param array $tinymce_state Array of field defaults for the TinyMCE modal.
			 *  'format'           (string) Display format. Default 'title'.
			 *  'template'         (string) Custom template. Default 'all'.
			 *  'class'            (string) HTML wrapper class. Default ''.
			 *  'id'               (string) HTML wrapper id. Default ''.
			 *  'querystring'      (string) Querystring params. Default ''.
			 *  'inline'           (bool)   Use <span> element for wrapper. Default false.
			 *  'public'           (bool)   Whether anonymous users can see this page if
			 *                              its status is Private. Default false.
			 *  'hide_querystring' (bool)   Skip rendering querystring field. Default false.
			 *  'hide_public'      (bool)   Skip rendering public field. Default false.
			 */
			$tinymce_state = apply_filters( 'insert_pages_tinymce_state', $tinymce_state );

			return $tinymce_state;
		}

		/**
		 * Registers the theme widget for inserting a page into an area.
		 *
		 * @return void
		 */
		public function insert_pages_widgets_init() {
			register_widget( 'InsertPagesWidget' );
		}

		/**
		 * Render post meta as an unordered list.
		 *
		 * Note: This function sanitizes postmeta value via wp_kses_post(); the
 		 * core WordPress function the_meta() does not.
		 *
		 * @see https://developer.wordpress.org/reference/functions/the_meta/
		 *
		 * @param  int $post_id Post ID.
		 */
		public function the_meta( $post_id = 0 ) {
			if ( empty( $post_id ) ) {
				$post_id = get_the_ID();
			}

			$keys = get_post_custom_keys( $post_id );
			if ( $keys ) {
				$li_html = '';
				foreach ( (array) $keys as $key ) {
					$keyt = trim( $key );
					if ( is_protected_meta( $keyt, 'post' ) ) {
						continue;
					}

					$values = array_map( 'trim', get_post_custom_values( $key, $post_id ) );
					$value  = implode( ', ', $values );

					// Sanitize post meta values.
					$value = wp_kses_post( $value );

					$html = sprintf(
						"<li><span class='post-meta-key'>%s</span> %s</li>\n",
						/* translators: %s: Post custom field name. */
						sprintf( _x( '%s:', 'Post custom field name' ), $key ),
						$value
					);

					/**
					 * Filters the HTML output of the li element in the post custom fields list.
					 *
					 * @since 2.2.0
					 *
					 * @param string $html  The HTML output for the li element.
					 * @param string $key   Meta key.
					 * @param string $value Meta value.
					 */
					$li_html .= apply_filters( 'the_meta_key', $html, $key, $value );
				}

				if ( $li_html ) {
					echo "<ul class='post-meta'>\n{$li_html}</ul>\n";
				}
			}
		}

	}
}

// Initialize InsertPagesPlugin object.
if ( class_exists( 'InsertPagesPlugin' ) ) {
	$insert_pages_plugin = InsertPagesPlugin::get_instance();
}

// Actions and Filters handled by InsertPagesPlugin class.
if ( isset( $insert_pages_plugin ) ) {
	// Include the code that generates the options page.
	require_once dirname( __FILE__ ) . '/options.php';

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

	// Ajax: save user's last selected display and template inputs.
	add_action( 'wp_ajax_insertpage_save_presets', array( $insert_pages_plugin, 'insert_pages_save_presets' ) );

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
