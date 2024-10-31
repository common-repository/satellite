jQuery( document ).ready( function( $ ) {
	if ( $( '.satellite_widget' ).length ) {
		// Make the ajax call to the form
		$.ajax( {
			url		: '/wp-admin/admin-ajax.php',
			type	: 'POST',
			data	: { 
				action	: 'satellite_ajax_call', 
				view 	: $( '.satellite_widget' ).data( 'view' )
			},
			success : function( result ) {
				$( '.satellite_widget' ).html( result.data.html );
			},
			error	: function ( xhr, ajaxOptions, thrownError ) {
				$( '.satellite_widget' ).html( '<p>The connection has failed, please reload the page.</p>' );
			}
		} );
	}
	if ( $( '.satellite_dashboard' ).length ) {
		// Make the ajax call to the form
		$.ajax( {
			url		: '/wp-admin/admin-ajax.php',
			type	: 'POST',
			data	: { 
				action	: 'satellite_ajax_call', 
				view 	: $( '.satellite_dashboard' ).data( 'view' )
			},
			success : function( result ) {
				$( '.satellite_dashboard' ).html( result.data.html );
			},
			error	: function ( xhr, ajaxOptions, thrownError ) {
				$( '.satellite_dashboard' ).html( '<p>The connection has failed, please reload the page.</p>' );
			}
		} );
	}
} );