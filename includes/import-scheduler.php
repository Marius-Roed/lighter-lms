<?php

namespace LighterLMS;

defined( 'ABSPATH' ) || exit;

class Import_Scheduler {

	const string HOOK  = 'lighter_process_import_batch';
	const string GROUP = 'lighter_imports';

	public static function schedule_batch( string $job_id, bool $force = false ): void {
		// Check if Action scheduler is installed:
		if ( function_exists( 'as_schedule_single_action' ) ) {
			if ( $force || ! \as_next_scheduled_action( self::HOOK, array( $job_id ), self::GROUP ) ) {
				\as_schedule_single_action( time(), self::HOOK, array( $job_id ), self::GROUP );
			}
			return;
		}

		// If not, use wp cron.
		if ( $force || ! wp_next_scheduled( self::HOOK, array( $job_id ) ) ) {
			wp_schedule_single_event( time(), self::HOOK, array( $job_id ), self::GROUP );
		}
	}

	public static function clear_schedule( string $job_id ): void {
		if ( function_exists( 'as_unschedule_action' ) ) {
			\as_unschedule_action( self::HOOK, array( $job_id ), self::GROUP );
		}
		wp_clear_scheduled_hook( self::HOOK, array( $job_id ) );
	}
}
