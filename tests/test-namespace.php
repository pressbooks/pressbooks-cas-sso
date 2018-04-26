<?php

class NamespaceTest extends \WP_UnitTestCase {

	public function test_blade() {
		$blade = \Pressbooks\CAS\blade();
		$this->assertTrue( is_object( $blade ) );
	}

}