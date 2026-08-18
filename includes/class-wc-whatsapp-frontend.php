<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_WhatsApp_Frontend {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'add_whatsapp_button_on_product' ) );
		add_shortcode( 'wa_catalog', array( $this, 'render_catalog_shortcode' ) );
		add_action( 'wp_footer', array( $this, 'add_floating_button' ) );
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'wcws-frontend-css', WCWS_PLUGIN_URL . 'assets/css/frontend.css', array(), WCWS_VERSION );
		wp_enqueue_script( 'wcws-frontend-js', WCWS_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), WCWS_VERSION, true );
	}

	public function add_whatsapp_button_on_product() {
		global $product;
		if ( ! $product ) {
			return;
		}
		$settings = get_option( 'wcws_settings_catalog', array() );
		$button_text = isset( $settings['button_text'] ) ? $settings['button_text'] : __( 'Order via WhatsApp', 'wc-whatsapp-suite' );
		$product_name = $product->get_name();
		$product_url = get_permalink( $product->get_id() );
		$whatsapp_url = $this->build_whatsapp_link( sprintf( __( 'Hi! I am interested in %s (%s)', 'wc-whatsapp-suite' ), $product_name, $product_url ) );

		echo '<a href="' . esc_url( $whatsapp_url ) . '" class="button wcws-whatsapp-button" target="_blank" rel="noopener noreferrer">';
		echo '<span class="wcws-whatsapp-icon"></span> ' . esc_html( $button_text );
		echo '</a>';
	}

	public function render_catalog_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'limit'    => -1,
			'columns'  => isset( get_option( 'wcws_settings_catalog', array() )['columns'] ) ? get_option( 'wcws_settings_catalog', array() )['columns'] : 3,
			'category' => '',
		), $atts, 'wa_catalog' );

		$args = array(
			'limit'   => $atts['limit'],
			'status'  => 'publish',
			'orderby' => 'date',
			'order'   => 'DESC',
		);
		if ( ! empty( $atts['category'] ) ) {
			$args['category'] = array( $atts['category'] );
		}

		$products = wc_get_products( $args );
		if ( empty( $products ) ) {
			return '<p>' . esc_html__( 'No products found.', 'wc-whatsapp-suite' ) . '</p>';
		}

		$settings = get_option( 'wcws_settings_catalog', array() );
		$button_text = isset( $settings['button_text'] ) ? $settings['button_text'] : __( 'Order via WhatsApp', 'wc-whatsapp-suite' );
		$columns = absint( $atts['columns'] );
		if ( ! in_array( $columns, array( 2, 3, 4 ) ) ) {
			$columns = 3;
		}

		ob_start();
		?>
		<ul class="wcws-catalog products columns-<?php echo esc_attr( $columns ); ?>">
			<?php foreach ( $products as $product ) : ?>
				<li class="wcws-catalog-item product">
					<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" class="woocommerce-LoopProduct-link">
						<?php echo $product->get_image(); ?>
						<h2 class="woocommerce-loop-product__title"><?php echo esc_html( $product->get_name() ); ?></h2>
						<span class="price"><?php echo $product->get_price_html(); ?></span>
					</a>
					<?php
					$whatsapp_url = $this->build_whatsapp_link( sprintf( __( 'Hi! I am interested in %s (%s)', 'wc-whatsapp-suite' ), $product->get_name(), get_permalink( $product->get_id() ) ) );
					?>
					<a href="<?php echo esc_url( $whatsapp_url ); ?>" class="button wcws-whatsapp-button" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $button_text ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		return ob_get_clean();
	}

	public function add_floating_button() {
		$general = get_option( 'wcws_settings_general', array() );
		$enabled = isset( $general['floating_enabled'] ) ? $general['floating_enabled'] : 'no';
		if ( 'yes' !== $enabled ) {
			return;
		}
		$default_number = isset( $general['whatsapp_number'] ) ? $general['whatsapp_number'] : '';
		if ( empty( $default_number ) ) {
			return;
		}
		$message = __( 'Hi! I have a question.', 'wc-whatsapp-suite' );
		$url = 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $default_number ) . '?text=' . rawurlencode( $message );
		echo '<a href="' . esc_url( $url ) . '" class="wcws-floating-button" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-whatsapp"></span></a>';
	}

	private function build_whatsapp_link( $message ) {
		$general = get_option( 'wcws_settings_general', array() );
		$number = isset( $general['whatsapp_number'] ) ? $general['whatsapp_number'] : '';
		if ( empty( $number ) ) {
			$number = '0000000000';
		}
		$clean = preg_replace( '/[^0-9]/', '', $number );
		return 'https://wa.me/' . $clean . '?text=' . rawurlencode( $message );
	}
}
