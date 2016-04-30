<?php

/**
 * Class: InsertPagesWidget extends WP_Widget
 * Provides a widget for inserting a page into a widget area.
 */

class InsertPagesWidget extends WP_Widget {

	/**
	 * Set up the widget.
	 */
	public function __construct() {
		// Load admin javascript for Widget options on admin page (widgets.php).
		add_action( 'sidebar_admin_page', array( $this, 'widget_admin_js' ) );

		// Load admin javascript for Widget options on theme customize page (customize.php)
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'widget_admin_js' ) );

		// Call parent constructor to initialize the widget.
		parent::__construct( 'ipw', 'Insert Page', array( 'description' => 'Insert a page into a widget area.' ) );
	}

	/**
	 * Load javascript for interacting with the Insert Page widget.
	 */
	function widget_admin_js() {
		wp_enqueue_script( 'insertpages_widget', plugins_url( '/js/widget.js', __FILE__ ), array( 'jquery' ), '20160429' );
	}

	/**
	 * Output the content of the widget.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		global $insertPages_plugin;

		// Print widget wrapper.
		echo $args['before_widget'];

		// Build the shortcode attributes array from the widget args.
		$atts = array();
		if ( array_key_exists( 'page', $instance ) ) {
			$atts['page'] = $instance['page'];
		}
		if ( array_key_exists( 'display', $instance ) ) {
			$atts['display'] = $instance['display'];
		}
		if ( array_key_exists( 'template', $instance ) && $instance['display'] === 'template' ) {
			$atts['display'] = $instance['template'];
		}
		if ( array_key_exists( 'class', $instance ) ) {
			$atts['class'] = $instance['class'];
		}
		if ( array_key_exists( 'inline', $instance ) ) {
			$atts['inline'] = $instance['inline'] === '1';
		}

		// Render the inserted page using the plugin's shortcode handler.
		$content = $insertPages_plugin->insertPages_handleShortcode_insert( $atts );

		// Print inserted page.
		echo $content;

		// Print widget wrapper.
		echo $args['after_widget'];
	}

	/**
	 * Output the options form on admin.
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array)$instance, array(
			'page' => '',
			'display' => 'link',
			'template' => '',
			'class' => '',
			'inline' => '',
		)); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'page' ); ?>"><?php _e( 'Page/Post ID or Slug' ); ?>:</label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'page' ); ?>" name="<?php echo $this->get_field_name( 'page' ); ?>" value="<?php echo $instance['page']; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'display' ); ?>"><?php _e( 'Display' ); ?>:</label><br />
			<select class="insertpage-format-select" name="<?php echo $this->get_field_name( 'display' ); ?>" id="<?php echo $this->get_field_id( 'display' ); ?>">
				<option value='title' <?php selected( $instance['display'], 'title' ); ?>>Title</option>
				<option value='link' <?php selected( $instance['display'], 'link' ); ?>>Link</option>
				<option value='excerpt' <?php selected( $instance['display'], 'excerpt' ); ?>>Excerpt</option>
				<option value='excerpt-only' <?php selected( $instance['display'], 'excerpt-only' ); ?>>Excerpt only (no title)</option>
				<option value='content' <?php selected( $instance['display'], 'content' ); ?>>Content</option>
				<option value='all' <?php selected( $instance['display'], 'all' ); ?>>All (includes custom fields)</option>
				<option value='template' <?php selected( $instance['display'], 'template' ); ?>>Use a custom template &raquo;</option>
			</select>
			<select class="insertpage-template-select" name="<?php echo $this->get_field_name( 'template' ); ?>" id="<?php echo $this->get_field_id( 'template' ); ?>" disabled="disabled">
				<option value='all'><?php _e( 'Default Template' ); ?></option>
				<?php page_template_dropdown( $instance['template'] ); ?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'class' ); ?>"><?php _e( 'Extra Classes' ); ?>:</label>
			<input type="text" class="widefat" autocomplete="off" name="<?php echo $this->get_field_name( 'class' ); ?>" id="<?php echo $this->get_field_id( 'class' ); ?>" value="<?php echo esc_attr( $instance['class'] ); ?>" />
		</p>
		<p>
			<input class="checkbox" type="checkbox" name="<?php echo $this->get_field_name( 'inline' ); ?>" id="<?php echo $this->get_field_id( 'inline' ); ?>" value="1" <?php checked( $instance['inline'], '1' ); ?> />
			<label for="<?php echo $this->get_field_id( 'inline' ); ?>"><?php _e( 'Inline?' ); ?></label>
		</p><?php
	}

	/**
	 * Process widget options on save.
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		// Sanitize form options.
		$instance = $old_instance;
		$instance['page'] = strip_tags( $new_instance['page'] );
		$instance['display'] = strip_tags( $new_instance['display'] );
		$instance['template'] = strip_tags( $new_instance['template'] );
		$instance['class'] = strip_tags( $new_instance['class'] );
		$instance['inline'] = strip_tags( $new_instance['inline'] );

		return $instance;
	}
}
