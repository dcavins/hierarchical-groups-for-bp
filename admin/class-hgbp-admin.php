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

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Add the options page and menu item.
		add_action( bp_core_admin_hook(), array( $this, 'add_plugin_admin_menu' ), 99 );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// Add settings to the admin page.
		add_action( bp_core_admin_hook(), array( $this, 'settings_init' ) );

		/*
		 * Save settings. This can't be done using the Settings API, because
		 * the API doesn't handle saving settings in network admin.
		 */
		add_action( 'bp_admin_init', array( $this, 'settings_save' ) );

		// Add "Parent Group" column to the WP Groups List table.
		add_filter( 'bp_groups_list_table_get_columns', array( $this, 'add_parent_group_column' ) );
		add_filter( 'bp_groups_admin_get_group_custom_column', array( $this, 'column_content_parent_group' ), 10, 3 );

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
		$target_screens = array(
			$this->plugin_screen_hook_suffix, // Single site settings screen
			$this->plugin_screen_hook_suffix . '-network', // Network admin settings screen
			'toplevel_page_bp-groups' // Groups and single group screens
			);
		if ( isset( $screen->id ) && in_array( $screen->id, $target_screens ) ) {
			if ( is_rtl() ) {
				wp_enqueue_style( $this->plugin_slug .'-admin-styles-rtl', plugins_url( 'css/admin-rtl.css', __FILE__ ), array(), $this->version );
			} else {
				wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), $this->version );
			}
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
			'bp_moderate',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', 'hierarchical-groups-for-bp' ) . '</a>'
			),
			$links
		);
	}

	/**
	 * Register the settings and set up the sections and fields for the
	 * global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function settings_init() {

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

		// Setting for including activity in related groups.
		add_settings_section(
			'hgbp_activity_syndication',
			__( 'Group Activity Syndication', 'hierarchical-groups-for-bp' ),
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

		// Setting for what should be included on a single group's hierarchy screen.
		add_settings_section(
			'hgbp_group_hierarchy_screen_contents_section',
			__( 'Single Group Hierarchy Screen Contents', 'hierarchical-groups-for-bp' ),
			array( $this, 'group_hierarchy_screen_contents_section_callback' ),
			$this->plugin_slug
		);

		register_setting( $this->plugin_slug, 'hgbp-group-hierarchy-screen-contents', 'hgbp_sanitize_hierarchy_screen_contents_setting' );
		add_settings_field(
			'hgbp-group-hierarchy-screen-contents',
			__( 'Include these sections on a single group\'s hierarchy screen.', 'hierarchical-groups-for-bp' ),
			array( $this, 'render_group_hierarchy_screen_contents' ),
			$this->plugin_slug,
			'hgbp_group_hierarchy_screen_contents_section'
		);

		// Label customization.
		add_settings_section(
			'hgbp_labels',
			__( 'Customize labels', 'hierarchical-groups-for-bp' ),
			array( $this, 'labels_section_callback' ),
			$this->plugin_slug
		);

		register_setting( $this->plugin_slug, 'hgbp-directory-enable-tree-view-label', 'sanitize_text_field' );
		add_settings_field(
			'hgbp-directory-enable-tree-view-label',
			'',
			array( $this, 'render_hgbp_directory_enable_tree_view_label_section' ),
			$this->plugin_slug,
			'hgbp_labels'
		);

		register_setting( $this->plugin_slug, 'hgbp-directory-child-group-section-label', 'sanitize_text_field' );
		add_settings_field(
			'hgbp-directory-child-group-section-label',
			'',
			array( $this, 'render_hgbp_directory_child_group_section_label_section' ),
			$this->plugin_slug,
			'hgbp_labels'
		);

		register_setting( $this->plugin_slug, 'hgbp-directory-child-group-view-all-link', 'sanitize_text_field' );
		add_settings_field(
			'hgbp-directory-child-group-view-all-link',
			'',
			array( $this, 'render_hgbp_directory_child_group_view_all_link_section' ),
			$this->plugin_slug,
			'hgbp_labels'
		);

		register_setting( $this->plugin_slug, 'hgbp-group-tab-label', 'sanitize_text_field' );
		add_settings_field(
			'hgbp-group-tab-label',
			'',
			array( $this, 'render_group_tab_label_section' ),
			$this->plugin_slug,
			'hgbp_labels'
		);

		// Tools for importing settings from previous plugins.
		add_settings_section(
			'hgbp_import_tools',
			__( 'Import Data from Other Plugins', 'hierarchical-groups-for-bp' ),
			array( $this, 'import_tools_section_callback' ),
			$this->plugin_slug
		);

		register_setting( $this->plugin_slug, 'hgbp-run-import-tools', array( $this, 'maybe_run_import_tools' ) );
		add_settings_field(
			'hgbp-include-activity-from-relatives',
			__( 'Select an import tool to run.', 'hierarchical-groups-for-bp' ),
			array( $this, 'render_import_tools_selection' ),
			$this->plugin_slug,
			'hgbp_import_tools'
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
	 * Provide a section description.
	 *
	 * @since    1.0.0
	 */
	public function group_hierarchy_screen_contents_section_callback() {}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_group_hierarchy_screen_contents() {
		$setting = hgbp_get_group_hierarchy_screen_contents_setting();
		?>
		<label for="hgbp-group-hierarchy-screen-contents-ancestors"><input type="checkbox" id="hgbp-group-hierarchy-screen-contents-ancestors" name="hgbp-group-hierarchy-screen-contents[ancestors]" value="1"<?php checked( $setting['ancestors'] ); ?>> <?php _ex( 'Ancestor breadcrumbs', 'Response for single group hierarchy screen contents setting', 'hierarchical-groups-for-bp' ); ?></label>
		<label for="hgbp-group-hierarchy-screen-contents-siblings"><input type="checkbox" id="hgbp-group-hierarchy-screen-contents-siblings" name="hgbp-group-hierarchy-screen-contents[siblings]" value="1"<?php checked( $setting['siblings'] ); ?>> <?php _ex( 'Directory of sibling groups', 'Response for single group hierarchy screen contents setting', 'hierarchical-groups-for-bp' ); ?></label>
		<label for="hgbp-group-hierarchy-screen-contents-children"><input type="checkbox" id="hgbp-group-hierarchy-screen-contents-children" name="hgbp-group-hierarchy-screen-contents[children]" value="1"<?php checked( $setting['children'] ); ?>> <?php _ex( 'Directory of child groups', 'Response for group single hierarchy screen contents setting', 'hierarchical-groups-for-bp' ); ?></label>
		<?php
	}



	/**
	 * Provide a section description for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function labels_section_callback() {}

	/**
	 * Render "enable tree view" label setting.
	 *
	 * @since    1.0.0
	 */
	public function render_hgbp_directory_enable_tree_view_label_section() {
		$label = bp_get_option( 'hgbp-directory-enable-tree-view-label' );
		?>
		<label for="hgbp-directory-enable-tree-view-label"><?php _ex( 'Group directory &ldquo;use tree view&rdquo; toggle label:', 'Label for label setting on site hierarchy options screen', 'hierarchical-groups-for-bp' ); ?></label>&emsp;<input type="text" id="hhgbp-directory-enable-tree-view-label" name="hgbp-directory-enable-tree-view-label" value="<?php echo esc_textarea( $label ); ?>"> <a href="#TB_inline?width=650&height=630&inlineId=modal-hgbp-directory-enable-tree-view-label-location" class="thickbox"><?php _e( 'Where is this label used?', 'hierarchical-groups-for-bp' ); ?></a>
		<p class="description"><?php _e( 'Change the label of the &ldquo;use tree view&rdquo; toggle on the main group directory.', 'hierarchical-groups-for-bp' ); ?></p>

		<div id="modal-hgbp-directory-enable-tree-view-label-location" style="display:none;">
			<img src="<?php echo hgbp_get_plugin_base_uri() . 'admin/images/directory-enable-tree-view-label-location.png'; ?>" alt="<?php _e( 'Graphic showing where this label is used', 'hierarchical-groups-for-bp' ); ?>">
		</div>
		<?php
	}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_hgbp_directory_child_group_section_label_section() {
		$label = bp_get_option( 'hgbp-directory-child-group-section-label' );
		?>
		<label for="hgbp-directory-child-group-section-label"><?php _ex( 'Group directory child group section label:', 'Label for label setting on site hierarchy options screen', 'hierarchical-groups-for-bp' ); ?></label>&emsp;<input type="text" id="hgbp-directory-child-group-section-label" name="hgbp-directory-child-group-section-label" value="<?php echo esc_textarea( $label ); ?>"> <a href="#TB_inline?width=650&height=630&inlineId=modal-directory-child-group-section-label-location" class="thickbox"><?php _e( 'Where is this label used?', 'hierarchical-groups-for-bp' ); ?></a>
		<p class="description"><?php _e( 'Change the child groups section header that appears on the hierarchical version of the BuddyPress groups directory. To show the number of child groups in the label, include the string <code>%s</code> in your new label, like <code>Subgroups %s</code>.', 'hierarchical-groups-for-bp' ); ?></p>

		<div id="modal-directory-child-group-section-label-location" style="display:none;">
			<img src="<?php echo hgbp_get_plugin_base_uri() . 'admin/images/directory-child-group-section-label-location.png'; ?>" alt="<?php _e( 'Graphic showing where this label is used', 'hierarchical-groups-for-bp' ); ?>">
		</div>
		<?php
	}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_hgbp_directory_child_group_view_all_link_section() {
		$label = bp_get_option( 'hgbp-directory-child-group-view-all-link' );
		?>
		<label for="hgbp-directory-child-group-view-all-link"><?php _ex( 'Group directory child group &ldquo;view all&rdquo; link text:', 'Label for label setting on site hierarchy options screen', 'hierarchical-groups-for-bp' ); ?></label>&emsp;<input type="text" id="hgbp-directory-child-group-view-all-link" name="hgbp-directory-child-group-view-all-link" value="<?php echo esc_textarea( $label ); ?>"> <a href="#TB_inline?width=650&height=630&inlineId=modal-directory-child-group-view-all-link-location" class="thickbox"><?php _e( 'Where is this label used?', 'hierarchical-groups-for-bp' ); ?></a>
		<p class="description"><?php _e( 'Change the text of the &ldquo;view all&rdquo; link that appears on the hierarchical version of the BuddyPress groups directory. To include the name of the parent group in the linked text, include the string <code>%s</code> in your new string, like <code>View all subgroups of %s.</code>.', 'hierarchical-groups-for-bp' ); ?></p>

		<div id="modal-directory-child-group-view-all-link-location" style="display:none;">
			<img src="<?php echo hgbp_get_plugin_base_uri() . 'admin/images/directory-child-group-view-all-link-location.png'; ?>" alt="<?php _e( 'Graphic showing where this label is used', 'hierarchical-groups-for-bp' ); ?>">
		</div>
		<?php
	}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_group_tab_label_section() {
		$label = bp_get_option( 'hgbp-group-tab-label' );
		?>
		<label for="hgbp-group-tab-label"><?php _ex( 'Group navigation tab label:', 'Label for label setting on site hierarchy options screen', 'hierarchical-groups-for-bp' ); ?></label>&emsp;<input type="text" id="hgbp-group-tab-label" name="hgbp-group-tab-label" value="<?php echo esc_textarea( $label ); ?>"> <a href="#TB_inline?width=650&height=630&inlineId=modal-tab-label-location" class="thickbox"><?php _e( 'Where is this label used?', 'hierarchical-groups-for-bp' ); ?></a>
		<p class="description"><?php _e( 'Change the word on the BuddyPress group tab from &ldquo;Hierarchy&rdquo; to whatever you&rsquo;d like. To show the number of child groups in the label, include the string <code>%s</code> in your new label, like <code>Subgroups %s</code>.', 'hierarchical-groups-for-bp' ); ?></p>

		<div id="modal-tab-label-location" style="display:none;">
			<img src="<?php echo hgbp_get_plugin_base_uri() . 'admin/images/tab-label-location.png'; ?>" alt="<?php _e( 'Graphic showing where this label is used', 'hierarchical-groups-for-bp' ); ?>">
		</div>
		<?php
	}

	/**
	 * Provide a section description for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function import_tools_section_callback() {}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_import_tools_selection() {
		?>
		<label for="hgbp-run-import-tools-do-nothing"><input type="radio" id="hgbp-run-import-tools-do-nothing" name="hgbp-run-import-tools" value="do-nothing" checked="checked"> <?php _e( 'Don\'t import anything right now.', 'hierarchical-groups-for-bp' ); ?></label>

		<label for="hgbp-run-import-tools-bpgh-subgroup-creators"><input type="radio" id="hgbp-run-import-tools-bpgh-subgroup-creators" name="hgbp-run-import-tools" value="bpgh-subgroup-creators"> <?php _e( 'Import the "subgroup creators" setting for each group as set by BP Group Hierarchy.', 'hierarchical-groups-for-bp' ); ?></label>
		<?php
	}

	/**
	 * Save settings. This can't be done using the Settings API, because
	 * the API doesn't handle saving settings in network admin. This function
	 * handles saving the plugin's global settings in both the single site and
	 * network admin contexts.
	 *
	 * @since    1.0.0
	 */
	public function settings_save() {
		if ( ! isset( $_POST['option_page'] ) || $this->plugin_slug != $_POST['option_page'] ) {
			return;
		}

		/*
		 * Check nonce.
		 * Nonce name as set in settings_fields(), used to output the form's meta inputs.
		 */
		if ( ! check_admin_referer( $this->plugin_slug . '-options' ) ) {
			return;
		}

		// Check that user has the proper capability.
		if ( ! current_user_can( 'bp_moderate' ) ) {
			return;
		}

		// Clean up the passed values and update the stored values.
		$fields = array(
			'hgbp-groups-directory-show-tree'           => 'absint',
			'hgbp-include-activity-from-relatives'      => 'hgbp_sanitize_include_setting',
			'hgbp-include-activity-enforce'             => 'hgbp_sanitize_include_setting_enforce',
			'hgbp-group-hierarchy-screen-contents'      => 'hgbp_sanitize_hierarchy_screen_contents_setting',
			'hgbp-directory-enable-tree-view-label'     => 'sanitize_text_field',
			'hgbp-directory-child-group-section-label'  => 'sanitize_text_field',
			'hgbp-directory-child-group-view-all-link'  => 'sanitize_text_field',
			'hgbp-group-tab-label'                      => 'sanitize_text_field',
		);
		foreach ( $fields as $key => $sanitize_callback ) {
			$value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : '';
			$value = call_user_func( $sanitize_callback, $value );
			bp_update_option( $key, $value );
		}

		// Run import tools if needed.
		if ( isset( $_POST['hgbp-run-import-tools'] ) && 'bpgh-subgroup-creators' == $_POST['hgbp-run-import-tools'] ) {
			$this->run_import_tools();
		}

		// Redirect back to the form.
		$redirect = bp_get_admin_url( add_query_arg( array( 'page' => $this->plugin_slug, 'updated' => 'true' ), 'admin.php' ) );
		wp_redirect( $redirect );
		die();
	}

	/**
	 * Maybe run an import tool to migrate data from the old BP Group Hierarchy plugin.
	 *
	 * @since 1.0.0
	 */
	public function run_import_tools() {
		// Fetch all of the groups that have the relevant metadata.
		$group_args = array(
			'meta_query'  => array(
				array(
					'key'      => 'bp_group_hierarchy_subgroup_creators',
					'compare'  => 'exists'
				)
			),
			'show_hidden' => true,
			'per_page'    => null,
		);
		$groups = groups_get_groups( $group_args );

		foreach ( $groups[ 'groups' ] as $group ) {
			$old_setting = groups_get_groupmeta( $group->id, 'bp_group_hierarchy_subgroup_creators' );

			switch ( $old_setting ) {
				case 'anyone':
					$new_setting = 'loggedin';
					break;

				case 'group_members':
					$new_setting = 'member';
					break;

				case 'group_admins':
					$new_setting = 'admin';
					break;

				case 'noone':
				default:
					$new_setting = 'noone';
					break;
			}

			groups_update_groupmeta( $group->id, 'hgbp-allowed-subgroup-creators', $new_setting );
		}
	}

	/**
	 * Render the global settings screen for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		// Thickbox is used to display the labels location images in a modal window.
		add_thickbox();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<hr class="wp-header-end">
			<?php
			if ( ! empty( $_REQUEST[ 'updated' ] ) ) {
				?>
				<div id="message" class="updated notice notice-success">
					<p><?php _e( 'Settings updated.', 'hierarchical-groups-for-bp' ); ?></p>
				</div>
				<?php
			}
			?>
			<form action="<?php echo bp_get_admin_url( add_query_arg( array( 'page' => $this->plugin_slug ), 'admin.php' ) ); ?>" method="post">
				<?php
				settings_fields( $this->plugin_slug );
				do_settings_sections( $this->plugin_slug );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Add Parent Group column to the WordPress admin groups list table.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns Groups table columns.
	 *
	 * @return array $columns
	 */
	public function add_parent_group_column( $columns = array() ) {
		$columns['hgbp_parent_group'] = _x( 'Parent Group', 'Label for the WP groups table parent group column', 'hierarchical-groups-for-bp' );

		return $columns;
	}

	/**
	 * Markup for the Parent Group column.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value       Empty string.
	 * @param string $column_name Name of the column being rendered.
	 * @param array  $item        The current group item in the loop.
	 */
	public function column_content_parent_group( $retval = '', $column_name, $item ) {
		if ( 'hgbp_parent_group' !== $column_name ) {
			return $retval;
		}

		if ( 0 != $item[ 'parent_id' ] ) {
			$parent_group    = groups_get_group( $item[ 'parent_id' ] );
			$parent_edit_url = esc_url( add_query_arg( array(
				'page'   => 'bp-groups',
				'gid'    => $item['parent_id'],
				'action' => 'edit',
			), bp_get_admin_url( 'admin.php' ) ) );
			$retval = '<a href="' . $parent_edit_url . '">' . esc_html( bp_get_group_name( $parent_group ) ) . '</a>';
		}

		/**
		 * Filters the markup for the Parent Group column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $retval Markup for the Parent Group column.
		 * @param array  $item   The current group item in the loop.
		 */
		echo apply_filters_ref_array( 'hgbp_groups_admin_get_parent_group_column', array( $retval, $item ) );
	}
}
