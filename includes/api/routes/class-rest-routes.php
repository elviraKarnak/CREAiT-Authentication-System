<?php

if (!defined('ABSPATH')) {
    exit;
}

class CAS_REST_Routes {

    public function __construct() {
        add_action('rest_api_init', [$this,'register_routes']);
    }

    public function register_routes(){

        register_rest_route(
            'creait/v1',
            '/auth/login',
            [
                'methods'  => 'POST',
                'callback' => ['CAS_API_Auth_Login','handle'],
                'permission_callback' => '__return_true'
            ]
        );

        register_rest_route(
            'creait/v1',
            '/auth/refresh',
            [
                'methods'  => 'POST',
                'callback' => ['CAS_API_Auth_Refresh','handle'],
                'permission_callback' => '__return_true'
            ]
        );

    }

}