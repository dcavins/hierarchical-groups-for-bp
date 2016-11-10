<?php

class HGBP_Tests extends HGBP_TestCase {

	public function test_hgbp_get_child_groups_no_user_scope() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );

		$children = hgbp_get_child_groups( $g1 );
		$found = wp_list_pluck( $children, 'id' );

		$this->assertEqualSets( array( $g2, $g3), $found );
	}

	public function test_hgbp_get_child_groups_user_scope_logged_out() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );

		$children = hgbp_get_child_groups( $g1, 0 );
		$found = wp_list_pluck( $children, 'id' );

		$this->assertEqualSets( array( $g2 ), $found );
	}

	public function test_hgbp_get_child_groups_user_scope_not_group_member() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );
		$u1 = $this->factory->user->create();

		$children = hgbp_get_child_groups( $g1, $u1 );
		$found = wp_list_pluck( $children, 'id' );

		$this->assertEqualSets( array( $g2 ), $found );
	}

	public function test_hgbp_get_child_groups_user_scope_group_member() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
			'status'     => 'hidden',
			'creator_id' => $u1,
		) );

		$children = hgbp_get_child_groups( $g1, $u1 );
		$found = wp_list_pluck( $children, 'id' );

		$this->assertEqualSets( array( $g2, $g3 ), $found );
	}

	public function test_hgbp_get_child_groups_user_scope_site_admin() {
		$u1 = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
			'status'     => 'hidden',
		) );

		$children = hgbp_get_child_groups( $g1, $u1 );
		$found = wp_list_pluck( $children, 'id' );

		$this->assertEqualSets( array( $g2, $g3 ), $found );
	}

	public function test_hgbp_get_descendent_groups_no_user_scope() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g5 = $this->factory->group->create();

		$children = hgbp_get_descendent_groups( $g1 );
		$found = wp_list_pluck( $children, 'id' );

		$this->assertEqualSets( array( $g2, $g3, $g4 ), $found );
	}

	public function test_hgbp_get_descendent_groups_user_scope_logged_out() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g5 = $this->factory->group->create();

		$children = hgbp_get_descendent_groups( $g1, 0 );
		$found = wp_list_pluck( $children, 'id' );

		$this->assertEqualSets( array( $g2, $g4 ), $found );
	}

	public function test_hgbp_get_descendent_groups_user_scope_not_group_member() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g5 = $this->factory->group->create();
		$u1 = $this->factory->user->create();

		$children = hgbp_get_descendent_groups( $g1, $u1 );
		$found = wp_list_pluck( $children, 'id' );

		$this->assertEqualSets( array( $g2, $g4 ), $found );
	}

	public function test_hgbp_get_descendent_groups_user_scope_group_member() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
			'status'     => 'hidden',
			'creator_id' => $u1,
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g5 = $this->factory->group->create();

		$children = hgbp_get_descendent_groups( $g1, $u1 );
		$found = wp_list_pluck( $children, 'id' );

		$this->assertEqualSets( array( $g2, $g3, $g4 ), $found );
	}

	public function test_hgbp_get_descendent_groups_user_scope_site_admin() {
		$u1 = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->grant_super_admin( $u1 );
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
			'status'     => 'hidden',
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g5 = $this->factory->group->create();

		$children = hgbp_get_descendent_groups( $g1, $u1 );
		$found = wp_list_pluck( $children, 'id' );

		$this->assertEqualSets( array( $g2, $g3, $g4 ), $found );
	}

	public function test_hgbp_get_ancestor_group_ids_no_user_scope() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g5 = $this->factory->group->create();

		$children = hgbp_get_ancestor_group_ids( $g4 );

		$this->assertEqualSets( array( $g1, $g2 ), $children );
	}

	public function test_hgbp_get_ancestor_group_ids_user_scope_logged_out() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g5 = $this->factory->group->create();

		$children = hgbp_get_ancestor_group_ids( $g4, 0 );

		$this->assertEqualSets( array( $g1, $g2 ), $children );
	}

	public function test_hgbp_get_ancestor_group_ids_user_scope_logged_out_w_hidden() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();

		$children = hgbp_get_ancestor_group_ids( $g4, 0 );

		$this->assertEqualSets( array(), $children );
	}

	public function test_hgbp_get_ancestor_group_ids_user_scope_not_group_member() {
		$g1 = $this->factory->group->create( array(
			'status' => 'hidden',
		) );
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();
		$u1 = $this->factory->user->create();

		$children = hgbp_get_ancestor_group_ids( $g4, $u1 );

		$this->assertEqualSets( array( $g3 ), $children );
	}

	public function test_hgbp_get_ancestor_group_ids_user_scope_group_member() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
			'status'     => 'hidden',
			'creator_id' => $u1,
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();

		$children = hgbp_get_ancestor_group_ids( $g4, $u1 );

		$this->assertEqualSets( array( $g1, $g3 ), $children );
	}

	public function test_hgbp_get_ancestor_group_ids_user_scope_site_admin() {
		$u1 = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->grant_super_admin( $u1 );
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
			'status'     => 'hidden',
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();

		$children = hgbp_get_ancestor_group_ids( $g4, $u1 );

		$this->assertEqualSets( array( $g1, $g3 ), $children );
	}

	public function test_hgbp_get_possible_parent_groups_user_is_nonmember_member_allowed() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();
		$g6 = $this->factory->group->create();

		// Members can create subgroups.
		groups_update_groupmeta( $g6, 'allowed_subgroup_creators', 'member' );

		$groups = hgbp_get_possible_parent_groups( $g1, $u1 );
		$found = wp_list_pluck( $groups, 'id' );

		$this->assertEqualSets( array(), $found );
	}

	public function test_hgbp_get_possible_parent_groups_user_is_member_member_allowed() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();
		$g6 = $this->factory->group->create();

		// Make $u1 a member.
		$this->add_user_to_group( $u1, $g6 );

		// Members can create subgroups.
		groups_add_groupmeta( $g6, 'allowed_subgroup_creators', 'member' );

		$groups = hgbp_get_possible_parent_groups( $g1, $u1 );
		$found = wp_list_pluck( $groups, 'id' );

		$this->assertEqualSets( array( $g6 ), $found );
	}

	public function test_hgbp_get_possible_parent_groups_user_is_member_mod_allowed() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();
		$g6 = $this->factory->group->create();

		// Make $u1 a member.
		$this->add_user_to_group( $u1, $g6 );

		// Members can create subgroups.
		groups_update_groupmeta( $g6, 'allowed_subgroup_creators', 'mod' );

		$groups = hgbp_get_possible_parent_groups( $g1, $u1 );
		$found = wp_list_pluck( $groups, 'id' );

		$this->assertEqualSets( array(), $found );
	}

	public function test_hgbp_get_possible_parent_groups_user_is_mod_mod_allowed() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();
		$g6 = $this->factory->group->create();

		// Make $u1 a member, then promote.
		$this->add_user_to_group( $u1, $g6 );
		$m1 = new BP_Groups_Member( $u1, $g6 );
		$m1->promote( 'mod' );

		// Members can create subgroups.
		groups_update_groupmeta( $g6, 'allowed_subgroup_creators', 'mod' );

		$groups = hgbp_get_possible_parent_groups( $g1, $u1 );
		$found = wp_list_pluck( $groups, 'id' );

		$this->assertEqualSets( array( $g6 ), $found );
	}

	public function test_hgbp_get_possible_parent_groups_user_is_mod_admin_allowed() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();
		$g6 = $this->factory->group->create();

		// Make $u1 a member, then promote.
		$this->add_user_to_group( $u1, $g6 );
		$m1 = new BP_Groups_Member( $u1, $g6 );
		$m1->promote( 'mod' );

		// Members can create subgroups.
		groups_update_groupmeta( $g6, 'allowed_subgroup_creators', 'admin' );

		$groups = hgbp_get_possible_parent_groups( $g1, $u1 );
		$found = wp_list_pluck( $groups, 'id' );

		$this->assertEqualSets( array(), $found );
	}

	public function test_hgbp_get_possible_parent_groups_user_is_admin_admin_allowed() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();
		$g6 = $this->factory->group->create();

		// Make $u1 a member, then promote.
		$this->add_user_to_group( $u1, $g6 );
		$m1 = new BP_Groups_Member( $u1, $g6 );
		$m1->promote( 'admin' );

		// Members can create subgroups.
		groups_update_groupmeta( $g6, 'allowed_subgroup_creators', 'admin' );

		$groups = hgbp_get_possible_parent_groups( $g1, $u1 );
		$found = wp_list_pluck( $groups, 'id' );

		$this->assertEqualSets( array( $g6 ), $found );
	}

	public function test_hgbp_get_possible_parent_groups_user_is_admin_noone_allowed() {
		$u1 = $this->factory->user->create();
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();
		$g6 = $this->factory->group->create();

		// Make $u1 a member, then promote.
		$this->add_user_to_group( $u1, $g6 );
		$m1 = new BP_Groups_Member( $u1, $g6 );
		$m1->promote( 'admin' );

		// Members can create subgroups.
		groups_update_groupmeta( $g6, 'allowed_subgroup_creators', 'noone' );

		$groups = hgbp_get_possible_parent_groups( $g1, $u1 );
		$found = wp_list_pluck( $groups, 'id' );

		$this->assertEqualSets( array(), $found );
	}

	public function test_hgbp_get_possible_parent_groups_user_scope_site_admin() {
		$u1 = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->grant_super_admin( $u1 );
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id'  => $g1,
		) );
		$g4 = $this->factory->group->create( array(
			'parent_id' => $g3,
		) );
		$g5 = $this->factory->group->create();
		$g6 = $this->factory->group->create();

		$groups = hgbp_get_possible_parent_groups( $g1, $u1 );
		$found = wp_list_pluck( $groups, 'id' );

		$this->assertEqualSets( array( $g5, $g6 ), $found );
	}

	/**
	 * @group hgbp_build_hierarchical_slug
	 */
	public function test_hgbp_build_hierarchical_slug_top_level() {
		$slugs = array( 'cero', 'uno', 'dos', 'tres', 'cuatro' );
		$g1 = $this->factory->group->create( array(
			'slug' => $slugs[1]
		) );
		$g2 = $this->factory->group->create( array(
			'slug' => $slugs[2],
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'slug' => $slugs[3],
			'parent_id'  => $g2,
		) );

		$path = hgbp_build_hierarchical_slug( $g1 );
		$this->assertEquals( $slugs[1], $path );
	}

	/**
	 * @group hgbp_build_hierarchical_slug
	 */
	public function test_hgbp_build_hierarchical_slug_two_levels() {
		$slugs = array( 'cero', 'uno', 'dos', 'tres', 'cuatro' );
		$g1 = $this->factory->group->create( array(
			'slug' => $slugs[1]
		) );
		$g2 = $this->factory->group->create( array(
			'slug' => $slugs[2],
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'slug' => $slugs[3],
			'parent_id'  => $g2,
		) );

		$path = hgbp_build_hierarchical_slug( $g2 );
		$this->assertEquals( $slugs[1] . '/' . $slugs[2], $path );
	}

	/**
	 * @group hgbp_build_hierarchical_slug
	 */
	public function test_hgbp_build_hierarchical_slug_three_levels() {
		$slugs = array( 'cero', 'uno', 'dos', 'tres', 'cuatro' );
		$g1 = $this->factory->group->create( array(
			'slug' => $slugs[1]
		) );
		$g2 = $this->factory->group->create( array(
			'slug' => $slugs[2],
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'slug' => $slugs[3],
			'parent_id'  => $g2,
		) );

		$path = hgbp_build_hierarchical_slug( $g3 );
		$this->assertEquals( $slugs[1] . '/' . $slugs[2] . '/' . $slugs[3], $path );
	}
}
