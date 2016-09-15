<?php
/**
 * @package   HierarchicalGroupsForBP
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2016 David Cavins
 */

class Hierarchical_Groups_for_BP extends BP_Group_Extension {

	function __construct() {
		$enable_nav_item = $this->enable_nav_item();

		$args = array(
			'slug'              => apply_filters( 'hgbp_screen_slug', 'hierarchy' ),
			'name'              => apply_filters( 'hgbp_screen_nav_item_name', 'Hierarchy' ),
			'nav_item_position' => 61,
			'access'            => ( $enable_nav_item ) ? 'anyone' : 'noone',
			'show_tab'          => ( $enable_nav_item ) ? 'anyone' : 'noone',
		);
		parent::init( $args );
	}

	function display( $group_id = NULL ) {
		bp_get_template_part( 'groups/single/subgroups-loop' );
	}

	function settings_screen( $group_id = NULL ) {
		?>
		<label for="parent-id">Parent Group</label>
		<?php
		$current_parent_group_id = hgbp_get_parent_group_id();
		$possible_parent_groups = hgbp_get_possible_parent_groups( $group_id, bp_loggedin_user_id() );
		if ( $possible_parent_groups ) :
			?>
			<select id="parent-id" name="parent-id">
				<option value="0" <?php selected( 0, $current_parent_group_id ); ?>><?php echo _x( 'None selected', 'The option that sets a group to be a top-level group and have no parent.', 'buddypress' ); ?></option>
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
			<p><?php _e( 'There are no groups available to be a parent to this group.', 'buddypress' ); ?></p>
			<?php
		endif;
		?>

		<fieldset class="radio">

			<legend><?php _e( 'Which members of this group are allowed to create subgroups?', 'buddypress' ); ?></legend>

			<?php
			$subgroup_creators = groups_get_groupmeta( $group_id, 'allowed_subgroup_creators' );
			if ( ! $subgroup_creators ) {
				$subgroup_creators = 'noone';
			}
			?>

			<?php
			// If only site admins can create groups, don't display impossible options.
			if ( ! bp_restrict_group_creation() ) :
			?>
				<label for="allowed-subgroup-creators-members"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-members" value="member" <?php checked( $subgroup_creators, 'member' ); ?> /> <?php _e( 'All group members', 'buddypress' ); ?></label>

				<label for="allowed-subgroup-creators-mods"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-mods" value="mod" <?php checked( $subgroup_creators, 'mod' ); ?> /> <?php _e( 'Group admins and mods only', 'buddypress' ); ?></label>

				<label for="allowed-subgroup-creators-admins"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-admins" value="admin" <?php checked( $subgroup_creators, 'admin' ); ?> /> <?php _e( 'Group admins only', 'buddypress' ); ?></label>
			<?php endif; ?>

			<label for="allowed-subgroup-creators-noone"><input type="radio" name="allowed-subgroup-creators" id="allowed-subgroup-creators-noone" value="noone" <?php checked( $subgroup_creators, 'noone' ); ?> /> <?php _e( 'No one', 'buddypress' ); ?></label>
		</fieldset>
	<?php
	}

	function settings_screen_save( $group_id = NULL ) {
		$group_object = groups_get_group( $group_id );
		$parent_id = isset( $_POST['parent-id'] ) ? $_POST['parent-id'] : 0;
		if ( $group_object->parent_id != $parent_id ) {
			$group_object->parent_id = $parent_id;
			$group_object->save();
		}

		$allowed_creators = isset( $_POST['allowed-subgroup-creators'] ) ? $_POST['allowed-subgroup-creators'] : '';
		$subgroup_creators = groups_update_groupmeta( $group_id, 'allowed_subgroup_creators', $allowed_creators );
	}

	/**
	 * Determine whether the group nav item should show up for the current user
	 *
	 * @since 1.0.0
	 */
	function enable_nav_item() {
		// The nav item should only be enabled when subgroups exist.
		$enable_nav_item = ( hgbp_group_has_children( bp_get_current_group_id(), bp_loggedin_user_id() ) ) ? true : false;

		return apply_filters( 'hgbp_enable_nav_item', $enable_nav_item );
	}

}
bp_register_group_extension( 'Hierarchical_Groups_for_BP' );