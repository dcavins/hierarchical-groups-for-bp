<?php
/**
 * Utility functions for the plugin in the global scope.
 *
 * @package   HierarchicalGroupsForBP
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2016 David Cavins
 */

/**
 * Is the current page a group's subgroup directory?
 *
 * Eg http://example.com/groups/mygroup/hierarchy/.
 *
 * @since 1.0.0
 *
 * @return bool True if the current page is a group's directory of subgroups.
 */
function hgbp_is_group_subgroups() {
	$screen_slug = hgbp_get_subgroups_screen_slug();
	return (bool) ( bp_is_groups_component() && bp_is_current_action( $screen_slug ) );
}

/**
 * Get the slug of the hierarchy screen for a group.
 *
 * @since 1.0.0
 *
 * @return string Slug to use as part of the url.
 */
function hgbp_get_subgroups_screen_slug() {
	return apply_filters( 'hgbp_screen_slug', 'hierarchy' );
}

/**
 * Get the label of the hierarchy screen's navigation item for a group.
 *
 * @since 1.0.0
 *
 * @return string Label to use on the hierarchy navigation item.
 */
function hgbp_get_subgroups_nav_item_name() {
	$name = _x( 'Hierarchy', 'Label for group navigation tab', 'hierarchical-groups-for-bp' );
	return apply_filters( 'hgbp_screen_nav_item_name', $name );
}

/**
 * Get the child groups for a specific group.
 *
 * To return all child groups, leave the $user_id parameter empty. To return
 * only those child groups visible to a specific user, specify a $user_id.
 *
 * @since 1.0.0
 *
 * @param  int   $group_id ID of the group.
 * @param  int   $user_id  ID of a user to check group visibility for.
 *
 * @return array Array of group objects.
 */
function hgbp_get_child_groups( $group_id = false, $user_id = false ) {
	$retval = array();

	/*
	 * Passing a group id of 0 would find all top-level groups, which could be
	 * intentional. We only try to find the current group when the $group_id is false.
	 */
	if ( $group_id === false ) {
		$group_id = bp_get_current_group_id();
		if ( ! $group_id ) {
			// If we can't resolve the group_id, don't proceed with a zero value.
			return $retval;
		}
	}

	$child_ids = hgbp_get_child_group_ids( $group_id );

	// If a user ID has been specified, we filter hidden groups accordingly.
	$filter = ( false !== $user_id && ! bp_user_can( $user_id, 'bp_moderate' ) ) ? true : false;

	foreach ( $child_ids as $child_id ) {
		// The child groups will be built from the cache.
		$child_group = groups_get_group( $child_id );
		if ( $filter && 'hidden' == $child_group->status && ! groups_is_user_member( $user_id, $child_id ) ) {
			continue;
		}
		$retval[] = $child_group;
	}

	return $retval;
}

/**
 * Get the child group IDs for a specific group.
 *
 *
 * @since 1.0.0
 *
 * @param  int   $group_id ID of the group.
 *
 * @return array Array of group IDs.
 */
function hgbp_get_child_group_ids( $group_id = false ) {
	/*
	 * Passing a group id of 0 would find all top-level groups, which could be
	 * intentional. We only try to find the current group when the $group_id is false.
	 */
	if ( $group_id === false ) {
		$group_id = bp_get_current_group_id();
		if ( ! $group_id ) {
			// If we can't resolve the group_id, don't proceed with a zero value.
			return array();
		}
	}

	// Check the cache first.
	$cache_key = 'bp_groups_child_groups_of_' . $group_id;
	$child_ids = bp_core_get_incremented_cache( $cache_key, 'hgbp' );

	if ( false === $child_ids ) {
		// Fetch all child groups.
		$child_args = array(
			'parent_id'   => $group_id,
			'show_hidden' => true,
			'per_page'    => false,
			'page'        => false,
		);
		$children  = groups_get_groups( $child_args );
		$child_ids = wp_list_pluck( $children['groups'], 'id' );

		// Set the cache to avoid duplicate requests.
		bp_core_set_incremented_cache( $cache_key, 'hgbp', $child_ids );
	}

	return $child_ids;
}

/**
 * Does a specific group have child groups?
 *
 * To check for the actual existence of child groups, leave the $user_id
 * parameter empty. To check whether any exist that are visible to a user,
 * supply a $user_id.
 *
 * @since 1.0.0
 *
 * @param  int   $group_id ID of the group.
 * @param  int   $user_id  ID of a user to check group visibility for.
 *
 * @return bool True if true, false if not.
 */
function hgbp_group_has_children( $group_id = false, $user_id = false ) {
	/*
	 * Passing a group id of 0 finds all top-level groups, which could be
	 * intentional. Try to find the current group only when the $group_id is false.
	 */
	if ( $group_id === false ) {
		$group_id = bp_get_current_group_id();
		if ( ! $group_id ) {
			// If we can't resolve the group_id, don't proceed with a zero value.
			return false;
		}
	}

	$children = hgbp_get_child_groups( $group_id, $user_id );
	return ! empty ( $children ) ? true : false;
}

/**
 * Get all groups that are descendants of a specific group.
 *
 * To return all descendent groups, leave the $user_id parameter empty. To return
 * only those child groups visible to a specific user, specify a $user_id.
 *
 * @since 1.0.0
 *
 * @param  int   $group_id ID of the group.
 * @param  int   $user_id  ID of a user to check group visibility for.
 *
 * @return array Array of group objects.
 */
function hgbp_get_descendent_groups( $group_id = false, $user_id = false ) {
	/*
	 * Passing a group id of 0 would find all top-level groups, which could be
	 * intentional. We only try to find the current group when the $group_id is false.
	 */
	if ( $group_id === false ) {
		$group_id = bp_get_current_group_id();
		if ( ! $group_id ) {
			// If we can't resolve the group_id, don't proceed with a zero value.
			return array();
		}
	}

	// Check the cache first.
	$cache_key   = 'descendants_of_' . $group_id;
	$descendants = bp_core_get_incremented_cache( $cache_key, 'hgbp' );

	if ( false === $descendants ) {
		// Start from the group specified.
		$parents = array( $group_id );
		$descendants = array();

		// We work down the tree until no new children are found.
		while ( $parents ) {
			// Fetch all child groups.
			$child_args = array(
				'parent_id' => $parents,
				'show_hidden' => true,
			);
			$children = groups_get_groups( $child_args );

			// Add groups to the set of found groups.
			$descendants = array_merge( $descendants, $children['groups'] );

			// Set up the next set of parents.
			$parents = wp_list_pluck( $children['groups'], 'id' );
		}

		// Set the cache to avoid duplicate requests.
		bp_core_set_incremented_cache( $cache_key, 'hgbp', $descendants );
	}

	// If a user ID has been specified, we filter hidden groups accordingly.
	if ( false !== $user_id && ! bp_user_can( $user_id, 'bp_moderate' ) ) {
		foreach ( $descendants as $k => $group ) {
			// Check whether the user should be able to see this group.
			// @TODO: Use group capabilities for this when possible.
			if ( 'hidden' == $group->status && ! groups_is_user_member( $user_id, $group->id ) ) {
				unset( $descendants[$k] );
			}
		}
	}

	return $descendants;
}

/**
 * Get the parent group ID for a specific group.
 *
 * To return the parent group regardless of visibility, leave the $user_id
 * parameter empty. To return the parent only when visible to a specific user,
 * specify a $user_id.
 *
 * @since 1.0.0
 *
 * @param  int   $group_id ID of the group.
 * @param  int   $user_id  ID of a user to check group visibility for.
 *
 * @return int ID of parent group.
 */
function hgbp_get_parent_group_id( $group_id = false, $user_id = false ) {
	/*
	 * Passing a group id of 0 would find all top-level groups, which could be
	 * intentional. We only try to find the current group when the $group_id is false.
	 */
	if ( $group_id === false ) {
		$group_id = bp_get_current_group_id();
		if ( ! $group_id ) {
			// If we can't resolve the group_id, don't proceed with a zero value.
			return array();
		}
	}

	$group     = groups_get_group( $group_id );
	$parent_id = $group->parent_id;

	// If the user is specified, is the parent group visible to that user?
	// @TODO: This could make use of group visibility when available.
	if ( false !== $user_id && ! bp_user_can( $user_id, 'bp_moderate' ) ) {
		$parent_group = groups_get_group( $parent_id );
		if ( 'hidden' == $parent_group->status && ! groups_is_user_member( $user_id, $parent_group->id ) ) {
			// If the group is not visible to the user, break the chain.
			$parent_id = 0;
		}
	}

	return (int) $parent_id;
}

/**
 * Get an array of group ids that are ancestors of a specific group.
 *
 * To return all ancestor groups, leave the $user_id parameter empty. To return
 * only those ancestor groups visible to a specific user, specify a $user_id.
 * Note that if groups the user can't see are encountered, the chain of ancestry
 * is stopped. Also note that the order here is useful: the first element is the
 * parent group id, the second is the grandparent group id and so on.
 *
 * @since 1.0.0
 *
 * @param  int   $group_id ID of the group.
 * @param  int   $user_id  ID of a user to check group visibility for.
 *
 * @return array Array of group objects.
 */
function hgbp_get_ancestor_group_ids( $group_id = false, $user_id = false ) {
	/*
	 * Passing a group id of 0 would find all top-level groups, which could be
	 * intentional. We only try to find the current group when the $group_id is false.
	 */
	if ( $group_id === false ) {
		$group_id = bp_get_current_group_id();
		if ( ! $group_id ) {
			// If we can't resolve the group_id, don't proceed with a zero value.
			return array();
		}
	}

	$ancestors = array();

	// We work up the tree until no new parent is found.
	while ( $group_id ) {
		$parent_group_id = hgbp_get_parent_group_id( $group_id, $user_id );
		if ( $parent_group_id ) {
			$ancestors[] = $parent_group_id;
		}
		// Set a new group id to work from.
		$group_id = $parent_group_id;
	}

	return $ancestors;
}

/**
 * Get an array of possible parent group ids for a specific group and user.
 *
 * To be a candidate for group parenthood, the group cannot be a descendent of
 * this group, and the user must be allowed to create child groups in that group.
 *
 * @since 1.0.0
 *
 * @param  int   $group_id ID of the group.
 * @param  int   $user_id  ID of a user to check group visibility for.
 *
 * @return array Array of group objects.
 */
function hgbp_get_possible_parent_groups( $group_id = false, $user_id = false ) {
	/*
	 * Passing a group id of 0 would find all top-level groups, which could be
	 * intentional. We only try to find the current group when the $group_id is false.
	 */
	if ( false === $group_id ) {
		$group_id = bp_get_current_group_id();
		if ( ! $group_id ) {
			// If we can't resolve the group_id, don't proceed with a zero value.
			return array();
		}
	}

	if ( false === $user_id ) {
		$user_id = bp_loggedin_user_id();
		if ( ! $user_id ) {
			// If we can't resolve the user_id, don't proceed with a zero value.
			return array();
		}
	}

	// First, get a list of descendants (don't pass a user id--we want them all).
	$descendants = hgbp_get_descendent_groups( $group_id );
	$exclude_ids = wp_list_pluck( $descendants, 'id' );
	// Also exclude the current group.
	$exclude_ids[] = $group_id;

	$args = array(
		'orderby'         => 'name',
		'order'           => 'ASC',
		'populate_extras' => false,
		'exclude'         => $exclude_ids, // Exclude descendants and this group.
		'show_hidden'     => true,
		'per_page'        => false, // Do not limit the number returned.
		'page'            => false, // Do not limit the number returned.
	);
	// If the user is not a site admin, limit the set to groups she belongs to.
	if ( ! bp_user_can( $user_id, 'bp_moderate' ) ) {
		$args['user_id'] = $user_id;
	}
	$possible_parents = groups_get_groups( $args );
	foreach ( $possible_parents['groups'] as $k => $group ) {
		// Check whether the user can create child groups of this group.
		if ( ! bp_user_can( $user_id, 'create_subgroups', array( 'group_id' => $group->id ) ) ) {
			unset( $possible_parents['groups'][$k] );
		}
	}

	return $possible_parents['groups'];
}
