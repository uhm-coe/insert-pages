<?php

/*
Plugin Name: Insert Pages
Plugin URI: https://bitbucket.org/figureone/insert-pages
Description: Insert Pages lets you embed any WordPress content (e.g., pages, posts, custom post types) into other WordPress content using the Shortcode API.
Author: Paul Ryan
Version: 1.7
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
	[insert page='{slug}|{id}' display='title|link|excerpt|content|all|{custom-template.php}']
*/

// Define the InsertPagesPlugin class (variables and functions)
if (!class_exists('InsertPagesPlugin')) {
	class InsertPagesPlugin {
		// Save the id of the page being edited
		protected $pageID;

		// Constructor
		public function InsertPagesPlugin() {
			//$this->pageID = '1'; echo $_GET['post'];
		}

		// Getter/Setter for pageID
		function getPageID() { return $this->pageID; }
		function setPageID($id) { return $this->pageID = $id; }

		// Action hook: Wordpress 'init'
		function insertPages_init() {
			add_shortcode('insert', array($this, 'insertPages_handleShortcode_insert'));
		}

		// Action hook: Wordpress 'admin_init'
		function insertPages_admin_init() {
			// Add TinyMCE toolbar button filters only if current user has permissions
			if (current_user_can('edit_posts') && current_user_can('edit_pages') && get_user_option('rich_editing')=='true') {

				// Register the TinyMCE toolbar button script
				wp_enqueue_script(
					'wpinsertpages',
					plugins_url( '/assets/js/wpinsertpages.js', __FILE__ ),
					array( 'wpdialogs' ),
					'20140819'
				);
				wp_localize_script( 'wpinsertpages', 'wpInsertPagesL10n', array(
					'update' => __('Update'),
					'save' => __('Insert Page'),
					'noTitle' => __('(no title)'),
					'noMatchesFound' => __('No matches found.'),
					'l10n_print_after' => 'try{convertEntities(wpLinkL10n);}catch(e){};',
				));

				// Register the TinyMCE toolbar button styles
				wp_enqueue_style(
					'wpinsertpagescss',
					plugins_url( '/assets/css/wpinsertpages.css', __FILE__ ),
					array( 'wp-jquery-ui-dialog' ),
					'20140819'
				);

				add_filter( 'mce_external_plugins', array( $this, 'insertPages_handleFilter_mceExternalPlugins' ) );
				add_filter( 'mce_buttons', array( $this, 'insertPages_handleFilter_mceButtons' ) );

				//load_plugin_textdomain('insert-pages', false, dirname(plugin_basename(__FILE__)).'/languages/');
			}

		}


		// Shortcode hook: Replace the [insert ...] shortcode with the inserted page's content
		function insertPages_handleShortcode_insert($atts, $content=null) {
			global $wp_query, $post;
			extract(shortcode_atts(array(
				'page' => '0',
				'display' => 'all',
			), $atts));

			// Validation checks
			if ($page==='0')
				return $content;
			//if (!preg_match('/_(title|link|excerpt|content|all|.*\.tpl\.php/)', $display, $matches))
			//  return $content;
			if ($page==$post->ID || $page==$post->post_name) // trying to embed same page in itself
				return $content;

			// Get page object from slug or id
			$temp_query = clone $wp_query; // we're starting a new loop within the main loop, so save the main query
			$temp_post = $wp_query->get_queried_object(); // see: http://codex.wordpress.org/The_Loop#Multiple_Loops_Example_2
			if (is_numeric($page)) {
				query_posts("p=".intval($page)."&post_type=any");
			} else {
				query_posts("name=".esc_attr($page)."&post_type=any");
			}

			// Start our new Loop
			while (have_posts()) {
				ob_start(); // Start output buffering so we can save the output to string

				// Show either the title, link, content, everything, or everything via a custom template
				switch ($display) {
					case "title":
						the_post(); ?>
						<h1><?php the_title(); ?></h1>
						<?php break;
					case "link":
						the_post(); ?>
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						<?php break;
					case "excerpt":
						the_post(); ?>
						<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
						<?php the_excerpt(); ?>
						<?php break;
					case "content":
						the_post(); ?>
						<?php the_content(); ?>
						<?php break;
					case "all":
						the_post(); ?>
						<h1><?php the_title(); ?></h1>
						<?php the_content(); ?>
						<?php the_meta(); ?>
						<?php break;
					default: // display is either invalid, or contains a template file to use
						$template = locate_template( $display );
						if ( strlen( $template ) > 0 ) {
							include( $template ); // execute the template code
						}
						break;
				}

				$content = ob_get_contents(); // Save off output buffer
				ob_end_clean(); // End output buffering
			}
			wp_reset_postdata();
			$wp_query = clone $temp_query; // Restore main Loop's wp_query
			$post = $temp_post;

			$content = "<div id='insertPages_Content'>$content</div>";
			return $content;
			//return do_shortcode($content); // careful: watch for infinite loops with nested inserts
		}


		// Filter hook: Add a button to the TinyMCE toolbar for our insert page tool
		function insertPages_handleFilter_mceButtons( $buttons ) {
			array_push( $buttons, 'wpInsertPages_button' ); // add a separator and button to toolbar
			return $buttons;
		}

		// Filter hook: Load the javascript for our custom toolbar button
		function insertPages_handleFilter_mceExternalPlugins( $plugins ) {
			$plugins['wpInsertPages'] = plugins_url( '/assets/js/wpinsertpages_plugin.js', __FILE__ );
			return $plugins;
		}

		/**
		 * Modified from /wp-admin/includes/internal-linking.php, function wp_link_dialog()
		 * Dialog for internal linking.
		 * @since 3.1.0
		 */
		function insertPages_wp_tinymce_dialog() {
			$options_panel_visible = '1' == get_user_setting( 'wplink', '0' ) ? ' options-panel-visible' : '';

			// display: none is required here, see #WP27605
			?>
			<div id="wp-insertpage-backdrop" style="display: none"></div>
			<div id="wp-insertpage-wrap" class="wp-core-ui<?php echo $options_panel_visible; ?>" style="display: none">
			<form id="wp-insertpage" tabindex="-1">
			<?php wp_nonce_field( 'internal-inserting', '_ajax_inserting_nonce', false ); ?>
			<input type="hidden" id="insertpage-parent-pageID" value="<?php echo $_GET['post'] ?>" />
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
							<input id="insertpage-slug-field" type="text" tabindex="10" autocomplete="off" />
							<input id="insertpage-pageID" type="hidden" />
						</label>
					</div>
					<div class="insertpage-format">
						<label for="insertpage-format-select">
							<?php _e( 'Display' ); ?>
							<select name="insertpage-format-select" id="insertpage-format-select">
								<option value='title'>Title</option>
								<option value='link'>Link</option>
								<option value='excerpt'>Excerpt</option>
								<option value='content'>Content</option>
								<option value='all'>All (includes custom fields)</option>
								<option value='template'>Use a custom template &raquo;</option>
							</select>
							<select name="insertpage-template-select" id="insertpage-template-select" disabled="true">
								<option value='all'><?php _e('Default Template'); ?></option>
								<?php page_template_dropdown(); ?>
							</select>
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
		 * @package WordPress
		 * @subpackage Administration
		 * @since 3.1.0
		 */
		function insertPages_insert_page_callback() {
			check_ajax_referer( 'internal-inserting', '_ajax_inserting_nonce' );
			$args = array();
			if ( isset( $_POST['search'] ) )
				$args['s'] = stripslashes( $_POST['search'] );
			$args['pagenum'] = !empty($_POST['page']) ? absint($_POST['page']) : 1;
			$args['pageID'] =  !empty($_POST['pageID']) ? absint($_POST['pageID']) : 0;

			$results = $this->insertPages_wp_query( $args );

			if (!isset($results))
				die('0');
			echo json_encode($results);
			echo "\n";
			die();
		}
		/** Modified from:
		 * Performs post queries for internal linking.
		 * @since 3.1.0
		 * @param array $args Optional. Accepts 'pagenum' and 's' (search) arguments.
		 * @return array Results.
		 */
		function insertPages_wp_query( $args = array() ) {
			$pts = get_post_types( array( 'public' => true ), 'objects' );
			$pt_names = array_keys( $pts );

			$query = array(
				'post_type' => $pt_names,
				'suppress_filters' => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'post_status' => 'publish',
				'order' => 'DESC',
				'orderby' => 'post_date',
				'posts_per_page' => 20,
				'post__not_in' => array($args['pageID']),
			);

			$args['pagenum'] = isset( $args['pagenum'] ) ? absint( $args['pagenum'] ) : 1;

			if ( isset( $args['s'] ) )
				$query['s'] = $args['s'];

			$query['offset'] = $args['pagenum'] > 1 ? $query['posts_per_page'] * ( $args['pagenum'] - 1 ) : 0;

			// Do main query.
			$get_posts = new WP_Query;
			$posts = $get_posts->query( $query );
			// Check if any posts were found.
			if ( ! $get_posts->post_count )
				return false;

			// Build results.
			$results = array();
			foreach ( $posts as $post ) {
				if ( 'post' == $post->post_type )
					$info = mysql2date( __( 'Y/m/d' ), $post->post_date );
				else
					$info = $pts[ $post->post_type ]->labels->singular_name;

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

	}
}

// Initialize InsertPagesPlugin object
if (class_exists('InsertPagesPlugin')) {
	$insertPages_plugin = new InsertPagesPlugin();
}

// Actions and Filters handled by InsertPagesPlugin class
if (isset($insertPages_plugin)) {
	// Actions
	add_action('init', array($insertPages_plugin, 'insertPages_init'), 1); // Register Shortcodes here
	add_action('admin_head', array($insertPages_plugin, 'insertPages_admin_init'), 1); // Add TinyMCE buttons here
	add_action('before_wp_tiny_mce', array($insertPages_plugin, 'insertPages_wp_tinymce_dialog'), 1); // Preload TinyMCE popup
	add_action('wp_ajax_insertpage', array($insertPages_plugin, 'insertPages_insert_page_callback')); // Populate page search in TinyMCE button popup in this ajax call
}

