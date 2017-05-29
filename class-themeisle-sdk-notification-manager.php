<?php
/**
 * The notification manager class for ThemeIsle SDK
 *
 * @package     ThemeIsleSDK
 * @subpackage  Notification
 * @copyright   Copyright (c) 2017, Marius Cristea
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.0.0
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'ThemeIsle_SDK_Notification_Manager' ) ) :
	/**
	 * Notification manager model for ThemeIsle SDK.
	 */
	class ThemeIsle_SDK_Notification_Manager {
		/**
		 * Time between notifications.
		 */
		const NOTIFICATION_INTERVAL_HOURS = 0.06;
		/**
		 * @var array Notifications for the current product.
		 */
		private $notifications = array();

		/**
		 * ThemeIsle_SDK_Notification_Manager constructor.
		 *
		 * @param ThemeIsle_SDK_Product $product_object Product Object.
		 * @param array                 $callbacks the objects that will be called when a notification is due.
		 */
		public function __construct( $product_object, $callbacks ) {

			// Load the notifications only if we have it installed after the required interval.
			if ( ( time() - $product_object->get_install_time() ) > self::NOTIFICATION_INTERVAL_HOURS * HOUR_IN_SECONDS ) {
				if ( $product_object instanceof ThemeIsle_SDK_Product && $callbacks && is_array( $callbacks ) ) {
					foreach ( $callbacks as $instance ) {
						$this->notifications[ $product_object->get_key() . get_class( $instance ) ] = $instance;
					}
				}
			}

			$this->setup_hooks();
		}

		/**
		 * Setup the internal hooks
		 */
		private function setup_hooks() {
			add_action( 'admin_head', array( $this, 'show_notification' ) );
		}

		/**
		 * Shows the notification
		 */
		function show_notification() {

			$hidden    = get_option( 'themeisle_sdk_notification_hidden', array() );
			$instances = $this->notifications;
			if ( empty( $instances ) ) {
				return;
			}

			// Get timestamp of last notification.
			$old = 0;
			if ( ! empty( $hidden ) ) {
				$old = $hidden[ count( $hidden ) - 1 ]['time'];
			}
			// Check if the current one is expired.
			if ( ( time() - $old ) > self::NOTIFICATION_INTERVAL_HOURS * HOUR_IN_SECONDS ) {
				// Get hidden notifications key.
				$hidden_ones = wp_list_pluck( $hidden, 'key' );
				// Get the non-hidden notifications.
				$available_notifications = array_diff( array_keys( $instances ), $hidden_ones );
				if ( empty( $available_notifications ) ) {
					return;
				}
				// Get the first notification available.
				$new_one = reset( $available_notifications );

				$instance = $instances[ $new_one ];
				$hidden[] = array(
					'time' => time(),
					'key'  => $new_one,
				);
				update_option( 'themeisle_sdk_notification_hidden', $hidden );
			} else {
				$key = $hidden[ count( $hidden ) - 1 ]['key'];
				if ( ! isset( $this->notifications[ $key ] ) ) {
					return;
				} else {
					$instance = $this->notifications[ $key ];
				}
			}
			$instance->show_notification();
		}
	}
endif;
