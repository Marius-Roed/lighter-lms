<?php

namespace LighterLMS;

class Autoloader
{

	/**
	 * Registers the autoloader with SPL.
	 */
	public static function register()
	{
		spl_autoload_register([__CLASS__, '_autoload']);
	}

	/**
	 * Handles class autoloading logic
	 *
	 * @param	string	$class	The fully-qualified class name.
	 */
	private static function _autoload($class)
	{
		// project specific namespace prefix
		$prefix = 'LighterLMS\\';

        // base directory the autloader will look in.
        // In this case it is 'lighter-lms/inlcudes/'
		$base_dir = __DIR__ . '/';

		// does the class use the namespace prefix?
		$len = strlen($prefix);
		if (strncmp($prefix, $class, $len) !== 0) {
			return; //no, move to the next registered autoloader.
		}

		// grab relative class name.
		$rel_class = substr($class, $len);

		// replace namespace prefix with the base dir. replace namespace seperators
		// with dir seperators in the $rel_class class name, append .php
		$file = $base_dir . strtolower(str_replace('_', '-', str_replace('\\', DIRECTORY_SEPARATOR, $rel_class))) . '.php';

		if (file_exists($file)) {
			require $file;
		}
	}
}
