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
		parent::__construct( 'ipw', 'Insert Page', array( 'description' => 'Insert a page into a widget area.' ) );
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

		$atts = array();
		if ( array_key_exists( 'page', $instance ) ) {
			$atts['page'] = $instance['page'];
		}
		if ( array_key_exists( 'display', $instance ) ) {
			$atts['display'] = $instance['display'];
		}
		if ( array_key_exists( 'class', $instance ) ) {
			$atts['class'] = $instance['class'];
		}
		if ( array_key_exists( 'inline', $instance ) ) {
			$atts['inline'] = true;
		}

		$content = $insertPages_plugin->insertPages_handleShortcode_insert( $atts );

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
			'display' => '',
			'class' => '',
			'inline' => '',
		)); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'page' ); ?>">Page ID or Slug:</label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'page' ); ?>" name="<?php echo $this->get_field_name( 'page' ); ?>" value="<?php echo $instance['page']; ?>" />
			<label for="insertpage-format-select"><?php _e( 'Display' ); ?></label>
			<select name="insertpage-format-select" id="insertpage-format-select">
				<option value='title'>Title</option>
				<option value='link'>Link</option>
				<option value='excerpt'>Excerpt</option>
				<option value='excerpt-only'>Excerpt only (no title)</option>
				<option value='content'>Content</option>
				<option value='all'>All (includes custom fields)</option>
				<option value='template'>Use a custom template &raquo;</option>
			</select>
			<select name="insertpage-template-select" id="insertpage-template-select" disabled="true">
				<option value='all'><?php _e( 'Default Template' ); ?></option>
				<?php page_template_dropdown(); ?>
			</select>

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

		return $instance;
	}
}
