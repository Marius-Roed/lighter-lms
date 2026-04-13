<?php

namespace LighterLMS\Attributes;

use Attribute;

defined( 'ABSPATH' ) || exit;

#[Attribute( Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE )]
class Filter {
	public function __construct(
		public string $hook,
		public int $priority = 10,
		public int $accepted_args = 1,
	) {
	}
}
