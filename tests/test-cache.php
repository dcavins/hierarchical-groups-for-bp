<?php

/**
 * @group cache
 */
class HGBP_Cache_Tests extends HGBP_TestCase {

	public function test_hgbp_get_child_group_ids_hits_cache() {
		global $wpdb;

		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );

		$children = hgbp_get_child_group_ids( $g1 );
		$first_query_count = $wpdb->num_queries;

		// Run it again.
		$children = hgbp_get_child_group_ids( $g1 );

		$this->assertEquals( $first_query_count, $wpdb->num_queries );
	}

	public function test_hgbp_get_descendent_groups_hits_cache() {
		global $wpdb;

		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );

		$children = hgbp_get_descendent_groups( $g1 );
		$first_query_count = $wpdb->num_queries;

		// Run it again.
		$children = hgbp_get_descendent_groups( $g1 );

		$this->assertEquals( $first_query_count, $wpdb->num_queries );
	}

	public function test_create_group_invalidates_cache() {
		global $wpdb;

		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );

		$children = hgbp_get_child_group_ids( $g1 );
		$first_inc = bp_core_get_incrementor( 'hgbp' );

		// Create a new group.
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );

		// Run it again--making sure we get the right answer.
		$children = hgbp_get_child_group_ids( $g1 );
		$second_inc = bp_core_get_incrementor( 'hgbp' );

		$this->assertEquals( array( $g2, $g3 ), $children );
		$this->assertNotEquals( $first_inc, $second_inc );
	}

	public function test_update_group_invalidates_cache() {
		global $wpdb;

		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );
		$children = hgbp_get_child_group_ids( $g1 );
		$first_inc = bp_core_get_incrementor( 'hgbp' );

		// Update a group
		groups_edit_group_settings( $g3, false, 'private', false, $g1 );

		// Run it again--making sure we get the right answer.
		$children = hgbp_get_child_group_ids( $g1 );
		$second_inc = bp_core_get_incrementor( 'hgbp' );

		$this->assertEquals( array( $g2, $g3 ), $children );
		$this->assertNotEquals( $first_inc, $second_inc );
	}

	public function test_delete_group_invalidates_cache() {
		global $wpdb;

		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g1,
			'status'    => 'hidden',
		) );
		$children = hgbp_get_child_group_ids( $g1 );
		$first_inc = bp_core_get_incrementor( 'hgbp' );

		// Delete a group.
		groups_delete_group( $g3 );

		// Run it again--making sure we get the right answer.
		$children = hgbp_get_child_group_ids( $g1 );
		$second_inc = bp_core_get_incrementor( 'hgbp' );

		$this->assertEquals( array( $g2 ), $children );
		$this->assertNotEquals( $first_inc, $second_inc );
	}

}
