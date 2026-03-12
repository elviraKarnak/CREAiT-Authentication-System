<?php

if (!defined('ABSPATH')) {
    exit;
}

class CAS_API_Auth_Login {

    public static function handle($request){

        return CAS_Auth_Controller::login($request);

    }

}