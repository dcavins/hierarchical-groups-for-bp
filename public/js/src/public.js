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

			// Show or hide the child groups div.
			toggle_results_pane( $( this ) );

			// Send for the results.
			fetch_child_groups( $( this ) );
		} );
	} );

	/**
	 * Fetch the child groups of a group,
	 * if the container isn't already populated.
	 */
	function fetch_child_groups( anchor ) {
		var target = anchor.closest( '.child-groups-container' ).find( '.child-groups' ).first(),
			length = $.trim( target.text() ).length;

		// If the folder content has already been populated, do nothing.
		if ( length ) {
			return;
		}

		// Do not continue if we are currently fetching a set of results.
		if ( fetching_child_groups !== false ) {
			return;
		}
		fetching_child_groups = true;
		target.addClass( 'loading' );

		// Make the AJAX request and populate the list.
		$.ajax({
			url: ajaxurl,
			type: 'GET',
			data: {
				parent_id: anchor.data( 'group-id' ),
				action: 'hgbp_get_child_groups',
			},
			success: function( response ) {
				console.log( 'runnning success' );
				$( target ).html( response );
			},
			error: function( response ) {
				console.log( 'runnning error' );
			}
		})
		.done( function( response ) {
				console.log( 'runnning done' );
				fetching_child_groups = false;
				target.removeClass( 'loading' );
		});

	}

	/**
	 * Toggle the child groups pane and indicators.
	 */
	function toggle_results_pane( anchor ) {
		// Toggle the class.
		anchor.siblings( ".child-groups" ).toggleClass( "open" );
		anchor.toggleClass( "open" );

		// Update the aria-expanded atribute on the related control.
		if ( anchor.siblings( ".child-groups" ).hasClass( "open" ) ) {
			anchor.attr( "aria-expanded", true );
		} else {
			anchor.attr( "aria-expanded", false );
		}
	}

}(jQuery));