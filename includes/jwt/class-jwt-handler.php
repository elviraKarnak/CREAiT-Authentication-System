<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined('ABSPATH')) {
    exit;
}

class CAS_JWT_Handler {

    public static function generate_access_token($user_id, $device_id = null){

        $issued = time();

        $payload = [

            'iss' => CAS_Config::$jwt_issuer,

            'aud' => CAS_Config::$jwt_audience,

            'sub' => (string) $user_id,

            'iat' => $issued,

            'exp' => $issued + CAS_Config::$jwt_access_ttl,

            'jti' => wp_generate_uuid4(),

            'device_id' => $device_id

        ];

        return JWT::encode(
            $payload,
            CREAIT_JWT_SECRET,
            'HS256'
        );
    }

    public static function verify_token($token){

        try{

            $decoded = JWT::decode(
                $token,
                new Key(CREAIT_JWT_SECRET,'HS256')
            );

            if($decoded->iss !== CAS_Config::$jwt_issuer){
                return false;
            }

            if($decoded->aud !== CAS_Config::$jwt_audience){
                return false;
            }

            return $decoded;

        }catch(Exception $e){

            return false;

        }

    }

}