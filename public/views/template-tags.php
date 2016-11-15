<?php
/**
 * @package   HierarchicalGroupsForBP
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2016 David Cavins
 */

/**
 * Output the permalink breadcrumbs for the current group in the loop.
 *
 * @since 1.0.0
 *
 * @param object|bool $group Optional. Group object.
 *                           Default: current group in loop.
 * @param string      $separator String to place between group links.
 */
function hgbp_group_permalink_breadcrumbs( $group = false, $separator = ' / ' ) {
	echo hgbp_get_group_permalink_breadcrumbs( $group, $separator );
}
	/**
	 * Return the permalink breadcrumbs for the current group in the loop.
	 *
	 * @since 1.0.0
	 *
	 * @param object|bool $group Optional. Group object.
	 *                           Default: current group in loop.
     * @param string      $separator String to place between group links.
     *
	 * @return string
	 */
	function hgbp_get_group_permalink_breadcrumbs( $group = false, $separator = ' / ' ) {
		global $groups_template;

		if ( empty( $group ) ) {
			$group = $groups_template->group;
		}
		$user_id = bp_loggedin_user_id();

		// Create the base group's entry.
		$item        = '<a href="' . bp_get_group_permalink( $group ) . '">' . bp_get_group_name( $group ) . '</a>';
		$breadcrumbs = array( $item );
		$parent_id   = hgbp_get_parent_group_id( $group->id, $user_id );

		// Add breadcrumbs for the ancestors.
		while ( $parent_id ) {
			$parent_group  = groups_get_group( $parent_id );
			$breadcrumbs[] = '<a href="' . bp_get_group_permalink( $parent_group ) . '">' . bp_get_group_name( $parent_group ) . '</a>';
			$parent_id     = hgbp_get_parent_group_id( $parent_group->id, $user_id );
		}

		$breadcrumbs = implode( $separator, array_reverse( $breadcrumbs ) );

		/**
		 * Filters the breadcrumb trail for the current group in the loop.
		 *
		 * @since 1.0.0
		 *
		 * @param string          $breadcrumb String of breadcrumb links.
		 * @param BP_Groups_Group $group      Group object.
		 */
		return apply_filters( 'hgbp_get_group_permalink_breadcrumbs', $breadcrumbs, $group );
	}