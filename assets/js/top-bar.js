(function( $ ) {
	'use strict';

	if ( tjdData.current ) {
		$( '#tjd-top-bar-theme-select' ).val( tjdData.current )
	}

	$( '#tjd-top-bar-theme-select' ).on( 'change', function( e ) {
		let current_url = new URL( document.location );
		current_url.searchParams.set('style', $( this ).val() );
		document.location = current_url;
	} )
})( jQuery );