<?php

class CasTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\CAS\CAS
	 */
	protected $cas;

	protected function getTestOptions() {
		return [
			'server_version' => 'CAS_VERSION_2_0',
			'server_hostname' => 'cas.server.edu',
			'server_port' => 443,
			'server_path' => '/',
			'provision' => 'create',
			'email_domain' => '',
			'button_text' => '',
			'bypass' => 0,
			'forced_redirection' => 0,
		];
	}

	/**
	 * @return \Pressbooks\CAS\Admin
	 */
	protected function getMockAdmin() {

		$stub1 = $this
			->getMockBuilder( '\Pressbooks\CAS\Admin' )
			->getMock();
		$stub1
			->method( 'getOptions' )
			->willReturn( $this->getTestOptions() );

		return $stub1;
	}

	/**
	 * @return \Pressbooks\CAS\CAS
	 */
	protected function getCas() {

		// Ignore session warnings
		PHPUnit_Framework_Error_Notice::$enabled = false;
		PHPUnit_Framework_Error_Warning::$enabled = false;
		ini_set( 'error_reporting', 0 );
		ini_set( 'display_errors', 0 );

		CAS_GracefullTerminationException::throwInsteadOfExiting();
		$cas = new \Pressbooks\CAS\CAS( $this->getMockAdmin() );

		PHPUnit_Framework_Error_Notice::$enabled = true;
		PHPUnit_Framework_Error_Warning::$enabled = true;
		ini_set( 'error_reporting', 1 );
		ini_set( 'display_errors', 1 );

		return $cas;
	}

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->cas = $this->getCas();
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
		$this->fail();
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

		// User doesn't exist
		$user = $this->cas->matchUser( $prefix );
		$this->assertFalse( $user );
		try {
			$this->cas->handleLoginAttempt( $prefix, $email );
			$this->assertInstanceOf( '\WP_User', get_user_by( 'email', $email ) );
			$this->assertContains( $_SESSION['pb_notices'][0], 'Registered and logged in!' );
		} catch ( \Exception $e ) {
			$this->fail( $e->getMessage() );
		}

		// User was created
		$user = $this->cas->matchUser( $prefix );
		$this->assertInstanceOf( '\WP_User', $user );

		// User exists
		try {
			$this->cas->handleLoginAttempt( $prefix, $email );
			$this->assertContains( $_SESSION['pb_notices'][0], 'Logged in!' );
		} catch ( \Exception $e ) {
			$this->fail( $e->getMessage() );
		}
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
		// Plan A
		$_SESSION[ $this->cas::SIGN_IN_PAGE ] = 'https://pressbooks.test';
		$this->cas->endLogin( 'My first message' );
		$this->assertTrue( in_array( 'My first message', $_SESSION['pb_notices'] ) );

		// Plan B
		unset( $_SESSION[ $this->cas::SIGN_IN_PAGE ] );
		$this->cas->endLogin( 'My second message' );
		$this->assertTrue( in_array( 'My second message', $_SESSION['pb_notices'] ) );
	}

	public function test_trackHomeUrl() {
		unset( $_SESSION[ $this->cas::SIGN_IN_PAGE ] );
		$this->cas->trackHomeUrl();
		$this->assertNotEmpty( $_SESSION[ $this->cas::SIGN_IN_PAGE ] );
	}

	public function test_authenticationFailedMessage() {
		$msg = $this->cas->authenticationFailedMessage( 'create' );
		$this->assertEquals( 'CAS authentication failed.', $msg );
		$msg = $this->cas->authenticationFailedMessage( 'refuse' );
		$this->assertContains( 'To request an account', $msg );
		$this->assertContains( '@', $msg );
	}

	public function test_getAdminEmail() {
		$email = $this->cas->getAdminEmail();
		$this->assertContains( '@', $email );
	}

}
