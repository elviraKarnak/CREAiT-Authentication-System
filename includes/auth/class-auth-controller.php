<?php

if (!defined('ABSPATH')) {
    exit;
}

class CAS_Auth_Controller {

    public static function login($request){

        global $wpdb;

        $email       = sanitize_email($request['email']);
        $password    = $request['password'];
        $device_id   = sanitize_text_field($request['device_id']);
        $device_name = sanitize_text_field($request['device_name']);

        if(empty($email) || empty($password)){
            return new WP_REST_Response([
                'error' => 'Missing credentials'
            ],400);
        }

        $user = get_user_by('email',$email);

        if(!$user){
            return new WP_REST_Response([
                'error' => 'Invalid credentials'
            ],401);
        }

        if(!wp_check_password($password,$user->user_pass,$user->ID)){
            return new WP_REST_Response([
                'error' => 'Invalid credentials'
            ],401);
        }

        /*
        =========================
        MFA CHECK
        =========================
        */

        $mfa_enabled = get_user_meta($user->ID,'creait_mfa_enabled',true);

        if($mfa_enabled){

            return new WP_REST_Response([
                'mfa_required' => true,
                'methods' => ['totp']
            ],200);

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
        $refresh_hash  = hash('sha256',$refresh_token);

        $table = $wpdb->prefix.'creait_refresh_tokens';

        $wpdb->insert($table,[
            'user_id' => $user->ID,
            'token_hash' => $refresh_hash,
            'device_id' => $device_id,
            'device_name' => $device_name,
            'created_at' => current_time('mysql'),
            'expires_at' => date(
                'Y-m-d H:i:s',
                time() + CAS_Config::$refresh_token_ttl
            )
        ]);

        return new WP_REST_Response([
            'access_token' => $access_token,
            'expires_in' => CAS_Config::$jwt_access_ttl,
            'refresh_token' => $refresh_token,
            'refresh_expires_in' => CAS_Config::$refresh_token_ttl
        ],200);

    }

}