<?php

namespace LighterLMS;

defined( 'ABSPATH' ) || exit;

class Randflake {

	private static int $sequence      = 0;
	private static int $lastTimestamp = -1;

	/**
	 * Generate a Randflake ID encoded in base36
	 */
	public static function generate(): string {
		$timestamp = (int) ( microtime( true ) * 1000 );
		$machineId = lighter_lms()->machineId;

		if ( $timestamp === self::$lastTimestamp ) {
			self::$sequence = ( self::$sequence + 1 ) & 0xFFF;
			if ( self::$sequence === 0 ) {
				while ( $timestamp <= self::$lastTimestamp ) {
					$timestamp = (int) ( microtime( true ) * 1000 );
				}
			}
		} else {
			self::$sequence = 0;
		}

		self::$lastTimestamp = $timestamp;

		$id = ( ( $timestamp & 0x1FFFFFFFFFF ) << 22 ) | ( ( $machineId & 0x3FF ) << 12 ) | ( self::$sequence & 0xFFF );

		return base_convert( (string) $id, 10, 36 );
	}

	public static function sanitize( mixed $id ): string {
		if ( ! self::validate( $id, true ) ) {
			return self::generate();
		}

		return $id;
	}

	/**
	 * Validate a given ID.
	 *
	 * @param mixed $id The value to validate.
	 * @param bool $strict Whether to decode and check internal structure.
	 *
	 * @return bool If value is valid or not.
	 */
	public static function validate( mixed $id, bool $strict = false ): bool {
		if ( ! is_string( $id ) || empty( $id ) ) {
			return false;
		}

		$id = trim( strtolower( $id ) );

		if ( ! preg_match( '/^[0-9a-z]{8,13}$/', $id ) ) {
			return false;
		}

		if ( ! $strict ) {
			return true;
		}

		$decimal = self::_base36_to_decimal( $id );

		// Must be an unsigned 64 bit int
		if ( self::_decimal_compare( $decimal, '0' ) <= 0 || self::_decimal_compare( $decimal, '9223372036854775808' ) >= 0 ) {
			return false;
		}

		[ $timestamp, $machine ] = self::_decode( $decimal );

		if ( $machine < 0 || $machine > 0x3FF ) {
			return false;
		}

		$minTimestamp = 1262304000000; // Jan 1 2010. No need to look before this
		$maxTimestamp = (int) ( microtime( true ) * 1000 ) + 60000; // Now + 1 minute;

		if ( $timestamp < $minTimestamp || $timestamp >= $maxTimestamp ) {
			return false;
		}

		return true;
	}

	private static function _decode( string $decimal ): array {
		if ( extension_loaded( 'gmp' ) ) {
			$n         = \gmp_init( $decimal );
			$machine   = \gmp_intval( \gmp_and( \gmp_div( $n, \gmp_pow( 2, 12 ) ), \gmp_init( '0x3FF' ) ) );
			$timestamp = \gmp_intval( \gmp_div( $n, \gmp_pow( 2, 22 ) ) );
		} elseif ( extension_loaded( 'bcmath' ) ) {
			$machine   = (int) \bcmod( \bcdiv( $decimal, \bcpow( '2', '12' ) ), '1024' );
			$timestamp = (int) \bcdiv( $decimal, \bcpow( '2', '22' ) );
		} else {
			$machine   = (int) fmod( floor( (float) $decimal / ( 1 << 12 ) ), 1024 );
			$timestamp = (int) floor( (float) $decimal / ( 1 << 22 ) );
		}

		return array( $timestamp, $machine );
	}

	private static function _base36_to_decimal( string $id ): string {
		if ( extension_loaded( 'gmp' ) ) {
			return \gmp_strval( \gmp_init( $id, 36 ) );
		}

		$chars   = '0123456789abcdefghijklmnopqrstuvwxyz';
		$decimal = '0';

		for ( $i = 0; $i < strlen( $id ); $i++ ) {
			$digit = (string) strpos( $chars, $id[ $i ] );

			if ( extension_loaded( 'bcmath' ) ) {
				$decimal = \bcadd( \bcmul( $decimal, '36' ), $digit );
			} else {
				$decimal = self::_string_add( self::_string_multiply( $decimal, '36' ), $digit );
			}
		}

		return $decimal;
	}

	private static function _decimal_compare( string $a, string $b ): int {
		if ( extension_loaded( 'gmp' ) ) {
			return \gmp_cmp( \gmp_init( $a ), \gmp_init( $b ) );
		}

		if ( extension_loaded( 'bcmath' ) ) {
			return \bccomp( $a, $b );
		}

		$a = ltrim( $a, '0' ) ?: '0';
		$b = ltrim( $b, '0' ) ?: '0';

		if ( strlen( $a ) !== strlen( $b ) ) {
			return strlen( $a ) <=> strlen( $b );
		}

		return strcmp( $a, $b ) <=> 0;
	}

	private static function _string_multiply( string $a, string $b ): string {
		$result = array_fill( 0, strlen( $a ) + strlen( $b ), 0 );

		$a = str_split( strrev( $a ) );
		$b = str_split( strrev( $b ) );

		foreach ( $a as $i => $digitA ) {
			foreach ( $b as $j => $digitB ) {
				$result[ $i + $j ]     += (int) $digitA * (int) $digitB;
				$result[ $i + $j + 1 ] += (int) floor( $result[ $i + $j ] / 10 );
				$result[ $i + $j ]     %= 10;
			}
		}

		return ltrim( strrev( implode( '', $result ) ), '0' ) ?: '0';
	}

	private static function _string_add( string $a, string $b ): string {
		$result = '';
		$carry  = 0;

		$a   = str_split( strrev( $a ) );
		$b   = str_split( strrev( $b ) );
		$len = max( count( $a ), count( $b ) );

		for ( $i = 0; $i < $len || $carry; $i++ ) {
			$sum     = $carry + (int) ( $a[ $i ] ?? 0 ) + (int) ( $b[ $i ] ?? 0 );
			$carry   = (int) floor( $sum / 10 );
			$result .= $sum % 10;
		}

		return strrev( $result ) ?: '0';
	}
}
