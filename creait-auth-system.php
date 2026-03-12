<?php
/*
 * Plugin Name: CREAiT Auth System
 * Plugin URI: https://systaero.com/
 * Description: Secure authentication system for CREAiT Desktop including JWT authentication, MFA verification, and WooCommerce subscription license validation.
 * Author: Raihan Reza
 * Author URI: https://webtaxonomy.com/
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Tested up to: 6.8
 * Text Domain: cas
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
/*
CREAiT Auth System is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with CREAiT Auth System. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Creait_Auth_System')) {

        class Creait_Auth_System {

            function __construct() {

                    $this->define_constants();

                    require_once CREAIT_AUTH_SYSTEM_PATH . 'vendor/autoload.php';

                    /*===== CONFIG =====*/

                    require_once CREAIT_AUTH_SYSTEM_PATH . 'includes/class-config.php';
                    CAS_Config::init();

                    /*==== JWT HANDLER ====*/

                    require_once CREAIT_AUTH_SYSTEM_PATH . 'includes/jwt/class-jwt-handler.php';

                    /*==== AUTH CONTROLLER ===*/

                    require_once CREAIT_AUTH_SYSTEM_PATH . 'includes/auth/class-auth-controller.php';
                    require_once CREAIT_AUTH_SYSTEM_PATH . 'includes/auth/class-refresh-token.php';

                    /*==== API ENDPOINTS ====*/

                    require_once CREAIT_AUTH_SYSTEM_PATH . 'includes/api/endpoints/class-auth-login.php';

                    new CAS_API_Auth_Login();

                    require_once CREAIT_AUTH_SYSTEM_PATH . 'includes/api/endpoints/class-auth-refresh.php';

                    new CAS_API_Auth_Refresh();

                    /* ==== ROUTES ====*/

                    require_once CREAIT_AUTH_SYSTEM_PATH . 'includes/api/routes/class-rest-routes.php';

                    new CAS_REST_Routes();

                    require_once CREAIT_AUTH_SYSTEM_PATH . 'includes/2fa-myaccount.php';

                    new CAS_2FA_DASHBOARD();

                    require_once CREAIT_AUTH_SYSTEM_PATH . 'includes/api/class-jwt-test.php';

                    new CAS_REST_TEST_Routes();

                }

        public function define_constants() {

            if(!defined('CREAIT_AUTH_SYSTEM_PATH')){
                define('CREAIT_AUTH_SYSTEM_PATH', plugin_dir_path(__FILE__));
            }

            if(!defined('CREAIT_AUTH_SYSTEM_URL')){
                define('CREAIT_AUTH_SYSTEM_URL', plugin_dir_url(__FILE__));
            }

            if(!defined('CREAIT_AUTH_SYSTEM_VERSION')){
                define('CREAIT_AUTH_SYSTEM_VERSION', '1.0.0');
            }

        }

        //  CREATE BOTH TABLES ON ACTIVATION
        public static function activate() {

            require_once CREAIT_AUTH_SYSTEM_PATH . 'database/install.php';

            Creait_Auth_System_DB::install();

            flush_rewrite_rules();

        
        }

        public static function deactivate() {
            flush_rewrite_rules();
        }

        public static function uninstall() {
            flush_rewrite_rules();
            // delete tables here if needed
        }

    }
}

if( class_exists( 'Creait_Auth_System' ) ){
    register_activation_hook( __FILE__, array( 'Creait_Auth_System', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'Creait_Auth_System', 'deactivate' ) );
    register_uninstall_hook( __FILE__, array( 'Creait_Auth_System', 'uninstall' ) );

    $creait_auth_system = new Creait_Auth_System();
}