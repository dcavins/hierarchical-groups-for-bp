<?php
/**
 * Functions for the plugin in the global scope.
 * These may be useful for users working on theming or extending the plugin.
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
 * Is this a user's "My Groups" view? This can happen on the main directory or
 * at a user's profile (/members/username/groups/).
 *
 * @since 1.0.0
 *
 * @return bool True if yes.
 */
function hgbp_is_my_groups_view() {
	$retval = false;

	// Could be the user profile groups pane.
	if ( bp_is_user_groups() ) {
		$retval = true;

	// Could be the "my groups" filter on the main directory?
	} elseif ( bp_is_groups_directory() && ( isset( $_COOKIE['bp-groups-scope'] ) && 'personal' == $_COOKIE['bp-groups-scope'] ) ) {
		$retval = true;
	}

	return $retval;
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
	if ( false === $group_id ) {
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

	// If a user ID has been specified, we filter groups accordingly.
	$filter = ( false !== $user_id && ! bp_user_can( $user_id, 'bp_moderate' ) );

	foreach ( $children['groups'] as $child ) {
		if ( $filter ) {
			if ( hgbp_include_group_by_context( $child, $user_id, $context ) ) {
				$groups[] = $child;
			}
		} else {
			$groups[] = $child;
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
	if ( false === $group_id ) {
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
		} else {
			// Fallback: Hidden groups are not included in standard directories.
			$context = 'exclude_hidden';
		}
	}

	$children = hgbp_get_child_groups( $group_id, $user_id, $context );
	return count( $children );
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
	if ( false === $group_id ) {
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
			'parent_id'   => $parents,
			'show_hidden' => true,
			'per_page'    => false,
			'page'        => false,
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
 * Check a slug to see if a child group of a specific parent group exists.
 *
 * Like `groups_get_id()`, but limited to children of a specific group. Avoids
 * slug collisions between group tab names and groups with the same slug.
 * For instance, if there's a unrelated group called "Docs", you want the
 * "docs" tab of a group to ignore that group and return the docs pane for the
 * current group.
 * Caveat: If you create a child group with the same slug as a tab of the parent
 * group, you'll always get the child group.
 *
 * @since 1.0.0
 *
 * @param string $slug      Group slug to check.
 * @param int    $parent_id ID of the parent group.
 *
 * @return int ID of found group.
 */
function hgbp_child_group_exists( $slug, $parent_id = 0 ) {
	if ( empty( $slug ) ) {
		return 0;
	}

	/*
	 * Take advantage of caching in groups_get_groups().
	 */
	$child_id = 0;
	if ( version_compare( bp_get_version(), '2.9', '<' ) ) {
		// Fetch groups with parent_id and loop through looking for a matching slug.
		$child_groups = groups_get_groups( array(
			'parent_id'   => array( $parent_id ),
			'show_hidden' => true,
			'per_page'    => false,
			'page'        => false,
		) );

		foreach ( $child_groups['groups'] as $group ) {
			if ( $slug == $group->slug ) {
				$child_id = $group->id;
				// Stop once we've got a match.
				break;
			}
		}
	} else {
		// BP 2.9 adds "slug" support to groups_get_groups().
		$child_groups = groups_get_groups( array(
			'parent_id'   => array( $parent_id ),
			'slug'        => array( $slug ),
			'show_hidden' => true,
			'per_page'    => false,
			'page'        => false,
		) );

		if ( $child_groups['groups'] ) {
			$child_id = current( $child_groups['groups'] )->id;
		}
	}

	return $child_id;
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
	if ( false === $group_id ) {
		$group_id = bp_get_current_group_id();
		if ( ! $group_id ) {
			// If we can't resolve the group_id, don't proceed.
			return 0;
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
	if ( false === $group_id ) {
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

	$possible_parents = groups_get_groups( $args );
	foreach ( $possible_parents['groups'] as $k => $group ) {
		// Check whether the user can create child groups of this group.
		if ( ! bp_user_can( $user_id, 'create_subgroups', array( 'group_id' => $group->id ) ) ) {
			unset( $possible_parents['groups'][$k] );
		}
	}

	return $possible_parents['groups'];
}
