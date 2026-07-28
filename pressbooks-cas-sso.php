<?php
/**
 * Plugin Name:         Pressbooks CAS Single Sign-On
 * Plugin URI:          https://pressbooks.org
 * GitHub Plugin URI:   pressbooks/pressbooks-cas-sso
 * Release Asset:       true
 * Description:         CAS Single Sign-On integration for Pressbooks.
 * Version:             2.4.0
 * Requires PHP:        8.1
 * Requires at least:   6.5
 * Requires Plugins:    pressbooks
 * Author:              Pressbooks (Book Oven Inc.)
 * Author URI:          https://pressbooks.org
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:         pressbooks-cas-sso
 * Domain Path:         /languages
 * Network: True
 */

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------
\HM\Autoloader\register_class_path( 'PressbooksCasSso', __DIR__ . '/inc' );

// -------------------------------------------------------------------------------------------------------------------
// Composer autoloader
// -------------------------------------------------------------------------------------------------------------------
if ( ! class_exists( '\phpCAS' ) ) {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		$title = __( 'Dependencies Missing', 'pressbooks-cas-sso' );
		$body = __( 'Please run <code>composer install</code> from the root of the Pressbooks CAS Single Sign-On plugin directory.', 'pressbooks-cas-sso' );
		$message = "<h1>{$title}</h1><p>{$body}</p>";
		wp_die( $message, $title );
	}
}

// -------------------------------------------------------------------------------------------------------------------
// Requires
// -------------------------------------------------------------------------------------------------------------------

require __DIR__ . '/inc/namespace.php';

// -------------------------------------------------------------------------------------------------------------------
// Hooks
// -------------------------------------------------------------------------------------------------------------------

add_action( 'plugins_loaded', function () {
	\Pressbooks\Container::get( 'Blade' )->addNamespace( 'PressbooksCasSso', __DIR__ . '/templates' );
} );
add_action( 'plugins_loaded', [ '\PressbooksCasSso\CAS', 'init' ] );
add_action( 'plugins_loaded', [ '\PressbooksCasSso\Admin', 'init' ] );
