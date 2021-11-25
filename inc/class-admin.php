<?php

namespace PressbooksCasSso;

class Admin {

	const OPTION = 'pressbooks_cas_sso';

	/**
	 * @var Admin
	 */
	private static $instance = null;

	/**
	 * @return Admin
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Admin $obj
	 */
	static public function hooks( Admin $obj ) {
		load_plugin_textdomain( 'pressbooks-cas-sso', false, 'pressbooks-cas-sso/languages/' );

		add_action( 'network_admin_menu', [ $obj, 'addMenu' ] );
	}

	/**
	 *
	 */
	public function __construct() {

	}

	/**
	 *
	 */
	public function addMenu() {
		$parent_slug = \Pressbooks\Admin\Dashboard\init_network_integrations_menu();

		add_submenu_page(
			$parent_slug,
			__( 'CAS', 'pressbooks-cas-sso' ),
			__( 'CAS', 'pressbooks-cas-sso' ),
			'manage_network',
			'pb_cas_admin',
			[ $this, 'printMenu' ]
		);
	}

	/**
	 *
	 */
	public function printMenu() {
		if ( $this->saveOptions() ) {
			echo '<div id="message" role="status" class="updated notice is-dismissible"><p>' . __( 'Settings saved.' ) . '</p></div>';
		}
		$html = blade()->render(
			'admin', [
				'form_url' => network_admin_url( '/admin.php?page=pb_cas_admin' ),
				'options' => $this->getOptions(),
			]
		);
		echo $html;
	}

	/**
	 * @return bool
	 */
	public function saveOptions() {
		if ( ! empty( $_POST ) && check_admin_referer( 'pb-cas-sso' ) ) {
			$_POST = array_map( 'trim', $_POST );
			$update = [];

			if ( isset( $_POST['server_version'] ) ) {
				$update['server_version'] = $_POST['server_version'];
			}
			if ( isset( $_POST['server_hostname'] ) ) {
				$update['server_hostname'] = preg_replace( '#^https?://#', '', $_POST['server_hostname'] );
			}
			if ( isset( $_POST['server_port'] ) ) {
				$update['server_port'] = (int) $_POST['server_port'];
			}
			if ( isset( $_POST['server_path'] ) ) {
				$update['server_path'] = trailingslashit( $_POST['server_path'] );
			}
			if ( isset( $_POST['provision'] ) ) {
				$update['provision'] = in_array( $_POST['provision'], [ 'refuse', 'create' ], true ) ? $_POST['provision'] : 'refuse';
			}
			if ( isset( $_POST['email_domain'] ) ) {
				$update['email_domain'] = ltrim( $_POST['email_domain'], '@' );
			}
			if ( isset( $_POST['button_text'] ) ) {
				$update['button_text'] = wp_unslash( wp_kses( $_POST['button_text'], [
					'br' => [],
				] ) );
			}
			// Checkboxes
			$update['bypass'] = ! empty( $_POST['bypass'] ) ? 1 : 0;
			$update['forced_redirection'] = ! empty( $_POST['forced_redirection'] ) ? 1 : 0;

			$fallback = $this->getOptions();
			$update = array_merge( $fallback, $update );

			$result = update_site_option( self::OPTION, $update );
			return $result;
		}
		return false;
	}

	/**
	 * @return array{server_version: string, server_hostname: string, server_port: int, server_path: string, provision: string, email_domain: string, button_text: string, bypass: bool, forced_redirection: bool}
	 */
	public function getOptions() {

		$options = get_site_option( self::OPTION, [] );

		if ( empty( $options['server_version'] ) ) {
			$options['server_version'] = 'CAS_VERSION_2_0';
		}
		if ( empty( $options['server_hostname'] ) ) {
			$options['server_hostname'] = '';
		}
		if ( empty( $options['server_port'] ) ) {
			$options['server_port'] = 443;
		}
		if ( empty( $options['server_path'] ) ) {
			$options['server_path'] = '/';
		}
		if ( empty( $options['provision'] ) ) {
			$options['provision'] = 'refuse';
		}
		if ( empty( $options['email_domain'] ) ) {
			$options['email_domain'] = '';
		}
		if ( empty( $options['button_text'] ) ) {
			$options['button_text'] = '';
		}
		if ( empty( $options['bypass'] ) ) {
			$options['bypass'] = false;
		}
		if ( empty( $options['forced_redirection'] ) ) {
			$options['forced_redirection'] = false;
		}

		return $options;
	}

}
