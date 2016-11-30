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
function hgbp_is_hierarchy_screen() {
	$screen_slug = hgbp_get_hierarchy_screen_slug();
	return (bool) ( bp_is_groups_component() && bp_is_current_action( $screen_slug ) );
}

/**
 * Get the slug of the hierarchy screen for a group.
 *
 * @since 1.0.0
 *
 * @return string Slug to use as part of the url.
 */
function hgbp_get_hierarchy_screen_slug() {
	return apply_filters( 'hgbp_screen_slug', 'hierarchy' );
}

/**
 * Get the label of the hierarchy screen's navigation item for a group.
 *
 * @since 1.0.0
 *
 * @return string Label to use on the hierarchy navigation item.
 */
function hgbp_get_hierarchy_nav_item_name() {
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
 * @param int    $group_id ID of the group.
 * @param int    $user_id  ID of a user to check group visibility for.
 * @param string $context  See hgbp_include_group_by_context() for description.
 *
 * @return array Array of group objects.
 */
function hgbp_get_child_groups( $group_id = false, $user_id = false, $context = 'normal' ) {
	$groups = array();

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

	// Fetch all child groups.
	$child_args = array(
		'parent_id'   => $group_id,
		'show_hidden' => true,
		'per_page'    => false,
		'page'        => false,
	);
	$children  = groups_get_groups( $child_args );
	$child_ids = wp_list_pluck( $children['groups'], 'id' );

	// If a user ID has been specified, we filter groups accordingly.
	$filter = ( false !== $user_id && ! bp_user_can( $user_id, 'bp_moderate' ) );

	foreach ( $child_ids as $child_id ) {
		// The child groups will be built from the cache.
		$child_group = groups_get_group( $child_id );

		if ( $filter ) {
			if ( hgbp_include_group_by_context( $child_group, $user_id, $context ) ) {
				$groups[] = $child_group;
			}
		} else {
			$groups[] = $child_group;
		}
	}

	return $groups;
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
 * @param int    $group_id ID of the group.
 * @param int    $user_id  ID of a user to check group visibility for.
 * @param string $context  See hgbp_include_group_by_context() for description.
 *
 * @return bool True if true, false if not.
 */
function hgbp_group_has_children( $group_id = false, $user_id = false, $context = 'normal' ) {

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

	// We may need to adjust the context, based on what kind of directory we're on.
	if ( 'directory' == $context ) {
		if ( bp_is_groups_directory() ) {
			// If the directory is AJAX powered, we have to check cookies.
			if ( isset( $_COOKIE['bp-groups-scope'] ) && 'personal' == $_COOKIE['bp-groups-scope'] ) {
				// Hidden groups are included in this directory.
				$context = 'mygroups';
			} else {
				// Hidden groups are not included in standard directories.
				$context = 'exclude_hidden';
			}
		} elseif ( bp_is_user_groups() ) {
			// Hidden groups are included in this directory.
			$context = 'mygroups';
		}
	}

	$children = hgbp_get_child_groups( $group_id, $user_id, $context );
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
 * @param int    $group_id ID of the group.
 * @param int    $user_id  ID of a user to check group visibility for.
 * @param string $context  See hgbp_include_group_by_context() for description.
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

	// Prepare the return set.
	$groups = array();
	// If a user ID has been specified, we filter hidden groups accordingly.
	$filter = ( false !== $user_id && ! bp_user_can( $user_id, 'bp_moderate' ) );

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
		// Reset parents array to rebuild for next round.
		$parents = array();
		foreach ( $children['groups'] as $group ) {
			if ( $filter ) {
				if ( hgbp_include_group_by_context( $group, $user_id, $context ) ) {
					$groups[] = $group;
					$parents[] = $group->id;
				}
			} else {
				$groups[] = $group;
				$parents[] = $group->id;
			}
		}
	}

	return $groups;
}

/**
 * Determine whether a group should be included in results sets for a
 * user in a specific context.
 *
 * @since 1.0.0
 *
 * @param object $group   BP_Groups_Group object to check.
 * @param int    $user_id ID of a user to check group visibility for.
 * @param string $context 'normal' filters hidden groups only that the user doesn't belong to.
 *                        'activity' includes only groups for which the user should see
 *                        the activity streams.
 *                        'exclude_hidden' filters all hidden groups out (for directories).
 *
 * @return bool True if group meets context requirements.
 */
function hgbp_include_group_by_context( $group = false, $user_id = false, $context = 'normal' ) {
	$include = false;
	if ( ! isset( $group->id ) ) {
		return $include;
	}
	/*
	 * 'exclude_hidden' is useful on directories, where hidden groups
	 * are excluded by BP.
	 */
	if ( 'exclude_hidden' == $context ) {
		if ( 'hidden' != $group->status ) {
			$include = true;
		}
	/*
	 * 'exclude_private' includes only groups for which the user can view the activity streams.
	 */
	} elseif ( 'exclude_private' == $context ) {
		// For activity stream inclusion, require public status or membership.
		if ( 'public' == $group->status || groups_is_user_member( $user_id, $group->id ) ) {
			$include = true;
		}
	/*
	 * 'mygroups' is useful on user-specific directories, where only groups the
	 * user belongs to are returned, and the group status is irrelevant.
	 */
	} elseif ( 'mygroups' == $context ) {
		if ( groups_is_user_member( $user_id, $group->id ) ) {
			$include = true;
		}
	/*
	 * 'activity' includes only groups for which the user can view the activity streams.
	 */
	} elseif ( 'activity' == $context ) {
		// For activity stream inclusion, require public status or membership.
		if ( 'public' == $group->status || groups_is_user_member( $user_id, $group->id ) ) {
			$include = true;
		}
	} elseif ( 'normal' == $context ) {
		if ( 'hidden' != $group->status || groups_is_user_member( $user_id, $group->id ) ) {
			$include = true;
		}
	}

	/**
	 * Filters whether this group should be included for this user and context combination.
	 *
	 * @since 1.0.0
	 *
	 * @param bool            $include Whether to include this group.
	 * @param BP_Groups_Group $group   The group object in question.
	 * @param int             $user_id ID of user to check.
	 * @param string          $user_id Current context.
	 */

	return apply_filters( 'hgbp_include_group_by_context', $include, $group, $user_id, $context );
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
 * @param int    $group_id ID of the group.
 * @param int    $user_id  ID of a user to check group visibility for.
 * @param string $context  See hgbp_include_group_by_context() for description.
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
		if ( ! hgbp_include_group_by_context( $parent_group, $user_id, $context ) ) {
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
	if ( ! in_array( $value, $valid_options, true ) ) {
		$value = 'noone';
	}
	return $value;
}

/**
 * Fetch and parse the saved global settings.
 *
 * @since 1.0.0
 *
 * @return string|bool "yes" or "no" if it's set, false if unknown.
 */
function hgbp_get_global_activity_setting() {
	$option = get_option( 'hgbp-include-activity-from-relatives' );
	return hgbp_sanitize_include_setting( $option );
}

/**
 * Fetch and parse the saved global settings.
 *
 * @since 1.0.0
 *
 * @return string Level of enforcement for overriding the default settings.
 */
function hgbp_get_global_activity_enforce_setting() {
	$option = get_option( 'hgbp-include-activity-enforce' );
	return hgbp_sanitize_include_setting_enforce( $option );
}

/**
 * Fetch and parse the saved global settings.
 *
 * @since 1.0.0
 *
 * @return bool Which members of a group are allowed to associate subgroups with it.
 */
function hgbp_get_directory_as_tree_setting() {
	return (bool) get_option( 'hgbp-groups-directory-show-tree' );
}

/**
 * Filter the syndication enforcement setting against a whitelist.
 *
 * @since 1.0.0
 *
 * @return string Level of enforcement for overriding the default settings.
 */
function hgbp_sanitize_include_setting( $value ) {
	$valid = array(
		'include-from-parents',
		'include-from-children',
		'include-from-both',
		'include-from-none'
	);
	if ( ! in_array( $value, $valid, true ) ) {
		$value = 'include-from-none';
	}
	return $value;
}

/**
 * Filter the syndication enforcement setting against a whitelist.
 *
 * @since 1.0.0
 *
 * @return string Level of enforcement for overriding the default settings.
 */
function hgbp_sanitize_include_setting_enforce( $value ) {
	$valid = array(
		'group-admins',
		'site-admins',
		'strict'
	);
	if ( ! in_array( $value, $valid, true ) ) {
		$value = 'strict';
	}
	return $value;
}

/**
 * Should a group's activity stream include parent or child group activity?
 *
 * @since 1.0.0
 *
 * @param int $group_id Group to fetch setting for.
 *
 * @return string Setting to use.
 */
function hgbp_group_include_hierarchical_activity( $group_id = 0 ) {
	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}
	$include = false;

	/*
	 * First, we check which setting has priority.
	 */
	$enforce = hgbp_get_global_activity_enforce_setting();
	if ( 'site-admins' == $enforce || 'group-admins' == $enforce ) {
		// Groups can override, so check the group's raw setting first.
		$include = groups_get_groupmeta( $group_id, 'hgbp-include-activity-from-relatives' );

		if ( $include ) {
			// Only run this if not empty. We want to pass empty values to the next check.
			$include = hgbp_sanitize_include_setting( $include );
		}
	}

	// If include hasn't yet been set, check the global setting.
	if ( ! $include ) {
		$include = hgbp_get_global_activity_setting();
	}

	return apply_filters( 'hgbp_group_include_hierarchical_activity', $include, $group_id );
}
