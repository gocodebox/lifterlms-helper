(function( $ ){

	$( '.llms-helper-bulk-cb-all' ).on( 'change', function() {

		$cbs = $( '.llms-helper-bulk-cb, .llms-helper-bulk-cb-all' );

		if ( 'checked' === $( this ).attr( 'checked' ) ) {
			$cbs.attr( 'checked', 'checked' );
		} else {
			$cbs.removeAttr( 'checked' );
		}

	} );

	$( '#lifterlms-helper-dismiss-notice' ).on( 'click', function() {

		var $top = $( this );

		$.ajax( ajaxurl, {

			method: 'POST',
			data: {
				action: 'llms_helper_dismiss_notice',
				nonce: $top.attr( 'data-nonce' ),
				slug: $top.attr( 'data-slug' )
			},
			success: function( r ) {

				if( r.success ) {

					$top.closest( 'div' ).fadeOut( 400 );
					setTimeout( function() {

						$top.closest( 'div' ).remove();

					}, 500 );

				} else {



				}


			}

		});

	} );

})( jQuery );
