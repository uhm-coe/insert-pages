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
				bookmark, cursorPosition, regexp, match, startPos, endPos,
				parentAnchor = editor.dom.getParent( node, 'a' ),
				parentImg = editor.dom.getParent( node, 'img' );

			// Get whether cursor is in an existing shortcode
			content = node.innerHTML;
			if ( content.indexOf( '[insert page=' ) >= 0 ) {
				// Find the cursor position in the current node.
				bookmark = editor.selection.getBookmark( 0 );
				cursorPosition = node.innerHTML.indexOf( '<span data-mce-type="bookmark"' );
				editor.selection.moveToBookmark( bookmark );

				// Find occurrences of shortcode in current node and see if the cursor
				// position is inside one of them.
				regexp = /\[insert page=[^\]]*]/g;
				while ( ( match = regexp.exec( content ) ) != null ) {
					startPos = match.index;
					endPos = startPos + match[0].length;
					if ( cursorPosition > startPos && cursorPosition <= endPos ) {
						parentIsShortcode = true;
						break;
					}
				}
			}

			button.disabled( parentAnchor !== null || parentImg !== null );
			button.active( parentIsShortcode );
		}

		editor.addButton( 'wpInsertPages_button', {
			image: url + '/../img/insertpages_toolbar_icon' + ( isRetinaDisplay() ? '-2x' : '' ) + '.png',
			tooltip: 'Insert page',
			cmd: 'WP_InsertPages',

			onPostRender: function() {
				insertPagesButton = this;

				editor.on( 'nodechange', function( event ) {
					setState( insertPagesButton, event.element );
				});
			}
		});
	})

} )();
