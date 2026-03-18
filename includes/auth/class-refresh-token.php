<?php

if (!defined('ABSPATH')) {
    exit;
}

class CAS_Refresh_Token {

    public static function create($user_id, $device_id, $device_name) {

        global $wpdb;

        $token = bin2hex(random_bytes(64));
        $hash  = hash('sha256', $token);

        $table = $wpdb->prefix . 'creait_refresh_tokens';

        $wpdb->insert($table, [
            'user_id'     => $user_id,
            'token_hash'  => $hash,
            'device_id'   => $device_id,
            'device_name' => $device_name,
            'created_at'  => current_time('mysql'),
            'expires_at'  => date(
                'Y-m-d H:i:s',
                time() + CAS_Config::$refresh_token_ttl
            ),
           'last_used_at' => current_time('mysql'),
           'ip_last' => $_SERVER['REMOTE_ADDR'] ?? '',
           'ua_last' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        return $token;

    }

    public static function find($token) {

        global $wpdb;

        $hash = hash('sha256', $token);

        $table = $wpdb->prefix . 'creait_refresh_tokens';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE token_hash=%s",
                $hash
            )
        );

    }

    public static function revoke($id, $new_hash = null) {

        global $wpdb;

        $table = $wpdb->prefix . 'creait_refresh_tokens';

        $wpdb->update(
            $table,
            [
                'revoked_at'       => current_time('mysql'),
                'replaced_by_hash' => $new_hash
            ],
            ['id' => $id]
        );

    }

}