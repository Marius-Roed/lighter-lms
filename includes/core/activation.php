<?php

namespace LighterLMS\Core;

class Activation
{
	public static function register_hooks()
	{
		register_activation_hook(LIGHTER_LMS__FILE__, [__CLASS__, 'on_activation']);
		register_deactivation_hook(LIGHTER_LMS__FILE__, [__CLASS__, 'on_deactivation']);
	}

	public static function on_activation() {}

	public static function on_deactivation() {}
}
