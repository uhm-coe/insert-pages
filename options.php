<?php
/**
 * Render the Insert Pages Settings page.
 *
 * @package insert-pages
 */

/**
 * Add 'Insert Pages' to the Settings menu.
 *
 * @return void
 */
function wpip_add_admin_menu() {
	add_options_page( 'Insert Pages', 'Insert Pages', 'manage_options', 'insert_pages', 'wpip_options_page' );
}
add_action( 'admin_menu', 'wpip_add_admin_menu' );

/**
 * Register settings fields.
 *
 * @return void
 */
function wpip_settings_init() {
	register_setting( 'wpipSettings', 'wpip_settings' );
	add_settings_section(
		'wpip_section',
		__( 'Insert Pages', 'insert-pages' ),
		'wpip_settings_section_callback',
		'wpipSettings'
	);
	add_settings_field(
		'wpip_format',
		__( 'Shortcode format', 'insert-pages' ),
		'wpip_format_render',
		'wpipSettings',
		'wpip_section'
	);
	add_settings_field(
		'wpip_wrapper',
		__( 'Wrapper for inserts', 'insert-pages' ),
		'wpip_wrapper_render',
		'wpipSettings',
		'wpip_section'
	);
	add_settings_field(
		'wpip_insert_method',
		__( 'Insert method', 'insert-pages' ),
		'wpip_insert_method_render',
		'wpipSettings',
		'wpip_section'
	);
	add_settings_field(
		'wpip_tinymce_filter',
		__( 'TinyMCE filter', 'insert-pages' ),
		'wpip_tinymce_filter_render',
		'wpipSettings',
		'wpip_section'
	);
	add_settings_field(
		'wpip_gutenberg_block',
		__( 'Gutenberg block', 'insert-pages' ),
		'wpip_gutenberg_block_render',
		'wpipSettings',
		'wpip_section'
	);
	add_settings_field(
		'wpip_classic_editor_hide_others_posts',
		__( 'TinyMCE capabilities', 'insert-pages' ),
		'wpip_classic_editor_hide_others_posts_render',
		'wpipSettings',
		'wpip_section'
	);
	add_settings_field(
		'wpip_public_post_statuses',
		__( 'Allow anyone to see inserted pages with these statuses', 'insert-pages' ),
		'wpip_public_post_statuses_render',
		'wpipSettings',
		'wpip_section'
	);
}
add_action( 'admin_init', 'wpip_settings_init' );

/**
 * Set meaningful defaults for settings.
 *
 * @return array Insert Pages settings.
 */
function wpip_set_defaults() {
	$options = get_option( 'wpip_settings' );
	if ( false === $options ) {
		$options = array();
	}

	if ( ! array_key_exists( 'wpip_format', $options ) ) {
		$options['wpip_format'] = 'slug';
	}

	if ( ! array_key_exists( 'wpip_wrapper', $options ) ) {
		$options['wpip_wrapper'] = 'block';
	}

	if ( ! array_key_exists( 'wpip_insert_method', $options ) ) {
		$options['wpip_insert_method'] = 'normal';
	}

	if ( ! array_key_exists( 'wpip_tinymce_filter', $options ) ) {
		$options['wpip_tinymce_filter'] = 'normal';
	}

	if ( ! array_key_exists( 'wpip_gutenberg_block', $options ) ) {
		$options['wpip_gutenberg_block'] = 'enabled';
	}

	if ( empty( $options['wpip_classic_editor_hide_others_posts'] ) ) {
		$options['wpip_classic_editor_hide_others_posts'] = 'disabled';
	}

	if ( empty( $options['wpip_public_post_statuses'] ) || ! is_array( $options['wpip_public_post_statuses'] ) ) {
		$options['wpip_public_post_statuses'] = array();
	}

	update_option( 'wpip_settings', $options );

	return $options;
}
register_activation_hook( __FILE__, 'wpip_set_defaults' );

/**
 * Print heading for Insert Pages settings page.
 *
 * @return void
 */
function wpip_settings_section_callback() {
	esc_html_e( 'You may override some default settings here.', 'insert-pages' );
}

/**
 * Print Insert Pages settings page.
 *
 * @return void
 */
function wpip_options_page() {
	?>
	<form action='options.php' method='post'>
		<?php
		settings_fields( 'wpipSettings' );
		do_settings_sections( 'wpipSettings' );
		submit_button();
		?>
	</form>
	<?php
}

/**
 * Print 'Format' setting.
 *
 * @return void
 */
function wpip_format_render() {
	$options = get_option( 'wpip_settings' );
	if ( false === $options || ! is_array( $options ) || ! array_key_exists( 'wpip_format', $options ) ) {
		$options = wpip_set_defaults();
	}
	?>
	<input type='radio' name='wpip_settings[wpip_format]' <?php checked( $options['wpip_format'], 'slug' ); ?> id="wpip_format_slug" value='slug'><label for="wpip_format_slug">Use page slugs (more readable). Example: <code>[insert&nbsp;page='hello&#8209;world&#8209;post'&nbsp;display='all']</code></label><br />
	<input type='radio' name='wpip_settings[wpip_format]' <?php checked( $options['wpip_format'], 'post_id' ); ?> id="wpip_format_id" value='post_id'><label for="wpip_format_id">Use page IDs (more compatible). Example: <code>[insert&nbsp;page='1'&nbsp;display='all']</code></label><br />
	<small><em>If your site reuses page slugs (for example, WPML sites often use the same page slug for each translation of the page in a different language), you should use page IDs.</em></small>
	<?php
}

/**
 * Print 'Wrapper' setting.
 *
 * @return void
 */
function wpip_wrapper_render() {
	$options = get_option( 'wpip_settings' );
	if ( false === $options || ! is_array( $options ) || ! array_key_exists( 'wpip_wrapper', $options ) ) {
		$options = wpip_set_defaults();
	}
	?>
	<input type='radio' name='wpip_settings[wpip_wrapper]' <?php checked( $options['wpip_wrapper'], 'block' ); ?> id="wpip_wrapper_block" value='block'><label for="wpip_wrapper_block">Use block wrapper (div). Example: <code>&lt;div data-post-id="1" class="insert-page">...&lt;/div></code></label><br />
	<input type='radio' name='wpip_settings[wpip_wrapper]' <?php checked( $options['wpip_wrapper'], 'inline' ); ?> id="wpip_wrapper_inline" value='inline'><label for="wpip_wrapper_inline">Use inline wrapper (span). Example: <code>&lt;span data-post-id="1" class="insert-page">...&lt;/span></code></label><br />
	<small><em>If you want to embed pages inline (for example, you can insert a link to a page in the flow of a normal paragraph), you should use inline tags. Note that the HTML spec does not allow block level elements within inline elements, so the inline wrapper has limited use.</em></small>
	<?php
}

/**
 * Print 'Insert Method' setting.
 *
 * @return void
 */
function wpip_insert_method_render() {
	$options = get_option( 'wpip_settings' );
	if ( false === $options || ! is_array( $options ) || ! array_key_exists( 'wpip_insert_method', $options ) ) {
		$options = wpip_set_defaults();
	}
	?>
	<input type='radio' name='wpip_settings[wpip_insert_method]' <?php checked( $options['wpip_insert_method'], 'legacy' ); ?> id="wpip_insert_method_legacy" value='legacy'><label for="wpip_insert_method_legacy">Use legacy method (compatible with <a href="https://wordpress.org/plugins/beaver-builder-lite-version/" target="_blank">Beaver Builder</a> and <a href="https://wordpress.org/plugins/siteorigin-panels/" target="_blank">Page Builder by SiteOrigin</a>, but less efficient). </label><br />
	<input type='radio' name='wpip_settings[wpip_insert_method]' <?php checked( $options['wpip_insert_method'], 'normal' ); ?> id="wpip_insert_method_normal" value='normal'><label for="wpip_insert_method_normal">Use normal method (more compatible with other plugins, and more efficient). Compatible with Gutenberg.</label><br />
	<small><em>The legacy method uses <a href="https://codex.wordpress.org/Function_Reference/query_posts" target="_blank">query_posts()</a>, which the Codex cautions against using. However, to recreate the exact state that many page builder plugins are expecting, the Main Loop has to be replaced with the inserted page while it is being rendered. The normal method, on the other hand, just uses <a href="https://developer.wordpress.org/reference/functions/get_post/" target="_blank">get_post()</a>.</em></small>
	<?php
}

/**
 * Print 'TinyMCE Filter' setting.
 *
 * @return void
 */
function wpip_tinymce_filter_render() {
	$options = get_option( 'wpip_settings' );
	if ( false === $options || ! is_array( $options ) || ! array_key_exists( 'wpip_tinymce_filter', $options ) ) {
		$options = wpip_set_defaults();
	}
	?>
	<input type='radio' name='wpip_settings[wpip_tinymce_filter]' <?php checked( $options['wpip_tinymce_filter'], 'normal' ); ?> id="wpip_tinymce_filter_normal" value='normal'><label for="wpip_tinymce_filter_normal">Use normal method (compatible with Divi theme and most situations). </label><br />
	<input type='radio' name='wpip_settings[wpip_tinymce_filter]' <?php checked( $options['wpip_tinymce_filter'], 'compatibility' ); ?> id="wpip_tinymce_filter_compatibility" value='compatibility'><label for="wpip_tinymce_filter_compatibility">Use compatibility method (works with <a href="https://wordpress.org/plugins/siteorigin-panels/" target="_blank">Page Builder by SiteOrigin</a>).</label><br />
	<small><em>The normal method adds the TinyMCE plugin filters in the <a href="https://developer.wordpress.org/reference/hooks/admin_head/" target="_blank">admin_head</a> hook. For users using SiteOrigin PageBuilder with the so-widgets-bundle enabled and using Contact Form, Editor, Google Maps, Hero Image, or Testimonials widgets, a bug in that plugin prevents other plugins from registering TinyMCE plugins. Use compatibility mode here to use a workaround.</em></small>
	<?php
}

/**
 * Print 'Gutenberg block' setting.
 *
 * @return void
 */
function wpip_gutenberg_block_render() {
	$options = get_option( 'wpip_settings' );
	if ( false === $options || ! is_array( $options ) || ! array_key_exists( 'wpip_gutenberg_block', $options ) ) {
		$options = wpip_set_defaults();
	}
	?>
	<input type='radio' name='wpip_settings[wpip_gutenberg_block]' <?php checked( $options['wpip_gutenberg_block'], 'enabled' ); ?> id="wpip_gutenberg_block_enabled" value='enabled'><label for="wpip_gutenberg_block_enabled">Enable Insert Pages Gutenberg block.</label><br />
	<input type='radio' name='wpip_settings[wpip_gutenberg_block]' <?php checked( $options['wpip_gutenberg_block'], 'disabled' ); ?> id="wpip_gutenberg_block_disabled" value='disabled'><label for="wpip_gutenberg_block_disabled">Disable Insert Pages Gutenberg block.</label>
	<?php
}

/**
 * Print 'TinyMCE capabilities' setting.
 *
 * @return void
 */
function wpip_classic_editor_hide_others_posts_render() {
	$options = get_option( 'wpip_settings' );
	if ( false === $options || ! is_array( $options ) || empty( $options['wpip_classic_editor_hide_others_posts'] ) ) {
		$options = wpip_set_defaults();
	}
	?>
	<input type='radio' name='wpip_settings[wpip_classic_editor_hide_others_posts]' <?php checked( $options['wpip_classic_editor_hide_others_posts'], 'enabled' ); ?> id="wpip_classic_editor_hide_others_posts_enabled" value='enabled'><label for="wpip_classic_editor_hide_others_posts_enabled">Authors and Contributors only see their own content to insert.</label><br />
	<input type='radio' name='wpip_settings[wpip_classic_editor_hide_others_posts]' <?php checked( $options['wpip_classic_editor_hide_others_posts'], 'disabled' ); ?> id="wpip_classic_editor_hide_others_posts_disabled" value='disabled'><label for="wpip_classic_editor_hide_others_posts_disabled">Authors and Contributors see all published content to insert.</label><br />
	<small><em>Note: this option only restricts Contributors and Authors (i.e., the roles without the <code>edit_others_posts</code> capability) from seeing other's content in the TinyMCE Insert Page popup; they can still insert any published content if they know the page slug.</em></small>
	<?php
}

/**
 * Print 'Insertable post statuses' setting.
 *
 * @return void
 */
function wpip_public_post_statuses_render() {
	$options = get_option( 'wpip_settings' );
	if ( false === $options || ! is_array( $options ) || empty( $options['wpip_public_post_statuses'] ) || ! is_array( $options['wpip_public_post_statuses'] ) ) {
		$options = wpip_set_defaults();
	}
	?>
	<input type='checkbox' disabled checked id="wpip_public_post_statuses_publish" value='publish'><label for="wpip_public_post_statuses_publish">Published</label><br />

	<input type='checkbox' name='wpip_settings[wpip_public_post_statuses][]' <?php checked( in_array( 'private', $options['wpip_public_post_statuses'], true ) ); ?> id="wpip_public_post_statuses_private" value='private'><label for="wpip_public_post_statuses_private">Private, authored by anyone (normally only visible to site admins and editors)</label><br />
	<input type='checkbox' name='wpip_settings[wpip_public_post_statuses][]' <?php checked( in_array( 'private_self', $options['wpip_public_post_statuses'], true ) ); ?> id="wpip_public_post_statuses_private_self" value='private_self'><label for="wpip_public_post_statuses_private_self">Private, only where inserted page and parent page have the same author (normally only visible to site admins and editors)</label><br />
	<input type='checkbox' name='wpip_settings[wpip_public_post_statuses][]' <?php checked( in_array( 'draft', $options['wpip_public_post_statuses'], true ) ); ?> id="wpip_public_post_statuses_draft" value='draft'><label for="wpip_public_post_statuses_draft">Draft (not ready to publish)</label><br />
	<input type='checkbox' name='wpip_settings[wpip_public_post_statuses][]' <?php checked( in_array( 'pending', $options['wpip_public_post_statuses'], true ) ); ?> id="wpip_public_post_statuses_pending" value='pending'><label for="wpip_public_post_statuses_pending">Pending (waiting for review before publishing)</label><br />
	<input type='checkbox' name='wpip_settings[wpip_public_post_statuses][]' <?php checked( in_array( 'future', $options['wpip_public_post_statuses'], true ) ); ?> id="wpip_public_post_statuses_future" value='future'><label for="wpip_public_post_statuses_future">Scheduled (scheduled to be published on a future date)</label><br />
	<input type='checkbox' name='wpip_settings[wpip_public_post_statuses][]' <?php checked( in_array( 'has_password', $options['wpip_public_post_statuses'], true ) ); ?> id="wpip_public_post_statuses_has_password" value='has_password'><label for="wpip_public_post_statuses_has_password">Password-protected (normally only visible to those who know the password)</label><br />
	<small><em>Note: there are security considerations when making these statuses publicly viewable, such as unintentional information disclosure when inserting private pages. If enabling them, please review accordingly.</em></small>
	<?php
}
