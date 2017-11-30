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
			'screens'           => apply_filters( 'hgbp_group_extension_screens_param', array(
				'create' => array(
					'name' => _x( 'Hierarchy', 'Label for group management tab', 'hierarchical-groups-for-bp' ),
				),
				'edit' => array(
					'name' => _x( 'Hierarchy', 'Label for group management tab', 'hierarchical-groups-for-bp' ),
				),
				'admin' => array(
					'metabox_context' => 'side',
					'name' => _x( 'Hierarchy', 'Label for group management tab', 'hierarchical-groups-for-bp' ),
				),
			) ),
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
		 * groups/single/single-group-hierarchy-screen is used to create the
		 * hierarchy screen for a single group.
		 * Note that groups-loop will load groups-loop-tree if
		 * 'use hierarchical template' is set to true.
		 */
		bp_get_template_part( 'groups/single/single-group-hierarchy-screen' );

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
		// On the create screen, the group_id isn't passed reliably.
		if ( empty( $group_id ) && ! empty( $_COOKIE['bp_new_group_id'] ) ) {
			$group_id = (int) $_COOKIE['bp_new_group_id'];
		}
		?>
		<label class="emphatic" for="parent-id"><?php _ex( 'Parent Group', 'Label for the parent group select on a single group manage screen', 'hierarchical-groups-for-bp' ); ?></label>
		<?php
		$current_parent_group_id = hgbp_get_parent_group_id( $group_id );
		$possible_parent_groups = hgbp_get_possible_parent_groups( $group_id, bp_loggedin_user_id() );

		if ( ! $current_parent_group_id ) :
			?>
			<p class="info"><?php _e( 'This group is currently a top-level group.', 'hierarchical-groups-for-bp' ); ?></p>
			<?php
		else :
			$parent_group = groups_get_group( $current_parent_group_id );
			// The parent group could be a hidden group, so the current user may not be able to know about it. :\
			if ( 'hidden' == bp_get_group_status( $parent_group ) && ! groups_is_user_member( bp_loggedin_user_id(), $parent_group->id ) ) :
				$current_parent_group_id = 'hidden-from-user';
				?>
				<p class="info"><?php _e( 'This group&rsquo;s current parent group is a hidden group, and you are not a member of that group.', 'hierarchical-groups-for-bp' ); ?></p>
				<?php
			else :
				?>
				<p class="info"><?php esc_html( printf( __( 'This group&rsquo;s current parent group is %s.', 'hierarchical-groups-for-bp' ), bp_get_group_name( $parent_group ) ) ); ?></p>
				<?php
			endif;
		endif; ?>
			<select id="parent-id" name="parent-id" autocomplete="off">
				<option value="no-change" <?php selected( 'hidden-from-user', $current_parent_group_id ); ?>><?php echo _x( 'Keep current parent group', 'The option to keep the current parent.', 'hierarchical-groups-for-bp' ); ?></option>
				<option value="0" <?php selected( 0, $current_parent_group_id ); ?>><?php echo _x( 'No parent group', 'The option that sets a group to be a top-level group and have no parent.', 'hierarchical-groups-for-bp' ); ?></option>
			<?php
			if ( $possible_parent_groups ) {

				foreach ( $possible_parent_groups as $possible_parent_group ) {
					?>
					<option value="<?php echo $possible_parent_group->id; ?>" <?php selected( $current_parent_group_id, $possible_parent_group->id ); ?>><?php echo esc_html( $possible_parent_group->name ); ?></option>
					<?php
				}
			}
			?>
			</select>
			<?php
		?>

		<fieldset class="hierarchy-allowed-subgroup-creators radio">

			<legend><?php _e( 'Who is allowed to select this group as the parent group of another group?', 'hierarchical-groups-for-bp' ); ?></legend>

			<?php
			$subgroup_creators = hgbp_get_allowed_subgroup_creators( $group_id );
			/*
			 * Don't include the loggedin option if this group is hidden--
			 * you have to be a member to even know about hidden groups.
			 */
			if ( 'hidden' != bp_get_group_status( groups_get_group( $group_id ) ) ) : ?>
				<label for="allowed-subgroup-creators-loggedin"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-loggedin" value="loggedin" <?php checked( $subgroup_creators, 'loggedin' ); ?> /> <?php _e( 'Any logged-in site member', 'hierarchical-groups-for-bp' ); ?></label>
			<?php endif; ?>

			<label for="allowed-subgroup-creators-members"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-members" value="member" <?php checked( $subgroup_creators, 'member' ); ?> /> <?php _e( 'All group members', 'hierarchical-groups-for-bp' ); ?></label>

			<label for="allowed-subgroup-creators-mods"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-mods" value="mod" <?php checked( $subgroup_creators, 'mod' ); ?> /> <?php _e( 'Group admins and mods only', 'hierarchical-groups-for-bp' ); ?></label>

			<label for="allowed-subgroup-creators-admins"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-admins" value="admin" <?php checked( $subgroup_creators, 'admin' ); ?> /> <?php _e( 'Group admins only', 'hierarchical-groups-for-bp' ); ?></label>

			<label for="allowed-subgroup-creators-noone"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-noone" value="noone" <?php checked( $subgroup_creators, 'noone' ); ?> /> <?php _e( 'No one', 'hierarchical-groups-for-bp' ); ?></label>
		</fieldset>

			<?php
			// Only display the syndication sections if the current user can change it.
			if ( bp_current_user_can( 'hgbp_change_include_activity' ) ) :
				$setting = groups_get_groupmeta( $group_id, 'hgbp-include-activity-from-relatives' );
				if ( ! $setting ) {
					$setting = 'inherit';
				}
			?>
				<fieldset class="hierarchy-syndicate-activity radio">
					<legend><?php _e( 'Include activity from parent and child groups in this group&rsquo;s activity stream.', 'hierarchical-groups-for-bp' ); ?></legend>

					<label for="include-activity-from-parents"><input type="radio" id="include-activity-from-parents" name="hgbp-include-activity-from-relatives" value="include-from-parents"<?php checked( 'include-from-parents', $setting ); ?>> <?php _e( 'Include parent group activity.', 'hierarchical-groups-for-bp' ); ?></label>

					<label for="include-activity-from-children"><input type="radio" id="include-activity-from-children" name="hgbp-include-activity-from-relatives" value="include-from-children"<?php checked( 'include-from-children', $setting ); ?>> <?php _e( 'Include child group activity.', 'hierarchical-groups-for-bp' ); ?></label>

					<label for="include-activity-from-both"><input type="radio" id="include-activity-from-both" name="hgbp-include-activity-from-relatives" value="include-from-both"<?php checked( 'include-from-both', $setting ); ?>> <?php _e( 'Include parent and child group activity.', 'hierarchical-groups-for-bp' ); ?></label>

					<label for="include-activity-from-none"><input type="radio" id="include-activity-from-none" name="hgbp-include-activity-from-relatives" value="include-from-none"<?php checked( 'include-from-none', $setting ); ?>> <?php _e( 'Do not include related group activity.', 'hierarchical-groups-for-bp' ); ?></label>

					<label for="hgbp-include-activity-from-relatives-inherit"><input type="radio" name="hgbp-include-activity-from-relatives" id="hgbp-include-activity-from-relatives-inherit" value="inherit" <?php checked( 'inherit', $setting ); ?> /> <?php _e( 'Inherit global setting.', 'hierarchical-groups-for-bp' ); ?></label>
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

		// Save parent ID. Do nothing if value passed is "no-change".
		if ( isset( $_POST['parent-id'] ) && 'no-change' != $_POST['parent-id'] ) {
			$parent_id = $_POST['parent-id'] ? (int) $_POST['parent-id'] : 0;

			if ( $group_object->parent_id != $parent_id ) {
				$group_object->parent_id = $parent_id;
				$group_object->save();
			}
		}

		$allowed_creators = isset( $_POST['allowed-subgroup-creators'] ) ? $_POST['allowed-subgroup-creators'] : '';
		$allowed_creators = hgbp_sanitize_subgroup_creators_setting( $allowed_creators );
		$subgroup_creators = groups_update_groupmeta( $group_id, 'hgbp-allowed-subgroup-creators', $allowed_creators );

		// Syndication settings.
		if ( isset( $_POST['hgbp-include-activity-from-relatives'] ) ) {
			if ( 'inherit' == $_POST['hgbp-include-activity-from-relatives'] ) {
				// If "inherit", delete the group meta.
				$success = groups_delete_groupmeta( $group_id, 'hgbp-include-activity-from-relatives' );
			} else {
				$setting = hgbp_sanitize_include_setting( $_POST['hgbp-include-activity-from-relatives'] );
				$success = groups_update_groupmeta( $group_id, 'hgbp-include-activity-from-relatives', $setting );
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
		if ( $group_id && ( hgbp_group_has_children( $group_id, bp_loggedin_user_id(), 'exclude_hidden' ) || hgbp_get_parent_group_id( $group_id, bp_loggedin_user_id(), 'normal' ) ) ) {
			// If this group is hidden, make the tab visible to members only.
			if ( 'hidden' == bp_get_group_status( groups_get_group( $group_id ) ) ) {
				$nav_item_vis = 'member';
			} else {
				// Else, anyone can see how public and private groups are related.
				$nav_item_vis = 'anyone';
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