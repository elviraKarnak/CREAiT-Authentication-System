<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CAS_Config')) {

    class CAS_Config {

        /* =========================
           JWT SETTINGS
        ========================== */

        // Access token lifetime (900 seconds ≈ 15 minutes)
        public static $jwt_access_ttl = 900;

        // Token audience
        public static $jwt_audience = 'creait-desktop';

        // Token issuer (site URL)
        public static $jwt_issuer = '';


        /* =========================
           REFRESH TOKEN SETTINGS
        ========================== */

        // Refresh token lifetime (30 days)
        public static $refresh_token_ttl = 2592000;


        /* =========================
           MFA SETTINGS
        ========================== */

        // MFA challenge expiration (~5 minutes)
        public static $mfa_challenge_ttl = 300;

        // Maximum MFA verification attempts
        public static $mfa_max_attempts = 5;


        /* =========================
           LOGIN SECURITY
        ========================== */

        // Maximum login attempts
        public static $login_max_attempts = 5;


        public static function init() {

            self::$jwt_issuer = get_site_url();

        }

    }

}