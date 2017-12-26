<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds Wpau_Cryptocurrency_Ticker_Widget widget.
 */
class Wpau_Cryptocurrency_Ticker_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wpau_cryptocurrency_ticker', // Base ID
			esc_html__( 'Easy CryptoCurrency Ticker', 'cc-ticker' ), // Name
			array(
				'description' => esc_html__( 'Prices of various crypticurrencies like BTC, ETH, XMR, etc', 'cc-ticker' ),
			) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		$wpau_cryptocurrency_ticker = Wpau_Cryptocurrency_Ticker::instance();
		echo $wpau_cryptocurrency_ticker->shortcode( $instance );
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		// Initiate main class instance
		$wpau_cryptocurrency_ticker = Wpau_Cryptocurrency_Ticker::instance();
		// Enqueue admin JS
		wp_register_script(
			'cc-ticker-admin',
			$wpau_cryptocurrency_ticker->plugin_url . ( WP_DEBUG ? 'assets/js/admin.js' : 'assets/js/admin.min.js' ),
			array( 'jquery' ),
			$wpau_cryptocurrency_ticker::VER,
			true
		);
		wp_localize_script(
			'cc-ticker-admin',
			'ccTickerJs',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
		wp_enqueue_script( 'cc-ticker-admin' );

		// Prepare widget form values
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'CryptoCurrency Ticker', 'cc-ticker' );
		$f = ! empty( $instance['f'] ) ? $wpau_cryptocurrency_ticker::sanitize_symbols( $instance['f'] ) : 'BTC,ETH,XMR';
		$t = ! empty( $instance['t'] ) ? $wpau_cryptocurrency_ticker::sanitize_symbols( $instance['t'] ) : 'USD';
		$nolink = ! empty( $instance['nolink'] ) ? true : false;
		$noicon = ! empty( $instance['noicon'] ) ? true : false;
		$showchange = ! empty( $instance['showchange'] ) ? true : false;
		$coinbase = ! empty( $instance['coinbase'] ) ? $wpau_cryptocurrency_ticker::sanitize_coinbase_id( $instance['coinbase'] ) : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'cc-ticker' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'f' ) ); ?>"><?php esc_attr_e( 'From Symbols:', 'cc-ticker' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'f' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'f' ) ); ?>" type="text" value="<?php echo esc_attr( $f ); ?>" title="Separate multiple cryptocurrency symbols with comma">
			<br />
			<label for="<?php echo esc_attr( $this->get_field_id( 't' ) ); ?>"><?php esc_attr_e( 'To Symbols:', 'cc-ticker' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 't' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 't' ) ); ?>" type="text" value="<?php echo esc_attr( $t ); ?>" title="Separate multiple currency symbols with comma">
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $showchange, 1 ); ?> id="<?php echo $this->get_field_id( 'showchange' ); ?>" name="<?php echo $this->get_field_name( 'showchange' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'showchange' ); ?>"><?php _e( 'Show change day amount/percent', 'cc-ticker' ); ?></label>
			<br />
			<input class="checkbox" type="checkbox" <?php checked( $noicon, 1 ); ?> id="<?php echo $this->get_field_id( 'noicon' ); ?>" name="<?php echo $this->get_field_name( 'noicon' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'noicon' ); ?>"><?php _e( 'Do not add coin icons', 'cc-ticker' ); ?></label>
			<br />
			<input class="checkbox" type="checkbox" <?php checked( $nolink, 1 ); ?> id="<?php echo $this->get_field_id( 'nolink' ); ?>" name="<?php echo $this->get_field_name( 'nolink' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'nolink' ); ?>"><?php _e( 'Do not link coin to overview', 'cc-ticker' ); ?></label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'coinbase' ) ); ?>"><?php esc_attr_e( 'Your Coinbase.com Referral ID:', 'cc-ticker' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'coinbase' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'coinbase' ) ); ?>" type="text" value="<?php echo esc_attr( $coinbase ); ?>" title="<?php _e( 'Enter only ID, not whole URL. Example: 5a3578b6abbfc80226e411ec', 'cc-ticker' ); ?>"><br />
		</p>

		<p>
			<label>Coin List:</label><br />
			<button class="button button-secondary" name="cct_get_coinlist">Get Live</button>
			<button class="button button-secondary" name="cct_parse_coinlist">Rebuild Local</button>
			<a class="button button-secondary" href="<?php echo $wpau_cryptocurrency_ticker::$upload_dir_url; ?>/coinlist.min.json" target="_coinlist">View</a>
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$wpau_cryptocurrency_ticker = Wpau_Cryptocurrency_Ticker::instance();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['f'] = ( ! empty( $new_instance['f'] ) ) ? $wpau_cryptocurrency_ticker::sanitize_symbols( $new_instance['f'] ) : '';
		$instance['t'] = ( ! empty( $new_instance['t'] ) ) ? $wpau_cryptocurrency_ticker::sanitize_symbols( $new_instance['t'] ) : '';
		$instance['showchange'] = ( ! empty( $new_instance['showchange'] ) ) ? true : false;
		$instance['noicon'] = ( ! empty( $new_instance['noicon'] ) ) ? true : false;
		$instance['nolink'] = ( ! empty( $new_instance['nolink'] ) ) ? true : false;
		$instance['coinbase'] = ( ! empty( $new_instance['coinbase'] ) ) ? $wpau_cryptocurrency_ticker::sanitize_coinbase_id( $new_instance['coinbase'] ) : '';
		return $instance;
	}

} // class Wpau_Cryptocurrency_Ticker_Widget

// register Wpau_Cryptocurrency_Ticker_Widget widget
function register_wpau_cryptocurrency_ticker_widget() {
	register_widget( 'Wpau_Cryptocurrency_Ticker_Widget' );
}
add_action( 'widgets_init', 'register_wpau_cryptocurrency_ticker_widget' );
