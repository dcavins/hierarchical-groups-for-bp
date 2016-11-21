<?php
/**
 * The public class.
 *
 * @package   HierarchicalGroupsForBP
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2016 David Cavins
 */

/**
 * Plugin class for public functionality.
 *
 * @package   HierarchicalGroupsForBP_Public_Class
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2016 David Cavins
 */
class HGBP_Public {

	/**
	 *
	 * The current version of the plugin.
	 *
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $version = '1.0.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'hierarchical-groups-for-bp';

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	public function __construct() {
		$this->version = hgbp_get_plugin_version();
	}

	/**
	 * Add actions and filters to WordPress/BuddyPress hooks.
	 *
	 * @since    1.0.0
	 */
	public function add_action_hooks() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Caching
		add_action( 'init', array( $this, 'add_cache_groups' ) );
		// Reset the cache group's incrementor when groups are added, changed or deleted.
		add_action( 'groups_group_after_save', array( $this, 'reset_cache_incrementor' ) );
		add_action( 'bp_groups_delete_group', array( $this, 'reset_cache_incrementor' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );

		// Add our templates to BuddyPress' template stack.
		add_filter( 'bp_get_template_stack', array( $this, 'add_template_stack'), 10, 1 );

		// Potentially override the groups loop template.
		add_filter( 'bp_get_template_part', array( $this, 'filter_groups_loop_template'), 10, 3 );
			// Add hierarchically related activity to group activity streams.
		add_filter( 'bp_after_has_groups_parse_args', array( $this, 'filter_has_groups_args' ) );
		add_filter( 'bp_get_group_class', array( $this, 'filter_group_classes' ) );

		// Save a group's allowed_subgroup_creators setting as group metadata.
		add_action( 'groups_group_settings_edited', array( $this, 'save_allowed_subgroups_creators' ) );
		add_action( 'bp_group_admin_edit_after',    array( $this, 'save_allowed_subgroups_creators' ) );

		// Save a group's allowed_subgroup_creators setting from the create group screen.
		add_action( 'groups_create_group_step_save_group-settings', array( $this, 'save_allowed_subgroups_creators_create_step' ) );

		// Modify group permalinks to reflect hierarchy
		add_filter( 'bp_get_group_permalink', array( $this, 'make_permalink_hierarchical' ), 10, 2 );

		/*
		 * Update the current action and action variables, after the table name is set,
		 * but before BP Groups Component sets the current group, action and variables.
		 */
		add_action( 'bp_groups_setup_globals', array( $this, 'reset_action_variables' ) );

		// Filter user capabilities.
		add_filter( 'bp_user_can', array( $this, 'check_user_caps' ), 10, 5 );

		// Add hierarchically related activity to group activity streams.
		add_filter( 'bp_after_has_activities_parse_args', array( $this, 'add_activity_aggregation' ) );

		// Handle AJAX requests for subgroups
		add_action( 'wp_ajax_hgbp_get_child_groups', array( $this, 'ajax_subgroups_response_cb' ) );
		add_action( 'wp_ajax_nopriv_hgbp_get_child_groups', array( $this, 'ajax_subgroups_response_cb' ) );
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return   string Plugin slug.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
	}

	/**
	 * Set up a group for cache usage.
	 *
	 * @since    1.0.0
	 */
	public function add_cache_groups() {
		wp_cache_add_global_groups( 'hgbp' );
	}

	/**
	 * Reset the cache group's incrementor when groups are added, changed or deleted.
	 *
	 * @since    1.0.0
	 */
	public function reset_cache_incrementor() {
		bp_core_reset_incrementor( 'hgbp' );
	}

	/**
	 * Add our templates to BuddyPress' template stack.
	 *
	 * @since    1.0.0
	 */
	public function add_template_stack( $templates ) {
		if ( bp_is_current_component( 'groups' ) ) {
			$templates[] = plugin_dir_path( __FILE__ ) . 'views/templates';
		}
		return $templates;
	}

	/**
	 * Potentially override the groups loop template.
	 *
	 * @since    1.0.0
	 *
	 * @param array  $templates Array of templates located.
	 * @param string $slug      Template part slug requested.
	 * @param string $name      Template part name requested.
	 *
	 * @return array $templates
	 */
	public function filter_groups_loop_template( $templates, $slug, $name ) {
		if ( 'groups/groups-loop' == $slug && hgbp_get_global_directory_setting() ) {
			// If ( not searching or applying other filters ) {}

			// Add our setting to the front of the array.
			array_unshift( $templates, 'groups/groups-loop-tree.php' );
		}

		return $templates;
	}

	/**
	 * Filter has_groups parameters to change results for groups directory.
	 *
	 * @since 1.0.0
	 *
	 * @param $args Array of parsed arguments.
	 *
	 * @return array
 	 */
	public function filter_has_groups_args( $args ) {
		if ( hgbp_get_global_directory_setting() ) {
			// Don't filter if search is active.
			// @TODO: Don't filter if orderby?
			if ( ! isset( $args['parent_id'] ) && empty( $args['search_terms'] ) ) {
				$args['parent_id'] = isset( $_REQUEST['parent_id'] ) ? intval( $_REQUEST['parent_id'] ) : 0;
				// $args['parent_id'] = isset( $_GET['parent_id'] ) ? intval( $_GET['parent_id'] ) : 0;
			}
			// $parent_id = isset( $_POST['parent_id'] ) ? intval( $_POST['parent_id'] ) : 0;
			$towrite = PHP_EOL . 'args: ' . print_r( $args, TRUE );
			// $towrite .= PHP_EOL . 'parent_id: ' . print_r( $parent_id, TRUE );
			$fp = fopen('has_groups.txt', 'a');
			fwrite($fp, $towrite);
			fclose($fp);
		}

		return $args;
	}
	/**
	 * Filter has_groups parameters to change results for groups directory.
	 *
	 * @since 1.0.0
	 *
	 * @param array $classes Array of determined classes for the group.
	 *
	 * @return array
 	 */
	public function filter_group_classes( $classes ) {
		if ( $has_children = hgbp_group_has_children( bp_get_group_id(), bp_loggedin_user_id() ) ) {
			$classes[] = 'has-children';
		}
		return $classes;
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles_scripts() {
		if ( bp_is_active( 'groups' ) ) {
			// Styles
			wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), $this->version );

			// IE specific
			// global $wp_styles;
			// wp_enqueue_style( $this->plugin_slug . '-ie-plugin-styles', plugins_url( 'css/public-ie.css', __FILE__ ), array(), $this->version );
			// $wp_styles->add_data( $this->plugin_slug . '-ie-plugin-styles', 'conditional', 'lte IE 9' );

			// Scripts
			wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'js/public.min.js', __FILE__ ), array( 'jquery' ), $this->version );
		}
	}

	/**
	 * Save a group's allowed_subgroup_creators setting as group metadata.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $group_id   ID of the group to update.
	 */
	public function save_allowed_subgroups_creators( $group_id ) {
		if ( isset( $_POST['allowed-subgroup-creators'] ) &&
			 in_array( $_POST['allowed-subgroup-creators'], array( 'noone', 'admin', 'mod', 'member' ) ) ) {
			groups_update_groupmeta( $group_id, 'hgbp-allowed-subgroup-creators', $_POST['allowed-subgroup-creators'] );
		}
	}

	/**
	 * Save a group's allowed_subgroup_creators setting from the create group screen.
	 *
	 * @since 1.0.0
	 */
	public function save_allowed_subgroups_creators_create_step() {
		$group_id = buddypress()->groups->new_group_id;
		$this->save_allowed_subgroups_creators( $group_id );
	}

	/**
	 * Filter a child group's permalink to take the form
	 * /groups/parent-group/child-group.
	 *
	 * @since 1.0.0
	 *
	 * @param string $permalink Permalink for the current group in the loop.
	 * @param object $group     Group object.
	 *
	 * @return string Filtered permalink for the group.
	 */
	public function make_permalink_hierarchical( $permalink, $group ) {
		// We only need to filter if this not a top-level group.
		if ( $group->parent_id != 0 ) {
			$group_path = hgbp_build_hierarchical_slug( $group->id );
			$permalink  = trailingslashit( bp_get_groups_directory_permalink() . $group_path . '/' );
		}
		return $permalink;
	}

	/**
	 * Filter $bp->current_action and $bp->action_variables before the single
	 * group details are set up in the Single Group Globals section of
	 * BP_Groups_Component::setup_globals() to ignore the hierarchical
	 * piece of the URL for child groups.
	 *
	 * @since 1.0.0
	 *
	 */
	public function reset_action_variables() {
		if ( bp_is_groups_component() ) {
			$bp = buddypress();

			// We're looking for group slugs masquerading as action variables.
			$action_variables = bp_action_variables();
			if ( ! $action_variables || ! is_array( $action_variables ) ) {
				return;
			}

			/*
			 * The Single Group Globals section of BP_Groups_Component::setup_globals()
			 * uses the current action to set up the current group. Pull found
			 * group slugs out of the action variables array.
			 */
			foreach ( $action_variables as $maybe_slug ) {
				if ( groups_get_id( $maybe_slug ) ) {
					$bp->current_action = array_shift( $bp->action_variables );
				} else {
					// If we've gotten into real action variables, stop.
					break;
				}
			}
		}
	}

	/**
	 * Check for user capabilities specific to this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $retval     Whether or not the current user has the capability.
	 * @param int    $user_id
	 * @param string $capability The capability being checked for.
	 * @param int    $site_id    Site ID. Defaults to the BP root blog.
	 * @param array  $args       Array of extra arguments passed.
	 *
	 * @return bool
	 */
	public function check_user_caps( $retval, $user_id, $capability, $site_id, $args ) {
		$activity_caps = array(
			'hgbp_change_include_activity_from_parents',
			'hgbp_change_include_activity_from_children'
		);
		if ( in_array( $capability, $activity_caps ) ) {

			$global_setting = 'group-admins';
			if ( $capability == 'hgbp_change_include_activity_from_parents' ) {
				$global_setting = hgbp_get_global_activity_enforce_setting( 'parents' );
			} elseif ( $capability == 'hgbp_change_include_activity_from_children' ) {
				$global_setting = hgbp_get_global_activity_enforce_setting( 'children' );
			}

			$retval = false;
			switch ( $global_setting ) {
				case 'site-admins':
					if ( bp_user_can( $user_id, 'bp_moderate' ) ) {
						$retval = true;
					}
					break;
				case 'group-admins':
					if ( bp_user_can( $user_id, 'bp_moderate' )
						 || groups_is_user_admin( $user_id, bp_get_current_group_id() ) ) {
						$retval = true;
					}
					break;
				case 'strict':
				default:
					$retval = false;
					break;
			}

		}

		if ( 'create_subgroups' == $capability ) {
			// If group creation is restricted, respect that setting.
			if ( bp_restrict_group_creation() && ! bp_user_can( $user_id, 'bp_moderate' ) ) {
				return false;
			}

			// We need to know which group is in question.
			if ( empty( $args['group_id'] ) ) {
				return false;
			}
			$group_id = (int) $args['group_id'];

			// Possible settings for the group meta setting 'allowed_subgroup_creators'
			$creator_setting = groups_get_groupmeta( $group_id, 'hgbp-allowed-subgroup-creators' );
			switch ( $creator_setting ) {
				case 'admin' :
					$retval = groups_is_user_admin( $user_id, $group_id );
					break;

				case 'mod' :
					$retval = ( groups_is_user_mod( $user_id, $group_id ) ||
								groups_is_user_admin( $user_id, $group_id ) );
					break;

				case 'member' :
					$retval = groups_is_user_member( $user_id, $group_id );
					break;

				case 'noone' :
				default :
					// @TODO: This seems weird, but I can imagine situations where only site admins should be able to associate groups.
					$retval = bp_user_can( $user_id, 'bp_moderate' );
					break;
			}
		}

		return $retval;
	}

	/**
	 * Filter has_activities parameters to add hierarchically related groups of
	 * the current group that user has access to.
	 *
	 * @since 1.0.0
	 *
	 * @param $args Array of parsed arguments.
	 *
	 * @return array
	 */
	public function add_activity_aggregation( $args ) {

		// Only fire on group activity streams.
		if ( $args['object'] != 'groups' ) {
			return $args;
		}

		$group_id = bp_get_current_group_id();

		// Check if this group is set to aggregate child group activity.
		$include_child_activity = hgbp_group_include_hierarchical_activity( $group_id, 'children' );
		$include_parent_activity = hgbp_group_include_hierarchical_activity( $group_id, 'parents' );

		if ( ! $include_child_activity && ! $include_parent_activity ) {
			return $args;
		}

		$include = array( $group_id );
		if ( $include_parent_activity ) {
			$parents = hgbp_get_ancestor_group_ids( $group_id, bp_loggedin_user_id(), 'activity' );
			$include = array_merge( $include, $parents );
		}

		if ( $include_child_activity ) {
			$children  = hgbp_get_descendent_groups( $group_id, bp_loggedin_user_id(), 'activity' );
			$child_ids = wp_list_pluck( $children, 'id' );
			$include   = array_merge( $include, $child_ids );
		}

		if ( ! empty( $include ) ) {
			$args['primary_id'] = $include;
		}

		return $args;
	}

	/**
	 * Generate the response for the AJAX hgbp_get_child_groups action.
	 *
	 * @since 1.0.0
	 *
	 * @return html
	 */
	public function ajax_subgroups_response_cb() {
		// Within a single group, prefer the subgroups loop template.
		if ( hgbp_is_hierarchy_screen() ) {
			bp_get_template_part( 'groups/single/subgroups-loop' );
		} else {
			bp_get_template_part( 'groups/groups-loop-tree' );
		}

		exit;
	}
}
