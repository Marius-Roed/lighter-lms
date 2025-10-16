<?php

namespace LighterLMS;

class Randflake
{
	private static $machineId = apply_filters('lighterlms_use_machine_id', 0);
	private static $sequence = 0;
	private static $lastTimestamp = -1;

	/**
	 * Generate a Randflake ID encoded in base36
	 *
	 * @return string
	 */
	public static function generate()
	{
		$timestamp = (int) (microtime(true) * 1000);

		if ($timestamp === self::$lastTimestamp) {
			self::$sequence = (self::$sequence + 1) & 0xFFF;
			if (self::$sequence === 0) {
				while ($timestamp <= self::$lastTimestamp) {
					$timestamp = (int) (microtime(true) * 1000);
				}
			}
		} else {
			self::$sequence = 0;
		}

		self::$lastTimestamp = $timestamp;

		$id = (($timestamp & 0x1FFFFFFFFFF) << 22) | ((self::$machineId & 0x3FF) << 12) | (self::$sequence & 0xFFF);

		return base_convert((string) $id, 10, 36);
	}
}
