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
			'slug'              => hgbp_get_subgroups_screen_slug(),
			'name'              => hgbp_get_subgroups_nav_item_name(),
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
		bp_get_template_part( 'groups/single/subgroups-loop' );
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

			<legend><?php _e( 'Which members of this group are allowed to create subgroups?', 'hierarchical-groups-for-bp' ); ?></legend>

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
		// Only display the syndication section if the current user can change it.
		$can_change_synd_from_parents  = bp_current_user_can( 'hgbp_change_include_activity_from_parents' );
		$can_change_synd_from_children = bp_current_user_can( 'hgbp_change_include_activity_from_children' );

		if ( $can_change_synd_from_parents || $can_change_synd_from_children ) :
			$include_from_parents  = groups_get_groupmeta( $group_id, "hgbp-include-activity-from-parents" );
			$include_from_children = groups_get_groupmeta( $group_id, "hgbp-include-activity-from-children" );
		?>
			<fieldset class="hierarchy-syndicate-activity checkbox">
				<legend><?php _e( 'Show hierarchical activity in this group\'s activity stream', 'hierarchical-groups-for-bp' ); ?></legend>

				<?php if ( $can_change_synd_from_parents ) : ?>
					<label for="hgbp-include-activity-from-parents"><input type="checkbox" name="hgbp-include-activity-from-parents" id="hgbp-include-activity-from-parents" value="1" <?php checked( $include_from_parents, 'yes' ); ?> /> <?php _e( 'Include activity from parent groups.', 'hierarchical-groups-for-bp' ); ?></label>
				<?php endif; ?>

				<?php if ( $can_change_synd_from_children ) : ?>
					<label for="hgbp-include-activity-from-children"><input type="checkbox" name="hgbp-include-activity-from-children" id="hgbp-include-activity-from-children" value="1" <?php checked( $include_from_children, 'yes' ); ?> /> <?php _e( 'Include activity from child groups.', 'hierarchical-groups-for-bp' ); ?></label>
				<?php endif; ?>

			</fieldset>
		<?php endif;

		// We'll also need to know which inputs existed on the screen to know how to treat the input. Ugh.
		if ( $can_change_synd_from_parents && $can_change_synd_from_children ) {
			$enabled_settings = 'both';
		} elseif ( $can_change_synd_from_parents ) {
			$enabled_settings = 'parents';
		} elseif ( $can_change_synd_from_children ) {
			$enabled_settings = 'children';
		} else {
			$enabled_settings = 'neither';
		}
		?>
		<input type="hidden" name="hgbp_syndication_settings_enabled" value="<?php echo $enabled_settings; ?>">
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
		if ( isset( $_POST['hgbp_syndication_settings_enabled'] ) ) {
			switch ( $_POST['hgbp_syndication_settings_enabled'] ) {
				case 'both':
					$inputs_to_save = array( 'hgbp-include-activity-from-children', 'hgbp-include-activity-from-parents' );
					break;
				case 'children':
					$inputs_to_save = array( 'hgbp-include-activity-from-children' );
					break;
				case 'parents':
					$inputs_to_save = array( 'hgbp-include-activity-from-parents' );
					break;
				default:
					$inputs_to_save = array();
					break;
			}
			foreach ( $inputs_to_save as $posted ) {
				$value   = ( isset( $_POST[ $posted ] ) && $_POST[ $posted ] ) ? 'yes' : 'no';
				$success = groups_update_groupmeta( $group_id, $posted, $value );
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

		// The nav item should only be enabled when subgroups exist.
		$has_children = hgbp_group_has_children( $group_id, bp_loggedin_user_id() );
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