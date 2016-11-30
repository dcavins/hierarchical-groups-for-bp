<?php

/**
 * @group cache
 */
class HGBP_Cache_Tests extends HGBP_TestCase {

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

}
