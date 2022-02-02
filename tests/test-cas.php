<?php

class CasTest extends \WP_UnitTestCase {

	/**
	 * @var \PressbooksCasSso\CAS
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
	 * @return \PressbooksCasSso\Admin
	 */
	protected function getMockAdmin() {

		$stub1 = $this
			->getMockBuilder( '\PressbooksCasSso\Admin' )
			->getMock();
		$stub1
			->method( 'getOptions' )
			->willReturn( $this->getTestOptions() );

		return $stub1;
	}

	/**
	 * @return \PressbooksCasSso\CAS
	 */
	protected function getCas() {

		ini_set( 'error_reporting', 0 );
		ini_set( 'display_errors', 0 );

		CAS_GracefullTerminationException::throwInsteadOfExiting();
		$cas = new \PressbooksCasSso\CAS( $this->getMockAdmin() );

		ini_set( 'error_reporting', 1 );
		ini_set( 'display_errors', 1 );

		return $cas;
	}

	/**
	 *
	 */
	public function set_up() {
		parent::set_up();
		$this->cas = $this->getCas();
	}

	public function test_changeLoginUrl() {
		$url = $this->cas->changeLoginUrl( 'https://pressbooks.test' );
		$this->assertStringContainsString( 'action=pb_cas', $url );
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
			$this->assertStringContainsString( '</html>', $e->getMessage() ); // phpCas generated error, other people's code is untestable
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
		$this->assertStringContainsString( 'pressbooks-cas-sso', get_echo( 'wp_print_scripts' ) );
	}

	public function test_loginForm() {
		ob_start();
		$this->cas->loginForm();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<div id="pb-cas-wrap">', $buffer );
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
			$this->assertStringContainsString( $_SESSION['pb_notices'][0], 'Registered and logged in!' );
		} catch ( \Exception $e ) {
			$this->fail( $e->getMessage() );
		}

		// User was created
		$user = $this->cas->matchUser( $prefix );
		$this->assertInstanceOf( '\WP_User', $user );

		// User exists
		try {
			$this->cas->handleLoginAttempt( $prefix, $email );
			$this->assertStringContainsString( $_SESSION['pb_notices'][0], 'Logged in!' );
		} catch ( \Exception $e ) {
			$this->fail( $e->getMessage() );
		}
	}

	public function test_handleLoginAttempt_exceptions() {
		try {
			$bad_net_id = '111111111111111111111111111111111111111111111111111111111111'; // 61 characters
			$bad_email = '1';
			$this->cas->handleLoginAttempt( $bad_net_id, $bad_email );
		} catch ( \Exception $e ) {
			$this->assertStringContainsString( 'Please enter a valid email address', $e->getMessage() );
			$this->assertStringContainsString( 'Username may not be longer than 60 characters', $e->getMessage() );
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
		$this->assertStringContainsString( 'To request an account', $msg );
		$this->assertStringContainsString( '@', $msg );
	}

	public function test_getAdminEmail() {
		$email = $this->cas->getAdminEmail();
		$this->assertStringContainsString( '@', $email );
	}

	public function test_sanitizeUser() {
		$this->assertEquals( 'test', $this->cas->sanitizeUser( 'test' ) );
		$this->assertEquals( 'test', $this->cas->sanitizeUser( '(:test:)' ) );
		$this->assertEquals( 'tst1', $this->cas->sanitizeUser( 'tst' ) );
		$this->assertEquals( 'tst1', $this->cas->sanitizeUser( '(:tst:)' ) );
		$this->assertEquals( 'yo11', $this->cas->sanitizeUser( 'yo' ) );
		$this->assertEquals( 'yo11', $this->cas->sanitizeUser( '(:yo:)' ) );
		$this->assertEquals( '1111a', $this->cas->sanitizeUser( '1111' ) );
		$this->assertEquals( '1a11', $this->cas->sanitizeUser( '1' ) );
	}

}
