(function ( $ ) {
	"use strict";

	var fetching_child_groups = false;

	$( document ).ready( function() {
		/*
		 * Expand folders to show contents on click.
		 * Contents are fetched via an AJAX request.
		 */
		$( '#buddypress' ).on( 'click', '.toggle-child-groups', function( e ) {
			e.preventDefault();
			console.log( 'about to fire toggle' );
			toggle_child_groups( $( this ) );
			$( this ).closest( '.child-groups-container' ).toggleClass( 'open' );
		} );
	} );

	/**
	 * Fetch the first set of results in a folder,
	 * if the folder isn't already populated.
	 */
	function toggle_child_groups( anchor ) {
		var container = $( anchor ).closest( '.child-groups-container' ),
			target = container.find( '.child-groups' ),
			length = $.trim( target.text() ).length;

		console.log( container );

		// If the folder content has already been populated, do nothing.
		if ( length ) {
			console.log( length );
			return;
		}

		// Do not continue if we are currently fetching a set of results.
		if ( fetching_child_groups !== false ) {
			return;
		}
		fetching_child_groups = true;
		container.addClass( 'loading' );
			console.log( 'about to send ajax' );

		// Make the AJAX request and populate the list.
		$.ajax( {
			url: ajaxurl,
			type: 'GET',
			data: {
				group_id: $( anchor ).data( 'group-id' ),
				action: 'hgbp_get_child_groups',
			},
			success: function( response ) {
				console.log( 'runnning success' );
				$( target ).html( response );
				fetching_child_groups = false;
				container.removeClass( 'loading' );
			},
			error: function( response ) {
				console.log( 'runnning error' );
				fetching_child_groups = false;
				container.removeClass( 'loading' );
			},
			done: function( response ) {
				console.log( 'runnning done' );
				fetching_child_groups = false;
				container.removeClass( 'loading' );
			},

		} );

	}

}(jQuery));