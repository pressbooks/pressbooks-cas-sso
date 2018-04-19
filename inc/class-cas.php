<?php

namespace Pressbooks\CAS;

use \phpCAS as phpCAS;

class CAS {

	/**
	 * @var CAS
	 */
	private static $instance = null;

	/**
	 * @return CAS
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param CAS $obj
	 */
	static public function hooks( CAS $obj ) {
		add_filter( 'authenticate', [ $obj, 'authenticate' ], 10, 3 );
		add_action( 'wp_logout', [ $obj, 'logout' ] );
	}

	/**
	 * CAS constructor.
	 */
	public function __construct() {

		phpCAS::client(
			CAS_VERSION_2_0,
			'test-cas.rutgers.edu',
			intval( 443 ),
			'/'
		);

		// DEBUG
		phpCAS::setFixedServiceURL( 'https://textopress.com/wp/wp-login.php' );

		// phpCAS::setCasServerCACert( 'production' );
		phpCAS::setNoCasServerValidation();
		phpCAS::setNoClearTicketsFromUrl();

		phpCAS::setDebug();
		phpCAS::setVerbose( false );
	}

	/**
	 * @param null|\WP_User|\WP_Error $user WP_User if the user is authenticated. WP_Error or null otherwise.
	 * @param string $username Username or email address.
	 * @param string $password User password
	 *
	 * @return false|\WP_User
	 */
	public function authenticate( $user, $username, $password ) {

		phpCAS::forceAuthentication();
		if ( ! phpCAS::isAuthenticated() ) {
			die( 'TODO: FAIL' );
		}


		var_dump( phpCAS::getUser() );
		var_dump( phpCAS::getAttributes() );
		die( 'TODO: SUCCESS' );
	}

	/**
	 *
	 */
	public function logout() {
		phpCAS::logoutWithRedirectService( get_option( 'siteurl' ) );
		exit;
	}

}