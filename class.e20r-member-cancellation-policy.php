<?php
/*
Plugin Name: E20R Member Cancellation Policy for Paid Memberships Pro
Plugin URI: http://eighty20results.com/wordpress-plugins/e20r-member-cancellation-policy
Description: Adds a membership level setting to configure how PMPro will handle membership cancellations.
Version: 1.6
Requires: 4.7
Author: Thomas Sjolshagen <thomas@eighty20results.com>
Author URI: http://www.eighty20results.com/thomas-sjolshagen/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: e20r-member-cancellation-policy
*/

/**
 * Copyright (C) 2017  Thomas Sjolshagen - Eighty / 20 Results by Wicked Strong Chicks, LLC
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace E20R\Member_Cancellation;

use E20R\Member_Cancellation\Utilities\Utilities;
use E20R\Member_Cancellation\Utilities\Cache;

define( 'E20R_MEMBER_CANCELLATION_POLICY', '1.6' );

if ( ! class_exists( 'E20R\Member_Cancellation\E20R_Member_Cancellation_Policy' ) ) {
	class E20R_Member_Cancellation_Policy {
		
		const plugin_slug = 'e20r-member-cancellation-policy';
		
		/**
		 * @var null|E20R_Member_Cancellation_Policy
		 */
		private static $instance = null;
		
		/**
		 * @var array Settings for the expiration choice
		 */
		private $settings;
		
		private $user_level = array();
		
		private $subscription_label_singular = null;
		private $subscription_label_plural = null;
		
		/**
		 * E20R_Member_Cancellation_Policy constructor.
		 */
		public function __construct() {
			
			add_filter( 'e20r-licensing-text-domain', array( $this, 'set_plugin_slug' ) );
			$slug                              = apply_filters( 'e20r-licensing-text-domain', E20R_Member_Cancellation_Policy::plugin_slug );
			$this->subscription_label_singular = apply_filters( 'e20r-member-cancellation-subscription-label-singular', __( "Subscription", E20R_Member_Cancellation_Policy::plugin_slug ) );
			$this->subscription_label_plural   = apply_filters( 'e20r-member-cancellation-subscription-label-plural', __( "Subscriptions", E20R_Member_Cancellation_Policy::plugin_slug ) );
			
		}
		
		/**
		 * @return E20R_Member_Cancellation_Policy|null
		 */
		public static function get_instance() {
			
			if ( is_null( self::$instance ) ) {
				self::$instance = new self;
				self::register();
			}
			
			return self::$instance;
		}
		
		/**
		 * Loads an instance of the plugin and configures key actions,.
		 */
		public static function register() {
			
			$current_user_level = null;
			$util               = Utilities::get_instance();
			
			global $current_user;
			
			if ( function_exists( 'is_user_logged_in' ) && ! is_user_logged_in() ) {
				return;
			}
			
			if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
				return;
			}
			
			if ( is_null( self::$instance ) ) {
				self::$instance = new self;
			}
			
			$plugin = self::$instance;
			
			add_action( 'init', array( $plugin, 'load_translation' ) );
			add_action( 'admin_enqueue_scripts', array( $plugin, 'load_css' ) );
			add_action( 'wp_enqueue_scripts', array( $plugin, 'load_css' ) );
			
			add_action( 'pmpro_membership_level_after_other_settings', array( $plugin, 'add_level_settings' ) );
			add_action( 'pmpro_save_membership_level', array( $plugin, 'save_level_settings' ) );
			add_action( 'pmpro_delete_membership_level', array( $plugin, 'delete_level_settings' ) );
			
			if ( true === $util->plugin_is_active( null, 'pmpro_changeMembershipLevel' ) ) {
				
				$plugin->set_user_level( $current_user->ID, pmpro_getMembershipLevelForUser( $current_user->ID ) );
				$current_user_level = $plugin->get_user_level( $current_user->ID );
				
				if ( is_user_logged_in() && ! empty( $current_user_level->id ) ) {
					
					$plugin->load_filters( $current_user_level->id );
					
					if ( 'end_of_period' === $plugin->get_setting( 'termination-choice', $current_user_level->id ) &&
					     ! has_action( 'pmpro_after_change_membership_level', array(
						     $plugin,
						     'after_change_membership_level',
					     ) )
					) {
						$util->log( "Adding after change action for membership level {$current_user_level->id} w/end_of_period termination policy" );
						
						add_action( 'pmpro_after_change_membership_level', array(
							$plugin,
							'after_change_membership_level',
						), 10, 2 );
					}
					
					if ( 'end_of_period' === $plugin->get_setting( 'termination-choice', $current_user_level->id ) &&
					     ! has_action( 'pmpro_before_change_membership_level', array(
						     $plugin,
						     'before_change_membership_level',
					     ) )
					) {
						$util->log( "Adding before change action for membership level {$current_user_level->id} w/end_of_period termination policy" );
						
						add_action( 'pmpro_before_change_membership_level', array(
							$plugin,
							'before_change_membership_level',
						), 10, 2 );
					}
				}
			}
		}
		
		/**
		 * Save the active user level definition for the specific user ID
		 *
		 * @param int       $user_id
		 * @param \stdClass $level
		 */
		private function set_user_level( $user_id, $level ) {
			$this->user_level[ $user_id ] = $level;
		}
		
		/**
		 * Currently saved/active Membership Level definition for the user ID
		 *
		 * @param int $user_id
		 *
		 * @return \stdClass|null
		 */
		private function get_user_level( $user_id ) {
			
			return isset( $this->user_level[ $user_id ] ) ? $this->user_level[ $user_id ] : null;
		}
		
		/**
		 * Update the plugin slug for translations, etc.
		 *
		 * @return string
		 */
		public function set_plugin_slug() {
			return E20R_Member_Cancellation_Policy::plugin_slug;
		}
		
		/**
		 * Load the CSS for this add-on
		 */
		public function load_css() {
			
			if ( is_admin() ) {
				wp_enqueue_style( 'e20r-member-cancellation-policy', plugins_url( 'css/e20r-member-cancellation-policy.css', __FILE__ ), null, E20R_MEMBER_CANCELLATION_POLICY );
			}
		}
		
		/**
		 * Save settings when saving membership level
		 *
		 * @param int $level_id
		 */
		public function save_level_settings( $level_id ) {
			
			foreach ( $_REQUEST as $key => $value ) {
				
				if ( false !== strpos( $key, 'e20r-cancellation-policy_' ) ) {
					
					$rk_arr = explode( '_', $key );
					
					$real_key = $rk_arr[ ( count( $rk_arr ) - 1 ) ];
					$this->save_setting( $real_key, sanitize_text_field( $_REQUEST[ $key ] ), $level_id );
				}
			}
		}
		
		/**
		 * Delete settings when admin deletes the membership level
		 *
		 * @param int $level_id
		 */
		public function delete_level_settings( $level_id ) {
			
			if ( ! empty( $this->settings[ $level_id ] ) ) {
				unset( $this->settings[ $level_id ] );
			}
			
			update_option( 'e20r_cancellation_policy', $this->settings, false );
		}
		
		/**
		 *  Save Membership level settings as option(s)
		 *
		 * @param null   $key
		 * @param null   $value
		 * @param string $level_id
		 */
		public function save_setting( $key = null, $value = null, $level_id = 'default' ) {
			
			// Configure default setting(s).
			if ( empty( $this->settings ) ) {
				
				$this->settings = $this->default_settings();
			}
			
			if ( ! isset( $this->settings[ $level_id ] ) && $level_id !== 'default' ) {
				$this->settings[ $level_id ] = $this->settings['default'];
			}
			
			// Append the settings array for the level ID if it doesn't exits
			if ( isset( $this->settings[ $level_id ] ) && ! is_array( $this->settings[ $level_id ] ) ) {
				$this->settings[ $level_id ] = array();
			}
			
			// Assign the key/value pair
			$this->settings[ $level_id ][ $key ] = $value;
			
			update_option( 'e20r_cancellation_policy', $this->settings, false );
			
			$test = get_option( 'e20r_cancellation_policy' );
			
			if ( $test[ $level_id ][ $key ] != $this->settings[ $level_id ][ $key ] ) {
				$this->set_message( sprintf( __( "Unable to save Level cancellation choice settings for %s", "e20r-member-cancellation-policy" ), $key ), "error" );
			}
		}
		
		/**
		 * Set the PMPro Error/Warning message
		 *
		 * @param string $msg
		 * @param string $class
		 */
		private function set_message( $msg, $class ) {
			?>
            <div class="<?php echo $class; ?>">
				<?php echo $msg; ?>
            </div>
			<?php
		}
		
		/**
		 * Return the default settings
		 *
		 * @return array
		 */
		private function default_settings() {
			
			return array(
				'default' => array(
					'termination-choice'    => apply_filters( 'e20r-member-cancellation-policy-default', 'end_of_period' ),
					'delete-inactive-users' => apply_filters( 'e20r-member-cancellation-deletion', false ),
				),
			);
		}
		
		/**
		 * Fetch a specific setting by it's key value
		 *
		 * @param string $key
		 * @param string $level_id
		 *
		 * @return mixed
		 */
		public function get_setting( $key, $level_id = 'default' ) {
			
			if ( empty( $this->settings ) ) {
				$this->settings = get_option( 'e20r_cancellation_policy', $this->default_settings() );
			}
			
			if ( ! isset( $this->settings[ $level_id ][ $key ] ) ) {
				$this->settings[ $level_id ]                       = array();
				$this->settings[ $level_id ]['termination-choice'] = apply_filters( 'e20r-member-cancellation-policy-default', 'end_of_period' );
			}
			
			return ( isset( $this->settings[ $level_id ][ $key ] ) ? $this->settings[ $level_id ][ $key ] : null );
		}
		
		/**
		 * Load the required locale/translation file for the plugin
		 */
		public function load_translation() {
			
			$locale = apply_filters( "plugin_locale", get_locale(), E20R_Member_Cancellation_Policy::plugin_slug );
			$mo     = E20R_Member_Cancellation_Policy::plugin_slug . "-{$locale}.mo";
			
			//paths to local (plugin) and global (WP) language files
			$local_mo  = plugin_dir_path( __FILE__ ) . "/languages/{$mo}";
			$global_mo = WP_LANG_DIR . "/" . E20R_Member_Cancellation_Policy::plugin_slug . "/{$mo}";
			
			//load global first
			load_textdomain( E20R_Member_Cancellation_Policy::plugin_slug, $global_mo );
			
			//load local second
			load_textdomain( E20R_Member_Cancellation_Policy::plugin_slug, $local_mo );
		}
		
		/**
		 * Load filter hooks we'll need to use
		 */
		public function load_filters( $level_id = null ) {
			
			if ( 'end_of_period' === $this->get_setting( 'termination-choice', $level_id ) && ! has_filter( 'pmpro_email_body', array(
					$this,
					'cancellation_email_body',
				) )
			) {
				// add_filter('pmpro_account_membership_expiration_text', array( $this, 'change_expiration_text', 10, 2 ) );
				add_filter( 'gettext', array( $this, 'change_expiration_header' ), 10, 3 );
				add_filter( 'pmpro_email_body', array( $this, 'cancellation_email_body' ), 10, 2 );
			}
		}
		
		/**
		 * Change the status header for the user's membership that's ending due to policy setting
		 *
		 * @param $translated
		 * @param $text
		 * @param $domain
		 *
		 * @return mixed|string
		 */
		public function change_expiration_header( $translated, $text, $domain ) {
			
			if ( is_user_logged_in() && ( $domain == 'paid-memberships-pro' || $domain == 'pmpro' ) && ( $text == 'Expiration' ) ) {
				
				$level = pmpro_getMembershipLevelForUser();
				
				if ( ! empty( $level->enddate ) ) {
					$translated = __( 'Ends', E20R_Member_Cancellation_Policy::plugin_slug );
				}
			}
			
			return $translated;
		}
		
		/**
		 * Generate the level setting HTML
		 */
		public function add_level_settings() {
			
			$util = Utilities::get_instance();
			
			$level_id = $util->get_variable( 'edit', 'default' );
			?>
            <hr/>
            <h3 class="e20r-member-cancellation-policy-header"><?php _e( "Cancellation Policy ", E20R_Member_Cancellation_Policy::plugin_slug ); ?></h3>
            <div class="e20r-member-cancellation-policy-settings">
                <div class="e20r-settings-body">
                    <div class="e20r-settings-row">
                        <div class="e20r-settings-cell">
                            <label for="e20r-cancellation-policy_termination-choice"><?php _e( "Membership is cancelled:", E20R_Member_Cancellation_Policy::plugin_slug ); ?></label>
                        </div>
                        <div class="e20r-settings-cell">
                            <select name="e20r-cancellation-policy_termination-choice"
                                    class="e20r-cancellation-policy_termination-choice"
                                    id="e20r-cancellation-policy_termination-choice">
                                <option
                                        value="immediately" <?php selected( 'immediately', $this->get_setting( 'termination-choice', $level_id ) ); ?>><?php _e( "Immediately", E20R_Member_Cancellation_Policy::plugin_slug ); ?></option>
                                <option
                                        value="end_of_period" <?php selected( 'end_of_period', $this->get_setting( 'termination-choice', $level_id ) ); ?>><?php _e( "At the end of the billing period", E20R_Member_Cancellation_Policy::plugin_slug ); ?></option>
                            </select>
                        </div>
                    </div>
                    <!--
                    <div class="e20r-settings-row">
                        <div class="e20r-settings-cell">
                            <label for="e20r-cancellation-policy_delete-inactive-users"><?php _e( "Delete inactive users:", E20R_Member_Cancellation_Policy::plugin_slug ); ?></label>
                        </div>
                        <div class="e20r-settings-cell">
                            <input type="checkbox" name="e20r-cancellation-policy_delete-inactive-users"
                                   class="e20r-cancellation-policy_delete-inactive-users"
                                   id="e20r-cancellation-policy_delete-inactive-users" <?php checked( true, $this->get_setting( '', $level_id ) ); ?> />
                        </div>
                    </div>
                    -->
                </div>
            </div>
			<?php
		}
		
		/**
		 * Preserve the timestamp for the next scheduled payment (before it's removed/cancelled as part of the cancellation
		 * action)
		 *
		 * @param int $level_id The ID of the membership level we're changing to for the user
		 * @param int $user_id  The User ID we're changing membership information for
		 *
		 * @return int  $pmpro_next_payment_timestamp   - The UNIX epoch value for the next payment (as a global variable)
		 */
		public function before_change_membership_level( $level_id, $user_id ) {
			
			
			global $pmpro_pages;
			global $pmpro_stripe_event;
			global $pmpro_next_payment_timestamp;
			
			$util = Utilities::get_instance();
			$from = trim( $util->get_variable( 'from', null ) );
			
			// Only process this hook if we're on the PMPro cancel page (and it's not triggered from the user's profile page)
			if ( 0 == $level_id && ( is_page( $pmpro_pages['cancel'] ) || ( is_admin() && ( empty( $from ) || 'profile' != $from ) ) ) ) {
				
				// Check if the user is in their trial period. If so, end at the end of the trial period
				if ( false !== ( $trial_ends_ts = $util->is_in_trial( $user_id, $level_id ) ) ) {
					
					$util->log( "User is in a trial period, so returning {$trial_ends_ts}" );
					$pmpro_next_payment_timestamp = $trial_ends_ts;
					
					return $pmpro_next_payment_timestamp;
				}
				
				$util->log( "Using 'old' way of calculating probable end time for membership..." );
				
				// Retrieve the last succssfully paid-for order
				$order = new \MemberOrder();
				$order->getLastMemberOrder( $user_id, "success" );
				
				// When using PayPal Express or Stripe, use their API to get the 'end of this subscription period' value
				if ( ! empty( $order->id ) && 'stripe' == $order->gateway ) {
					
					if ( ! empty( $pmpro_stripe_event ) ) {
						
						// The Stripe WebHook is asking us to cancel the membership
						if ( ! empty( $pmpro_stripe_event->data->object->current_period_end ) ) {
							
							$pmpro_next_payment_timestamp = $pmpro_stripe_event->data->object->current_period_end;
						}
						
					} else {
						
						//User initiated cancellation event, request the data from the Stripe.com service
						$pmpro_next_payment_timestamp = \PMProGateway_stripe::pmpro_next_payment( "", $user_id, "success" );
					}
					
				} else if ( ! empty( $order->id ) && "paypalexpress" == $order->gateway ) {
					
					$next_payment_date_str = $util->get_variable( 'next_payment_date', null );
					
					if ( ! empty( $next_payment_date_str ) && 'N/A' != $next_payment_date_str ) {
						
						// PayPal server initiated the cancellation (via their IPN hook)
						$pmpro_next_payment_timestamp = strtotime( $next_payment_date_str, current_time( 'timestamp' ) );
					} else {
						
						// User initiated cancellation event, request the data from the PayPal service
						$pmpro_next_payment_timestamp = \PMProGateway_paypalexpress::pmpro_next_payment( "", $user_id, "success" );
					}
				}
			}
		}
		
		/**
		 * Process the cancellation and set the expiration (enddate value) date to be the last day of the subscription
		 * period
		 *
		 * @param int $level_id The ID of the membership/subscription level they're currently at
		 * @param int $user_id  The ID of the user on this system
		 *
		 * @return bool
		 */
		public function after_change_membership_level( $level_id, $user_id ) {
			
			global $pmpro_pages;
			global $pmpro_next_payment_timestamp;
			
			global $wpdb;
			
			$util = Utilities::get_instance();
			$from = trim( $util->get_variable( 'from', null ) );
			
			// Only process this if we're on the cancel page
			if ( true === $util->plugin_is_active( null, 'pmpro_changeMembershipLevel' ) && 0 === $level_id && ( is_page( $pmpro_pages['cancel'] ) || ( is_admin() && ( empty( $from ) || 'profile' != $from ) ) ) ) {
				
				// Search the databas for the most recent order object
				$order = new \MemberOrder();
				$order->getLastMemberOrder( $user_id, "cancelled" );
				
				// Nothing to do if there's no order found
				if ( empty( $order->id ) ) {
					return false;
				}
				
				$util->log( "Most recent member order: " . print_r( $order, true ) );
				
				// Fetch the most recent membership level definition for the user
				$sql = $wpdb->prepare( "SELECT *
                    FROM {$wpdb->pmpro_memberships_users}
                    WHERE membership_id = %d
                        AND user_id = %d
                    ORDER BY id DESC
                    LIMIT 1",
					intval( $order->membership_id ),
					intval( $user_id )
				);
				
				$level = $wpdb->get_row( $sql );
				
				$util->log( "Last membership level for {$user_id}: " . print_r( $level, true ) );
				
				// Return error if the last level wasn't a recurring one
				if ( ! isset( $level->cycle_number ) || ( isset( $level->cycle_number ) && $level->cycle_number < 1 ) ) {
					$util->log( "No membership level found for user {$user_id}" );
					
					return false;
				}
				
				// Return error if there's no level found
				if ( ! isset( $level->id ) || empty( $level ) ) {
					return false;
				}
				
				// Format the date string for the the last time the order was processed
				$lastdate = date_i18n( "Y-m-d", $order->timestamp );
				
				/**
				 * Find the timestamp indicating when the next payment is supposed to occur
				 * For PayPal Express and Stripe, we'll use their native gateway look-up functionality
				 */
				if ( ! empty( $pmpro_next_payment_timestamp ) ) {
					
					// Stripe or PayPal would have configured this global
					$next_payment = $pmpro_next_payment_timestamp;
					
				} else if ( $level->cycle_number > 0 && ! empty( $level->cycle_period ) && empty( $pmpro_next_payment_timestamp ) ) {
					
					// Calculate when the next scheduled payment is estimated to happen
					$nextdate_sql = $wpdb->prepare(
						"SELECT UNIX_TIMESTAMP( %s + INTERVAL %d {$level->cycle_period})",
						$lastdate,
						intval( $level->cycle_number )
					);
					
					$next_payment = $wpdb->get_var( $nextdate_sql );
					
				} else {
					
					if ( false !== ( $next_payment = $util->is_in_trial( $user_id, $level_id ) ) ) {
						$util->log( "Using the end of the trial period as the enddate for this user's ({$user_id}) membership ({$level_id})" );
						
					} else {
						
						$util->log( "Absolutely no recurring payment info was found and no trial membership configured!" );
						
						// No recurring payment info found!
						return false;
					}
				}
				
				if ( WP_DEBUG ) {
					error_log( "Next payment date for {$user_id} should be: {$next_payment}" );
				}
				
				/**
				 * Process this if the next payment date is in the future
				 */
				if ( $next_payment - current_time( 'timestamp' ) > 0 ) {
					
					// Fetch their previous membership level info
					$old_level_sql = $wpdb->prepare( "
                        SELECT *
                        FROM {$wpdb->pmpro_memberships_users}
                        WHERE membership_id = %d
                            AND user_id = %d
                        ORDER BY id DESC
                        LIMIT 1",
						intval( $order->membership_id ),
						intval( $user_id )
					);
					
					$old_level = $wpdb->get_row( $old_level_sql, ARRAY_A );
					
					// TODO: Handle situations where the Utilities::is_in_trial( $user_id, $level_id ) === true!
					
					// Only makes sense to do this if the user has an old payment level.
					if ( ! empty( $old_level ) && ! empty( $next_payment ) ) {
						
						$old_level['enddate'] = date_i18n( "Y-m-d H:i:s", $next_payment );
						
						$util->log( "Setting termination of membership to (date): {$old_level['enddate']}" );
						
						// Remove action so we won't cause ourselves to loop indefinitely (or for 255 loops, whichever comes first).
						remove_action( 'pmpro_after_change_membership_level', array(
							$this,
							'after_change_membership_level',
						), 10, 2 );
						
						// Remove action in case it's here.
						if ( function_exists( 'my_pmpro_cancel_previous_subscriptions' ) &&
						     has_filter( 'pmpro_cancel_previous_subscriptions', 'my_pmpro_cancel_previous_subscriptions' )
						) {
							
							remove_filter( 'pmpro_cancel_previous_subscriptions', 'my_pmpro_cancel_previous_subscriptions' );
						}
						
						// Change the membership level for the user to the new "old level"
						pmpro_changeMembershipLevel( $old_level, $user_id );
						
						// Reattach this action (function)
						add_action( 'pmpro_after_change_membership_level', array(
							$this,
							'after_change_membership_level',
						), 10, 2 );
						
						// Add the backwards compatible filter (if it exists)
						if ( ! has_filter( 'pmpro_cancel_previous_subscriptions', 'my_pmpro_cancel_previous_subscriptions' ) &&
						     function_exists( 'my_pmpro_cancel_previous_subscriptions' )
						) {
							
							add_filter( 'pmpro_cancel_previous_subscriptions', 'my_pmpro_cancel_previous_subscriptions' );
						}
						
						// Change the cancelleation message shown on cancel confirmation page
						add_filter( 'gettext', array( $this, 'change_cancel_text' ), 10, 3 );
						
					} // End of 'only makes sense if user has old membership level
				}
				
			}
		}
		
		
		/**
		 * Filter to replace the "your membership has been cancelled" message with something to indicate when it'll be
		 * cancelled from.
		 *
		 * @param string $translated_text
		 * @param string $text
		 * @param string $domain
		 *
		 * @return      string      - Updated/modified 'translation' of the text (always)
		 */
		public function change_cancel_text( $translated_text, $text, $domain ) {
			
			global $current_user;
			$util = Utilities::get_instance();
			
			// Update the membership cancellation text if needed
			if ( true === $util->plugin_is_active( null, 'pmpro_changeMembershipLevel' ) && ( 'pmpro' === $domain || 'paid-memberships-pro' === $domain ) && 'Your membership has been cancelled.' === $text ) {
				
			    $end_ts = $this->membership_ends( $current_user->ID );
			    
			    if ( !empty( $end_ts ) ) {
			        
                    $next_payment_date = date_i18n( get_option( "date_format" ), $end_ts );
                    
                    $translated_text   = sprintf( __( "Your %s has been cancelled. Your access will expire on %s", E20R_Member_Cancellation_Policy::plugin_slug ), strtolower( $this->subscription_label_singular ), $next_payment_date );
                } else {
				    $translated_text   = sprintf( __( "Your %s has been cancelled. Your access will expire at the end of your membership", E20R_Member_Cancellation_Policy::plugin_slug  ), strtolower( $this->subscription_label_singular ) );
                }
			}
			
			return $translated_text;
		}
		
		/**
		 * Update the body of the email message on cancellation to reflect the actual cancellation date for the subscription
		 *
		 * @param   string      $body  - Body of the email message
		 * @param   \PMProEmail $email - The PMPro Email object
		 *
		 * @return  string          - The new body of the email message
		 */
		public function cancellation_email_body( $body, $email ) {
			
			$util = Utilities::get_instance();
			
			if ( false === $util->plugin_is_active( null, 'pmpro_changeMembershipLevel' ) ) {
				return $body;
			}
			
			if ( $email->template == "cancel" ) {
				
				$user = get_user_by( 'email', $email->email );
				
				if ( ! empty( $user->ID ) ) {
					
					$expiration_ts = $this->membership_ends( $user->ID );
					
					//if the date in the future?
					if ( ! empty( $expiration_date ) && $expiration_date - time() > 0 ) {
						
						$enddate = date_i18n( get_option( "date_format" ), $expiration_ts );
						
						$body .= "<p>" . sprintf(
						        __( "Your %s has been cancelled. Your access will expire on %s",
                                        E20R_Member_Cancellation_Policy::plugin_slug ),
                                strtolower( $this->subscription_label_singular ),
                                $enddate
                            ) . "</p>";
						
					} else if ( empty( $expiration_ts ) ) {
						
						$body .= "<p>" . sprintf( __( "Your %s has been cancelled. Access will terminate at the end of your membership period.", E20R_Member_Cancellation_Policy::plugin_slug ), strtolower( $this->subscription_label_singular ) ) . "</p>";
					}
				}
			}
			
			return $body;
		}
		
		/**
		 * Fetch the enddate for user's membership
		 *
		 * @param int $user_id User ID to check enddate for
		 *
		 * @return int  Timestamp indicating the enddate for the user's membership
		 */
		public function membership_ends( $user_id ) {
			
			if ( null === ( $user_map = Cache::get( "terminates", 'e20r_member_cancellation' ) ) ) {
				
				global $wpdb;
				
				// Get all expiring membership records that are currently active
				$query = "SELECT DISTINCT user_id,
                              UNIX_TIMESTAMP( enddate ) AS end_of_membership
                            FROM {$wpdb->pmpro_memberships_users}
                          WHERE enddate IS NOT NULL
                            AND enddate >= CURRENT_DATE()
                            AND status = 'active'";
				
				$results = $wpdb->get_results( $query );
				
				if ( ! empty( $results ) ) {
					
					$user_map = array();
					
					// Generate the map
					foreach ( $results AS $record ) {
						$user_map[ $record->user_id ] = $record->end_of_membership;
					}
					
					// Save the map in cache (30 minutes)
					Cache::set( "terminates", $user_map, ( 30 * MINUTE_IN_SECONDS ), 'e20r_member_cancellation' );
				}
			}
			
			return ( ! empty( $user_map[ $user_id ] ) ? $user_map[ $user_id ] : null );
		}
		
		/**
		 * Class auto-loader for this plugin
		 *
		 * @param string $class_name Name of the class to auto-load
		 *
		 * @since  1.0
		 * @access public
		 */
		public static function auto_loader( $class_name ) {
			
			if ( false === strpos( $class_name, 'E20R' ) ) {
				return;
			}
			
			$parts = explode( '\\', $class_name );
			$name  = strtolower( preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] ) );
			
			$base_path = plugin_dir_path( __FILE__ );
			$filename  = "class.{$name}.php";
			
			// For the E20R\Member_Cancellation namespace
			if ( file_exists( "{$base_path}/{$filename}" ) ) {
				require_once( "{$base_path}/{$filename}" );
			}
			
			// For the E20R\Member_Cancellation\Utilities namespace
			if ( file_exists( "{$base_path}/class/utilities/{$filename}" ) ) {
				require_once( "{$base_path}/class/utilities/{$filename}" );
			}
			
			// For the E20R\Licensing namespace
			if ( file_exists( "{$base_path}/class/licensing/{$filename}" ) ) {
				require_once( "{$base_path}/class/licensing/{$filename}" );
			}
		}
	}
}

// Init the autoloader method for this plugin
spl_autoload_register( 'E20R\\Member_Cancellation\\E20R_Member_Cancellation_Policy::auto_loader' );

/**
 * Load the plugin
 */
add_action( 'plugins_loaded', array( E20R_Member_Cancellation_Policy::get_instance(), 'register' ), 5 );

if ( ! class_exists( '\PucFactory' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'plugin-updates/plugin-update-checker.php' );
}

$plugin_updates = \PucFactory::buildUpdateChecker(
	'https://eighty20results.com/protected-content/e20r-member-cancellation-policy/metadata.json',
	__FILE__,
	'e20r-member-cancellation-policy'
);