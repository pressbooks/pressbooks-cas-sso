<?php

class NamespaceTest extends \WP_UnitTestCase {


	/**
	 * Test PB style class initializations
	 */
	public function test_classInitConventions() {
		$classes = [
			'\Pressbooks\CAS\Admin',
			'\Pressbooks\CAS\CAS',
			'\Pressbooks\CAS\Updates',
		];
		foreach ( $classes as $class ) {
			$result = $class::init();
			$this->assertInstanceOf( $class, $result );
		}
	}


	public function test_blade() {
		$blade = \Pressbooks\CAS\blade();
		$this->assertTrue( is_object( $blade ) );
	}

}