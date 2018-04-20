<?php

namespace Pressbooks\CAS;

/**
 * @return \Jenssegers\Blade\Blade
 */
function blade() {
	static $blade;
	if ( empty( $blade ) ) {
		$views = __DIR__ . '/../templates';
		$cache = \Pressbooks\Utility\get_cache_path();
		$blade = new \Jenssegers\Blade\Blade( $views, $cache );
	}
	return $blade;
}
