<?php
/**
 * Plugin Name:  WooCommerce Ultimate Points And Rewards
 * Plugin URI: https://makewebbetter.com
 * Description: This woocommerce extension allow merchants to reward their customers with loyalty points.
 * Version: 2.1.0
 * Author: MakeWebBetter <webmaster@makewebbetter.com>
 * Author URI: https://makewebbetter.com
 * Requires at least: 3.5
 * Tested up to: 5.8.1
 * WC tested up to: 5.7.1
 * Text Domain: woocommerce-ultimate-points-and-rewards
 * Domain Path: /languages
 * License:  GPL-3.0+
 * License URI:  http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package WooCommerce Ultimate Points And Rewards
 */

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$activated = true;
if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		$activated = false;
	}
} else {
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		$activated = false;
	}
}
/**
 * Check if WooCommerce is active
 */
if ( $activated ) {
	define( 'MWB_WPR_DIRPATH', plugin_dir_path( __FILE__ ) );
	define( 'MWB_WPR_URL', plugin_dir_url( __FILE__ ) );
	define( 'MWB_WPR_HOME_URL', admin_url() );
	define( 'MWB_WPR_DOMAIN', 'woocommerce-ultimate-points-and-rewards' );
	define( 'MWB_WPR_JS_LOAD_ADMIN', MWB_WPR_URL . 'assets/js/admin/woocommerce-ultimate-points-admin.js' );
	define( 'MWB_WPR_JS_LOAD_PUBLIC', MWB_WPR_URL . 'assets/js/public/woocommerce-ultimate-points-acount.js' );
	include_once MWB_WPR_DIRPATH . '/includes/admin/class-mwb-wpr-admin-manager.php';
	include_once MWB_WPR_DIRPATH . '/includes/public/class-mwb-wpr-front-end.php';

	/**
	 * This function is used to load language'.
	 *
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_wpr_load_plugin_textdomain() {
		$domain = 'woocommerce-ultimate-points-and-rewards';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, MWB_WPR_DIRPATH . 'languages/' . $domain . '-' . $locale . '.mo' );
		$var = load_plugin_textdomain( $domain, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}
	add_action( 'plugins_loaded', 'mwb_wpr_load_plugin_textdomain' );

	/**
	 * Dynamically Generate Coupon Code
	 *
	 * @name mwb_wpr_coupon_generator
	 * @param int $length for coupon.
	 * @return string
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_wpr_coupon_generator( $length = 5 ) {
		if ( '' === $length ) {
			$length = 5;
		}
		$password    = '';
		$alphabets   = range( 'A', 'Z' );
		$numbers     = range( '0', '9' );
		$final_array = array_merge( $alphabets, $numbers );
		while ( $length-- ) {
			$key       = array_rand( $final_array );
			$password .= $final_array[ $key ];
		}

		return $password;
	}
	/**
	 * Dynamically Generate referral Code
	 *
	 * @name mwb_wpr_create_referral_code
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_wpr_create_referral_code() {
		$length      = 10;
		$pkey        = '';
		$alphabets   = range( 'A', 'Z' );
		$numbers     = range( '0', '9' );
		$final_array = array_merge( $alphabets, $numbers );
		while ( $length-- ) {
			$key   = array_rand( $final_array );
			$pkey .= $final_array[ $key ];
		}

		return $pkey;
	}

	/**
	 * Add settings link on plugin page
	 *
	 * @name mwb_wpr_admin_settings()
	 * @param   String $actions   value for contant.
	 * @param   String $plugin_file   value for contant.
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_wpr_admin_settings( $actions, $plugin_file ) {
		static $plugin;
		if ( ! isset( $plugin ) ) {

			$plugin = plugin_basename( __FILE__ );
		}
		if ( $plugin === $plugin_file ) {
			$settings = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=mwb-wpr-setting' ) . '">' . __( 'Settings', 'woocommerce-ultimate-points-and-rewards' ) . '</a>',
			);
			$actions  = array_merge( $settings, $actions );
		}
		return $actions;
	}

	// Add link for settings.
	add_filter( 'plugin_action_links', 'mwb_wpr_admin_settings', 10, 5 );
	add_shortcode( 'MYCURRENTPOINT', 'mwb_wpr_mytotalpoint_shortcode', 10 );
	add_shortcode( 'MYCURRENTUSERLEVEL', 'mwb_wpr_mycurrentlevel_shortcode', 10 );
	add_shortcode( 'SIGNUPNOTIFICATION', 'mwb_wpr_signupnotif_shortcode', 10 );


	/**
	 * Add shortcode on shop page.
	 *
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_wpr_mytotalpoint_shortcode() {
		ob_start();
		$user_ID                      = get_current_user_ID();
		$mwb_wpr_shortcode_text_point = get_option( 'mwb_wpr_shortcode_text_point', 'Your Current Point' );
		if ( isset( $user_ID ) && ! empty( $user_ID ) ) {
			$get_points = (int) get_user_meta( $user_ID, 'mwb_wpr_points', true );
			echo '<div class="mwb_wpr_shortcode_wrapper">';
			echo esc_attr( $mwb_wpr_shortcode_text_point ) . ' ' . esc_attr( $get_points );
			echo '</div>';
		}
		return ob_get_clean();
	}

	/**
	 * Display your Current Level by using shortcode
	 *
	 * @name mwb_wpr_mycurrentlevel_shortcode
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_wpr_mycurrentlevel_shortcode() {
		ob_start();
		$user_ID                           = get_current_user_ID();
		$mwb_wpr_shortcode_text_membership = get_option( 'mwb_wpr_shortcode_text_membership', 'Your Current Level' );
		if ( isset( $user_ID ) && ! empty( $user_ID ) ) {
			$user_level = get_user_meta( $user_ID, 'membership_level', true );
			if ( isset( $user_level ) && ! empty( $user_level ) ) {
				echo esc_attr( $mwb_wpr_shortcode_text_membership ) . ' ' . esc_attr( $user_level );
			}
		}
		return ob_get_clean();
	}

	/**
	 * Display the SIgnup Notification by using shortcode
	 *
	 * @name mwb_wpr_signupnotif_shortcode
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_wpr_signupnotif_shortcode() {
		ob_start();
		$general_settings  = get_option( 'mwb_wpr_settings_gallery', true );
		$enable_mwb_signup = isset( $general_settings['enable_mwb_signup'] ) ? intval( $general_settings['enable_mwb_signup'] ) : 0;
		if ( $enable_mwb_signup && ! is_user_logged_in() ) {
			$mwb_wpr_signup_value = isset( $general_settings['mwb_signup_value'] ) ? intval( $general_settings['mwb_signup_value'] ) : 1;
			?>
			<div class="woocommerce-message">
				<?php
					echo esc_attr_e( 'You will get ', 'woocommerce-ultimate-points-and-rewards' ) . esc_attr( $mwb_wpr_signup_value ) . esc_attr_e( ' points for SignUp', 'woocommerce-ultimate-points-and-rewards' );
				?>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	// Activation hook.
	register_activation_hook( __FILE__, 'mwb_wpr_activation_functionality' );

	/**
	 * This function is used to schedule a cron for membership expiration
	 *
	 * @name mwb_wpr_activation_functionality
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_wpr_activation_functionality() {
		$mwb_wpr_existing_time = get_option( 'mwb_wpr_activation_date_time', 'not_yet' );
		if ( isset( $mwb_wpr_existing_time ) && 'not_yet' === $mwb_wpr_existing_time ) {
			$mwb_wpr_current_datetime = current_time( 'timestamp' );
			update_option( 'mwb_wpr_activation_date_time', $mwb_wpr_current_datetime );
		}
		if ( ! wp_next_scheduled( 'mwb_wpr_membership_cron_schedule' ) ) {
			wp_schedule_event( time(), 'daily', 'mwb_wpr_membership_cron_schedule' );
		}
		if ( ! wp_next_scheduled( 'mwb_wpr_points_expiration_cron_schedule' ) ) {
			wp_schedule_event( time(), 'daily', 'mwb_wpr_points_expiration_cron_schedule' );
		}
		if ( ! wp_next_scheduled( 'mwb_wpr_update_json' ) ) {
			wp_schedule_event( time(), 'daily', 'mwb_wpr_update_json' );
		}

		$upload_dir_path = wp_upload_dir()['basedir'] . '/mwb_wpr_json';
		if ( ! is_dir( $upload_dir_path ) ) {
			wp_mkdir_p( $upload_dir_path );
			chmod( $upload_dir_path, 0755 );
		}
		/*Get data from the makewebbetter server*/
		$data   = wp_remote_get( 'https://makewebbetter.com/pluginupdates/addons/par/api.json' );
		$handle = fopen( wp_upload_dir()['basedir'] . '/mwb_wpr_json/api.json', 'w' );
		fwrite( $handle, $data['body'] );
		fclose( $handle );

	}
	register_deactivation_hook( __FILE__, 'mwb_wpr_remove_cron_schedule' );

	/**
	 * This function is used to remove the cron schedule
	 *
	 * @name mwb_wpr_remove_cron_schedule
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_wpr_remove_cron_schedule() {
		wp_clear_scheduled_hook( 'mwb_wpr_membership_cron_schedule' );
		wp_clear_scheduled_hook( 'mwb_wpr_points_expiration_cron_schedule' );
		wp_clear_scheduled_hook( 'mwb_wpr_update_json' );
	}

	/**
	 * This function is used to return the date format as per WP settings
	 *
	 * @param   int $saved_date   value for date.
	 * @name mwb_wpr_set_the_wordpress_date_format
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 **/
	function mwb_wpr_set_the_wordpress_date_format( $saved_date ) {
		$saved_date  = strtotime( $saved_date );
		$date_format = get_option( 'date_format', 'Y-m-d' );
		$time_format = get_option( 'time_format', 'g:i a' );
		$wp_date     = date_i18n( $date_format, $saved_date );
		$wp_time     = date_i18n( $time_format, $saved_date );
		$return_date = $wp_date . ' ' . $wp_time;
		return $return_date;
	}
} else {
	/**
	 * Show warning message if woocommerce is not install
	 *
	 * @name mwb_wpr_plugin_error_notice()
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_wpr_plugin_error_notice() {
		?>
	<div class="error notice is-dismissible">
	<p><?php esc_attr_e( 'Woocommerce is not activated, Please activate Woocommerce first to install WooCommerce Ultimate Points and Rewards.', 'woocommerce-ultimate-points-and-rewards' ); ?></p>
	</div>
		<?php
	}
	add_action( 'admin_init', 'mwb_wpr_plugin_deactivate' );
	/**
	 * Call Admin notices
	 *
	 * @name mwb_wpr_plugin_deactivate()
	 * @author makewebbetter<webmaster@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_wpr_plugin_deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', 'mwb_wpr_plugin_error_notice' );
	}
}
// Auto update.
$mwb_wpr_license_key = get_option( 'mwb_wpr_license_key', '' );
define( 'MWB_WPR_LICENSE_KEY', $mwb_wpr_license_key );
define( 'MWB_WPR_FILE', __FILE__ );
$mwb_wpr_update_check = 'https://makewebbetter.com/pluginupdates/codecanyon/woocommerce-ultimate-points-and-rewards/update.php';
require_once 'class-mwb-wpr-update.php';
?>
