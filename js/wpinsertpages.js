/**
 * Modified from WordPress Advanced Link dialog.
 *
 * @output wp-includes/js/wplink.js
 */

 /* global wpInsertPages */

( function( $, wpInsertPagesL10n, wp ) {
	var editor, searchTimer, RiverInsertPages, QueryInsertPages, correctedURL,
		inputs = {},
		rivers = {},
		isTouch = ( 'ontouchend' in document );

	window.wpInsertPages = {
		timeToTriggerRiverInsertPages: 150,
		minRiverInsertPagesAJAXDuration: 200,
		riverBottomThreshold: 5,
		keySensitivity: 100,
		lastSearch: '',
		textarea: '',
		modalOpen: false,

		init: function() {
			inputs.wrap = $( '#wp-insertpage-wrap' );
			inputs.dialog = $( '#wp-insertpage' );
			inputs.backdrop = $( '#wp-insertpage-backdrop' );
			inputs.submit = $('#wp-insertpage-submit' );
			inputs.close = $( '#wp-insertpage-close' );

			// Input.
			inputs.slug = $( '#insertpage-slug-field' );
			inputs.pageID = $( '#insertpage-page-id' );
			inputs.parentPageID = $( '#insertpage-parent-page-id' );
			inputs.search = $( '#insertpage-search-field' );

			// Custom template select field.
			inputs.template = $( '#insertpage-template-select' );

			// Custom format field (title, link, content, all, choose a custom template ->).
			inputs.format = $( '#insertpage-format-select' );

			// Custom extra fields (wrapper classes, inline checkbox, "visible to all" checkbox).
			inputs.extraClasses = $( '#insertpage-extra-classes' );
			inputs.extraID = $( '#insertpage-extra-id' );
			inputs.extraInline = $( '#insertpage-extra-inline' );
			inputs.extraPublic = $( '#insertpage-extra-public' );
			inputs.extraQuerystring = $( '#insertpage-extra-querystring' );

			// Build rivers.
			rivers.search = new RiverInsertPages( $( '#insertpage-search-results' ) );
			rivers.recent = new RiverInsertPages( $( '#insertpage-most-recent-results' ) );
			rivers.elements = inputs.dialog.find( '.query-results' );

			// Get search notice text.
			inputs.queryNotice = $( '#insertpage-query-notice-message' );
			inputs.queryNoticeTextDefault = inputs.queryNotice.find( '.query-notice-default' );
			inputs.queryNoticeTextHint = inputs.queryNotice.find( '.query-notice-hint' );

			// Bind event handlers.
			inputs.dialog.on( 'keydown', wpInsertPages.keydown );
			inputs.dialog.on( 'keyup', wpInsertPages.keyup );
			inputs.submit.on( 'click', function( event ) {
				event.preventDefault();
				wpInsertPages.update();
			});

			inputs.close.add( inputs.backdrop ).add( '#wp-insertpage-cancel button' ).on( 'click', function( event ) {
				event.preventDefault();
				wpInsertPages.close();
			});

			// Custom hide/show options toggle.
			$( '#insertpage-options-toggle' ).on( 'click', wpInsertPages.toggleInternalLinking );

			rivers.elements.on('river-select', wpInsertPages.updateFields );

			// Display 'hint' message when search field or 'query-results' box are focused.
			inputs.search.on( 'focus.wplink', function() {
				inputs.queryNoticeTextDefault.hide();
				inputs.queryNoticeTextHint.removeClass( 'screen-reader-text' ).show();
			} ).on( 'blur.wplink', function() {
				inputs.queryNoticeTextDefault.show();
				inputs.queryNoticeTextHint.addClass( 'screen-reader-text' ).hide();
			} );

			inputs.search.on( 'keyup input', function() {
				var self = this; // Custom reference to field.
				window.clearTimeout( searchTimer );
				searchTimer = window.setTimeout( function() {
					wpInsertPages.searchInternalLinks.call( self );
				}, 500 );
			});

			// Custom: enable template dropdown if "Custom Template" is chosen as the display.
			inputs.format.on( 'change', function() {
				if ( inputs.format.val() == 'template' ) {
					inputs.template.removeAttr( 'disabled' );
					inputs.template.focus();
				} else {
					inputs.template.attr( 'disabled', 'disabled' );
				}
				// Save last selected display for this user.
				$.post( ajaxurl, {
					action : 'insertpage_save_presets',
					format : inputs.format.val(),
					'_ajax_inserting_nonce' : $('#_ajax_inserting_nonce').val(),
				}, function (r) {}, 'json' );
			});

			// Custom: save last selected template for this user.
			inputs.template.on( 'change', function() {
				$.post( ajaxurl, {
					action : 'insertpage_save_presets',
					template : inputs.template.val(),
					'_ajax_inserting_nonce' : $('#_ajax_inserting_nonce').val(),
				}, function (r) {}, 'json' );
			});

			// Set search type to plaintext if someone types in the search field.
			// Might have been set to 'slug' or 'id' if editing a current shortcode.
			inputs.search.on( 'keydown', function () {
				inputs.search.data( 'type', 'text' );
			});

			/* for this to work, inputs.slug needs to populate inputs.pageID with id when it changes
			inputs.pageID.change(function() {
				if (inputs.pageID.val() == inputs.parentPageID.val()) { // trying to embed a page in itself
					inputs.submit.attr('disabled','disabled');
				} else {
					inputs.submit.removeAttr('disabled');
				}
			});
			*/
		},

		open: function( editorId ) {
			var ed,
				node, bookmark, cursorPosition = -1,
				$body = $( document.body );

			$body.addClass( 'modal-open' );
			wpInsertPages.modalOpen = true;

			wpInsertPages.range = null;

			if ( editorId ) {
				window.wpActiveEditor = editorId;
			}

			if ( ! window.wpActiveEditor ) {
				return;
			}

			this.textarea = $( '#' + window.wpActiveEditor ).get( 0 );

			if ( typeof window.tinymce !== 'undefined' ) {
				// Make sure the link wrapper is the last element in the body,
				// or the inline editor toolbar may show above the backdrop.
				$body.append( inputs.backdrop, inputs.wrap );

				ed = window.tinymce.get( window.wpActiveEditor );

				if ( ed && ! ed.isHidden() ) {
					editor = ed;

					// Custom: get cursor state (used later to determine if we're in an existing shortcode)
					node = editor.selection.getNode();
					bookmark = editor.selection.getBookmark( 0 );
					unencodedText = node.innerHTML.replace( /&amp;/g, '&' );
					cursorPosition = unencodedText.indexOf( '<span data-mce-type="bookmark"' );
					editor.selection.moveToBookmark( bookmark );
				} else {
					editor = null;
				}
			}

			if ( ! wpInsertPages.isMCE() && document.selection ) {
				this.textarea.focus();
				this.range = document.selection.createRange();
			}

			inputs.wrap.show();
			inputs.backdrop.show();

			wpInsertPages.refresh( cursorPosition );
		},

		isMCE: function() {
			return editor && ! editor.isHidden();
		},

		refresh: function( cursorPosition ) {
			// Refresh rivers (clear links, check visibility)
			rivers.search.refresh();
			rivers.recent.refresh();

			if ( wpInsertPages.isMCE() ) {
				wpInsertPages.mceRefresh( cursorPosition );
			} else {
				wpInsertPages.setDefaultValues();
			}

			if ( isTouch ) {
				// Close the onscreen keyboard.
				inputs.url.trigger( 'focus' ).trigger( 'blur' );
			} else {
				/**
				 * Focus the Slug field and highlight its contents.
				 * If this is moved above the selection changes,
				 * IE will show a flashing cursor over the dialog.
				 */
				window.setTimeout( function() {
					inputs.slug[0].select();
					inputs.slug.trigger( 'focus' );
				} );
			}

			// Load the most recent results if this is the first time opening the panel.
			if ( ! rivers.recent.ul.children().length )
				rivers.recent.ajax();
		},

		mceRefresh: function( cursorPosition ) {
			var shortcode, bookmark, regexp, match, matches, offset;

			// Get the existing shortcode the cursor is in (or get the entire node if cursor not in one)
			shortcode = '';
			content = editor.selection.getNode().innerHTML.replace( /&amp;/g, '&' );
			if ( content.indexOf( '[insert page=' ) >= 0 ) {
				// Find occurrences of shortcode in current node and see if the cursor
				// position is inside one of them.
				regexp = /\[insert page=[^\]]*]/g;
				while ( ( match = regexp.exec( content ) ) != null ) {
					startPos = match.index;
					endPos = startPos + match[0].length;
					if ( cursorPosition >= startPos && cursorPosition <= endPos ) {
						shortcode = match[0];
						break;
					}
				}
			}

			// If cursor is in a shortcode, set the proper values.
			if ( shortcode.indexOf( '[insert page=' ) == 0 ) {
				// Expand selection to the entire shortcode that the cursor is inside
				range = editor.selection.getRng();
				node = editor.selection.getNode();
				selectedChild = null;
				offset = 0;
				for ( i = 0; i < node.childNodes.length; i++ ) {
					selectedChild = node.childNodes[i];
					text = ( selectedChild.outerHTML ) ? selectedChild.outerHTML : selectedChild.textContent;
					length = text.length;
					if ( cursorPosition <= offset + length ) {
						break;
					}
					offset += length;
				}
				if ( selectedChild.length >= offset ) {
					range.setStart( selectedChild, startPos - offset );
					range.setEnd( selectedChild, endPos - offset );
					editor.selection.setRng( range );
				}

				// Set slug/id (also set the slug as the search term)
				regexp = /page=['"]([^['"]*)['"]/;
				matches = regexp.exec( shortcode );
				if ( matches && matches.length > 1 ) {
					// Indicate that this search term is a slug or id.
					if ( isNaN( parseInt( matches[1] ) ) ) {
						inputs.search.data( 'type', 'slug' );
					} else {
						inputs.search.data( 'type', 'post_id' );
					}

					inputs.slug.val( matches[1] );
					inputs.search.val( matches[1] );
					inputs.search.trigger( 'keyup' );
				}

				// Update display dropdown to match the selected shortcode.
				regexp = /display=['"]([^['"]*)['"]/;
				matches = regexp.exec( shortcode );
				if ( matches && matches.length > 1 ) {
					if ( ['title', 'link', 'excerpt', 'excerpt-only', 'content', 'post-thumbnail', 'all', ].indexOf( matches[1] ) >= 0 ) {
						inputs.format.val( matches[1] );
						inputs.template.val( 'all' );
					} else {
						inputs.format.val( 'template' );
						inputs.template.val( matches[1] );
					}
					inputs.format.trigger( 'change' );
				}

				// Update extra classes.
				regexp = /class=['"]([^['"]*)['"]/;
				matches = regexp.exec( shortcode );
				if ( matches && matches.length > 1 ) {
					inputs.extraClasses.val( matches[1] );
				} else {
					inputs.extraClasses.val( wpInsertPagesL10n.hasOwnProperty( 'tinymce_state' ) ? wpInsertPagesL10n.tinymce_state.class : '' );
				}

				// Update extra ID.
				regexp = /id=['"]([^['"]*)['"]/;
				matches = regexp.exec( shortcode );
				if ( matches && matches.length > 1 ) {
					inputs.extraID.val( matches[1] );
				} else {
					inputs.extraID.val( wpInsertPagesL10n.hasOwnProperty( 'tinymce_state' ) ? wpInsertPagesL10n.tinymce_state.id : '' );
				}

				// Update extra inline (i.e., use span instead of div for wrapper).
				regexp = /[\s]inline[\s\]]/;
				matches = regexp.exec( shortcode );
				if ( matches && matches.length > 0 ) {
					inputs.extraInline.attr( 'checked', true );
				} else {
					inputs.extraInline.attr( 'checked', wpInsertPagesL10n.hasOwnProperty( 'tinymce_state' ) ? wpInsertPagesL10n.tinymce_state.inline : false );
				}

				// If this is a private page, reveal the checkbox "Visible to everyone?"
				regexp = /[\s]public[\s\]]/;
				matches = regexp.exec( shortcode );
				if ( matches && matches.length > 0 ) {
					inputs.extraPublic.attr( 'checked', true );
				} else {
					inputs.extraPublic.attr( 'checked', wpInsertPagesL10n.hasOwnProperty( 'tinymce_state' ) ? wpInsertPagesL10n.tinymce_state.public : false );
				}

				// Update extra querystring.
				regexp = /querystring=['"]([^['"]*)['"]/;
				matches = regexp.exec( shortcode );
				if ( matches && matches.length > 1 ) {
					// Also unescape brackets in the querystring (if left alone, any
					// closing bracket will terminate the shortcode).
					inputs.extraQuerystring.val( matches[1].replace( /&amp;/g, '&' ) );
				} else {
					inputs.extraQuerystring.val( wpInsertPagesL10n.hasOwnProperty( 'tinymce_state' ) ? wpInsertPagesL10n.tinymce_state.querystring : '' );
				}

				// Update save prompt.
				inputs.submit.val( wpInsertPagesL10n.update );

			// If there's no link, set the default values.
			} else {
				wpInsertPages.setDefaultValues();
			}
		},

		close: function( reset ) {
			$( document.body ).removeClass( 'modal-open' );
			wpInsertPages.modalOpen = false;

			if ( reset !== 'noReset' ) {
				if ( ! wpInsertPages.isMCE() ) {
					wpInsertPages.textarea.focus();

					if ( wpInsertPages.range ) {
						wpInsertPages.range.moveToBookmark( wpInsertPages.range.getBookmark() );
						wpInsertPages.range.select();
					}
				} else {
					editor.focus();
				}
			}

			inputs.backdrop.hide();
			inputs.wrap.hide();

			$( document ).trigger( 'wpinsertpages-close', inputs.wrap );
		},

		getAttrs: function() {
			return {
				page: inputs.slug.val(),
				pageID: inputs.pageID.val(),
				display: inputs.format.val()=='template' ? inputs.template.val() : inputs.format.val(),
				class: inputs.extraClasses.val(),
				id: inputs.extraID.val(),
				inline: inputs.extraInline.is( ':checked' ),
				public: inputs.extraPublic.is( ':checked' ),
				querystring: inputs.extraQuerystring.val(),
			};
		},

		update: function() {
			var link,
				attrs = wpInsertPages.getAttrs(),
				b;

			wpInsertPages.close();
			editor.focus();

			if ( tinymce.isIE ) {
				editor.selection.moveToBookmark( editor.windowManager.bookmark );
			}

			// If the values are empty, undo and return
			if ( ! attrs.page || attrs.page == '' ) {
				editor.execCommand("mceBeginUndoLevel");
				b = editor.selection.getBookmark();
				editor.selection.setContent('');
				editor.selection.moveToBookmark(b);
				editor.execCommand("mceEndUndoLevel");
				return;
			}

			editor.execCommand("mceBeginUndoLevel");
			editor.selection.setContent("[insert " +
				"page='" + attrs.page +"' " +
				"display='" + attrs.display + "'" +
				( attrs['class'].length > 0 ? " class='" + attrs['class'] + "'" : "" ) +
				( attrs['id'].length > 0 ? " id='" + attrs['id'] + "'" : "" ) +
				( attrs.inline ? " inline" : "" ) +
				( attrs.public ? " public" : "" ) +
				( attrs.querystring ? " querystring='" +
					attrs['querystring'].replace( /&/g, '&amp;' ).replace( /\[/g, '{' ).replace( /\]/g, '}' )
					+ "'" : ""
				) +
				"]");
			editor.execCommand("mceEndUndoLevel");
		},

		updateFields: function( e, li, originalEvent ) {
			if ( wpInsertPagesL10n.format === 'post_id' ) {
				inputs.slug.val( li.children('.item-id').val() );
			} else {
				inputs.slug.val( li.children('.item-slug').val() );
			}
			inputs.pageID.val( li.children('.item-id').val() );
			if ( originalEvent && originalEvent.type == "click" )
				inputs.slug.focus();
		},

		setDefaultValues: function() {
			// Set inserted page slug/ID and search term to defaults.
			inputs.slug.val('');
			inputs.pageID.val('');
			inputs.search.val( '' );
			inputs.search.data( 'type', 'text' );
			inputs.search.keyup();

			// Restore field defaults from initial defaults.
			// inputs.format.val('title'); // Leave at last-selected value.
			// inputs.template.val('all'); // Leave at last-selected value.
			inputs.extraClasses.val( wpInsertPagesL10n.hasOwnProperty( 'tinymce_state' ) ? wpInsertPagesL10n.tinymce_state.class : '' );
			inputs.extraID.val( wpInsertPagesL10n.hasOwnProperty( 'tinymce_state' ) ? wpInsertPagesL10n.tinymce_state.id : '' );
			inputs.extraQuerystring.val( wpInsertPagesL10n.hasOwnProperty( 'tinymce_state' ) ? wpInsertPagesL10n.tinymce_state.querystring : '' );
			inputs.extraInline.attr( 'checked', wpInsertPagesL10n.hasOwnProperty( 'tinymce_state' ) ? wpInsertPagesL10n.tinymce_state.inline : false );
			inputs.extraPublic.attr( 'checked', wpInsertPagesL10n.hasOwnProperty( 'tinymce_state' ) ? wpInsertPagesL10n.tinymce_state.public : false );

			// Enable template dropdown if display format is set to 'template'.
			if ( inputs.format.val() == 'template' ) {
				inputs.template.removeAttr( 'disabled' );
			} else {
				inputs.template.attr( 'disabled', 'disabled' );
			}
		},

		searchInternalLinks: function() {
			var t = $(this), waiting,
				search = t.val(),
				type = t.data( 'type' );

			if ( search.length > 2 || ( type === 'post_id' && search.length > 0 ) ) {
				rivers.recent.hide();
				rivers.search.show();

				// Don't search if the keypress didn't change the title.
				if ( wpInsertPages.lastSearch == search )
					return;

				wpInsertPages.lastSearch = search;
				waiting = t.parent().find( '.spinner' ).addClass( 'is-active' );

				rivers.search.change( search, type );
				rivers.search.ajax( function() {
					waiting.removeClass( 'is-active' );
				});
			} else {
				rivers.search.hide();
				rivers.recent.show();
			}
		},

		next: function() {
			rivers.search.next();
			rivers.recent.next();
		},
		prev: function() {
			rivers.search.prev();
			rivers.recent.prev();
		},

		keydown: function( event ) {
			var fn, key = $.ui.keyCode;

			switch( event.which ) {
				case key.UP:
					fn = 'prev';
				case key.DOWN:
					fn = fn || 'next';
					clearInterval( wpInsertPages.keyInterval );
					wpInsertPages[ fn ]();
					wpInsertPages.keyInterval = setInterval( wpInsertPages[ fn ], wpInsertPages.keySensitivity );
					break;
				default:
					return;
			}
			event.preventDefault();
		},

		keyup: function( event ) {
			var key = $.ui.keyCode;

			switch( event.which ) {
				case key.ESCAPE:
					wpInsertPages.close();
					event.stopImmediatePropagation();
					break;
				case key.UP:
				case key.DOWN:
					clearInterval( wpInsertPages.keyInterval );
					break;
				default:
					return;
			}
			event.preventDefault();
		},

		delayedCallback: function( func, delay ) {
			var timeoutTriggered, funcTriggered, funcArgs, funcContext;

			if ( ! delay )
				return func;

			setTimeout( function() {
				if ( funcTriggered )
					return func.apply( funcContext, funcArgs );
				// Otherwise, wait.
				timeoutTriggered = true;
			}, delay);

			return function() {
				if ( timeoutTriggered )
					return func.apply( this, arguments );
				// Otherwise, wait.
				funcArgs = arguments;
				funcContext = this;
				funcTriggered = true;
			};
		},

		toggleInternalLinking: function( event ) {
			var visible = inputs.wrap.hasClass( 'options-panel-visible');

			inputs.wrap.toggleClass( 'options-panel-visible', ! visible );
			setUserSetting( 'wpinsertpage', visible ? '0' : '1' );
			inputs[ visible ? 'search' : 'slug' ].focus();
		}
	}

	RiverInsertPages = function( element, search ) {
		var self = this;
		var type = 'text';
		this.element = element;
		this.ul = element.children( 'ul' );
		this.contentHeight = element.children( '#link-selector-height' );
		this.waiting = element.find('.river-waiting');

		this.change( search, type );
		this.refresh();

		$( '#wp-insertpage .query-results, #wp-insertpage #link-selector' ).on( 'scroll', function() {
			self.maybeLoad();
		});
		element.on( 'click', 'li', function( event ) {
			self.select( $( this ), event );
		});
	};

	$.extend( RiverInsertPages.prototype, {
		refresh: function() {
			this.deselect();
			this.visible = this.element.is( ':visible' );
		},
		show: function() {
			if ( ! this.visible ) {
				this.deselect();
				this.element.show();
				this.visible = true;
			}
		},
		hide: function() {
			this.element.hide();
			this.visible = false;
		},
		// Selects a list item and triggers the river-select event.
		select: function( li, event ) {
			var liHeight, elHeight, liTop, elTop;

			if ( li.hasClass('unselectable') || li == this.selected )
				return;

			this.deselect();
			this.selected = li.addClass('selected');
			// Make sure the element is visible
			liHeight = li.outerHeight();
			elHeight = this.element.height();
			liTop = li.position().top;
			elTop = this.element.scrollTop();

			if ( liTop < 0 ) { // Make first visible element.
				this.element.scrollTop( elTop + liTop );
			} else if ( liTop + liHeight > elHeight ) { // Make last visible element.
				this.element.scrollTop( elTop + liTop - elHeight + liHeight );
			}

			// Trigger the river-select event
			this.element.trigger( 'river-select', [ li, event, this ] );
		},
		deselect: function() {
			if ( this.selected )
				this.selected.removeClass( 'selected' );
			this.selected = false;
		},
		prev: function() {
			if ( ! this.visible )
				return;

			var to;
			if ( this.selected ) {
				to = this.selected.prev( 'li' );
				if ( to.length )
					this.select( to );
			}
		},
		next: function() {
			if ( ! this.visible )
				return;

			var to = this.selected ? this.selected.next( 'li' ) : $( 'li:not(.unselectable):first', this.element );
			if ( to.length )
				this.select( to );
		},
		ajax: function( callback ) {
			var self = this,
				delay = this.query.page == 1 ? 0 : wpInsertPages.minRiverInsertPagesAJAXDuration,
				response = wpInsertPages.delayedCallback( function( results, params ) {
					self.process( results, params );
					if ( callback )
						callback( results, params );
				}, delay );

			this.query.ajax( response );
		},
		change: function( search, type ) {
			if ( this.query && this._search == search )
				return;

			this._search = search;
			this.query = new QueryInsertPages( search, type );
			this.element.scrollTop( 0 );
		},
		process: function( results, params ) {
			var list = '', alt = true, classes = '',
				firstPage = params.page == 1;

			if ( ! results ) {
				if ( firstPage ) {
					list += '<li class="unselectable"><span class="item-title"><em>' + wpInsertPagesL10n.noMatchesFound + '</em></span></li>';
				}
			} else {
				$.each( results, function() {
					classes = alt ? 'alternate' : '';
					classes += this.title ? '' : ' no-title';
					list += classes ? '<li class="' + classes + '">' : '<li>';
					list += '<input type="hidden" class="item-permalink" value="' + this.permalink + '" />';
					list += '<input type="hidden" class="item-slug" value="' + this.path + '" />';
					list += '<input type="hidden" class="item-id" value="' + this.ID + '" />';
					list += '<span class="item-title">';
					list += this.title ? this.title : wpInsertPagesL10n.noTitle;
					list += '</span><span class="item-info">' + this.info;
					list += this.status === 'private' ? ' (' + wpInsertPagesL10n.private + ')' : '';
					list += '</span></li>';
					alt = ! alt;
				});
			}

			this.ul[ firstPage ? 'html' : 'append' ]( list );
		},
		maybeLoad: function() {
			var self = this,
				el = this.element,
				bottom = el.scrollTop() + el.height();

			if ( ! this.query.ready() || bottom < this.contentHeight.height() - wpInsertPages.riverBottomThreshold )
				return;

			setTimeout( function() {
				var newTop = el.scrollTop(),
					newBottom = newTop + el.height();

				if ( ! self.query.ready() || newBottom < self.contentHeight.height() - wpInsertPages.riverBottomThreshold )
					return;

				self.waiting.addClass( 'is-active' );
				el.scrollTop( newTop + self.waiting.outerHeight() );

				self.ajax( function() {
					self.waiting.removeClass( 'is-active' );
				});
			}, wpInsertPages.timeToTriggerRiverInsertPages );
		}
	});

	QueryInsertPages = function( search, type ) {
		this.page = 1;
		this.allLoaded = false;
		this.querying = false;
		this.search = search;
		this.type = type;
	};

	$.extend( QueryInsertPages.prototype, {
		ready: function() {
			return ! ( this.querying || this.allLoaded );
		},
		ajax: function( callback ) {
			var self = this,
				query = {
					action : 'insertpage',
					page : this.page,
					type : this.type,
					'_ajax_inserting_nonce' : $('#_ajax_inserting_nonce').val()
				};

			if ( this.search )
				query.search = this.search;

			this.querying = true;

			// query.pageID = inputs.pageID.val(); // Remove?

			$.post( window.ajaxurl, query, function( r ) {
				self.page++;
				self.querying = false;
				self.allLoaded = ! r;
				callback( r, query );
			}, 'json' );
		}
	});

	$( document ).ready( wpInsertPages.init );
})( jQuery, window.wpInsertPagesL10n, window.wp );
