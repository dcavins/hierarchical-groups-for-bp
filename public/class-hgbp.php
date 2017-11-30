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

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );


		/* Changes to the groups directory view. ******************************/
		// Add our templates to BuddyPress' template stack.
		add_filter( 'bp_get_template_stack', array( $this, 'add_template_stack'), 10, 1 );

		// Potentially override the groups loop template.
		add_filter( 'bp_get_template_part', array( $this, 'filter_groups_loop_template'), 10, 3 );

		/*
		 * Adds toggle allowing user to choose whether to restrict groups list to top-level groups
		 * (working top down), or whether to intermingle.
		 */
		add_action( 'bp_groups_directory_group_types', array( $this, 'output_enable_tree_checkbox' ) );

		// Hook bp_has_groups filters right before a group directory is rendered.
		add_action( 'bp_before_groups_loop', array( $this, 'add_has_group_parse_arg_filters' ) );

		// Unhook bp_has_groups filters right after a group directory is rendered.
		add_action( 'bp_after_groups_loop', array( $this, 'remove_has_group_parse_arg_filters' ) );

		// Add pagination blocks to the groups-loop-tree directory.
		add_action( 'hgbp_before_directory_groups_list_tree', 'hgbp_groups_loop_pagination_top' );
		add_action( 'hgbp_after_directory_groups_list_tree', 'hgbp_groups_loop_pagination_bottom' );

		// Add the "has-children" class to a group item that has children.
		add_filter( 'bp_get_group_class', array( $this, 'filter_group_classes' ) );

		// Handle AJAX requests for subgroups.
		add_action( 'wp_ajax_hgbp_get_child_groups', array( $this, 'ajax_subgroups_response_cb' ) );
		add_action( 'wp_ajax_nopriv_hgbp_get_child_groups', array( $this, 'ajax_subgroups_response_cb' ) );


		/* Changes to single group behavior. **********************************/
		// Modify group permalinks to reflect hierarchy
		add_filter( 'bp_get_group_permalink', array( $this, 'make_permalink_hierarchical' ), 10, 2 );

		/*
		 * Update the current action and action variables, after the table name is set,
		 * but before BP Groups Component sets the current group, action and variables.
		 * This change allows the URLs to be hierarchically written, but for
		 * BuddyPress to know which group is really the current group.
		 */
		add_action( 'bp_groups_setup_globals', array( $this, 'reset_action_variables' ) );

		// Add hierarchically related activity to group activity streams.
		add_filter( 'bp_after_has_activities_parse_args', array( $this, 'add_activity_aggregation' ) );


		/* Add user capability checks. ****************************************/
		// Filter user capabilities.
		add_filter( 'bp_user_can', array( $this, 'check_user_caps' ), 10, 5 );

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
		global $wp_version;

		/*
		 * WordPress 4.6 and newer automatically loads language files found at
		 * wp-content/languages/plugins/hierarchical-groups-for-bp-LOCALE.mo
		 * This is for older installations of WordPress.
		 */

		if ( version_compare( $wp_version, '4.6', '<' ) ) {
			$domain = $this->plugin_slug;
			$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
			load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale . '.mo' );
		}
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles_scripts() {
		if ( bp_is_active( 'groups' ) ) {
			// Styles
			if ( is_rtl() ) {
				wp_enqueue_style( $this->plugin_slug . '-plugin-styles-rtl', plugins_url( 'css/public-rtl.css', __FILE__ ), array(), $this->version );
			} else {
				wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), $this->version );
			}

			// Scripts
			wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'js/public.min.js', __FILE__ ), array( 'jquery' ), $this->version );
		}
	}


	/* Changes to the groups directory view. **********************************/
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
		if ( 'groups/groups-loop' == $slug && hgbp_get_directory_as_tree_setting() ) {
			/*
			 * Add our setting to the front of the array, for the main groups
			 * directory and a single group's hierarchy screen.
			 * Make sure this isn't the "my groups" view on the main directory
			 * or a user's groups screen--those directories must be flat.
			 */
			if ( ! hgbp_is_my_groups_view() ) {
				array_unshift( $templates, 'groups/groups-loop-tree.php' );
			}
		}
		return $templates;
	}

	/**
	 * Add bp_has_groups filters right before the directory is rendered.
	 * This helps avoid modifying the "single-group" use of bp_has_group() used
	 * to render the group wrapper.
	 *
	 * @since 1.0.0
 	 */
	public function add_has_group_parse_arg_filters() {
		add_filter( 'bp_after_has_groups_parse_args', array( $this, 'filter_has_groups_args' ) );
	}

	/**
	 * Remove bp_has_groups filters right before the directory is rendered.
	 * This helps avoid modifying the other use of bp_has_group() like
	 * widgets that might appear on a page with a group directory.
	 *
	 * @since 1.0.0
 	 */
	public function remove_has_group_parse_arg_filters() {
		remove_filter( 'bp_after_has_groups_parse_args', array( $this, 'filter_has_groups_args' ) );
	}

	/**
	 * Adds toggle allowing user to choose whether to restrict groups list
	 * to top-level groups (working top down), or whether to intermingle.
	 *
 	 * @since 1.0.0
	 *
	 * @return 	string html markup
	 */
	public function output_enable_tree_checkbox() {
		if ( ! hgbp_get_directory_as_tree_setting() ) {
			return;
		}

		// Calculate the checkbox status, based on the cookie value.
		$checked = true;
		if ( isset( $_COOKIE['bp-groups-use-tree-view'] ) && 0 == $_COOKIE['bp-groups-use-tree-view'] ) {
			$checked = false;
		}

		// Set the label. Check for a saved option for this string first.
		$label = bp_get_option( 'hgbp-directory-enable-tree-view-label' );
		// Next, allow translations to be applied.
		if ( empty( $label ) ) {
			$label = __( 'Include top-level groups only.', 'hierarchical-groups-for-bp' );
		}

		/**
		 * Filters the "enable tree view" toggle label.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Label to use.
		 */
		$label = apply_filters( 'hgbp_directory_enable_tree_view_label', $label );
		?>
		<li class="hgbp-enable-tree-view-container no-ajax" id="hgbp-enable-tree-view-container" style="float:left;">
			<input id="hgbp-enable-tree-view" name="hgbp-enable-tree-view" type="checkbox" <?php checked( $checked ); ?> class="no-ajax" /> <label for="hgbp-enable-tree-view" class="no-ajax"><?php echo $label; ?></label>
		</li>
		<?php
	}

	/**
	 * Filter has_groups parameters to change results on the main directory
	 * and on a single group's hierarchy screen.
	 *
	 * @since 1.0.0
	 *
	 * @param $args Array of parsed arguments.
	 *
	 * @return array
 	 */
	public function filter_has_groups_args( $args ) {
		global $hgbp_group_loop_parent_group_id;

		/*
		 * Should we filter this groups loop at all?
		 * We only want to filter if adding the hierarchy makes sense.
		 * For instance, if a user searches for groups matching "oboes",
		 * they probably want the results, not only groups that match "oboes"
		 * AND have a parent_id of 0.
		 * Adding the toggle means that if a user choosing an orderby, we let
		 * them decide whether they want hierarchical results or not.
		 * We never apply hierarchy to a "my groups" view, because a user
		 * would have to belong to all ancestor groups of a child group they
		 * belong to in order to see that child group.
		 * This is a guess.
		 * Feel free to customize the guess for your site using the
		 * 'hgbp_enable_has_group_args_filter' filter.
		 */
		$use_tree = hgbp_get_directory_as_tree_setting();

		// If the tree view is allowed, has the user set a preference?
		if ( $use_tree && isset( $_COOKIE['bp-groups-use-tree-view'] ) ) {
			$use_tree = (bool) $_COOKIE['bp-groups-use-tree-view'];
		}

		$force_parent_id = false;
		if ( $use_tree ) {
			// Check that the incoming args are basically defaults.
			if (
					( empty( $args['slug'] ) )
					&& ( empty( $args['include'] ) )
					&& ( empty( $args['parent_id'] ) )
					&& ( empty( $args['scope'] ) || 'personal' != $args['scope'] )
				) {
				$force_parent_id = true;
			}
		}

		/**
		 * Filters whether or not to apply a parent_id to a groups loop.
		 *
		 * @since 1.0.0
		 *
		 * @param bool  $force_parent_id Whether to apply a parent_id to a groups loop.
		 * @param array $args            Incoming bp_has_groups() args.
		 */
		$force_parent_id = apply_filters( 'hgbp_enable_has_group_args_force_parent_id', $force_parent_id, $args );

		// Maybe set the parent_id on the main groups directory.
		if ( bp_is_groups_directory() && ! hgbp_is_my_groups_view() ) {
			if ( $force_parent_id ) {
				$args['parent_id'] = isset( $_REQUEST['parent_id'] ) ? (int) $_REQUEST['parent_id'] : 0;
			} elseif ( empty( $args['parent_id'] ) && isset( $_REQUEST['parent_id'] ) )  {
				/*
				 * Even if a parent ID is not forced, requests may still come
				 * in for subgroup loops. Respect a passed parent ID, though.
				 */
				$args['parent_id'] = (int) $_REQUEST['parent_id'];
			}
		}

		// We do have to filter some args on the single group 'hierarchy' screen.
		if ( hgbp_is_hierarchy_screen() ) {
			/*
			 * Change some of the default args to generate a directory-style loop.
			 *
			 * Use the current group id as the parent ID on a single group's
			 * hierarchy screen. (Don't override passed parent IDs, though.)
			 */
			if ( empty( $args['parent_id'] ) ) {
				// The global should have been set in the template.
				$parent_group_id   = isset( $hgbp_group_loop_parent_group_id ) ? $hgbp_group_loop_parent_group_id : bp_get_current_group_id();
				$args['parent_id'] = isset( $_REQUEST['parent_id'] ) ? (int) $_REQUEST['parent_id'] : $parent_group_id;
			}
			// Unset the type and slug set in bp_has_groups() when in a single group.
			$args['type'] = $args['slug'] = null;
			// Set update_admin_cache to true, because this is actually a directory.
			$args['update_admin_cache'] = true;
		}

		return $args;
	}

	/**
	 * Add the "has-children" class to items that have children.
	 *
	 * @since 1.0.0
	 *
	 * @param array $classes Array of determined classes for the group.
	 *
	 * @return array
 	 */
	public function filter_group_classes( $classes ) {
		if ( $has_children = hgbp_group_has_children( bp_get_group_id(), bp_loggedin_user_id(), 'directory' ) ) {
			$classes[] = 'has-children';
		}
		return $classes;
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
			bp_get_template_part( 'groups/single/single-group-hierarchy-screen' );
		} else {
			bp_get_template_part( 'groups/groups-loop' );
		}

		exit;
	}


	/* Changes to single group behavior. **************************************/
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
	public function make_permalink_hierarchical( $permalink, $group = null ) {

 		if ( is_null( $group ) ) {
 			return $permalink;
 		}

		// We only need to filter if this not a top-level group.
		if ( $group->parent_id != 0 ) {
			$group_path = hgbp_build_hierarchical_slug( $group->id );
			$permalink  = trailingslashit( bp_get_groups_directory_permalink() . $group_path );
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

			// The current group slug is the 'bp_current_action'.
			$parent_id = groups_get_id( bp_current_action() );

			/*
			 * The Single Group Globals section of BP_Groups_Component::setup_globals()
			 * uses the current action to set up the current group. Pull found
			 * group slugs out of the action variables array.
			 */
			foreach ( $action_variables as $maybe_slug ) {
				if ( $parent_id = hgbp_child_group_exists( $maybe_slug, $parent_id ) ) {
					$bp->current_action = array_shift( $bp->action_variables );
				} else {
					// If we've gotten into real action variables, stop.
					break;
				}
			}
		}
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
		$include_activity = hgbp_group_include_hierarchical_activity( $group_id );

		switch ( $include_activity ) {
			case 'include-from-both':
				$parents = hgbp_get_ancestor_group_ids( $group_id, bp_loggedin_user_id(), 'activity' );
				$children  = hgbp_get_descendent_groups( $group_id, bp_loggedin_user_id(), 'activity' );
				$child_ids = wp_list_pluck( $children, 'id' );
				$include   = array_merge( array( $group_id ), $parents, $child_ids );
				break;
			case 'include-from-parents':
				$parents = hgbp_get_ancestor_group_ids( $group_id, bp_loggedin_user_id(), 'activity' );
				// Add the parent IDs to the main group ID.
				$include = array_merge( array( $group_id ), $parents );
				break;
			case 'include-from-children':
				$children  = hgbp_get_descendent_groups( $group_id, bp_loggedin_user_id(), 'activity' );
				$child_ids = wp_list_pluck( $children, 'id' );
				// Add the child IDs to the main group ID.
				$include   = array_merge( array( $group_id ), $child_ids );
				break;
			case 'include-from-none':
			default:
				// Do nothing.
				$include = false;
				break;
		}

		if ( ! empty( $include ) ) {
			$args['primary_id'] = $include;
		}

		return $args;
	}


	/* Add user capability checks. ********************************************/
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
		if ( 'hgbp_change_include_activity' == $capability ) {

			$global_setting = hgbp_get_global_activity_enforce_setting();

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
			// We need to know which group is in question.
			if ( empty( $args['group_id'] ) ) {
				return false;
			}

			// Site admins can do the hokey pokey.
			if ( bp_user_can( $user_id, 'bp_moderate' ) ) {
				$retval = true;
			} else {
				$group_id = (int) $args['group_id'];

				// Possible settings for the group meta setting 'allowed_subgroup_creators'
				$creator_setting = hgbp_get_allowed_subgroup_creators( $group_id );
				switch ( $creator_setting ) {
					case 'admin' :
						$retval = groups_is_user_admin( $user_id, $group_id );
						break;

					case 'mod' :
						$retval = ( groups_is_user_mod( $user_id, $group_id )
									|| groups_is_user_admin( $user_id, $group_id ) );
						break;

					case 'member' :
						$retval = groups_is_user_member( $user_id, $group_id );
						break;

					case 'loggedin' :
						$retval = is_user_logged_in();
						break;

					case 'noone' :
					default :
						$retval = false;
						break;
				}
			}
		}

		return $retval;
	}

}
