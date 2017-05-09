(function ( $ ) {
	"use strict";

	var fetching_child_groups = false;

	$( document ).ready( function() {
		// Enable the "show tree view" toggle only when loading the "all groups" view.
		$( "#hgbp-enable-tree-view-container input" ).prop( "disabled", $( "#groups-personal" ).hasClass( "selected" ) );

		/*
		 * Hide the "show tree view" toggle when switching away from the "all groups" view.
		 * Using a very targeted MutationObserver seems like the best bet for now.
		 * Worst-case scenario if this code isn't supported is that user sees toggle that
		 * is ignored on the "my groups" view, so not too bad.
		 */
		var $directory_nav_item = document.getElementById( "groups-all" );
		if ( $directory_nav_item !== null && window.MutationObserver ) {
			var observer = new MutationObserver( function( mutations ) {
				mutations.forEach( function( mutation ) {
					if ( mutation.attributeName === "class" ) {
						$( "#hgbp-enable-tree-view-container input" ).prop( "disabled", $( "#groups-personal" ).hasClass( "selected" ) );
					}
				} );
			} );
			observer.observe( $directory_nav_item,  {
				attributes: true,
				childList: false,
				characterData: false,
				subtree: false,
				attributeFilter: ['class']
			} );
		}

		/*
		 * Expand folders to show contents on click.
		 * Contents are fetched via an AJAX request.
		 */
		$( "#buddypress" ).on( "click", ".toggle-child-groups", function( e ) {
			e.preventDefault();

			// Show or hide the child groups div.
			toggle_results_pane( $( this ) );

			// Send for the results.
			fetch_child_groups( $( this ) );
		} );

		// Refresh groups list when the "use tree view" toggle is clicked.
		$( "#buddypress" ).on( "change", "#hgbp-enable-tree-view", function( e ) {
			send_filter_request( $( this ) );
		} );
	} );

	/*
	 * Refresh groups list when the "use tree view" toggle is clicked.
	 */
	function send_filter_request( input ) {
		var checked      = input.prop( "checked" ) ? 1 : 0,
			filter       = $( "select#groups-order-by" ).val(),
			search_terms = "";

		$.cookie( "bp-groups-use-tree-view", checked, { path: "/" } );

		if ( $(".dir-search input").length ) {
			search_terms = $(".dir-search input").val();
		}

		bp_filter_request( "groups", filter, "filter", "div.groups", search_terms, 1, $.cookie( "bp-group-extras" ) );

		return false;
	}

	/**
	 * Toggle the child groups pane and indicators.
	 */
	function toggle_results_pane( anchor ) {
		// Toggle the child groups pane and open indicator.
		anchor.siblings( ".child-groups" ).toggleClass( "open" );
		anchor.toggleClass( "open" );

		// Update the aria-expanded attribute on the related control.
		anchor.attr( "aria-expanded", anchor.siblings( ".child-groups" ).hasClass( "open" ) );
	}

	/**
	 * Fetch the child groups of a group,
	 * if the container isn't already populated.
	 */
	function fetch_child_groups( anchor ) {
		var target = anchor.closest( ".child-groups-container" ).find( ".child-groups" ).first();

		// If the folder content has already been populated, do nothing.
		if ( $.trim( target.text() ).length ) {
			return;
		}

		// Do not continue if we are currently fetching a set of results.
		if ( fetching_child_groups !== false ) {
			return;
		}
		fetching_child_groups = true;

		// Show a loading indicator.
		target.addClass( "loading" );

		// Make the AJAX request and populate the list.
		$.ajax({
			url: ajaxurl,
			type: "GET",
			data: {
				parent_id: anchor.data( "group-id" ),
				action: "hgbp_get_child_groups",
			},
			success: function( response ) {
				/*
				 * Upon success, flow the html into the target container.
				 * Also fire an event so other javascript can respond if needed, like
				 * jQuery( ".child-groups" ).on( "childGroupsContainerPopulated", function(){ console.log( "doing something" ); });
				 */
				$( target ).html( response ).trigger( "childGroupsContainerPopulated" );
			}
		})
		.done( function( response ) {
			fetching_child_groups = false;
			target.removeClass( "loading" );
		});

	}

}(jQuery));