<?php

class NamespaceTest extends \WP_UnitTestCase {


	/**
	 * Test PB style class initializations
	 */
	public function test_classInitConventions() {
		global $wp_filter;
		$classes = [
			'\PressbooksCasSso\Admin',
			'\PressbooksCasSso\CAS',
		];
		foreach ( $classes as $class ) {
			$result = $class::init();
			$this->assertInstanceOf( $class, $result );
			$class::hooks( $result );
			$this->assertNotEmpty( $wp_filter );
		}
	}


	public function test_blade() {
		$blade = \PressbooksCasSso\blade();
		$this->assertTrue( is_object( $blade ) );
	}

}
