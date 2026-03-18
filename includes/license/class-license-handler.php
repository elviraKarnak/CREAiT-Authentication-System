<?php

if (!defined('ABSPATH')) {
    exit;
}

class CAS_License_Handler {

    public static function get_license_data($user_id) {

        $user = get_userdata($user_id);

        if (!$user) {
            return [
                'status'    => 'invalid',
                'licensee'  => '',
                'expiry'    => null,
                'message'   => 'User not found',
            ];
        }

        $licensee = trim($user->first_name . ' ' . $user->last_name);

        if (empty($licensee)) {
            $licensee = $user->display_name ?: $user->user_email;
        }

        if (!function_exists('wcs_get_users_subscriptions')) {
            return [
                'status'    => 'invalid',
                'licensee'  => $licensee,
                'expiry'    => null,
                'message'   => 'WooCommerce Subscriptions not available',
            ];
        }

        $subscriptions = wcs_get_users_subscriptions($user_id);

        if (empty($subscriptions)) {
            return [
                'status'    => 'never_subscribed',
                'licensee'  => $licensee,
                'expiry'    => null,
                'message'   => 'No subscription found',
            ];
        }

        $final_status = 'invalid",';
        $expiry_ts    = null;

        foreach ($subscriptions as $subscription) {
            if (!$subscription || !is_object($subscription)) {
                continue;
            }

            $sub_status = $subscription->get_status();

            if (in_array($sub_status, ['active', 'pending-cancel'], true)) {
                $final_status = 'valid';

                $end_date = $subscription->get_date('end');
                $next_payment_date = $subscription->get_date('next_payment');

                $candidate_ts = null;

                if (!empty($end_date)) {
                    $candidate_ts = strtotime($end_date);
                } elseif (!empty($next_payment_date)) {
                    $candidate_ts = strtotime($next_payment_date);
                }

                if ($candidate_ts && ($expiry_ts === null || $candidate_ts > $expiry_ts)) {
                    $expiry_ts = $candidate_ts;
                }

                 foreach ($subscription->get_items() as $item) {
                    $product_id = $item->get_product_id();

                    if (!$product_id) {
                        continue;
                    }

                    $productPlan = get_post_meta($product_id, 'plan_name', true);
                    $productRag = get_post_meta($product_id, 'rag', true);
                    $productProject = get_post_meta($product_id, 'project_handle', true);


                    // if (!empty($product_plan)) {
                    //     $final_plan = sanitize_text_field($product_plan);
                    // } else {
                    //     $final_plan = 'pro';
                    // }

                }
            }

            if ($sub_status === 'on-hold') {
                $final_status = 'expired';
            } elseif (in_array($sub_status, ['expired', 'cancelled'], true) && $final_status !== 'on_hold') {
                $final_status = 'expired';
            } elseif ($sub_status === 'pending' && !in_array($final_status, ['on_hold', 'expired'], true)) {
                $final_status = 'expired';
            }
        }

        $expiry = $expiry_ts ? gmdate('Y-m-d\TH:i:s\Z', $expiry_ts) : null;

        return [
            'status'    => $final_status,
            'plan'      => $productPlan,
            'licensee'  => $licensee,
            'expiry'    => $expiry,
            'features'  => [
                'rag' => $productRag,
                'max_projects' => $productProject
            ]
            
        ];
    }
}