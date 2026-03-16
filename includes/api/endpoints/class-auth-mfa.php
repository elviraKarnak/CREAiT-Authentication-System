<?php

if (!defined('ABSPATH')) {
    exit;
}

class CAS_API_Auth_MFA {

    public static function handle($request){

        global $wpdb;

        nocache_headers();

        $params = $request->get_json_params();

        $mfa_token   = sanitize_text_field($params['mfa_token'] ?? '');
        $code        = sanitize_text_field($params['code'] ?? '');
        $device_id   = sanitize_text_field($params['device_id'] ?? '');
        $device_name = sanitize_text_field($params['device_name'] ?? '');

        if(!$mfa_token || !$code){
            return new WP_Error(
                'invalid_request',
                'Missing MFA token or code',
                ['status'=>400]
            );
        }

        $hash = hash('sha256',$mfa_token);

        $table = $wpdb->prefix.'creait_mfa_challenges';

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE mfa_token_hash=%s",
                $hash
            )
        );

        if(!$row){
            return new WP_Error(
                'invalid_token',
                'Invalid MFA token',
                ['status'=>401]
            );
        }

      if (!empty($row->used_at)) {
            return new WP_Error(
                'invalid_token',
                'MFA challenge already used',
                ['status' => 401]
            );
        }

        if (strtotime($row->expires_at) < time()) {
            return new WP_Error(
                'expired',
                'MFA challenge expired',
                ['status' => 401]
            );
        }

        if ((int) $row->attempts >= 5) {
            return new WP_Error(
                'too_many_attempts',
                'Too many MFA attempts. Please login again.',
                ['status' => 429]
            );
        }

        /*
        VERIFY TOTP
        */

        $verify = CAS_MFA_Handler::verify_totp_strict(
            $row->user_id,
            $code
        );

         if (!$verify['success']) {

            $wpdb->update(
                $table,
                [
                    'attempts' => ((int) $row->attempts + 1),
                ],
                [
                    'id' => $row->id,
                ],
                ['%d'],
                ['%d']
            );

            return new WP_Error(
                'invalid_code',
                'Invalid authenticator code',
                ['status' => 401]
            );
        }

        /*
        ISSUE ACCESS TOKEN
        */

        $access_token = CAS_JWT_Handler::generate_access_token(
            $row->user_id,
            $device_id
        );

        /*
        CREATE REFRESH TOKEN
        */

        $refresh_token = CAS_Refresh_Token::create(
            $row->user_id,
            $device_id,
            $device_name
        );

        /*
        MARK CHALLENGE USED
        */

        $wpdb->update(
            $table,
            ['used_at'=>current_time('mysql')],
            ['id'=>$row->id]
        );

        return [

            'access_token' => $access_token,
            'expires_in'   => CAS_Config::$jwt_access_ttl,

            'refresh_token' => $refresh_token,
            'refresh_expires_in' => CAS_Config::$refresh_token_ttl

        ];

    }

}