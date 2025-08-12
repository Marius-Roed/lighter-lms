<?php

if (! defined('ABSPATH')) {
	exit;
}

if (! function_exists('lighter_lms')) {
	function lighter_lms()
	{
		return \LighterLMS\Core\Config::get_instance();
	}
}
