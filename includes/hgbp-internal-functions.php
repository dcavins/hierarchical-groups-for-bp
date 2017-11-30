<?php
/**
 * Utility functions for the plugin in the global scope.
 * These are used internally, but are probably not interesting for users
 * of the plugin.
 *
 * @package   HierarchicalGroupsForBP
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2016 David Cavins
 */

/**
 * Get the slug of the hierarchy screen for a group.
 *
 * @since 1.0.0
 *
 * @return string Slug to use as part of the url.
 */
function hgbp_get_hierarchy_screen_slug() {
	/**
	 * Filters the slug used for the hierarchy screen for a group.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Slug to use.
	 */
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
	// Check for a saved option for this string first.
	$name = bp_get_option( 'hgbp-group-tab-label' );
	// Next, allow translations to be applied.
	if ( empty( $name ) ) {
		$name = _x( 'Hierarchy %s', 'Label for group navigation tab. %s will be replaced with the number of child groups.', 'hierarchical-groups-for-bp' );
	}
	/*
	 * Apply the number of groups indicator span.
	 * Don't run if we don't know the group ID.
	 */
	if ( $group_id = bp_get_current_group_id() ) {
		$name = sprintf( $name, '<span>' . number_format( hgbp_group_has_children( $group_id, bp_loggedin_user_id(), 'exclude_hidden' ) ) . '</span>' );
	}
	/**
	 * Filters the label of the hierarchy screen's navigation item for a group.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value    Label to use.
	 * @param int    $group_id ID of the current group.
	 */
	return apply_filters( 'hgbp_group_tab_label', $name, $group_id );
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

	if ( current_user_can( 'bp_moderate' ) ) {
		$include = true;
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
	 * 'activity' includes only groups for which the user can view the activity streams.
	 */
	} elseif ( 'activity' == $context ) {
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

	/**
	 * Filters whether a group's activity stream should include parent or
	 * child group activity.
	 *
	 * @since 1.0.0
	 *
	 * @param string $include  Whether to include this group.
	 * @param int    $group_id ID of the group to check.
	 */
	return apply_filters( 'hgbp_group_include_hierarchical_activity', $include, $group_id );
}

/* Plugin settings management *************************************************/

/**
 * Fetch and parse the saved global settings.
 *
 * @since 1.0.0
 *
 * @return bool Which members of a group are allowed to associate subgroups with it.
 */
function hgbp_get_directory_as_tree_setting() {
	return (bool) bp_get_option( 'hgbp-groups-directory-show-tree' );
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

	return hgbp_sanitize_subgroup_creators_setting( $value );
}

/**
 * Filter the syndication enforcement setting against a whitelist.
 *
 * @since 1.0.0
 *
 * @return string Level of enforcement for overriding the default settings.
 */
function hgbp_sanitize_subgroup_creators_setting( $value = 'noone' ) {
	$valid = array( 'loggedin', 'member', 'mod', 'admin', 'noone' );
	if ( ! in_array( $value, $valid, true ) ) {
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
	$option = bp_get_option( 'hgbp-include-activity-from-relatives', 'include-from-none' );
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
	$option = bp_get_option( 'hgbp-include-activity-enforce', 'strict' );
	return hgbp_sanitize_include_setting_enforce( $option );
}

/**
 * Filter the syndication enforcement setting against a whitelist.
 *
 * @since 1.0.0
 *
 * @return string Level of enforcement for overriding the default settings.
 */
function hgbp_sanitize_include_setting( $value = 'include-from-none' ) {
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
function hgbp_sanitize_include_setting_enforce( $value = 'strict' ) {
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
 * Fetch and parse the saved setting for what's included on a single group's
 * hierarchy screen.
 *
 * @since 1.0.0
 *
 * @return array Which sections to include on the hierarchy screen.
 */
function hgbp_get_group_hierarchy_screen_contents_setting() {
	$option = bp_get_option( 'hgbp-group-hierarchy-screen-contents', '' );
	return hgbp_sanitize_hierarchy_screen_contents_setting( $option );
}

/**
 * Filter the hierarchy screen contents setting.
 *
 * @since 1.0.0
 *
 * @return array Which sections to include.
 */
function hgbp_sanitize_hierarchy_screen_contents_setting( $value = null ) {
	if ( ! is_array( $value ) ) {
		// Useful defaults.
		$value = array(
			'ancestors' => 1,
			'siblings'  => 0,
			'children'  => 1
		);
	}
	$sections = array(
		'ancestors' => 0,
		'siblings'  => 0,
		'children'  => 0
	);

	// Null or zero value means that section isn't selected
	foreach ( $sections as $key => $enabled ) {
		if ( ! empty( $value[$key] ) ) {
			$sections[$key] = 1;
		}
	}

	return $sections;
}
