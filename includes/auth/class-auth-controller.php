<?php

if (!defined('ABSPATH')) {
    exit;
}

class CAS_Auth_Controller {

    public static function login($request) {

        global $wpdb;

        $email       = sanitize_email($request['email']);
        $password    = $request['password'];
        $device_id   = sanitize_text_field($request['device_id']);
        $device_name = sanitize_text_field($request['device_name']);

        if (empty($email) || empty($password)) {
            return new WP_REST_Response([
                'error' => 'Missing credentials'
            ], 400);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $rate_key = 'cas_login_attempts_' . md5(strtolower($email) . '|' . $ip);
        $attempt_data = get_transient($rate_key);

        if (!is_array($attempt_data)) {
            $attempt_data = [
                'count' => 0,
                'locked_until' => 0,
            ];
        }

        if (!empty($attempt_data['locked_until']) && time() < (int) $attempt_data['locked_until']) {
            return new WP_REST_Response([
                'error' => 'Too many login attempts. Please try again later.'
            ], 429);
        }

        $user = get_user_by('email', $email);

        if (!$user || !wp_check_password($password, $user->user_pass, $user->ID)) {

            $attempt_data['count'] = (int) $attempt_data['count'] + 1;

            if ($attempt_data['count'] >= 5) {
                $attempt_data['locked_until'] = time() + (15 * MINUTE_IN_SECONDS);
            }

            set_transient($rate_key, $attempt_data, 15 * MINUTE_IN_SECONDS);

            return new WP_REST_Response([
                'error' => 'Invalid credentials'
            ], $attempt_data['count'] >= 5 ? 429 : 401);
        }

        delete_transient($rate_key);

        /*
        =========================
        MFA CHECK
        =========================
        */

        $mfa_enabled = get_user_meta($user->ID, 'creait_mfa_enabled', true);

        if ($mfa_enabled) {

            /*
            CREATE MFA CHALLENGE
            */

            $mfa_token = bin2hex(random_bytes(32));
            $mfa_hash  = hash('sha256', $mfa_token);

            $table = $wpdb->prefix . 'creait_mfa_challenges';

            $wpdb->insert($table, [
                'user_id'        => $user->ID,
                'mfa_token_hash' => $mfa_hash,
                'created_at'     => current_time('mysql'),
                'expires_at'     => date(
                    'Y-m-d H:i:s',
                    time() + CAS_Config::$mfa_challenge_ttl
                ),
               'device_id' => $device_id,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            return new WP_REST_Response([
                'mfa_required' => true,
                'mfa_token'    => $mfa_token,
                'methods'      => ['totp']
            ], 200);
        }

        /*
        =========================
        CREATE ACCESS TOKEN
        =========================
        */

        $access_token = CAS_JWT_Handler::generate_access_token(
            $user->ID,
            $device_id
        );

        /*
        =========================
        CREATE REFRESH TOKEN
        =========================
        */

        $refresh_token = bin2hex(random_bytes(64));
        $refresh_hash  = hash('sha256', $refresh_token);

        $table = $wpdb->prefix . 'creait_refresh_tokens';

        $wpdb->insert($table, [
            'user_id'     => $user->ID,
            'token_hash'  => $refresh_hash,
            'device_id'   => $device_id,
            'device_name' => $device_name,
            'created_at'  => current_time('mysql'),
            'expires_at'  => date(
                'Y-m-d H:i:s',
                time() + CAS_Config::$refresh_token_ttl
            )
        ]);

        return new WP_REST_Response([
            'access_token'       => $access_token,
            'expires_in'         => CAS_Config::$jwt_access_ttl,
            'refresh_token'      => $refresh_token,
            'refresh_expires_in' => CAS_Config::$refresh_token_ttl
        ], 200);
    }
}