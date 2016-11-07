<?php

/**
 * @group permissions
 */
class HGBP_Tests_Permissions extends HGBP_TestCase {
	/**
	 * @group hgbp_user_can
	 */
	function test_loggedout_user_cannot_create() {
		$this->assertFalse( false );
	}
}
