<?php


if (!defined('ABSPATH')) {
    exit;
}

/*
==========================================
Disable Cache For CREAiT Auth APIs
==========================================
*/

add_action('rest_pre_serve_request', function ($served, $result, $request) {

    if (strpos($request->get_route(), '/creait/v1') === 0) {

        nocache_headers();

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

    }

    return $served;

}, 10, 3);