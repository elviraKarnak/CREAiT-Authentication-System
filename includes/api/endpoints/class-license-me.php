<?php

if (!defined('ABSPATH')) {
    exit;
}

class CAS_API_License_Me {

    public static function handle($request) {

        nocache_headers();

        $auth_header = self::get_authorization_header();

        if (!$auth_header || stripos($auth_header, 'Bearer ') !== 0) {
            return new WP_Error(
                'missing_token',
                'Missing or invalid Authorization header',
                ['status' => 401]
            );
        }

        $jwt_token = trim(substr($auth_header, 7));

        if (empty($jwt_token)) {
            return new WP_Error(
                'missing_token',
                'Missing bearer token',
                ['status' => 401]
            );
        }

        $decoded = CAS_JWT_Handler::verify_token($jwt_token);

        if (!$decoded || empty($decoded->sub)) {
            return new WP_Error(
                'invalid_token',
                'Invalid or expired token',
                ['status' => 401]
            );
        }

        $user_id = absint($decoded->sub);

        if (!$user_id) {
            return new WP_Error(
                'invalid_token',
                'Invalid token subject',
                ['status' => 401]
            );
        }

        $data = CAS_License_Handler::get_license_data($user_id);

        return new WP_REST_Response($data, 200);
    }

    private static function get_authorization_header() {

        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_AUTHORIZATION']));
        }

        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return sanitize_text_field(wp_unslash($_SERVER['REDIRECT_HTTP_AUTHORIZATION']));
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            if (!empty($headers['Authorization'])) {
                return sanitize_text_field(wp_unslash($headers['Authorization']));
            }

            if (!empty($headers['authorization'])) {
                return sanitize_text_field(wp_unslash($headers['authorization']));
            }
        }

        return '';
    }
}