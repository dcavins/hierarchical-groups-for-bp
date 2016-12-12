<?php
/**
 * BuddyPress - Groups Loop
 *
 * Querystring is set via AJAX in _inc/ajax.php - bp_legacy_theme_object_filter().
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

/**
 * Fires before the display of groups from the groups loop.
 *
 * @since 1.2.0 (BuddyPress)
 */
do_action( 'bp_before_groups_loop' ); ?>

<?php if ( bp_get_current_group_directory_type() ) : ?>
	<p class="current-group-type"><?php bp_current_group_directory_type_message() ?></p>
<?php endif; ?>

<?php if ( bp_has_groups( bp_ajax_querystring( 'groups' ) ) ) : ?>

	<?php

	/**
	 * Fires before the listing of the groups tree.
	 * Specific to the Hierarchical Groups for BP plugin.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_before_directory_groups_list_tree' ); ?>

	<?php

	/**
	 * Fires before the listing of the groups list.
	 *
	 * @since 1.1.0 (BuddyPress)
	 */
	do_action( 'bp_before_directory_groups_list' ); ?>

	<ul id="groups-list" class="item-list" aria-live="assertive" aria-atomic="true" aria-relevant="all">

	<?php while ( bp_groups() ) : bp_the_group(); ?>

		<li <?php bp_group_class(); ?>>
			<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
				<div class="item-avatar">
					<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar( 'type=thumb&width=50&height=50' ); ?></a>
				</div>
			<?php endif; ?>

			<div class="item">
				<div class="item-title"><a href="<?php bp_group_permalink(); ?>"><?php bp_group_name(); ?></a></div>
				<div class="item-meta"><span class="activity" data-livestamp="<?php bp_core_iso8601_date( bp_get_group_last_active( 0, array( 'relative' => false ) ) ); ?>"><?php printf( __( 'active %s', 'buddypress' ), bp_get_group_last_active() ); ?></span></div>

				<div class="item-desc"><?php bp_group_description_excerpt(); ?></div>

				<?php

				/**
				 * Fires inside the listing of an individual group listing item.
				 *
				 * @since 1.1.0 (BuddyPress)
				 */
				do_action( 'bp_directory_groups_item' ); ?>

			</div>

			<div class="action">

				<?php

				/**
				 * Fires inside the action section of an individual group listing item.
				 *
				 * @since 1.1.0 (BuddyPress)
				 */
				do_action( 'bp_directory_groups_actions' ); ?>

				<div class="meta">

					<?php bp_group_type(); ?> / <?php bp_group_member_count(); ?>

				</div>

			</div>

			<?php
			/*
			 * Show the 'show child groups' toggle only if the group has child
			 * groups that should be visible in this context.
			 */
			if ( $number_children = hgbp_group_has_children( bp_get_group_id(), bp_loggedin_user_id(), 'directory' ) ) : ?>
				<div class="child-groups-container">
					<a href="<?php hgbp_group_hierarchy_permalink(); ?>" class="toggle-child-groups" data-group-id="<?php bp_group_id(); ?>" aria-expanded="false" aria-controls="child-groups-of-<?php bp_group_id(); ?>"><?php printf(
							_x( 'Child groups %s', 'Label for the control on group directories that shows or hides the child groups. %s will be replaced with the number of child groups.', 'hierarchical-groups-for-bp' ),
							'<span class="count">' . $number_children . '</span>'
						); ?></a>
					<div class="child-groups" id="child-groups-of-<?php bp_group_id(); ?>"></div>
				</div>
			<?php endif; ?>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>

	</ul>

	<?php

	/**
	 * Fires after the listing of the groups list.
	 *
	 * @since 1.1.0 (BuddyPress)
	 */
	do_action( 'bp_after_directory_groups_list' ); ?>

	<?php

	/**
	 * Fires before the listing of the groups tree.
	 * Specific to the Hierarchical Groups for BP plugin.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_after_directory_groups_list_tree' ); ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'There were no groups found.', 'buddypress' ); ?></p>
	</div>

<?php endif; ?>

<?php

/**
 * Fires after the display of groups from the groups loop.
 *
 * @since 1.2.0 (BuddyPress)
 */
do_action( 'bp_after_groups_loop' ); ?>
