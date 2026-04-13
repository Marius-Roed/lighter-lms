<?php

namespace LighterLMS\Attributes;

use Attribute;

defined( 'ABSPATH' ) || exit;

#[Attribute( Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE )]
class Action {
	/**
	 * Mirrors WordPress's native `add_action()` filter as a php Attribute
	 */
	public function __construct(
		public string $hook,
		public int $priority = 10,
		public int $accepted_args = 1,
	) {
	}
}
