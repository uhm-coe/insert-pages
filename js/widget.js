jQuery( document ).ready( function ( $ ) {
	// Enable/Disable template dropdown based on display choice in widget options.
	$( document ).on( 'change', '.insertpage-format-select', function() {
		if ( $( this ).val() == 'template' ) {
			$( '.insertpage-template-select' ).removeAttr( 'disabled' );
			if ( $( this ).is( ':focus' ) ) {
				$( '.insertpage-template-select' ).focus();
			}
		} else {
			$( '.insertpage-template-select' ).attr( 'disabled', 'disabled' );
		}
	});
	$( document ).on( 'widget-updated', function () {
		$( '.insertpage-format-select' ).change();
	})
	$( '.insertpage-format-select' ).change();

});
