<?php
/**
 * Plugin Name: Easy CryptoCurrency Ticker
 * Description: Display cryptocurrency ticker widget on your WordPress website
 * Plugin URI: https://urosevic.net/wordpress/plugins/cc-ticker/
 * Author: Aleksandar Urošević
 * Author URI: https://urosevic.net
 * Version: 1.0.1
 * License: GPL3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: cc-ticker
 * Domain Path: languages
 * Network: false
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wpau_Cryptocurrency_Ticker' ) ) {
	final class Wpau_Cryptocurrency_Ticker {
		// Hold an instance of the class
		private static $instance;

		const DB_VER = 1;
		const VER = '1.0';
		public $plugin_name = 'Easy CryptoCurrency Ticker';
		public $plugin_url;
		private $cache_timeout = 2;
		private $messages;
		private static $coinlist;
		private static $upload_dir_path;
		public static $upload_dir_url;

		function __construct() {

			// Define various variables
			$this->plugin_url = plugin_dir_url( __FILE__ );
			$wp_upload_dir = wp_upload_dir();
			self::$upload_dir_path = $wp_upload_dir['basedir'] . '/cc-ticker';
			self::$upload_dir_url = $wp_upload_dir['baseurl'] . '/cc-ticker';
			$this->messages = array(
				'delay' => sprintf(
					__( 'Quotes delayed up to %s minutes', 'cc-ticker' ),
					$this->cache_timeout
				),
				'attribution' => sprintf(
					__( 'and provided by %s', 'cc-ticker' ),
					'<a href="https://www.cryptocompare.com/" target="_blank">CryptoCompare.com</a>'
				),
			);

			// If there is no upload dir, attempt to create one
			if ( ! is_dir( self::$upload_dir_path . '/ico' ) ) {
				wp_mkdir_p( self::$upload_dir_path . '/ico' );
			}

			// Enqueue frontend scripts
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Include widget
			require_once( 'inc/widget.php' );

			// Register stock_ticker shortcode.
			add_shortcode( 'cryptocurrency_ticker', array( $this, 'shortcode' ) );

			// AJAX calls
			add_action( 'wp_ajax_cct_get_coinlist', array( $this, 'ajax_get_coinlist' ) );
			add_action( 'wp_ajax_cct_parse_coinlist', array( $this, 'ajax_parse_coinlist' ) );

			// Helpers to get and scramble coinlist
			if ( ! empty( $_GET['cct-get-coinlist'] ) ) {
				self::get_coinlist();
			}
			if ( ! empty( $_GET['cct-parse-coinlist'] ) ) {
				self::parse_coinlist();
			}

			// Parse coinlist
			self::coinlist();

		} // END function __construct()

		/**
		 * The singleton method
		 * @return object Instance of class Wpau_Cryptocurrency_Ticker
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new Wpau_Cryptocurrency_Ticker();
			}
			return self::$instance;
		} // END public static function instance()

		/**
		 * Enqueue frontend assets
		 */
		function enqueue_scripts() {
			wp_enqueue_style(
				'cc-ticker',
				$this->plugin_url . 'assets/css/style.css',
				array(),
				self::VER
			);
		} // END function enqueue_scripts()

		/**
		 * Render widget
		 * @param  array $atts  Array of shortcode parameters
		 * @return string       Composed HTML output
		 */
		function shortcode( $atts ) {

			// Parse shortcode with default values
			$atts = shortcode_atts( array(
				'f'          => 'BTC,ETC,XMR',
				't'          => 'USD',
				'noicon'     => false,
				'nolink'     => false,
				'coinbase'   => '',
				'showchange' => '',
			), $atts );

			// Fetch stock
			$json = $this->fetch( $atts['f'], $atts['t'] );

			// Convert JSON to data array
			$data = json_decode( $json, true );

			// If any error ocurred, return error message
			if ( ! is_array( $data ) ) {
				return __( 'We are so sorry because Easy CryptoCurrency Ticker can not be displayed at the moment.', 'cc-ticker' );
			}

			// Prepare empty variables used for shortcode
			$html = $icon_style = $icon_class = '';

			// Open ticker table
			$html .= '<table class="cctw"><tbody>';
			// Loop through all `from` currencies
			foreach ( $data['DISPLAY'] as $from_symbol => $to_symbols ) {
				$to_prices_html = array();
				// Loop through all `to` currencies
				foreach ( $to_symbols as $to_symbol => $to_data ) {
					// Get change dirrection
					$change_day = $data['RAW'][ $from_symbol ][ $to_symbol ]['CHANGEDAY'];
					if ( $change_day < 0 ) {
						$change_class = 'down';
					} else if ( $change_day > 0 ) {
						$change_class = 'up';
					} else {
						$change_class = 'unchanged';
					}
					// Get update timestamp from RAW
					$timestamp = $data['RAW'][ $from_symbol ][ $to_symbol ]['LASTUPDATE'];

					// Display change into?
					$change_info = '';
					if ( ! empty( $atts['showchange'] ) ) {
						$change_info = sprintf(
							'%1$s (%2$s%%)',
							$data['DISPLAY'][ $from_symbol ][ $to_symbol ]['CHANGEDAY'],
							$data['DISPLAY'][ $from_symbol ][ $to_symbol ]['CHANGEPCTDAY']
						);
					}

					// Compose item for amount table cell
					$to_prices_html[] = sprintf(
						'<span class="amount %7$s" title="Mkt. Cap. %5$s - Last update %4$s"><span class="price"><span class="currency">%1$s</span> %2$s</span> <span class="change">%6$s</span></span>',
						$to_data['TOSYMBOL'],                                            // 1
						str_replace( "{$to_data['TOSYMBOL']} ", '', $to_data['PRICE'] ), // 2
						$to_symbol,                                                      // 3
						date( 'r', intval( $timestamp ) ),                               // 4
						$to_data['MKTCAP'],                                              // 5
						$change_info,                                                    // 6
						$change_class                                                    // 7
					);
				}
				// Join all cell rows with linebreak separator
				$prices_html = implode( ' ', $to_prices_html );

				// Prepare currency name
				$currency_name = ! empty( self::$coinlist[ $from_symbol ] ) ? self::$coinlist[ $from_symbol ]['c'] : $from_symbol;

				// Define icon style and currency classes
				if ( empty( $atts['noicon'] ) && ! empty( self::$coinlist[ $from_symbol ]['i'] ) ) {
					// Try to get local image
					$icon_url = self::get_icon_url( self::$coinlist[ $from_symbol ]['i'] );
					$icon_style = sprintf(
						'style="background-image: url(%1$s);"',
						$icon_url
					);
					$icon_class = 'ico';
				} else {
					$icon_class = 'noico';
				}
				$currency_class = "currency $icon_class";

				// Compose cryptocurrency cell
				if ( empty( $atts['nolink'] ) && ! empty( self::$coinlist[ $from_symbol ]['u'] ) ) {
					// Do we need to link currency to overview?
					$from_name = sprintf(
						'<a href="%1$s%2$s" class="%6$s" %5$s target="_blank" title="%4$s">%3$s</a>',
						'https://www.cryptocompare.com',      // 1
						self::$coinlist[ $from_symbol ]['u'], // 2
						$from_symbol,                         // 3
						$currency_name,                       // 4
						$icon_style,                          // 5
						$currency_class                       // 6
					);
				} else {
					// Or just to print unlinked cryptocurrency?
					$from_name = sprintf(
						'<span class="%4$s" %3$s title="%1$s">%2$s</span>',
						$currency_name, // 1
						$from_symbol,   // 2
						$icon_style,    // 3
						$currency_class // 4
					);
				}

				// Join all details to table row
				$html .= sprintf(
					'<tr><th>%1$s</th><td>%2$s</td></tr>',
					$from_name,
					$prices_html
				);
			}

			// Close ticker table
			$html .= '</tbody>';

			// Prepare coinbase referral link if exists
			$coinbase = '';
			if ( ! empty( $atts['coinbase'] ) ) {
				$coinbase_referral_id = self::sanitize_coinbase_id( $atts['coinbase'] );
				if ( ! empty( $coinbase_referral_id ) ) {
					$coinbase = sprintf(
						'<span class="coinbase"><a href="https://www.coinbase.com/join/%s" target="_blank">Join coinbase.com community!</a></span>',
						$coinbase_referral_id
					);
				}
			}

			// Prepare attribution for CoinBase
			$attribution = '.';
			if ( ! empty( $atts['nolink'] ) ) {
				$attribution = ' ' . $this->messages['attribution'];
			}
			// Prepare Delay message
			$delay = sprintf(
				'<span class="delay">%1$s%2$s</span>',
				$this->messages['delay'],
				$attribution
			);
			// Append CryptoCompare.com attribution
			$html .= sprintf(
				'<tfoot><tr><td colspan="2">%1$s %2$s</td></tr></tfoot>',
				$delay,      // 1
				$coinbase     // 2
			);

			// Close table
			$html .= '</table>';

			// Return rendered HTML
			return $html;

		} // END function shortcode( $atts )

		/**
		 * Get data from cached transient or from live server and cache to transient
		 * @param  string $from From currencies
		 * @param  string $to   To currencies
		 * @return string       JSON value
		 */
		function fetch( $from = '', $to = '' ) {

			// If we don't know from what and to what to convert, escape
			if ( empty( $from ) || empty( $to ) ) {
				return '';
			}

			// Sanitize symbols
			$from = self::sanitize_symbols( $from );
			$to = self::sanitize_symbols( $to );

			// Define transient key
			$transient_key = 'ccticker_a' . md5( "f={$from}_t={$to}" ) . $this->cache_timeout;

			// Get transient if exists and not expired
			if ( false === ( $json = get_transient( $transient_key ) ) ) {

				// Build request URL
				$url = "https://min-api.cryptocompare.com/data/pricemultifull?fsyms={$from}&tsyms={$to}";

				// Do API request
				$wparg = array(
					'timeout' => intval( 10 ),
				);
				$response = wp_remote_get( $url, $wparg );

				// Parse response
				// @TODO: Make compatible with all API responses
				if ( is_wp_error( $response ) ) {
					return $response->get_error_message();
				} else {
					// Get response from body
					$json = wp_remote_retrieve_body( $response );
					// Save response JSON to transient
					set_transient( $transient_key, $json, $this->cache_timeout * MINUTE_IN_SECONDS );
				}
			} // END if ( false === ( $json = get_transient( $transient_key ) ) )

			// Return JSON content
			return $json;
		} // END function fetch( $from, $to )

		/**
		 * Strip from symbols string all except uppercase letters and comma
		 * @param  string $symbols Raw content of symbols string
		 * @return string          Sanitized content of symbols string
		 */
		public static function sanitize_symbols( $symbols ) {
			if ( empty( $symbols ) ) {
				return false;
			}
			return preg_replace( '/[^A-Z0-9\,\*]/', '', $symbols );
		} // END private static function sanitize_symbols( $symbols )

		/**
		 * Sanizite Coinbase Referral ID
		 * @param  string $coinbase_id Raw version of Coinbase referral ID
		 * @return string              Sanitized Coinbase referral ID or empty value
		 */
		public static function sanitize_coinbase_id( $coinbase_id ) {
			// If nothing provided, return empty value
			if ( empty( $coinbase_id ) ) {
				return '';
			}
			// Clean Coinbase referral ID
			$cleaned_id = preg_replace( '/a-z0-9/','', $coinbase_id );
			// Compare cleaned with trimmed value and return empty value if they are not same
			if ( trim( $coinbase_id ) !== $cleaned_id ) {
				return '';
			}
			// Return cleaned Coinbase Referral ID
			return $cleaned_id;
		} // END public static function validate_coinbase_id( $coinbase_id )

		/**
		 * Prepare Coinlist array
		 * @return array Cryptocurrency Coinlist array
		 */
		private static function coinlist() {
			// $coinlist = dirname( __FILE__ ) . '/coinlist.min.json';
			$coinlist = self::$upload_dir_path . '/coinlist.min.json';
			// If no coinlist file exists, call method to create new one
			if ( ! file_exists( $coinlist ) ) {
				self::parse_coinlist();
			}
			// Get content from coinlist file
			$json = file_get_contents( $coinlist );
			// Add decoded data array to $coinlist variable
			self::$coinlist = json_decode( $json, true );
		} // END private static function coinlist()

		static function ajax_get_coinlist() {
			self::get_coinlist();
			self::parse_coinlist();
			$result = array(
				'status'  => 'success',
				'message' => 'New coinlist has been fetched from CryptoCompare.com and prepared for local use. You can view updated coinlist.',
			);
			$result = json_encode( $result );
			echo $result;
			wp_die();
		} // END private static function ajax_get_coinlist()

		/**
		 * Download coinlist from live server and store locally
		 * @TODO: Move file to `res` directory
		 */
		private static function get_coinlist() {
			$url = 'https://min-api.cryptocompare.com/data/all/coinlist';
			$wparg = array(
				'timeout' => intval( 10 ),
			);
			$response = wp_remote_get( $url, $wparg );

			// Parse response
			if ( is_wp_error( $response ) ) {
				return $response->get_error_message();
			} else {
				// Get response from body
				$json = wp_remote_retrieve_body( $response );
				// Write to local file
				// file_put_contents( dirname( __FILE__ ) . '/coinlist.json', $json );
				file_put_contents( self::$upload_dir_path . '/coinlist.json', $json );
			}
		} // END private static function get_coinlist()

		static function ajax_parse_coinlist() {
			self::parse_coinlist();
			$result = array(
				'status'  => 'success',
				'message' => 'Local coinlist has been prepared. You can view it now.',
			);
			$result = json_encode( $result );
			echo $result;
			wp_die();
		} // END private static function ajax_parse_coinlist()

		/**
		 * Parse full coinlist and store only used data to minified coinlist for regular usage
		 * @TODO: Move file to `res` directory
		 */
		private static function parse_coinlist() {
			// $source_coinlist = dirname( __FILE__ ) . '/coinlist.json';
			// $coinlist = dirname( __FILE__ ) . '/coinlist.min.json';
			$source_coinlist = self::$upload_dir_path . '/coinlist.json';
			$coinlist = self::$upload_dir_path . '/coinlist.min.json';

			// Get new coinlist if file does not exists
			if ( ! file_exists( $source_coinlist ) ) {
				self::get_coinlist();
			}
			$json = file_get_contents( $source_coinlist );
			$data = json_decode( $json, true );

			// If response is success, go through Data array
			if ( ! empty( $data['Response'] ) && 'Success' == $data['Response'] ) {
				$new_data = array();
				foreach ( $data['Data'] as $curr => $curr_data ) {
					$new_data[ $curr ] = $curr_data['CoinName'];
					$new_data[ $curr ] = array(
						'u' => $curr_data['Url'],
						'c' => $curr_data['CoinName'],
					);
					// Append image if exists
					if ( ! empty( $curr_data['ImageUrl'] ) ) {
						$new_data[ $curr ]['i'] = $curr_data['ImageUrl'];
					}
				}
				// Pack PHP array to JSON
				$new_json = json_encode( $new_data );
				// Write JSON to local file
				file_put_contents( $coinlist, $new_json );
			} // END if ( ! empty( $data['Response'] ) ...
		} // END private static function parse_coinlist()

		/**
		 * Extract icon URL from local or remote URL as fallback
		 * @param  string $path Cryptocurrency icon media path
		 * @return string       URL from where icon will be pulled on page
		 */
		private static function get_icon_url( $path ) {

			// If no $path provided, return empty value
			if ( empty( $path ) ) {
				return '';
			}

			// Define remote file
			$remote_icon_url = "https://www.cryptocompare.com{$path}";

			// Prepare filename for new image
			$icon_filename = str_replace( '/media/', '', $path );
			$icon_filename = str_replace( '/', '-', $icon_filename );
			// Prepare target path for new image
			$icon_path = self::$upload_dir_path . '/ico/' . $icon_filename;
			$icon_url = self::$upload_dir_url . '/ico/' . $icon_filename;

			// If image file does not exists, download it
			if ( ! file_exists( $icon_path ) ) {

				// Include file.php
				@include_once( ABSPATH . '/wp-admin/includes/file.php' );

				// If download_url() function does not exists after including file, return fallback
				if ( ! function_exists( 'download_url' ) ) {
					return $remote_icon_url;
				}

				// Download remote file
				$icon_tmp = download_url( $remote_icon_url );
				if ( is_wp_error( $icon_tmp ) ) {
					// If WP_Error ocurred, unlink temp file and return fallback
					@unlink( $icon_tmp );
					return $remote_icon_url;
				} else {
					// Copy temp file to final destination
					$ret = copy( $icon_tmp, $icon_path );
					@unlink( $icon_tmp );
					// If file can not be copied, return fallback
					if ( false === $ret ) {
						return $remote_icon_url;
					}
					// Now let we resize big image
					// @ref: https://developer.wordpress.org/reference/functions/wp_get_image_editor/
					$image = wp_get_image_editor( $icon_path );
					if ( ! is_wp_error( $image ) ) {
						// Resize to 20px
						$image->resize( 20, 20 );
						$image->save( $icon_path );
					}
				}
			}
			return $icon_url;
		} // END private static function get_icon_url( $path )

	} // END class Wpau_Cryptocurrency_Ticker
} // END if ( ! class_exists( 'Wpau_Cryptocurrency_Ticker' ) )

// Initialize class
$wpau_cryptocurrency_ticker = Wpau_Cryptocurrency_Ticker::instance();
