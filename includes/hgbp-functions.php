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
 * @param  string $context  'normal' filters hidden groups only; 'activity' includes
 *                          only groups for which the user should see the activity streams.
 *
 * @return array Array of group objects.
 */
function hgbp_get_descendent_groups( $group_id = false, $user_id = false, $context = 'normal' ) {
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
	$cache_key      = 'descendants_of_' . $group_id;
	$descendant_ids = bp_core_get_incremented_cache( $cache_key, 'hgbp' );

	if ( false === $descendant_ids ) {
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

		// Save the IDs to the cache to avoid duplicate requests.
		$descendant_ids = wp_list_pluck( $descendants, 'id' );
		bp_core_set_incremented_cache( $cache_key, 'hgbp', $descendant_ids );
	}

	// Prepare the return set.
	$groups = array();
	// If a user ID has been specified, we filter hidden groups accordingly.
	$run_filters = ( false !== $user_id && ! bp_user_can( $user_id, 'bp_moderate' ) );

	foreach ( $descendant_ids as $group_id ) {
		// Check whether the user should be able to see this group.
		$group = groups_get_group( $group_id );

		if ( $run_filters ) {
			// @TODO: Use group capabilities for this when possible.
			if ( 'activity' == $context ) {
				// For activity stream inclusion, require public status or membership.
				if ( 'public' == $group->status || groups_is_user_member( $user_id, $group->id ) ) {
					$groups[$group_id] = $group;
				}
			} else {
				// For unspecified uses, hide hidden groups.
				if ( 'hidden' != $group->status || groups_is_user_member( $user_id, $group->id ) ) {
					$groups[$group_id] = $group;
				}
			}
		} else {
			$groups[$group_id] = $group;
		}
	}

	return $groups;
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
 * @param  int    $group_id ID of the group.
 * @param  int    $user_id  ID of a user to check group visibility for.
 * @param  string $context  'normal' filters hidden groups only; 'activity' includes
 *                          only groups for which the user should see the activity streams.
 *
 * @return int ID of parent group.
 */
function hgbp_get_parent_group_id( $group_id = false, $user_id = false, $context = 'normal' ) {
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
		if ( 'activity' == $context ) {
			// For activity stream inclusion, require public status or membership.
			if ( 'public' != $parent_group->status && ! groups_is_user_member( $user_id, $parent_group->id ) ) {
				// If the group's activity stream is not visible to the user, break the chain.
				$parent_id = 0;
			}
		} else {
			// For unspecified uses, hide hidden groups.
			if ( 'hidden' == $parent_group->status && ! groups_is_user_member( $user_id, $parent_group->id ) ) {
				// If the group is not visible to the user, break the chain.
				$parent_id = 0;
			}
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
 * @param  string $context  'normal' filters hidden groups only; 'activity' includes
 *                          only groups for which the user should see the activity streams.
 *
 * @return array Array of group IDs.
 */
function hgbp_get_ancestor_group_ids( $group_id = false, $user_id = false, $context = 'normal' ) {
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
		$parent_group_id = hgbp_get_parent_group_id( $group_id, $user_id, $context );
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

/**
 * Create the hierarchical-style URL for a subgroup: groups/parent/child/action.
 *
 * @since 1.0.0
 *
 * @param  int   $group_id ID of the group.
 *
 * @return string Slug for group, empty if no slug found.
 */
function hgbp_build_hierarchical_slug( $group_id = 0 ) {

	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}
	if ( ! $group_id ) {
		return '';
	}

	$group = groups_get_group( $group_id );
	$path = array( bp_get_group_slug( $group ) );

	while ( $group->parent_id != 0 ) {
		$group  = groups_get_group( $group->parent_id );
		$path[] = bp_get_group_slug( $group );
	}

	return implode( '/', array_reverse( $path ) );
}

/**
 * Fetch and parse the saved global settings.
 *
 * @since 1.0.0
 *
 * @param string $setting Which direction to check.
 *
 * @return string|bool "yes" or "no" if it's set, false if unknown.
 */
function hgbp_get_global_activity_setting( $setting = 'children' ) {
	if ( $setting !== 'children' ) {
		$setting = 'parents';
	}
	return get_option( "hgbp-include-activity-from-{$setting}" );
}

/**
 * Fetch and parse the saved global settings.
 *
 * @since 1.0.0
 *
 * @param string $setting Which direction to check.
 *
 * @return string Level of enforcement for overriding the default settings.
 */
function hgbp_get_global_activity_enforce_setting( $setting = 'children' ) {
	if ( $setting !== 'children' ) {
		$setting = 'parents';
	}
	$option = get_option( "hgbp-include-activity-from-{$setting}-enforce" );
	return hgbp_sanitize_group_setting_enforce( $option );
}

/**
 * Fetch and parse the saved global settings.
 *
 * @since 1.0.0
 *
 * @param int $group_id Which group ID's meta to fetch.
 *
 * @return string Which members of a group are allowed to associate subgroups with it.
 */
function hgbp_get_allowed_subgroup_creators( $group_id = 0 ) {
	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}

	$value = groups_get_groupmeta( $group_id, 'hgbp-allowed-subgroup-creators' );
	$valid_options = array( 'member', 'mod', 'admin', 'noone' );
	if ( ! in_array( $value, $valid_enforce, true ) ) {
		$value = 'noone';
	}
	return $value;
}

/**
 * Fetch and parse the saved global settings.
 *
 * @since 1.0.0
 *
 * @return bool Which members of a group are allowed to associate subgroups with it.
 */
function hgbp_get_global_directory_setting() {
	return (bool) get_option( 'hgbp-groups-directory-show-tree' );
}

/**
 * Filter the syndication enforcement setting against a whitelist.
 *
 * @since 1.0.0
 *
 * @return string Level of enforcement for overriding the default settings.
 */
function hgbp_sanitize_group_setting_enforce( $value ) {
	$valid_enforce = array( 'group-admins', 'site-admins', 'strict' );
	if ( ! in_array( $value, $valid_enforce, true ) ) {
		$value = 'strict';
	}
	return $value;
}

/**
 * Should a group's activity stream include parent or child group activity?
 *
 * @since 1.0.0
 *
 * @param string $setting Which direction to check.
 *
 * @return bool True to include the related activity.
 */
function hgbp_group_include_hierarchical_activity( $group_id = 0, $setting = 'children' ) {
	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}
	if ( $setting !== 'children' ) {
		$setting = 'parents';
	}
	$include = false;

	/*
	 * This is a calculated result that has to check the global setting first.
	 * First, we check which setting has priority.
	 */
	$enforce = hgbp_get_global_activity_enforce_setting( $setting );
	if ( 'site-admins' == $enforce || 'group-admins' == $enforce ) {
		// Check the group's raw setting first.
		$include = groups_get_groupmeta( $group_id, "hgbp-include-activity-from-{$setting}" );
		// If unknown (neither yes nor no), fall back to the global setting.
		if ( ! $include ) {
			$include = hgbp_get_global_activity_setting( $include );
		}
	} else {
		/*
		 * $enforce is strict or unknown.
		 * We know the global setting is the only setting to consider.
		 */
		$include = hgbp_get_global_activity_setting( $setting );
	}

	// Convert a yes, no or false to a boolean.
	$include = ( 'yes' == $include ) ? true : false;

	return apply_filters( 'hgbp_group_include_hierarchical_activity', $include, $group_id, $setting );
}
