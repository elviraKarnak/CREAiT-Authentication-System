<?php

if (!defined('ABSPATH')) {
    exit;
}

class CAS_REST_TEST_Routes {

    public function __construct() {

        add_action('rest_api_init', [$this,'register_routes_test']);
        add_action('rest_api_init', [$this,'register_routes_test_mfa']);


    }


    public function register_routes_test_mfa()
    {
        register_rest_route(
            'creait/v1',
            '/auth/test-mfa',
            [
                'methods'  => 'GET',
                'callback' => [$this,'test_mfa'],
                'permission_callback' => '__return_true'
            ]
        );

    }

    public function test_mfa($request){

        $user_id = 6; // test user

        $code = sanitize_text_field($request->get_param('code'));

        if(!$code){
            return [
                'success'=>false,
                'message'=>'Missing code'
            ];
        }

        /*
        Call your strict verification function
        */

        $result = CAS_MFA_Handler::verify_totp_strict($user_id,$code);

        return [
            'input_code' => $code,
            'result' => $result,
            'server_time' => time(),
            'server_readable' => date('Y-m-d H:i:s'),
            'time_slice' => floor(time()/30)
        ];
    }


    public function register_routes_test() {

        register_rest_route(
            'creait/v1',
            '/auth/test-jwt',
            [
                'methods'  => 'GET',
                'callback' => [$this,'test_jwt'],
                'permission_callback' => '__return_true'
            ]
        );

    }

    public function test_jwt() {

        $token = CAS_JWT_Handler::generate_access_token(1,'device-test');

        return [
            'success' => true,
            'token'   => $token
        ];

    }

}