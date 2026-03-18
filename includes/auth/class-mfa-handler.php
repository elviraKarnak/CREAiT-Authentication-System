<?php

use PragmaRX\Google2FA\Google2FA;

if (!defined('ABSPATH')) {
    exit;
}

class CAS_MFA_Handler {

    public function __construct(){

        add_action('wp_ajax_creait_verify_mfa', [$this,'ajax_verify_mfa']);

    }

    /*
    ==========================================
    GENERATE SECRET (ONLY IF NOT EXISTS)
    ==========================================
    */

    public static function generate_secret($user_id){

        $google2fa = new Google2FA();

        // reuse pending secret if already created
        $secret = get_user_meta($user_id,'creait_mfa_secret_pending',true);

        if(!$secret){

            $secret = $google2fa->generateSecretKey();

            update_user_meta(
                $user_id,
                'creait_mfa_secret_pending',
                $secret
            );

        }

        $user = get_userdata($user_id);

        $qr_url = $google2fa->getQRCodeUrl(
            'CREAiT',
            $user->user_email,
            $secret
        );

        /*
        Generate QR Image Locally
        */

        $qr_image = CAS_QR_Generator::generate($qr_url);

        return [
            'secret' => $secret,
            'qr_image' => $qr_image
        ];

    }

    /*
    ==========================================
    VERIFY CODE AND ENABLE MFA
    ==========================================
    */

    public static function verify_and_enable($user_id, $code){

        $secret = get_user_meta($user_id,'creait_mfa_secret_pending',true);

        if(!$secret){
            return [
                'success' => false,
                'message' => 'No MFA setup found'
            ];
        }

        $google2fa = new Google2FA();

        // allow small clock drift
        $valid = $google2fa->verifyKey($secret,$code,1);

        if(!$valid){
            return [
                'success' => false,
                'message' => 'Invalid code'
            ];
        }

        /*
        ENABLE MFA
        */

        update_user_meta($user_id,'creait_mfa_secret',$secret);
        update_user_meta($user_id,'creait_mfa_enabled',1);

        delete_user_meta($user_id,'creait_mfa_secret_pending');

        return [
            'success' => true,
            'message' => 'MFA enabled successfully'
        ];

    }

    public static function verify_totp_strict($user_id, $code){

        $secret = get_user_meta($user_id,'creait_mfa_secret',true);

        if(!$secret){
            return [
                'success'=>false,
                'message'=>'MFA not enabled'
            ];
        }

        $google2fa = new \PragmaRX\Google2FA\Google2FA();

        /*
        STRICT validation
        0 = only current 30 second window
        */

        $valid = $google2fa->verifyKey($secret,$code,0);

        if(!$valid){
            return [
                'success'=>false,
                'message'=>'Invalid or expired code'
            ];
        }

        return [
            'success'=>true,
            'message'=>'Valid code'
        ];
    }

    /*
    ==========================================
    AJAX VERIFY HANDLER
    ==========================================
    */

    public function ajax_verify_mfa(){

        if(!is_user_logged_in()){
            wp_send_json_error('Not logged in');
        }

        $user_id = get_current_user_id();
        $code = sanitize_text_field($_POST['code'] ?? '');

        if(!$code){
            wp_send_json_error('Missing code');
        }

        $secret = get_user_meta($user_id,'creait_mfa_secret_pending',true);

        if(!$secret){
            wp_send_json_error('No MFA setup found');
        }

        $google2fa = new Google2FA();

        // allow clock drift
        $valid = $google2fa->verifyKey($secret,$code,1);

        if(!$valid){
            wp_send_json_error('Invalid code');
        }

        /*
        ENABLE MFA
        */

        update_user_meta($user_id,'creait_mfa_secret',$secret);
        update_user_meta($user_id,'creait_mfa_enabled',1);

        delete_user_meta($user_id,'creait_mfa_secret_pending');

        wp_send_json_success('MFA enabled successfully');

    }

}