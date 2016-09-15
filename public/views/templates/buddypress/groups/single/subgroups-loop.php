<?php
/**
 * BuddyPress - Subgroups Loop
 *
 * Querystring is set via AJAX in _inc/ajax.php - bp_legacy_theme_object_filter().
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<?php
// Store the groups template global so we can "reset" the loop after this subloop.
global $groups_template;
$parent_groups_template = $groups_template;

/**
 * Fires before the display of groups from the groups loop.
 *
 * @since 1.2.0
 */
do_action( 'bp_before_subgroups_loop' ); ?>
<?php
// @TODO: Hidden groups are excluded by default. Showing them all is dangerous.
// Will be much better when group capabilities are in place that allow granular exclusion of hidden groups.
// @TODO: Ajax pagination doesn't work.
?>
<?php if ( bp_has_groups( bp_ajax_querystring( 'groups' ) . '&type=alphabetical&parent_id=' . bp_get_current_group_id() ) ) : ?>

	<div id="pag-top" class="pagination">

		<div class="pag-count" id="group-dir-count-top">

			<?php bp_groups_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="group-dir-pag-top">

			<?php bp_groups_pagination_links(); ?>

		</div>

	</div>

	<?php

	/**
	 * Fires before the listing of the groups list.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_directory_subgroups_list' ); ?>

	<ul id="groups-list" class="item-list">

	<?php while ( bp_groups() ) : bp_the_group(); ?>

		<li <?php bp_group_class(); ?>>
			<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
				<div class="item-avatar">
					<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar( 'type=thumb&width=50&height=50' ); ?></a>
				</div>
			<?php endif; ?>

			<div class="item">
				<div class="item-title"><a href="<?php bp_group_permalink(); ?>"><?php bp_group_name(); ?></a></div>
				<div class="item-meta"><span class="activity"><?php printf( __( 'active %s', 'buddypress' ), bp_get_group_last_active() ); ?></span></div>

				<div class="item-desc"><?php bp_group_description_excerpt(); ?></div>

				<?php if ( bp_allow_hierarchical_groups() ) :?>
					<div class="group-hierarchy-breadcrumbs"><?php hgbp_group_permalink_breadcrumbs(); ?></div>
				<?php endif; ?>

				<?php

				/**
				 * Fires inside the listing of an individual group listing item.
				 *
				 * @since 1.1.0
				 */
				do_action( 'bp_directory_groups_item' ); ?>

			</div>

			<div class="action">

				<?php

				/**
				 * Fires inside the action section of an individual group listing item.
				 *
				 * @since 1.1.0
				 */
				do_action( 'bp_directory_groups_actions' ); ?>

				<div class="meta">

					<?php bp_group_type(); ?> / <?php bp_group_member_count(); ?>

				</div>

			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>

	</ul>

	<?php

	/**
	 * Fires after the listing of the groups list.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_after_directory_subgroups_list' ); ?>

	<div id="pag-bottom" class="pagination">

		<div class="pag-count" id="group-dir-count-bottom">

			<?php bp_groups_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="group-dir-pag-bottom">

			<?php bp_groups_pagination_links(); ?>

		</div>

	</div>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'There were no subgroups found.', 'buddypress' ); ?></p>
	</div>

<?php endif; ?>

<?php

/**
 * Fires after the display of groups from the groups loop.
 *
 * @since 1.2.0
 */
do_action( 'bp_after_subgroups_loop' );

// Reset the $groups_template global and continue with the main group's
$groups_template = $parent_groups_template;
?>
