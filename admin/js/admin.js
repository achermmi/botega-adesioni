/* global baAdmin */
( function ( $ ) {
	'use strict';

	/**
	 * Confirm before deleting an application.
	 */
	$( document ).on( 'click', '.ba-delete-link', function ( e ) {
		var message = $( this ).data( 'confirm' ) || ( baAdmin && baAdmin.confirmDelete );
		if ( message && ! window.confirm( message ) ) {
			e.preventDefault();
		}
	} );
} )( jQuery );
