<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Creait_Auth_System_DB')) {

    class Creait_Auth_System_DB {

        public static function install() {

            global $wpdb;

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $charset_collate = $wpdb->get_charset_collate();

            /*
            ==========================================
            REFRESH TOKEN TABLE
            ==========================================
            */

            $refresh_table = $wpdb->prefix . 'creait_refresh_tokens';

            $sql_refresh = "CREATE TABLE $refresh_table (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                token_hash CHAR(64) NOT NULL,
                created_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                revoked_at DATETIME NULL,
                replaced_by_hash CHAR(64) NULL,
                device_id VARCHAR(128) NOT NULL,
                device_name VARCHAR(128) NOT NULL,
                last_used_at DATETIME NULL,
                ip_last VARCHAR(64) NULL,
                ua_last VARCHAR(255) NULL,

                PRIMARY KEY (id),
                UNIQUE KEY token_hash (token_hash),
                KEY user_id (user_id),
                KEY device_id (device_id)

            ) $charset_collate;";

            dbDelta($sql_refresh);


            /*
            ==========================================
            MFA CHALLENGES TABLE
            ==========================================
            */

            $mfa_table = $wpdb->prefix . 'creait_mfa_challenges';

            $sql_mfa = "CREATE TABLE $mfa_table (

                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                mfa_token_hash CHAR(64) NOT NULL,
                created_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                attempts INT DEFAULT 0,
                used_at DATETIME NULL,
                device_id VARCHAR(128) NOT NULL,
                ip VARCHAR(64) NULL,
                ua VARCHAR(255) NULL,

                PRIMARY KEY (id),
                UNIQUE KEY mfa_token_hash (mfa_token_hash),
                KEY user_id (user_id)

            ) $charset_collate;";

            dbDelta($sql_mfa);

        }

    }

}