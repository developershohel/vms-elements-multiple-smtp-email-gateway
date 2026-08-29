<?php
/**
 * SMTP password encryption helpers.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Encrypts and decrypts SMTP passwords using OpenSSL + WP salts.
 */
class VMS_MSG_Encryption {

	const METHOD = 'AES-256-CBC';

	/**
	 * Build a stable encryption key from WordPress salts.
	 *
	 * @return string Binary key (32 bytes).
	 */
	private static function get_key() {
		$material = '';

		if ( defined( 'AUTH_KEY' ) ) {
			$material .= AUTH_KEY;
		}
		if ( defined( 'SECURE_AUTH_KEY' ) ) {
			$material .= SECURE_AUTH_KEY;
		}
		if ( defined( 'LOGGED_IN_KEY' ) ) {
			$material .= LOGGED_IN_KEY;
		}
		if ( defined( 'NONCE_KEY' ) ) {
			$material .= NONCE_KEY;
		}

		if ( '' === $material ) {
			$material = 'vms-msg-fallback-key-' . (string) get_option( 'siteurl', 'localhost' );
		}

		return hash( 'sha256', $material, true );
	}

	/**
	 * Encrypt a plaintext password.
	 *
	 * @param string $plaintext Plain password.
	 * @return string Base64 payload (iv::cipher) or empty string on failure.
	 */
	public static function encrypt( $plaintext ) {
		$plaintext = (string) $plaintext;

		if ( '' === $plaintext ) {
			return '';
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}

		$iv_length = openssl_cipher_iv_length( self::METHOD );
		if ( false === $iv_length || $iv_length < 1 ) {
			return '';
		}

		try {
			$iv = random_bytes( $iv_length );
		} catch ( Exception $e ) {
			$iv = openssl_random_pseudo_bytes( $iv_length );
		}

		$cipher = openssl_encrypt( $plaintext, self::METHOD, self::get_key(), OPENSSL_RAW_DATA, $iv );

		if ( false === $cipher ) {
			return '';
		}

		return base64_encode( $iv . '::' . $cipher );
	}

	/**
	 * Decrypt an encrypted password payload.
	 *
	 * @param string $payload Encrypted payload from encrypt().
	 * @return string Plaintext password or empty string on failure.
	 */
	public static function decrypt( $payload ) {
		$payload = (string) $payload;

		if ( '' === $payload || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$decoded = base64_decode( $payload, true );
		if ( false === $decoded || false === strpos( $decoded, '::' ) ) {
			return '';
		}

		list( $iv, $cipher ) = explode( '::', $decoded, 2 );

		if ( '' === $iv || '' === $cipher ) {
			return '';
		}

		$plain = openssl_decrypt( $cipher, self::METHOD, self::get_key(), OPENSSL_RAW_DATA, $iv );

		return ( false === $plain ) ? '' : $plain;
	}
}
