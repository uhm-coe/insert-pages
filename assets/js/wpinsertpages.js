// Modified from WordPress Advanced Link dialog 
// /wp-includes/js/tinymce/plugins/wplink/js/wplink.dev.js

var wpInsertPages;

(function($){
	var inputs = {}, rivers = {}, ed, RiverInsertPages, QueryInsertPages;

	wpInsertPages = {
		timeToTriggerRiverInsertPages: 150,
		minRiverInsertPagesAJAXDuration: 200,
		riverBottomThreshold: 5,
		keySensitivity: 100,
		lastSearch: '',
		init : function() {
			inputs.dialog = $('#wp-insertpage');
			inputs.submit = $('#wp-insertpage-submit');
			inputs.slug = $('#insertpage-slug-field'); // Slug
			inputs.pageID = $('#insertpage-pageID');
			inputs.parentPageID = $('#insertpage-parent-pageID');
			inputs.format = $('#insertpage-format-select'); // Format field (title, link, content, all, choose a custom template ->)
			inputs.template = $('#insertpage-template-select'); // Custom template select field
			inputs.search = $('#insertpage-search-field');
			// Build RiverInsertPagess
			rivers.search = new RiverInsertPages( $('#insertpage-search-results') );
			rivers.recent = new RiverInsertPages( $('#insertpage-most-recent-results') );
			rivers.elements = $('.query-results', inputs.dialog);

			// Bind event handlers
			inputs.dialog.keydown( wpInsertPages.keydown );
			inputs.dialog.keyup( wpInsertPages.keyup );
			inputs.submit.click( function(e){
				wpInsertPages.update();
				e.preventDefault();
			});
			$('#wp-insertpage-cancel').click( wpInsertPages.cancel );
			$('#insertpage-internal-toggle').click( wpInsertPages.toggleInternalLinking );

			rivers.elements.bind('river-select', wpInsertPages.updateFields );

			inputs.search.keyup( wpInsertPages.searchInternalLinks );

			inputs.dialog.bind('wpdialogrefresh', wpInsertPages.refresh);
			
			inputs.format.change(function() {
				if (inputs.format.val()=='template') {
					inputs.template.removeAttr('disabled');
					inputs.template.focus();
				} else {
					inputs.template.attr('disabled', 'disabled');
				}
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

		refresh : function() {
			var e;
			ed = tinyMCEPopup.editor;

			// Refresh rivers (clear links, check visibility)
			rivers.search.refresh();
			rivers.recent.refresh();

			tinyMCEPopup.restoreSelection();

			wpInsertPages.setDefaultValues();
			// Update save prompt.
			inputs.submit.val( wpInsertPagesL10n.save );

			tinyMCEPopup.storeSelection();
			// Focus the URL field and highlight its contents.
			//     If this is moved above the selection changes,
			//     IE will show a flashing cursor over the dialog.
			inputs.slug.focus()[0].select();
			// Load the most recent results if this is the first time opening the panel.
			if ( ! rivers.recent.ul.children().length )
				rivers.recent.ajax();
		},

		cancel : function() {
			tinyMCEPopup.close();
		},

		update : function() {
			var ed = tinyMCEPopup.editor,
				attrs = {
					page : inputs.slug.val(),
					pageID : inputs.pageID.val(),
					display: inputs.format.val()=='template' ? inputs.template.val() : inputs.format.val(),
					//title : inputs.title.html(),
					//target : inputs.useCustomTemplate.attr('checked') ? '_blank' : ''
				}, e, b;

			tinyMCEPopup.restoreSelection();

			// If the values are empty, unlink and return
			if ( ! attrs.page || attrs.page == '' ) {
				if ( e ) {
					tinyMCEPopup.execCommand("mceBeginUndoLevel");
					b = ed.selection.getBookmark();
					ed.selection.setContent('');
					ed.selection.moveToBookmark(b);
					tinyMCEPopup.execCommand("mceEndUndoLevel");
					tinyMCEPopup.close();
				}
				return;
			}

			tinyMCEPopup.execCommand("mceBeginUndoLevel");
			ed.selection.setContent("[insert " +
				"page='" + attrs.page +"' " +
				"display='" + attrs.display + "'" +
				"]");
			tinyMCEPopup.execCommand("mceEndUndoLevel");
			tinyMCEPopup.close();
		},

		updateFields : function( e, li, originalEvent ) {
			inputs.slug.val( li.children('.item-slug').val() );
			inputs.pageID.val( li.children('.item-id').val() );
			if ( originalEvent && originalEvent.type == "click" )
				inputs.slug.focus();
		},
		setDefaultValues : function() {
			// Set URL and description to defaults.
			// Leave the new tab setting as-is.
			inputs.slug.val('');
			inputs.pageID.val('');
			inputs.format.val('title');
			inputs.template.val('all');
		},

		searchInternalLinks : function() {
			var t = $(this), waiting,
				search = t.val();

			if ( search.length > 2 ) {
				rivers.recent.hide();
				rivers.search.show();

				// Don't search if the keypress didn't change the title.
				if ( wpInsertPages.lastSearch == search )
					return;

				wpInsertPages.lastSearch = search;
				waiting = t.siblings('img.waiting').show();

				rivers.search.change( search );
				rivers.search.ajax( function(){ waiting.hide(); });
			} else {
				rivers.search.hide();
				rivers.recent.show();
			}
		},

		next : function() {
			rivers.search.next();
			rivers.recent.next();
		},
		prev : function() {
			rivers.search.prev();
			rivers.recent.prev();
		},

		keydown : function( event ) {
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
					wpInsertPages.cancel();
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

		delayedCallback : function( func, delay ) {
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

		toggleInternalLinking : function( event ) {
			var panel = $('#insertpage-options'),
				widget = inputs.dialog.wpdialog('widget'),
				// We're about to toggle visibility; it's currently the opposite
				visible = !panel.is(':visible'),
				win = $(window);

			$(this).toggleClass('toggle-arrow-active', visible);

			inputs.dialog.height('auto');
			panel.slideToggle( 300, function() {
				setUserSetting('wpInsertPages', visible ? '1' : '0');
				inputs[ visible ? 'slug' : 'search' ].focus();

				// Move the box if the box is now expanded, was opened in a collapsed state,
				// and if it needs to be moved. (Judged by bottom not being positive or
				// bottom being smaller than top.)
				var scroll = win.scrollTop(),
					top = widget.offset().top,
					bottom = top + widget.outerHeight(),
					diff = bottom - win.height();

				if ( diff > scroll ) {
					widget.animate({'top': diff < top ?  top - diff : scroll }, 200);
				}
			});
			event.preventDefault();
		}
	}

	RiverInsertPages = function( element, search ) {
		var self = this;
		this.element = element;
		this.ul = element.children('ul');
		this.waiting = element.find('.river-waiting');

		this.change( search );
		this.refresh();

		element.scroll( function(){ self.maybeLoad(); });
		element.delegate('li', 'click', function(e){ self.select( $(this), e ); });
	};

	$.extend( RiverInsertPages.prototype, {
		refresh: function() {
			this.deselect();
			this.visible = this.element.is(':visible');
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

			if ( liTop < 0 ) // Make first visible element
				this.element.scrollTop( elTop + liTop );
			else if ( liTop + liHeight > elHeight ) // Make last visible element
				this.element.scrollTop( elTop + liTop - elHeight + liHeight );

			// Trigger the river-select event
			this.element.trigger('river-select', [ li, event, this ]);
		},
		deselect: function() {
			if ( this.selected )
				this.selected.removeClass('selected');
			this.selected = false;
		},
		prev: function() {
			if ( ! this.visible )
				return;

			var to;
			if ( this.selected ) {
				to = this.selected.prev('li');
				if ( to.length )
					this.select( to );
			}
		},
		next: function() {
			if ( ! this.visible )
				return;

			var to = this.selected ? this.selected.next('li') : $('li:not(.unselectable):first', this.element);
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
		change: function( search ) {
			if ( this.query && this._search == search )
				return;

			this._search = search;
			this.query = new QueryInsertPages( search );
			this.element.scrollTop(0);
		},
		process: function( results, params ) {
			var list = '', alt = true, classes = '',
				firstPage = params.page == 1;

			if ( !results ) {
				if ( firstPage ) {
					list += '<li class="unselectable"><span class="item-title"><em>'
					+ wpInsertPagesL10n.noMatchesFound
					+ '</em></span></li>';
				}
			} else {
				$.each( results, function() {
					classes = alt ? 'alternate' : '';
					classes += this['title'] ? '' : ' no-title';
					list += classes ? '<li class="' + classes + '">' : '<li>';
					list += '<input type="hidden" class="item-permalink" value="' + this['permalink'] + '" />';
					list += '<input type="hidden" class="item-slug" value="' + this['slug'] + '" />';
					list += '<input type="hidden" class="item-id" value="' + this['ID'] + '" />';
					list += '<span class="item-title">';
					list += this['title'] ? this['title'] : wpInsertPagesL10n.noTitle;
					list += '</span><span class="item-info">' + this['info'] + '</span></li>';
					alt = ! alt;
				});
			}

			this.ul[ firstPage ? 'html' : 'append' ]( list );
		},
		maybeLoad: function() {
			var self = this,
				el = this.element,
				bottom = el.scrollTop() + el.height();

			if ( ! this.query.ready() || bottom < this.ul.height() - wpInsertPages.riverBottomThreshold )
				return;

			setTimeout(function() {
				var newTop = el.scrollTop(),
					newBottom = newTop + el.height();

				if ( ! self.query.ready() || newBottom < self.ul.height() - wpInsertPages.riverBottomThreshold )
					return;

				self.waiting.show();
				el.scrollTop( newTop + self.waiting.outerHeight() );

				self.ajax( function() { self.waiting.hide(); });
			}, wpInsertPages.timeToTriggerRiverInsertPages );
		}
	});

	QueryInsertPages = function( search ) {
		this.page = 1;
		this.allLoaded = false;
		this.querying = false;
		this.search = search;
	};

	$.extend( QueryInsertPages.prototype, {
		ready: function() {
			return !( this.querying || this.allLoaded );
		},
		ajax: function( callback ) {
			var self = this,
				query = {
					//action : 'wp-link-ajax',
					action : 'insertpage',
					page : this.page,
					'_ajax_inserting_nonce' : $('#_ajax_inserting_nonce').val()
				};

			if ( this.search )
				query.search = this.search;
		
		  query.pageID = inputs.pageID.val();

			this.querying = true;
			$.post( ajaxurl, query, function(r) {
				self.page++;
				self.querying = false;
				self.allLoaded = !r;
				callback( r, query );
			}, "json" );
		}
	});

	$(document).ready( wpInsertPages.init );
})(jQuery);
