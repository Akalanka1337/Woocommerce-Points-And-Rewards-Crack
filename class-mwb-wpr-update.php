<?php
/**
 * The update functionality of the plugin.
 *
 * @link       https://makewebbetter.com/
 * @since      1.0.0
 *
 * @package    Woocommerce_Ultimate_Points_And_Rewards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'MWB_WPR_Update' ) ) {
	/**
	 * Class for update.
	 */
	class MWB_WPR_Update {
		/**
		 * Initialize the class and set its properties.
		 */
		public function __construct() {
			register_activation_hook( MWB_WPR_FILE, array( $this, 'mwb_wpr_check_activation' ) );
			add_action( 'mwb_wpr_check_event', array( $this, 'mwb_wpr_check_update' ) );
			add_filter( 'http_request_args', array( $this, 'mwb_wpr_updates_exclude' ), 5, 2 );
			add_action( 'install_plugins_pre_plugin-information', array( $this, 'mwb_plugin_details' ) );
			register_deactivation_hook( MWB_WPR_FILE, array( $this, 'mwb_wpr_check_deactivation' ) );
		}
		/**
		 * Function for deactivation check.
		 *
		 * @return void
		 */
		public function mwb_wpr_check_deactivation() {
			wp_clear_scheduled_hook( 'mwb_wpr_check_event' );
		}
		/**
		 * Function for activation check.
		 *
		 * @return void
		 */
		public function mwb_wpr_check_activation() {
			wp_schedule_event( time(), 'daily', 'mwb_wpr_check_event' );
		}
		/**
		 * Function to check updates.
		 *
		 * @return bool
		 */
		public function mwb_wpr_check_update() {
			global $wp_version;
			global $mwb_wpr_update_check;
			$plugin_folder = plugin_basename( dirname( MWB_WPR_FILE ) );
			$plugin_file   = basename( ( MWB_WPR_FILE ) );
			if ( defined( 'WP_INSTALLING' ) ) {
				return false;
			}
			$postdata = array(
				'action'        => 'check_update',
				'purchase_code' => MWB_WPR_LICENSE_KEY,
			);
			$args     = array(
				'method' => 'POST',
				'body'   => $postdata,
			);
			$response = wp_remote_post( $mwb_wpr_update_check, $args );
			if ( empty( $response['body'] ) ) {
				return false;
			}
			list($version, $url) = explode( '~', $response['body'] );
			if ( $this->mwb_plugin_get( 'Version' ) === $version ) {
				return false;
			}
			if ( $this->mwb_plugin_get( 'Version' ) > $version ) {
				return false;
			}
			$plugin_transient = get_site_transient( 'update_plugins' );
			$a                = array(
				'slug'        => $plugin_folder,
				'new_version' => $version,
				'url'         => $this->mwb_plugin_get( 'AuthorURI' ),
				'package'     => $url,
			);
			$o                = (object) $a;
			$plugin_transient->response[ $plugin_folder . '/' . $plugin_file ] = $o;
			set_site_transient( 'update_plugins', $plugin_transient );
		}
		/**
		 * Function to check updates exclude.
		 *
		 * @param mixed $r contains plugin data.
		 * @param mixed $url contains url data.
		 * @return bool
		 */
		public function mwb_wpr_updates_exclude( $r, $url ) {
			if ( 0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check' ) ) {
				return $r;
			}
			$plugins = unserialize( $r['body']['plugins'] );
			if ( ! empty( $plugins ) ) {
				unset( $plugins->plugins[ plugin_basename( __FILE__ ) ] );
				if ( ! empty( $plugins->active ) ) {
					unset( $plugins->active[ array_search( plugin_basename( __FILE__ ), $plugins->active ) ] );
				}
			}
			$r['body']['plugins'] = serialize( $plugins );
			return $r;
		}
		/**
		 * Function returns current plugin info.
		 *
		 * @param mixed $i is the data.
		 * @return array
		 */
		public function mwb_plugin_get( $i ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugin_folder = get_plugins( '/' . plugin_basename( dirname( MWB_WPR_FILE ) ) );
			$plugin_file   = basename( ( MWB_WPR_FILE ) );
			return $plugin_folder[ $plugin_file ][ $i ];
		}
		/**
		 * Function to create plugin details.
		 *
		 * @return void
		 */
		public function mwb_plugin_details() {
			global $tab;
			$value = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
			if ( 'plugin-information' === $tab && 'woocommerce-ultimate-points-and-rewards' === $value ) {

				$url = 'https://makewebbetter.com/pluginupdates/codecanyon/woocommerce-ultimate-points-and-rewards/update.php';

				$postdata = array(
					'action'       => 'check_update',
					'license_code' => MWB_WPR_LICENSE_KEY,
				);

				$args = array(
					'method' => 'POST',
					'body'   => $postdata,
				);

				$data = wp_remote_post( $url, $args );
				if ( is_wp_error( $data ) ) {
					return;
				}

				if ( isset( $data['body'] ) ) {
					$all_data = json_decode( $data['body'], true );

					if ( is_array( $all_data ) && ! empty( $all_data ) ) {
						$this->create_html_data( $all_data );
					}
				}
			}
		}
		/**
		 * Create html data.
		 *
		 * @param [type] $all_data contain data.
		 * @return void
		 */
		public function create_html_data( $all_data ) {
			?>
			<style>
				#TB_window{
					top : 4% !important;
				}
				.mwb_plugin_banner > img {
					height: 55%;
					width: 100%;
					border: 1px solid;
					border-radius: 7px;
				}
				.mwb_plugin_description > h4 {
					background-color: #3779B5;
					padding: 5px;
					color: #ffffff;
					border-radius: 5px;
				}
				.mwb_plugin_requirement > h4 {
					background-color: #3779B5;
					padding: 5px;
					color: #ffffff;
					border-radius: 5px;
				}
				#error-page > p {
					display: none;
				}
			</style>
			<div class="mwb_plugin_details_wrapper">
				<div class="mwb_plugin_banner">
					<img src="<?php echo esc_url( $all_data['banners']['low'] ); ?>">	
				</div>
				<div class="mwb_plugin_description">
					<h4><?php esc_attr_( 'Plugin Description', 'woocommerce-ultimate-points-and-rewards' ); ?></h4>
					<span><?php echo esc_attr( $all_data['sections']['description'] ); ?></span>
				</div>
				<div class="mwb_plugin_requirement">
					<h4><?php esc_attr_( 'Plugin Change Log', 'woocommerce-ultimate-points-and-rewards' ); ?></h4>
					<span><?php echo esc_attr( $all_data['sections']['changelog'] ); ?></span>
				</div> 
			</div>
			<?php
		}
	}
	new MWB_WPR_Update();
}
?>
