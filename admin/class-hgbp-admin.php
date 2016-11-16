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
		// add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( bp_core_admin_hook(), array( $this, 'add_plugin_admin_menu' ), 99 );
		add_action( bp_core_admin_hook(), array( $this, 'settings_init' ), 99 );

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
			array( $this, 'activity_propagation_section_callback' ),
			$this->plugin_slug
		);

		register_setting( $this->plugin_slug, 'hgbp-include-activity-from-children', array( $this, 'sanitize_yes_no' ) );
		add_settings_field(
			'hgbp-include-activity-from-children',
			__( 'Include child group activity in parent group activity streams.', 'hierarchical-groups-for-bp' ),
			array( $this, 'render_syndicate_activity_up' ),
			$this->plugin_slug,
			'hgbp_activity_syndication'
		);

		register_setting( $this->plugin_slug, 'hgbp-include-activity-from-children-enforce', 'hgbp_sanitize_group_setting_enforce' );
		add_settings_field(
			'hgbp-include-activity-from-children-enforce',
			__( 'Global setting enforcement: Upward syndication', 'hierarchical-groups-for-bp' ),
			array( $this, 'render_syndicate_activity_up_enforce' ),
			$this->plugin_slug,
			'hgbp_activity_syndication'
		);

		register_setting( $this->plugin_slug, 'hgbp-include-activity-from-parents', array( $this, 'sanitize_yes_no' ) );
		add_settings_field(
			'hgbp-include-activity-from-parents',
			__( 'Include parent group activity in child group activity streams.', 'hierarchical-groups-for-bp' ),
			array( $this, 'render_syndicate_activity_down' ),
			$this->plugin_slug,
			'hgbp_activity_syndication'
		);

		register_setting( $this->plugin_slug, 'hgbp-include-activity-from-parents-enforce', 'hgbp_sanitize_group_setting_enforce' );
		add_settings_field(
			'hgbp-include-activity-from-parents-enforce',
			__( 'Global setting enforcement: Downward syndication', 'hierarchical-groups-for-bp' ),
			array( $this, 'render_syndicate_activity_down_enforce' ),
			$this->plugin_slug,
			'hgbp_activity_syndication'
		);
	}

	/**
	 * Provide a section description for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function activity_propagation_section_callback() {
		_e( 'Hierarchy settings can be set per-group or globally. Set global defaults here. Note that users will not see activity from groups they cannot visit.', 'hierarchical-groups-for-bp' );
	}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_syndicate_activity_up() {
		$setting  = hgbp_get_global_activity_setting( 'children' );
		$selected = ( $setting == 'yes' ) ? true : false;
		?>
		<label for="syndicate-activity-up-yes"><input type="radio" id="syndicate-activity-up-yes" name="hgbp-include-activity-from-children" value="yes"<?php checked( 'yes', $selected ); ?>> <?php _ex( 'Yes', 'Affirmative response for include child group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>
		<label for="syndicate-activity-up-no"><input type="radio" id="syndicate-activity-up-no" name="hgbp-include-activity-from-children" value="no"<?php checked( false, $selected ); ?>> <?php _ex( 'No', 'Negative response for include child group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>
		<?php
	}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_syndicate_activity_up_enforce() {
		$setting = hgbp_get_global_activity_enforce_setting( 'children' );
		?>
		<label for="hgbp-include-activity-from-children-enforce-group-admins"><input type="radio" id="hgbp-include-activity-from-children-enforce-group-admins" name="hgbp-include-activity-from-children-enforce" value="group-admins"<?php checked( 'group-admins', $setting ); ?>> <?php _ex( 'Allow setting to be overriden by group admins.', 'Response for allow overrides of child group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>
		<label for="hgbp-include-activity-from-children-enforce-site-admins"><input type="radio" id="hgbp-include-activity-from-children-enforce-site-admins" name="hgbp-include-activity-from-children-enforce" value="site-admins"<?php checked( 'site-admins', $setting ); ?>> <?php _ex( 'Allow setting to be overriden by site admins.', 'Response for allow overrides of child group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>
		<label for="hgbp-include-activity-from-children-enforce-strict"><input type="radio" id="hgbp-include-activity-from-children-enforce-strict" name="hgbp-include-activity-from-children-enforce" value="strict"<?php checked( 'strict', $setting ); ?>> <?php _ex( 'Enforce setting for all groups.', 'Response for allow overrides of child group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>
		<?php
	}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_syndicate_activity_down() {
		$setting = hgbp_get_global_activity_setting( 'parents' );
		$selected = ( $setting == 'yes' ) ? true : false;
		?>
		<label for="hgbp-include-activity-from-parents-yes"><input type="radio" id="hgbp-include-activity-from-parents-yes" name="hgbp-include-activity-from-parents" value="yes"<?php checked( true, $selected ); ?>> <?php _ex( 'Yes', 'Affirmative response for include parent group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>
		<label for="hgbp-include-activity-from-parents-no"><input type="radio" id="hgbp-include-activity-from-parents-no" name="hgbp-include-activity-from-parents" value="no"<?php checked( false, $selected ); ?>> <?php _ex( 'No', 'Negative response for include parent group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>
		<?php
	}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_syndicate_activity_down_enforce() {
		$setting = hgbp_get_global_activity_enforce_setting( 'parents' )
		?>
		<label for="hgbp-include-activity-from-parents-enforce-group-admins"><input type="radio" id="hgbp-include-activity-from-parents-enforce-group-admins" name="hgbp-include-activity-from-parents-enforce" value="group-admins"<?php checked( 'group-admins', $setting ); ?>> <?php _ex( 'Allow setting to be overriden by group admins.', 'Response for allow overrides of parent group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>
		<label for="hgbp-include-activity-from-parents-enforce-site-admins"><input type="radio" id="hgbp-include-activity-from-parents-enforce-site-admins" name="hgbp-include-activity-from-parents-enforce" value="site-admins"<?php checked( 'site-admins', $setting ); ?>> <?php _ex( 'Allow setting to be overriden by site admins.', 'Response for allow overrides of parent group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>
		<label for="hgbp-include-activity-from-parents-enforce-strict"><input type="radio" id="hgbp-include-activity-from-parents-enforce-strict" name="hgbp-include-activity-from-parents-enforce" value="strict"<?php checked( 'strict', $setting ); ?>> <?php _ex( 'Enforce setting for all groups.', 'Response for allow overrides of parent group activity global setting', 'hierarchical-groups-for-bp' ); ?></label>
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

	/**
	 * Sanitize saved input for yes or no questions.
	 *
	 * @since    1.0.0
	 */
	public function sanitize_yes_no( $input ) {
		if ( 'yes' != $input ) {
			$input = 'no';
		}
		return $input;
	}

}
