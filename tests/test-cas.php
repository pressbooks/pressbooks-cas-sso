<?php

class CasTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\CAS\CAS
	 */
	protected $cas;

	/**
	 *
	 */
	public function setUp() {

		parent::setUp();

		$stub1 = $this
			->getMockBuilder( '\Pressbooks\CAS\Admin' )
			->getMock();
		$stub1
			->method( 'getOptions' )
			->willReturn(
				[
					'server_version' => 'CAS_VERSION_2_0',
					'server_hostname' => 'cas.server.edu',
					'server_port' => 443,
					'server_path' => '/',
					'provision' => 'create',
					'email_domain' => '',
					'button_text' => '',
					'bypass' => 0,
					'forced_redirection' => 0,
				]
			);

		// Ignore session warnings
		PHPUnit_Framework_Error_Notice::$enabled = false;
		PHPUnit_Framework_Error_Warning::$enabled = false;
		ini_set( 'error_reporting', 0 );
		ini_set( 'display_errors', 0 );

		CAS_GracefullTerminationException::throwInsteadOfExiting();
		$this->cas = new \Pressbooks\CAS\CAS( $stub1 );

		PHPUnit_Framework_Error_Notice::$enabled = true;
		PHPUnit_Framework_Error_Warning::$enabled = true;
		ini_set( 'error_reporting', 1 );
		ini_set( 'display_errors', 1 );
	}


	public function test_changeLoginUrl() {
		$url = $this->cas->changeLoginUrl( 'https://pressbooks.test' );
		$this->assertContains( 'action=pb_cas', $url );
	}

	public function test_showPasswordFields() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		$user = get_userdata( $user_id );
		$this->assertTrue( is_bool( $this->cas->showPasswordFields( true, $user ) ) );
	}

	public function test_authenticate() {
		$result = $this->cas->authenticate( null, 'test', 'test' );
		$this->assertNull( $result );

		$_REQUEST['action'] = 'pb_cas';
		try {
			$result = $this->cas->authenticate( null, 'test', 'test' );
		} catch ( \LogicException $e ) {
			$this->assertContains( '</html>', $e->getMessage() ); // phpCas generated error, other people's code is untestable
		}
	}


	public function test_logoutRedirect() {
		try {
			$_SESSION['phpCAS']['user'] = 1;
			ob_start();
			$result = $this->cas->logoutRedirect( 'nochange' );
		} catch ( \Exception $e ) {
			ob_get_clean();
			$this->assertTrue( true ); // phpCas trying to redirect
			return;
		}
		$this->assertTrue( false ); // If PHPUnit gets to here then this test has failed
	}

	public function test_loginEnqueueScripts() {
		$this->cas->loginEnqueueScripts();
		$this->assertContains( 'pressbooks-cas-sso', get_echo( 'wp_print_scripts' ) );
	}

	public function test_loginForm() {
		ob_start();
		$this->cas->loginForm();
		$buffer = ob_get_clean();
		$this->assertContains( '<div id="pb-cas-wrap">', $buffer );
	}

	public function test_handleLoginAttempt_and_matchUser_and_so_on() {
		$prefix = uniqid( 'test' );
		$email = "{$prefix}@pressbooks.test";

		$user = $this->cas->matchUser( $prefix );
		$this->assertFalse( $user );

		try {
			$this->cas->handleLoginAttempt( $prefix, $email );
			$this->assertInstanceOf( '\WP_User', get_user_by( 'email', $email ) );
		} catch (\Exception $e ) {
			$this->assertTrue( false );
		}

		$user = $this->cas->matchUser( $prefix );
		$this->assertInstanceOf( '\WP_User',  $user );
	}

	public function test_handleLoginAttempt_exceptions() {
		try {
			$this->cas->handleLoginAttempt( '1', '1' );
		} catch ( \Exception $e ) {
			$this->assertContains( 'Please enter a valid email address', $e->getMessage() );
			$this->assertContains( 'Username must be at least 4 characters', $e->getMessage() );
			$this->assertContains( 'usernames must have letters too', $e->getMessage() );
			return;
		}
		$this->fail();
	}


	public function test_endLogin() {
		$this->cas->endLogin( 'My message' );
		$this->assertTrue( in_array( 'My message', $_SESSION['pb_notices'] ) );
	}

}
