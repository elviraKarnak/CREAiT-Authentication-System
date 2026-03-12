<?php

if (!defined('ABSPATH')) {
    exit;
}

class CAS_REST_TEST_Routes {

    public function __construct() {

        add_action('rest_api_init', [$this,'register_routes_test']);

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