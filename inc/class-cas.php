<?php

namespace Pressbooks\CAS;

use phpCAS as phpCAS;
use PressbooksMix\Assets;

class CAS {

	/**
	 * @var CAS
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	private $loginUrl;

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
		add_action( 'login_enqueue_scripts', [ $obj, 'loginEnqueueScripts' ] );
		add_action( 'login_form', [ $obj, 'loginForm' ] );
		add_filter( 'logout_redirect', [ $obj, 'logoutRedirect' ] );
	}

	/**
	 * CAS constructor.
	 */
	public function __construct() {

		$options = Admin::init()->getOptions();

		require_once( __DIR__ . '/../vendor/apereo/phpcas/source/CAS.php' );
		switch ( $options['server_version'] ) {
			case 'CAS_VERSION_3_0':
				$server_version = CAS_VERSION_3_0;
				break;
			case 'CAS_VERSION_1_0':
				$server_version = CAS_VERSION_1_0;
				break;
			default:
				$server_version = CAS_VERSION_2_0;
		}

		phpCAS::client(
			$server_version,
			$options['server_hostname'],
			intval( $options['server_port'] ),
			untrailingslashit( $options['server_path'] )
		);

		$login_url = wp_login_url();
		$login_url = add_query_arg( 'action', 'pb_cas', $login_url );
		$login_url = str_replace( 'pressbooks.test', 'textopress.com', $login_url ); // TODO: Forcing https://textopress.com for tests
		$login_url = str_replace( 'http://', 'https://', $login_url );
		$this->loginUrl = $login_url;
		phpCAS::setFixedServiceURL( $this->loginUrl );

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
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'pb_cas' ) {
			phpCAS::forceAuthentication();
			if ( ! phpCAS::isAuthenticated() ) {
				die( 'TODO: FAIL' );
			}
			var_dump( phpCAS::getUser() );
			var_dump( phpCAS::getAttributes() );
			die( 'TODO: SUCCESS' );
		}
	}


	/**
	 * @param string $redirect_to
	 *
	 * @return string
	 */
	public function logoutRedirect( $redirect_to ) {
		if ( phpCAS::isSessionAuthenticated() ) {
			phpCAS::logoutWithRedirectService( get_option( 'siteurl' ) );
			exit;
		}
		return $redirect_to;
	}

	/**
	 * Add login CSS and JS
	 */
	public function loginEnqueueScripts() {
		$assets = new Assets( 'pressbooks-cas-sso', 'plugin' );
		wp_enqueue_style( 'pb-cas-login', $assets->getPath( 'styles/login-form.css' ) );
		wp_enqueue_script( 'pb-cas-login', $assets->getPath( 'scripts/login-form.js' ), [ 'jquery' ] );
	}

	/**
	 * Print [ Connect via CAS ] button
	 */
	public function loginForm() {

		$url = phpCAS::getServerLoginURL();
		$button_string = __( 'Connect via CAS', 'pressbooks-cas-sso' );

		?>
		<div id="pb-cas-wrap">
			<div class="pb-cas-or">
				<span><?php esc_html_e( 'Or', 'pressbooks-cas-sso' ); ?></span>
			</div>
			<?php
			printf(
				'<div class="cas"><a href="%1$s" class="button button-hero cas">%2$s</a></div>',
				$url,
				$button_string
			);
			?>
		</div>
		<?php
	}

}
