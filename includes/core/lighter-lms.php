<?php

namespace LighterLMS\Core;

class Lighter_LMS
{
	private static $_instance = null;

	public static function get_instance()
	{
		if (null == self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {}
}
