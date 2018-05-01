<?php

namespace Pressbooks\CAS;

use phpCAS as phpCAS;
use PressbooksMix\Assets;

class CAS {

	const META_KEY = 'pressbooks_cas_identity';

	/**
	 * @var CAS
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	private $loginUrl;

	/**
	 * @var int
	 */
	private $currentUserId = 0;

	/**
	 * @var string
	 */
	private $provision = 'refuse';

	/**
	 * @var string
	 */
	private $emailDomain = '';

	/**
	 * @var bool
	 */
	private $bypass = false;

	/**
	 * @var Admin
	 */
	private $admin;

	/**
	 * @var bool
	 */
	private $forcedRedirection = false;

	/**
	 * @var bool
	 */
	private $casClientIsReady = false;

	/**
	 * @return CAS
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			$admin = Admin::init();
			self::$instance = new self( $admin );
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
		add_filter( 'show_password_fields', [ $obj, 'showPasswordFields' ], 10, 2 );
	}

	/**
	 * @param Admin $admin
	 */
	public function __construct( Admin $admin ) {

		$options = $admin->getOptions();
		if ( empty( $options['server_hostname'] ) ) {
			if ( 'pb_cas_admin' !== @$_REQUEST['page'] ) { // @codingStandardsIgnoreLine
				add_action(
					'network_admin_notices', function () {
						echo '<div id="message" class="error fade"><p>' . __( 'CAS is not configured.', 'pressbooks-cas-sso' ) . '</p></div>';
					}
				);
			}
			return;
		}

		// Init phpCAS
		switch ( $options['server_version'] ) {
			case 'CAS_VERSION_3_0':
				$server_version = defined( 'CAS_VERSION_3_0' ) ? CAS_VERSION_3_0 : '3.0';
				break;
			case 'CAS_VERSION_1_0':
				$server_version = defined( 'CAS_VERSION_1_0' ) ? CAS_VERSION_1_0 : '1.0';
				break;
			default:
				$server_version = defined( 'CAS_VERSION_2_0' ) ? CAS_VERSION_2_0 : '2.0';
		}
		if ( ! phpCAS::isInitialized() ) {
			phpCAS::client(
				$server_version,
				$options['server_hostname'],
				intval( $options['server_port'] ),
				untrailingslashit( $options['server_path'] )
			);
		}

		// Set Login URL
		if ( is_subdomain_install() ) {
			$login_url = network_site_url( 'wp-login.php' );
			$login_url = add_query_arg( 'redirect_to', get_site_url( get_current_blog_id(), '/wp-admin/' ), $login_url );
		} else {
			$login_url = wp_login_url();
		}
		$login_url = add_query_arg( 'action', 'pb_cas', $login_url );
		$login_url = \Pressbooks\Sanitize\maybe_https( $login_url );
		$this->loginUrl = $login_url;
		phpCAS::setFixedServiceURL( $this->loginUrl );

		// Path to the ca chain that issued the cas server certificate, ie. '/path/to/cachain.pem'
		$ca_cert_path = getenv( 'PB_CAS_CERT_PATH' );
		if ( ! empty( $ca_cert_path ) ) {
			phpCAS::setCasServerCACert( $ca_cert_path );
		} else {
			phpCAS::setNoCasServerValidation();
		}

		// DEBUG
		// @codingStandardsIgnoreStart
		// phpCAS::setNoClearTicketsFromUrl();
		// phpCAS::setDebug();
		// phpCAS::setVerbose( false );
		// @codingStandardsIgnoreEnd

		$this->currentUserId = get_current_user_id();
		$this->provision = $options['provision'];
		$this->emailDomain = ! empty( $options['email_domain'] ) ? $options['email_domain'] : "noreply.{$options['server_hostname']}";
		$this->bypass = (bool) $options['bypass'];
		$this->forcedRedirection = (bool) $options['forced_redirection'];
		$this->admin = $admin;
		if ( $this->forcedRedirection ) {
			// TODO:
			// This hijacks the same logic as seen in the shibboleth plugin.
			// If we want to support both shibboleth & CAS on the same site, then we'll need to handle the 'login_form_shibboleth' action ourselves.
			add_filter( 'login_url', [ $this, 'changeLoginUrl' ], 999 );
		}
		$this->casClientIsReady = true;
	}

	/**
	 * Change wp_login_url() to include an action param we use to trigger: do_action( "login_form_{$action}" )
	 *
	 * Hooked into filter: 'login_url'
	 *
	 *
	 * @param string $login_url The login URL. Not HTML-encoded.
	 *
	 * @return string
	 */
	public function changeLoginUrl( $login_url ) {
		$login_url = add_query_arg( 'action', 'pb_cas', $login_url );
		return $login_url;
	}

	/**
	 * @param bool $show
	 * @param \WP_User $profileuser
	 *
	 * @return bool
	 */
	public function showPasswordFields( $show, $profileuser ) {
		if ( ! current_user_can( 'manage_network' ) ) {
			$pressbooks_cas_identity = get_user_meta( $profileuser->ID, self::META_KEY, true );
			if ( $pressbooks_cas_identity ) {
				$show = false;
			}
		}
		return $show;
	}

	/**
	 * @param null|\WP_User|\WP_Error $user WP_User if the user is authenticated. WP_Error or null otherwise.
	 * @param string $username Username or email address.
	 * @param string $password User password
	 *
	 * @throws \LogicException (for unit tests! will die() when in website mode)
	 * @return mixed
	 */
	public function authenticate( $user, $username, $password ) {

		$use_cas = false;
		if ( $this->casClientIsReady ) {
			if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'pb_cas' ) { // @codingStandardsIgnoreLine
				$use_cas = true;
			}
		}

		if ( $use_cas ) {
			try {
				ob_start();
				phpCAS::forceAuthentication();
				if ( phpCAS::isAuthenticated() ) {
					$net_id = phpCAS::getUser();
					$email = "{$net_id}@{$this->emailDomain}";
					ob_get_clean();
					remove_filter( 'authenticate', [ $this, 'authenticate' ], 10 ); // Fix infinite loop
					$this->handleLoginAttempt( $net_id, $email );
				}
			} catch ( \Exception $e ) {
				$buffer = ob_get_clean();
				if ( ! empty( $buffer ) ) {
					if ( defined( 'WP_TESTS_MULTISITE' ) ) {
						throw new \LogicException( $buffer );
					} else {
						die( $buffer );
					}
				} else {
					if ( $this->forcedRedirection ) {
						wp_die( $e->getMessage() );
					} else {
						return new \WP_Error( 'authentication_failed', $e->getMessage() );
					}
				}
			}
			if ( $this->forcedRedirection ) {
				wp_die( __( 'CAS authentication failed.', 'pressbooks-cas-sso' ) );
			} else {
				return new \WP_Error( 'authentication_failed', __( 'CAS authentication failed.', 'pressbooks-cas-sso' ) );
			}
		}
		return null;
	}

	/**
	 * @param string $redirect_to
	 *
	 * @return string
	 */
	public function logoutRedirect( $redirect_to ) {
		if ( $this->casClientIsReady ) {
			if ( $this->forcedRedirection || phpCAS::isSessionAuthenticated() || get_user_meta( $this->currentUserId, self::META_KEY, true ) ) {
				phpCAS::logoutWithRedirectService( get_option( 'siteurl' ) );
				$this->doExit();
			}
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

		if ( ! $this->casClientIsReady ) {
			return;
		}

		$url = phpCAS::getServerLoginURL();

		$button_text = $this->admin->getOptions()['button_text'];
		if ( empty( $button_text ) ) {
			$button_text = __( 'Connect via CAS', 'pressbooks-cas-sso' );
		}

		?>
		<div id="pb-cas-wrap">
			<div class="pb-cas-or">
				<span><?php esc_html_e( 'Or', 'pressbooks-cas-sso' ); ?></span>
			</div>
			<?php
			printf(
				'<div class="cas"><a href="%1$s" class="button button-hero cas">%2$s</a></div>',
				$url,
				$button_text
			);
			?>
		</div>
		<?php
	}

	/**
	 * Login (or register and login) a WordPress user based on their CAS NetID identity.
	 *
	 * @param string $net_id
	 * @param string $email An email
	 * @throws \Exception
	 */
	public function handleLoginAttempt( $net_id, $email ) {
		// Try to find a matching WordPress user for the now-authenticated user's CAS NetID identity
		$user = $this->matchUser( $net_id );

		if ( $user ) {
			// If a matching user was found, log them in
			$logged_in = \Pressbooks\Redirect\programmatic_login( $user->user_login );
			if ( $logged_in === true ) {
				$this->endLogin( __( 'Logged in!', 'pressbooks-cas-sso' ) );
			}
		} else {
			$this->associateUser( $net_id, $email );
		}
	}

	/**
	 * Ends the login request by redirecting to the desired page
	 *
	 * @param string $msg
	 */
	public function endLogin( $msg ) {
		$_SESSION['pb_notices'][] = $msg;
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$blog = get_active_blog_for_user( $user->ID );
			if ( $blog ) {
				wp_safe_redirect( get_admin_url( $blog->blog_id ) );
				$this->doExit();
			} else {
				wp_safe_redirect( wp_registration_url() );
				$this->doExit();
			}
		} else {
			wp_safe_redirect( wp_registration_url() );
			$this->doExit();
		}
	}

	/**
	 * Attempt to match a WordPress user to the CAS NetID identity.
	 *
	 * @param string $net_id
	 *
	 * @return false|\WP_User
	 */
	public function matchUser( $net_id ) {
		global $wpdb;
		$condition = "{$net_id}|%";
		$query_result = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s", self::META_KEY, $condition ) );
		// attempt to get a WordPress user with the matched id:
		$user = get_user_by( 'id', $query_result );
		return $user;
	}

	/**
	 * Link a user to their CAS NetID identity
	 *
	 * @param int $user_id
	 * @param string $net_id
	 */
	public function linkAccount( $user_id, $net_id ) {
		$condition = "{$net_id}|" . time();
		add_user_meta( $user_id, self::META_KEY, $condition );
	}

	/**
	 * Create user (redirects if there is an error)
	 *
	 * @param string $username
	 * @param string $email
	 * @throws \Exception
	 *
	 * @return array [ (int) user_id, (string) sanitized username ]
	 */
	public function createUser( $username, $email ) {
		$i = 1;
		$unique_username = $this->sanitizeUser( $username );
		while ( username_exists( $unique_username ) ) {
			$unique_username = $this->sanitizeUser( "{$username}{$i}" );
			++$i;
		}

		// Validate
		if ( ! $this->bypass ) {
			remove_all_filters( 'wpmu_validate_user_signup' );
			$user_result = wpmu_validate_user_signup( $unique_username, $email );
			$username = $user_result['user_name'];
			$email = $user_result['user_email'];
			$errors = $user_result['errors'];
		} else {
			$username = $unique_username;
			$email = sanitize_email( $email );
			$errors = null;
		}

		/** @var \WP_Error $errors */
		if ( ! empty( $errors->errors ) ) {
			$error = '';
			foreach ( $errors->get_error_messages() as $message ) {
				$error .= "{$message} ";
			}
			throw new \Exception( $error );
		}

		// Attempt to generate the user and get the user id
		// we use wp_create_user instead of wp_insert_user so we can handle the error when the user being registered already exists
		$user_id = wp_create_user( $username, wp_generate_password(), $email );

		// Check if the user was actually created:
		if ( is_wp_error( $user_id ) ) {
			// there was an error during registration, redirect and notify the user:
			throw new \Exception( $user_id->get_error_message() );
		}

		remove_user_from_blog( $user_id, 1 );

		return [ $user_id, $username ];
	}

	/**
	 * Multisite has more restrictions on user login character set
	 *
	 * @see https://core.trac.wordpress.org/ticket/17904
	 *
	 * @param string $username
	 *
	 * @return string
	 */
	public function sanitizeUser( $username ) {
		$unique_username = sanitize_user( $username, true );
		$unique_username = strtolower( $unique_username );
		$unique_username = preg_replace( '/[^a-z0-9]/', '', $unique_username );
		return $unique_username;
	}

	/**
	 * Associate user
	 *
	 * @param string $net_id
	 * @param string $email
	 * @throws \Exception
	 */
	public function associateUser( $net_id, $email ) {

		$user = get_user_by( 'email', $email );
		if ( $user ) {
			// Associate existing users with CAS accounts
			$user_id = $user->ID;
			$username = $user->user_login;
		} else {
			if ( $this->provision === 'create' ) {
				list( $user_id, $username ) = $this->createUser( $net_id, $email );
			} else {
				// Refuse Access
				return;
			}
		}

		// Registration was successful, the user account was created (or associated), proceed to login the user automatically...
		// associate the WordPress user account with the now-authenticated third party account:
		$this->linkAccount( $user_id, $net_id );

		// Attempt to login the new user (this could be error prone):
		$logged_in = \Pressbooks\Redirect\programmatic_login( $username );
		if ( $logged_in === true ) {
			$this->endLogin( __( 'Registered and logged in!', 'pressbooks-cas-sso' ) );
		}
	}

	/**
	 * If not in unit tests, then exit!
	 */
	private function doExit() {
		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit;
		}
	}

}
