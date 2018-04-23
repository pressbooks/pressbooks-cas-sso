<?php

namespace Pressbooks\CAS;

use phpCAS as phpCAS;

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
		add_action( 'network_admin_menu', [ $obj, 'addMenu' ] );
	}

	/**
	 * CAS constructor.
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
			echo '<div id="message" class="updated notice is-dismissible"><p>' . __( 'Settings saved.' ) . '</p></div>';
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
			$update = [
				'server_version' => $_POST['server_version'],
				'server_hostname' => preg_replace( '#^https?://#', '', trim( $_POST['server_hostname'] ) ),
				'server_port' => (int) $_POST['server_port'],
				'server_path' => trailingslashit( trim( $_POST['server_path'] ) ),
				'email_suffix' => ltrim( trim( $_POST['email_suffix'] ), '@' ),
				'forced_redirection' => ! empty( $_POST['forced_redirection'] ) ? 1 : 0,
			];
			$result = update_site_option( self::OPTION, $update );
			return $result;
		}
		return false;
	}

	/**
	 * @return array
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
		if ( empty( $options['email_suffix'] ) ) {
			$options['email_suffix'] = '';
		}
		if ( empty( $options['forced_redirection'] ) ) {
			$options['forced_redirection'] = 0;
		}

		return $options;
	}

}
