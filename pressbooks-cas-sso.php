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

add_action('plugins_loaded', function () {
    \Pressbooks\Container::get('Blade')->addNamespace('PressbooksCasSso', __DIR__ . '/templates');
});
add_action('plugins_loaded', [ '\PressbooksCasSso\CAS', 'init' ]);
add_action('plugins_loaded', [ '\PressbooksCasSso\Admin', 'init' ]);
