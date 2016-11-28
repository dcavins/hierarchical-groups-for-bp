<?php
/**
 * @package   HierarchicalGroupsForBP
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2016 David Cavins
 */

class Hierarchical_Groups_for_BP extends BP_Group_Extension {

	function __construct() {
		$nav_item_visibility = $this->nav_item_visibility();

		$args = array(
			'slug'              => hgbp_get_hierarchy_screen_slug(),
			'name'              => hgbp_get_hierarchy_nav_item_name(),
			'nav_item_position' => 61,
			'access'            => $nav_item_visibility,
			'show_tab'          => $nav_item_visibility,
			'screens' => array(
				'admin' => array(
					'metabox_context' => 'side',
				),
			),
		);
		parent::init( $args );
	}

	/**
	 * Output the code for the front-end screen for a single group.
	 *
	 * @since 1.0.0
	 */
	function display( $group_id = null ) {
		global $groups_template;
		$parent_groups_template = $groups_template;

		/*
		 * groups/single/subgroups-loop is a shell that calls groups-loop-tree,
		 * to make it possible to override the subgroups loop using the
		 * BuddyPress template hierarchy.
		 */
		bp_get_template_part( 'groups/single/subgroups-loop' );

		/*
		 * Reset the $groups_template global, so that the wrapper group
		 * is restored after the has_groups() loop is completed.
		 */
		$groups_template = $parent_groups_template;
	}

	/**
	 * Output the code for the settings screen, the create step form
	 * and the wp-admin single group edit screen meta box.
	 *
	 * @since 1.0.0
	 */
	function settings_screen( $group_id = null ) {
		?>
		<label for="parent-id"><?php _ex( 'Parent Group', 'Label for the parent group select on a single group manage screen', 'hierarchical-groups-for-bp' ); ?></label>
		<?php
		$current_parent_group_id = hgbp_get_parent_group_id( $group_id );
		$possible_parent_groups = hgbp_get_possible_parent_groups( $group_id, bp_loggedin_user_id() );

		if ( $possible_parent_groups ) :
			?>
			<select id="parent-id" name="parent-id" autocomplete="off">
				<option value="0" <?php selected( 0, $current_parent_group_id ); ?>><?php echo _x( 'None selected', 'The option that sets a group to be a top-level group and have no parent.', 'hierarchical-groups-for-bp' ); ?></option>
			<?php foreach ( $possible_parent_groups as $possible_parent_group ) {
				?>
				<option value="<?php echo $possible_parent_group->id; ?>" <?php selected( $current_parent_group_id, $possible_parent_group->id ); ?>><?php echo $possible_parent_group->name; ?></option>
				<?php
			}
			?>
			</select>
			<?php
		else :
			?>
			<p><?php _e( 'There are no groups available to be a parent to this group.', 'hierarchical-groups-for-bp' ); ?></p>
			<?php
		endif;
		?>

		<fieldset class="hierarchy-allowed-subgroup-creators radio">

			<legend><?php _e( 'Which members of this group are allowed to select this group as the parent group of another group?', 'hierarchical-groups-for-bp' ); ?></legend>

			<?php
			$subgroup_creators = hgbp_get_allowed_subgroup_creators();

			// If only site admins can create groups, don't display impossible options.
			if ( ! bp_restrict_group_creation() ) :
			?>
				<label for="allowed-subgroup-creators-members"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-members" value="member" <?php checked( $subgroup_creators, 'member' ); ?> /> <?php _e( 'All group members', 'hierarchical-groups-for-bp' ); ?></label>

				<label for="allowed-subgroup-creators-mods"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-mods" value="mod" <?php checked( $subgroup_creators, 'mod' ); ?> /> <?php _e( 'Group admins and mods only', 'hierarchical-groups-for-bp' ); ?></label>

				<label for="allowed-subgroup-creators-admins"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-admins" value="admin" <?php checked( $subgroup_creators, 'admin' ); ?> /> <?php _e( 'Group admins only', 'hierarchical-groups-for-bp' ); ?></label>
			<?php endif; ?>

			<label for="allowed-subgroup-creators-noone"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-noone" value="noone" <?php checked( $subgroup_creators, 'noone' ); ?> /> <?php _e( 'No one', 'hierarchical-groups-for-bp' ); ?></label>
		</fieldset>

			<?php
			// Only display the syndication sections if the current user can change it.
			$can_change_synd_from_parents  = bp_current_user_can( 'hgbp_change_include_activity_from_parents' );
			$can_change_synd_from_children = bp_current_user_can( 'hgbp_change_include_activity_from_children' );

			if ( $can_change_synd_from_parents ) :
				$include_from_parents  = groups_get_groupmeta( $group_id, "hgbp-include-activity-from-parents" );
				if ( ! $include_from_parents ) {
					$include_from_parents = 'inherit';
				}
			?>
				<fieldset class="hierarchy-syndicate-activity checkbox">
					<legend><?php _e( 'Include activity from parent groups in this group&rsquo;s activity stream', 'hierarchical-groups-for-bp' ); ?></legend>

					<label for="hgbp-include-activity-from-parents-yes"><input type="radio" name="hgbp-include-activity-from-parents" id="hgbp-include-activity-from-parents-yes" value="yes" <?php checked( $include_from_parents, 'yes' ); ?> /> <?php _e( 'Include activity from parent groups.', 'hierarchical-groups-for-bp' ); ?></label>
					<label for="hgbp-include-activity-from-parents-no"><input type="radio" name="hgbp-include-activity-from-parents" id="hgbp-include-activity-from-parents-no" value="no" <?php checked( $include_from_parents, 'no' ); ?> /> <?php _e( 'Do not include activity from parent groups.', 'hierarchical-groups-for-bp' ); ?></label>
					<label for="hgbp-include-activity-from-parents-inherit"><input type="radio" name="hgbp-include-activity-from-parents" id="hgbp-include-activity-from-parents-inherit" value="inherit" <?php checked( $include_from_parents, 'inherit' ); ?> /> <?php _e( 'Inherit global setting.', 'hierarchical-groups-for-bp' ); ?></label>

				</fieldset>
			<?php endif; ?>

			<?php if ( $can_change_synd_from_children ) :
				$include_from_children = groups_get_groupmeta( $group_id, "hgbp-include-activity-from-children" );
				if ( ! $include_from_children ) {
					$include_from_children = 'inherit';
				}
			?>
				<fieldset class="hierarchy-syndicate-activity checkbox">
					<legend><?php _e( 'Include activity from child groups in this group&rsquo;s activity stream', 'hierarchical-groups-for-bp' ); ?></legend>

					<label for="hgbp-include-activity-from-children-yes"><input type="radio" name="hgbp-include-activity-from-children" id="hgbp-include-activity-from-children-yes" value="yes" <?php checked( $include_from_children, 'yes' ); ?> /> <?php _e( 'Include activity from child groups.', 'hierarchical-groups-for-bp' ); ?></label>
					<label for="hgbp-include-activity-from-children-no"><input type="radio" name="hgbp-include-activity-from-children" id="hgbp-include-activity-from-children-no" value="no" <?php checked( $include_from_children, 'no' ); ?> /> <?php _e( 'Do not include activity from child groups.', 'hierarchical-groups-for-bp' ); ?></label>
					<label for="hgbp-include-activity-from-children-inherit"><input type="radio" name="hgbp-include-activity-from-children" id="hgbp-include-activity-from-children-inherit" value="inherit" <?php checked( $include_from_children, 'inherit' ); ?> /> <?php _e( 'Inherit global setting.', 'hierarchical-groups-for-bp' ); ?></label>
				</fieldset>
			<?php endif; ?>
	<?php
	}

	/**
	 * Save parent association and subgroup creators set on settings screen.
	 *
	 * @since 1.0.0
	 */
	function settings_screen_save( $group_id = null ) {
		$group_object = groups_get_group( $group_id );
		$parent_id = isset( $_POST['parent-id'] ) ? $_POST['parent-id'] : 0;
		if ( $group_object->parent_id != $parent_id ) {
			$group_object->parent_id = $parent_id;
			$group_object->save();
		}

		$allowed_creators = isset( $_POST['allowed-subgroup-creators'] ) ? $_POST['allowed-subgroup-creators'] : '';
		$subgroup_creators = groups_update_groupmeta( $group_id, 'hgbp-allowed-subgroup-creators', $allowed_creators );

		// Syndication settings.
		$inputs_to_save = array( 'hgbp-include-activity-from-children', 'hgbp-include-activity-from-parents' );
		foreach ( $inputs_to_save as $posted ) {
			if ( isset( $_POST[ $posted ] ) ) {
				if ( in_array( $_POST[ $posted ], array( 'yes', 'no' ), true ) ) {
					$success = groups_update_groupmeta( $group_id, $posted, $_POST[ $posted ] );
				} else {
					// The other option is "inherit", so let's delete the group meta.
					$success = groups_delete_groupmeta( $group_id, $posted );
				}
			}
		}
	}

	/**
	 * Determine whether the group nav item should show up for the current user.
	 *
	 * @since 1.0.0
	 */
	function nav_item_visibility() {
		$nav_item_vis = 'noone';
		$group_id     = bp_get_current_group_id();

		// The nav item should only be enabled when the groups loop would return subgroups.
		$has_children = hgbp_group_has_children( $group_id, bp_loggedin_user_id(), 'exclude_hidden' );
		if ( $has_children ) {
			// If this group is not public, make the tab visible to members only.
			if ( 'public' == bp_get_group_status( groups_get_group( $group_id ) ) ) {
				$nav_item_vis = 'anyone';
			} else {
				$nav_item_vis = 'member';
			}
		}

		/**
		 * Fires before the calculated navigation item visibility is passed back to the constructor.
		 *
		 * @since 1.0.0
		 *
		 * @param string $nav_item_vis Access and visibility level.
		 * @param int    $group_id     ID of the current group.
		 */
		return apply_filters( 'hgbp_nav_item_visibility', $nav_item_vis, $group_id );
	}

}
bp_register_group_extension( 'Hierarchical_Groups_for_BP' );