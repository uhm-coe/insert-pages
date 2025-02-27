(function() { // Start a new namespace to avoid collisions

	function isRetinaDisplay() {
		if (window.matchMedia) {
			var mq = window.matchMedia("only screen and (-moz-min-device-pixel-ratio: 1.3), only screen and (-o-min-device-pixel-ratio: 2.6/2), only screen and (-webkit-min-device-pixel-ratio: 1.3), only screen  and (min-device-pixel-ratio: 1.3), only screen and (min-resolution: 1.3dppx)");
			if (mq && mq.matches || (window.devicePixelRatio > 1)) {
				return true;
			} else {
				return false;
			}
		}
	}

	tinymce.PluginManager.add( 'wpInsertPages', function( editor, url ) {
		var insertPagesButton;

		// Register a command so that it can be invoked by using tinyMCE.activeEditor.execCommand( 'WP_InsertPages' );
		editor.addCommand( 'WP_InsertPages', function() {
			if ( ( ! insertPagesButton || ! insertPagesButton.disabled() ) && typeof window.wpInsertPages !== 'undefined' ) {
				window.wpInsertPages.open( editor.id );
			}
		})

		function setState( button, node ) {
			var parentIsShortcode = false,
				elementContainingCursor = editor.selection.getNode(),
				cursorOffsetWithinElement = editor.selection.getRng(),
				indexOfShortcodeStart = elementContainingCursor.innerHTML.indexOf( '[insert page=' ),
				indexOfShortcodeEnd = elementContainingCursor.innerHTML.indexOf( ']', indexOfShortcodeStart ),
				parentAnchor = editor.dom.getParent( node, 'a' ),
				parentImg = editor.dom.getParent( node, 'img' );

			// Determine if cursor is in an existing shortcode.
			if ( indexOfShortcodeStart >= 0 && indexOfShortcodeStart <= cursorOffsetWithinElement.startOffset && indexOfShortcodeEnd + 1 >= cursorOffsetWithinElement.endOffset ) {
				parentIsShortcode = true;
			}

			button.disabled( parentAnchor !== null || parentImg !== null );
			button.active( parentIsShortcode );
		}

		editor.addButton( 'wpInsertPages_button', {
			image: url + '/../img/insertpages_toolbar_icon' + ( isRetinaDisplay() ? '-2x' : '' ) + '.png',
			tooltip: wpInsertPagesL10n ? wpInsertPagesL10n.save : 'Insert Page',
			cmd: 'WP_InsertPages',

			onPostRender: function() {
				insertPagesButton = this;

				editor.on( 'nodechange', function( event ) {
					setState( insertPagesButton, event.element );
				});
			}
		});

		return {};
	})

} )();
