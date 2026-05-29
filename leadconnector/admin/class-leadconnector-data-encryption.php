<?php
/**
 *
 * LeadConnector Plugin
 * Copyright (C) 2020-2026 LeadConnector
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * @package LeadConnector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AES-256-GCM authenticated encryption for at-rest secrets, with transparent
 * decrypt support for legacy AES-256-CTR ciphertexts written by 3.0.30 and
 * earlier. The previous CTR implementation used an unauthenticated mode and
 * a hardcoded fallback key; both are removed in 3.0.31 (#A3).
 *
 * This class refuses to operate (returns false from encrypt/decrypt) when no
 * usable key is available. Callers MUST handle the false-return case; the
 * higher-level LeadConnector_Admin::leadconnector_encrypt_string() helper now
 * calls wp_die() with a clear message when the OpenSSL extension or the
 * WordPress salts that derive the key are missing, so a misconfigured site
 * can never silently persist plaintext credentials.
 *
 * @package LeadConnector
 */
class LeadConnector_Data_Encryption {

	/**
	 * AEAD method used for new ciphertexts.
	 */
	const LEADCONNECTOR_CIPHER = 'aes-256-gcm';

	/**
	 * Length of the AES-256-GCM authentication tag, in bytes.
	 */
	const LEADCONNECTOR_GCM_TAG_LEN = 16;

	/**
	 * Magic prefix that identifies a 3.0.31+ GCM ciphertext blob. Older CTR
	 * ciphertexts have no prefix and start with the IV bytes directly.
	 */
	const LEADCONNECTOR_GCM_MAGIC = 'lcgcm.v1.';

	/**
	 * Encryption key derived from WordPress configuration.
	 *
	 * @var string
	 */
	private $key;

	/**
	 * Salt used as additional authenticated data (GCM) and as a poor-man's MAC
	 * suffix on legacy CTR payloads.
	 *
	 * @var string
	 */
	private $salt;

	/**
	 * Initialize encryption key and salt from WordPress configuration.
	 */
	public function __construct() {
		$this->key  = $this->get_default_key();
		$this->salt = $this->get_default_salt();
	}

	/**
	 * Whether this instance has a usable key and salt.
	 *
	 * @return bool
	 */
	public function is_ready() {
		return '' !== $this->key && '' !== $this->salt && extension_loaded( 'openssl' );
	}

	/**
	 * Retrieve the default encryption key from WordPress constants.
	 *
	 * Returns an empty string when no usable key is available - the caller
	 * MUST treat that as a hard failure. The previous implementation fell
	 * back to a hardcoded literal (`das-fallback-key-never-used-in-prod`),
	 * which was a Plugin Check `no-hardcoded-secrets` blocker (#A3).
	 *
	 * @return string
	 */
	private function get_default_key() {
		if ( defined( 'LEADCONNECTOR_ENCRYPTION_KEY' ) && '' !== LEADCONNECTOR_ENCRYPTION_KEY ) {
			return (string) LEADCONNECTOR_ENCRYPTION_KEY;
		}

		if ( defined( 'LOGGED_IN_KEY' ) && '' !== LOGGED_IN_KEY ) {
			return (string) LOGGED_IN_KEY;
		}

		return '';
	}

	/**
	 * Retrieve the default encryption salt from WordPress constants.
	 *
	 * @return string
	 */
	private function get_default_salt() {
		if ( defined( 'LEADCONNECTOR_ENCRYPTION_SALT' ) && '' !== LEADCONNECTOR_ENCRYPTION_SALT ) {
			return (string) LEADCONNECTOR_ENCRYPTION_SALT;
		}

		if ( defined( 'LOGGED_IN_SALT' ) && '' !== LOGGED_IN_SALT ) {
			return (string) LOGGED_IN_SALT;
		}

		return '';
	}

	/**
	 * Encrypt a value using AES-256-GCM with the salt as additional
	 * authenticated data.
	 *
	 * @param string $value Plaintext to encrypt.
	 * @return string|false Base64-encoded ciphertext or false on failure.
	 */
	public function encrypt( $value ) {
		if ( ! $this->is_ready() ) {
			return false;
		}

		$iv_len = openssl_cipher_iv_length( self::LEADCONNECTOR_CIPHER );
		if ( false === $iv_len || $iv_len <= 0 ) {
			return false;
		}

		try {
			$iv = random_bytes( $iv_len );
		} catch ( Exception $e ) {
			return false;
		}

		$tag        = '';
		$ciphertext = openssl_encrypt(
			(string) $value,
			self::LEADCONNECTOR_CIPHER,
			$this->key,
			OPENSSL_RAW_DATA,
			$iv,
			$tag,
			$this->salt,
			self::LEADCONNECTOR_GCM_TAG_LEN
		);
		if ( false === $ciphertext || '' === $tag ) {
			return false;
		}

		$payload = $iv . $tag . $ciphertext;
		if ( ! function_exists( 'sodium_bin2base64' ) ) {
			return false;
		}

		return self::LEADCONNECTOR_GCM_MAGIC . sodium_bin2base64( $payload, SODIUM_BASE64_VARIANT_ORIGINAL );
	}

	/**
	 * Decrypt a previously encrypted value. Transparently handles both the
	 * 3.0.31+ AES-256-GCM format and the legacy 3.0.30-and-earlier
	 * AES-256-CTR format (#A3 back-compat).
	 *
	 * @param string $raw_value Base64-encoded ciphertext.
	 * @return string|false Decrypted plaintext or false on failure.
	 */
	public function decrypt( $raw_value ) {
		if ( ! $this->is_ready() ) {
			return false;
		}
		if ( ! is_string( $raw_value ) || '' === $raw_value ) {
			return false;
		}

		if ( 0 === strpos( $raw_value, self::LEADCONNECTOR_GCM_MAGIC ) ) {
			return $this->decrypt_gcm( substr( $raw_value, strlen( self::LEADCONNECTOR_GCM_MAGIC ) ) );
		}

		// No magic prefix == legacy AES-256-CTR ciphertext written by an
		// older plugin version. Decrypt with the legacy routine; the caller
		// is responsible for re-encrypting the value under GCM next time it
		// is persisted.
		return $this->decrypt_legacy_ctr( $raw_value );
	}

	/**
	 * Whether the supplied stored ciphertext was produced by the legacy
	 * AES-256-CTR path. Callers can use this to opportunistically re-encrypt
	 * legacy values under AES-256-GCM the next time they persist them.
	 *
	 * @param string $raw_value Stored ciphertext.
	 * @return bool
	 */
	public function is_legacy_ciphertext( $raw_value ) {
		return is_string( $raw_value ) && '' !== $raw_value && 0 !== strpos( $raw_value, self::LEADCONNECTOR_GCM_MAGIC );
	}

	/**
	 * Decrypt an AES-256-GCM payload (without magic prefix).
	 *
	 * @param string $b64 Base64-encoded ciphertext minus the magic prefix.
	 * @return string|false
	 */
	private function decrypt_gcm( $b64 ) {
		if ( ! function_exists( 'sodium_base642bin' ) ) {
			return false;
		}

		try {
			$raw = sodium_base642bin( $b64, SODIUM_BASE64_VARIANT_ORIGINAL, true );
		} catch ( Exception $e ) {
			return false;
		}
		if ( false === $raw || '' === $raw ) {
			return false;
		}

		$iv_len = openssl_cipher_iv_length( self::LEADCONNECTOR_CIPHER );
		if ( false === $iv_len || strlen( $raw ) < ( $iv_len + self::LEADCONNECTOR_GCM_TAG_LEN ) ) {
			return false;
		}

		$iv         = substr( $raw, 0, $iv_len );
		$tag        = substr( $raw, $iv_len, self::LEADCONNECTOR_GCM_TAG_LEN );
		$ciphertext = substr( $raw, $iv_len + self::LEADCONNECTOR_GCM_TAG_LEN );

		$plaintext = openssl_decrypt(
			$ciphertext,
			self::LEADCONNECTOR_CIPHER,
			$this->key,
			OPENSSL_RAW_DATA,
			$iv,
			$tag,
			$this->salt
		);

		return false === $plaintext ? false : $plaintext;
	}

	/**
	 * Decrypt a legacy AES-256-CTR payload written by 3.0.30 and earlier.
	 *
	 * Format: base64( iv || ciphertext ); plaintext is verified by checking
	 * that it ends with the configured salt suffix (the legacy poor-man's
	 * MAC). This path is read-only - the plugin no longer creates new CTR
	 * ciphertexts.
	 *
	 * @param string $raw_value Base64-encoded legacy ciphertext.
	 * @return string|false
	 */
	private function decrypt_legacy_ctr( $raw_value ) {
		if ( ! function_exists( 'sodium_base642bin' ) ) {
			return false;
		}

		$method = 'aes-256-ctr';
		$iv_len = openssl_cipher_iv_length( $method );
		if ( false === $iv_len ) {
			return false;
		}

		try {
			$raw = sodium_base642bin( $raw_value, SODIUM_BASE64_VARIANT_ORIGINAL, true );
		} catch ( Exception $e ) {
			return false;
		}
		if ( false === $raw || strlen( $raw ) <= $iv_len ) {
			return false;
		}

		$iv         = substr( $raw, 0, $iv_len );
		$ciphertext = substr( $raw, $iv_len );

		$value = openssl_decrypt( $ciphertext, $method, $this->key, 0, $iv );
		if ( false === $value || '' === $value ) {
			return false;
		}

		$salt_len = strlen( $this->salt );
		if ( 0 === $salt_len || strlen( $value ) < $salt_len ) {
			return false;
		}
		if ( substr( $value, -$salt_len ) !== $this->salt ) {
			return false;
		}

		return substr( $value, 0, -$salt_len );
	}
}
