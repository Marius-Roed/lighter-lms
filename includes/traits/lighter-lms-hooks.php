<?php

namespace LighterLMS\Traits;

defined( 'ABSPATH' ) || exit;

use LighterLMS\Attributes\Action;
use LighterLMS\Attributes\Filter;
use ReflectionObject;

trait Lighter_LMS_Hooks {
	protected function register_hooks(): void {
		$reflection = new ReflectionObject( $this );

		foreach ( $reflection->getMethods() as $method ) {
			foreach ( $method->getAttributes( Action::class ) as $attribute ) {
				$action = $attribute->newInstance();
				add_action( $action->hook, array( $this, $method->getName() ), $action->priority, $action->accepted_args );
			}

			foreach ( $method->getAttributes( Filter::class ) as $attribute ) {
				$filter = $attribute->newInstance();
				add_filter( $filter->hook, array( $this, $method->getName() ), $filter->priority, $filter->accepted_args );
			}
		}
	}
}
