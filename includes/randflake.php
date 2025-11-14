<?php

namespace LighterLMS;

class Randflake
{
	private static $machineId = 0;
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

	/**
	 * Validate a given ID.
	 *
	 * @param string $id The value to validate.
	 * @param bool $strict Whether to decode and check internal structure.
	 *
	 * @return bool If value is valid or not.
	 */
	public static function validate($id, $strict = false)
	{
		if (!is_string($id) || empty($id)) {
			return false;
		}

		$id = trim(strtolower($id));

		if (!preg_match("/^[0-9a-z]{8,13}/", $id)) {
			return false;
		}

		if (!$strict) return true;

		$decimal = base_convert($id, 36, 10);
		if (!is_numeric($decimal) || $decimal <= 0 || $decimal >= (1 << 63)) {
			return false;
		}

		$decimalInt = (int) $decimal;

		$sequence = $decimalInt & 0xFFF;
		$machine = ($decimalInt >> 12) & 0x3FF;
		$timestamp = $decimalInt >> 22;

		if ($sequence < 0 || $sequence > 0xFFF) {
			return false;
		}

		if ($machine < 0 || $machine > 0x3FF || ($machine > 0 && !self::$machineId)) {
			return false;
		}

		$minTimestamp = 1262304000000; // Jan 1 2010. No need to look before this
		$maxTimestamp = (int) (microtime(true) * 1000) + 60000; // Now + 1 minute;

		if ($timestamp < $minTimestamp || $timestamp >= $maxTimestamp) {
			return false;
		}

		return true;
	}
}
