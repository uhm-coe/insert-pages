jQuery( document ).ready( function ( $ ) {
	// Enable/Disable template dropdown based on display choice in widget options.
	$( document ).on( 'change', '.insertpage-format-select', function() {
		if ( $( this ).val() == 'template' ) {
			$( '.insertpage-template-select' ).removeAttr( 'disabled' );
			$( '.insertpage-template-select' ).show();
			if ( $( this ).is( ':focus' ) ) {
				$( '.insertpage-template-select' ).focus();
			}
		} else {
			$( '.insertpage-template-select' ).attr( 'disabled', 'disabled' );
			$( '.insertpage-template-select' ).hide();
		}

		if ( $( this ).val() == 'post-thumbnail' ) {
			$( '.insertpage-size-select' ).removeAttr( 'disabled' );
			$( '.insertpage-size-select' ).show();
			if ( $( this ).is( ':focus' ) ) {
				$( '.insertpage-size-select' ).focus();
			}
		} else {
			$( '.insertpage-size-select' ).attr( 'disabled', 'disabled' );
			$( '.insertpage-size-select' ).hide();
		}
	});
	$( document ).on( 'widget-updated', function () {
		$( '.insertpage-format-select' ).change();
	})
	$( '.insertpage-format-select' ).change();

});
