<?php
/**
 * Hierarchical Groups for BP
 *
 * @package   HierarchicalGroupsForBP
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2016 David Cavins
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `public/class-hgbp.php`
 *
 * @package   HierarchicalGroupsForBP_Admin
 * @author  dcavins
 */
class HGBP_Admin extends HGBP_Public {

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Add actions and filters to WordPress/BuddyPress hooks.
	 *
	 * @since    1.0.0
	 */
	public function add_action_hooks() {
		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( bp_core_admin_hook(), array( $this, 'add_plugin_admin_menu' ), 99 );
		add_action( bp_core_admin_hook(), array( $this, 'settings_init' ) );

		// Add an action link pointing to the options page.
		// $plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		// add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( isset( $screen->id ) && in_array( $screen->id, array( $this->plugin_screen_hook_suffix, 'toplevel_page_bp-groups' ) ) ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), $this->version );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( isset( $screen->id ) && in_array( $screen->id, array( $this->plugin_screen_hook_suffix, 'toplevel_page_bp-groups' ) ) ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		$this->plugin_screen_hook_suffix = add_submenu_page(
			'bp-groups',
			__( 'Hierarchy Options', 'hierarchical-groups-for-bp' ),
			__( 'Hierarchy Options', 'hierarchical-groups-for-bp' ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Register the settings and set up the sections and fields for the
	 * global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function settings_init() {
		add_settings_section(
			'hgbp_activity_syndication',
			__( 'Group Activity Propagation', 'hierarchical-groups-for-bp' ),
			array( $this, 'activity_syndication_section_callback' ),
			$this->plugin_slug
		);

		register_setting( $this->plugin_slug, 'hgbp-include-activity-from-relatives', 'hgbp_sanitize_include_setting' );
		add_settings_field(
			'hgbp-include-activity-from-relatives',
			__( 'Include related group activity in group activity streams.', 'hierarchical-groups-for-bp' ),
			array( $this, 'render_include_activity_input' ),
			$this->plugin_slug,
			'hgbp_activity_syndication'
		);

		register_setting( $this->plugin_slug, 'hgbp-include-activity-enforce', 'hgbp_sanitize_include_setting_enforce' );
		add_settings_field(
			'hgbp-include-activity-enforce',
			__( 'Who can override this setting for each group?', 'hierarchical-groups-for-bp' ),
			array( $this, 'render_include_activity_enforce_input' ),
			$this->plugin_slug,
			'hgbp_activity_syndication'
		);

		// Setting for showing groups directory as tree.
		add_settings_section(
			'hgbp_use_tree_directory_template',
			__( 'Show the groups directories as a hierarchical tree.', 'hierarchical-groups-for-bp' ),
			array( $this, 'group_tree_section_callback' ),
			$this->plugin_slug
		);

		register_setting( $this->plugin_slug, 'hgbp-groups-directory-show-tree', 'absint' );
		add_settings_field(
			'hgbp-groups-directory-show-tree',
			__( 'Replace the flat groups directory with a hierarchical directory.', 'hierarchical-groups-for-bp' ),
			array( $this, 'render_groups_directory_show_tree' ),
			$this->plugin_slug,
			'hgbp_use_tree_directory_template'
		);
	}

	/**
	 * Provide a section description for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function activity_syndication_section_callback() {
		_e( 'Hierarchy settings can be set per-group or globally. Set global defaults here. Note that users will not see activity from groups they cannot visit.', 'hierarchical-groups-for-bp' );
	}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_include_activity_input() {
		$setting  = hgbp_get_global_activity_setting();
		?>
		<label for="include-activity-from-parents"><input type="radio" id="include-activity-from-parents" name="hgbp-include-activity-from-relatives" value="include-from-parents"<?php checked( 'include-from-parents', $setting ); ?>> <?php _e( '<strong>Include parent group activity</strong> in every group activity stream.', 'hierarchical-groups-for-bp' ); ?></label>

		<label for="include-activity-from-children"><input type="radio" id="include-activity-from-children" name="hgbp-include-activity-from-relatives" value="include-from-children"<?php checked( 'include-from-children', $setting ); ?>> <?php _e( '<strong>Include child group activity</strong> in every group activity stream.', 'hierarchical-groups-for-bp' ); ?></label>

		<label for="include-activity-from-both"><input type="radio" id="include-activity-from-both" name="hgbp-include-activity-from-relatives" value="include-from-both"<?php checked( 'include-from-both', $setting ); ?>> <?php _e( '<strong>Include parent and child group activity</strong> in every group activity stream.', 'hierarchical-groups-for-bp' ); ?></label>

		<label for="include-activity-from-none"><input type="radio" id="include-activity-from-none" name="hgbp-include-activity-from-relatives" value="include-from-none"<?php checked( 'include-from-none', $setting ); ?>> <?php _e( '<strong>Do not include related group activity</strong> in any group activity stream.', 'hierarchical-groups-for-bp' ); ?></label>

		<?php
	}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_include_activity_enforce_input() {
		$setting = hgbp_get_global_activity_enforce_setting();
		?>
		<label for="hgbp-include-activity-enforce-group-admins"><input type="radio" id="hgbp-include-activity-enforce-group-admins" name="hgbp-include-activity-enforce" value="group-admins"<?php checked( 'group-admins', $setting ); ?>> <?php _ex( '<strong>Group administrators</strong> can choose a setting for their group.', 'Response for allow overrides of include group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>

		<label for="hgbp-include-activity-enforce-site-admins"><input type="radio" id="hgbp-include-activity-enforce-site-admins" name="hgbp-include-activity-enforce" value="site-admins"<?php checked( 'site-admins', $setting ); ?>> <?php _ex( '<strong>Site administrators</strong> can choose a setting for each group.', 'Response for allow overrides of include group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>

		<label for="hgbp-include-activity-enforce-strict"><input type="radio" id="hgbp-include-activity-enforce-strict" name="hgbp-include-activity-enforce" value="strict"<?php checked( 'strict', $setting ); ?>> <?php _ex( '<strong>Enforce global setting</strong> for all groups.', 'Response for allow overrides of include group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>
		<?php
	}

	/**
	 * Provide a section description for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function group_tree_section_callback() {}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_groups_directory_show_tree() {
		$setting = hgbp_get_directory_as_tree_setting();
		?>
		<label for="hgbp-groups-directory-show-tree"><input type="checkbox" id="hgbp-groups-directory-show-tree" name="hgbp-groups-directory-show-tree" value="1"<?php checked( $setting ); ?>> <?php _ex( 'Show a hierarchical directory.', 'Response for use directory tree global setting', 'hierarchical-groups-for-bp' ); ?></label>
		<?php
	}

	/**
	 * Render the global settings screen for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		?>
		<form action="<?php echo admin_url( 'options.php' ) ?>" method='post'>
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

			<?php
			settings_fields( $this->plugin_slug );
			do_settings_sections( $this->plugin_slug );
			submit_button();
			?>

		</form>
		<?php
	}

}
