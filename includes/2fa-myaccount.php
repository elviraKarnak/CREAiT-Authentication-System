<?php

    class CAS_2FA_DASHBOARD {

        function __construct() {
		
              // ACH Payment Method My Account
            add_action( 'init', array($this,'register_2fa_dashboard_endpoint'));  
            add_filter( 'query_vars', array($this,'twofa_dashboard_query_vars')); 
            add_filter( 'woocommerce_account_menu_items',  array($this,'twofa_dashboard_query_tab'));
            add_action( 'woocommerce_account_creait-security_endpoint',  array($this,'twofa_dashboard_query_page_content' ));

        }

            // ACH Payment Method My Account

                function register_2fa_dashboard_endpoint() {
                    add_rewrite_endpoint( 'creait-security', EP_ROOT | EP_PAGES );
                }

                function twofa_dashboard_query_vars( $vars ) {
                    $vars[] = 'CREAiT Security';
                    return $vars;
                }

                function twofa_dashboard_query_tab( $items ) {
                    $items['creait-security'] = 'CREAiT Security';
                    return $items;
                }

                function twofa_dashboard_query_page_content(){
                    ob_start();
                    require_once(CREAIT_AUTH_SYSTEM_PATH ."views/creait-security-dashboard.php");

                    echo ob_get_clean();
                }

    }