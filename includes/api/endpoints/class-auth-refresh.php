<?php

if (!defined('ABSPATH')) {
    exit;
}

class CAS_API_Auth_Refresh {

    public static function handle($request) {

        $params = $request->get_json_params();

        $refresh_token = $params['refresh_token'] ?? '';
        $device_id     = sanitize_text_field($params['device_id'] ?? '');

        if (!$refresh_token) {
            return new WP_Error(
                'invalid_request',
                'Missing refresh token',
                ['status'=>400]
            );
        }

        $row = CAS_Refresh_Token::find($refresh_token);

        if (!$row) {
            return new WP_Error(
                'invalid_token',
                'Invalid refresh token',
                ['status'=>401]
            );
        }

        
        if ($row->device_id !== $device_id) {
            return new WP_Error(
                'device_mismatch',
                'Device mismatch detected',
                ['status' => 401]
            );
        }

        if ($row->revoked_at) {
            return new WP_Error(
                'revoked_token',
                'Token revoked',
                ['status'=>401]
            );
        }

        if (strtotime($row->expires_at) < time()) {
            return new WP_Error(
                'expired_token',
                'Refresh token expired',
                ['status'=>401]
            );
        }

        /*
        ROTATE TOKEN
        */

        $new_token = CAS_Refresh_Token::create(
            $row->user_id,
            $device_id,
            $row->device_name
        );

        $new_hash = hash('sha256', $new_token);

        CAS_Refresh_Token::revoke(
            $row->id,
            $new_hash
        );

        /*
        ISSUE NEW JWT
        */

        $jwt = CAS_JWT_Handler::generate_access_token(
            $row->user_id,
            $device_id
        );

        return [

            'access_token' => $jwt,
            'expires_in'   => CAS_Config::$jwt_access_ttl,

            'refresh_token' => $new_token,
            'refresh_expires_in' => CAS_Config::$refresh_token_ttl

        ];

    }

}