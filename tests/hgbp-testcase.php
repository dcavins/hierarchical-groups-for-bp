<?php

class HGBP_TestCase extends BP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->old_current_user = get_current_user_id();
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}
}
