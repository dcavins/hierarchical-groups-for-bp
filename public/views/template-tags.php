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
		$item        = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( bp_get_group_name( $group ) ) . '</a>';
		$breadcrumbs = array( $item );
		$parent_id   = hgbp_get_parent_group_id( $group->id, $user_id );

		// Add breadcrumbs for the ancestors.
		while ( $parent_id ) {
			$parent_group  = groups_get_group( $parent_id );
			$breadcrumbs[] = '<a href="' . esc_url( bp_get_group_permalink( $parent_group ) ) . '">' . esc_html( bp_get_group_name( $parent_group ) ) . '</a>';
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

/**
 * Output the URL of the hierarchy page of the current group in the loop.
 *
 * @since 1.0.0
 */
function hgbp_group_hierarchy_permalink( $group = false ) {
	echo esc_url( hgbp_get_group_hierarchy_permalink( $group ) );
}

	/**
	 * Generate the URL of the hierarchy page of the current group in the loop.
	 *
	 * @since 1.0.0
	 *
	 * @param object|bool $group Optional. Group object.
	 *                           Default: current group in loop.
	 * @return string
	 */
	function hgbp_get_group_hierarchy_permalink( $group = false ) {
		global $groups_template;

		if ( empty( $group ) ) {
			$group =& $groups_template->group;
		}

		// Filter the slug via the 'hgbp_screen_slug' filter.
		return trailingslashit( bp_get_group_permalink( $group ) . hgbp_get_hierarchy_screen_slug() );
	}

/**
 * Output the upper pagination block for a group directory list.
 *
 * @since 1.0.0
 */
function hgbp_groups_loop_pagination_top() {
	return hgbp_groups_loop_pagination( 'top' );
}

/**
 * Output the lower pagination block for a group directory list.
 *
 * @since 1.0.0
 */
function hgbp_groups_loop_pagination_bottom() {
	return hgbp_groups_loop_pagination( 'bottom' );
}

	/**
	 * Output the pagination block for a group directory list.
	 *
	 * @param string $location Which pagination block to produce.
	 *
	 * @since 1.0.0
	 */
	function hgbp_groups_loop_pagination( $location = 'top' ) {
		if ( 'top' != $location ) {
			$location = 'bottom';
		}

		// Pagination needs to be "no-ajax" on the hierarchy screen.
		$class = '';
		if ( hgbp_is_hierarchy_screen() ) {
			$class = ' no-ajax';
		}

		/*
		 * Return typical pagination on the main group directory first load and the
		 * hierarchy screen for a single group. However, when expanding the tree,
		 * we need to not use pagination, because it conflicts with the main list's
		 * pagination. Instead, show the first 20 and provide a link to the rest.
		 */
		?>
		<div id="pag-<?php echo $location; ?>" class="pagination<?php echo $class; ?>">

			<div class="pag-count" id="group-dir-count-<?php echo $location; ?>">

				<?php bp_groups_pagination_count(); ?>

			</div>

			<?php
			// Check for AJAX requests for the child groups toggle.
			// Provide a link to the parent group's hierarchy screen.
			if ( isset( $_REQUEST['action'] )
				&& 'hgbp_get_child_groups' == $_REQUEST['action']
				&& ! empty( $_REQUEST['parent_id'] )
				&& ( $parent_group = groups_get_group( (int) $_REQUEST['parent_id'] ) )
				&& hgbp_include_group_by_context( $parent_group, bp_loggedin_user_id(), 'normal' )
				) :
			?>
				<a href="<?php hgbp_group_hierarchy_permalink( $parent_group ); ?>" class="view-all-child-groups-link"><?php
					// Check for a saved option for this string first.
					$label = get_option( 'hgbp-directory-child-group-view-all-link' );
					// Next, allow translations to be applied.
					if ( empty( $label ) ) {
						$label = __( 'View all child groups of %s.', 'hierarchical-groups-for-bp' );
					}
					$label = sprintf( $label, bp_get_group_name( $parent_group ) );

					/**
					 * Filters the "view all subgroups" link text for a group.
					 *
					 * @since 1.0.0
					 *
					 * @param string          $value        Label to use.
					 * @param BP_Groups_Group $parent_group Parent group object.
					 */
					echo esc_html( apply_filters( 'hgbp_directory_child_group_view_all_link', $label, $parent_group ) );
				?></a>
			<?php else : ?>

				<div class="pagination-links" id="group-dir-pag-<?php echo $location; ?>">

					<?php bp_groups_pagination_links(); ?>

				</div>

				<?php
			endif; ?>
		</div>
		<?php
	}

/**
 * Output the child groups toggle and container for a group directory list.
 *
 * @since 1.0.0
 */
function hgbp_child_group_section() {
	global $groups_template;
	/*
	 * Store the $groups_template global, so that the wrapper group
	 * can be restored after the has_groups() loop is completed.
	 */
	$parent_groups_template = $groups_template;

	/*
	 * For the most accurate results, only show the 'show child groups' toggle
	 * if groups would be shown by a bp_has_groups() loop. Keep the args simple
	 * to avoid unnecessary joins and hopefully hit the BP_Groups_Group::get()
	 * cache.
	 */
	$has_group_args = array(
		'parent_id'          => bp_get_group_id(),
		'orderby'            => 'date_created',
		'update_admin_cache' => false,
		'per_page'           => false,
	);
	if ( bp_has_groups( $has_group_args ) ) :
		global $groups_template;
		$number_children = $groups_template->total_group_count;

		// Put the parent $groups_template back.
		$groups_template = $parent_groups_template;
		?>
		<div class="child-groups-container">
			<a href="<?php esc_url( hgbp_group_hierarchy_permalink() ); ?>" class="toggle-child-groups" data-group-id="<?php bp_group_id(); ?>" aria-expanded="false" aria-controls="child-groups-of-<?php bp_group_id(); ?>"><?php
				// Check for a saved option first.
				$label = bp_get_option( 'hgbp-directory-child-group-section-label' );
				// Next, allow translations to be applied.
				if ( empty( $label ) ) {
					$label = _x( 'Child groups %s', 'Label for the control on hierarchical group directories that shows or hides the child groups. %s will be replaced with the number of child groups.', 'hierarchical-groups-for-bp' );
				}
				$label = sprintf( esc_html( $label ), '<span class="count">' . $number_children . '</span>' );

				/**
				 * Filters the "Child groups" toggle text for a group's entry on the
				 * hierarchical groups directory.
				 *
				 * @since 1.0.0
				 *
				 * @param string $value Label to use.
				 */
				echo apply_filters( 'hgbp_directory_child_group_section_header_label', $label );
			?></a>
			<div class="child-groups" id="child-groups-of-<?php bp_group_id(); ?>"></div>
		</div>
	<?php else :
		$groups_template = $parent_groups_template;
	endif;
}
