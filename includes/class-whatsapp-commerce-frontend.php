<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WhatsApp_Commerce_Frontend {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'add_whatsapp_button_on_product' ) );
		add_shortcode( 'wa_catalog', array( $this, 'render_catalog_shortcode' ) );
		add_action( 'wp_footer', array( $this, 'add_floating_button' ) );
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'wacs-frontend-css', WACS_PLUGIN_URL . 'assets/css/frontend.css', array(), WACS_VERSION );
		wp_enqueue_script( 'wacs-frontend-js', WACS_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), WACS_VERSION, true );
	}

	public function add_whatsapp_button_on_product() {
		global $product;
		if ( ! $product ) {
			return;
		}
		$settings = get_option( 'wacs_settings_catalog', array() );
		$button_text = isset( $settings['button_text'] ) ? $settings['button_text'] : __( 'Order via WhatsApp', 'whatsapp-commerce-suite' );
		$product_name = $product->get_name();
		$product_url = get_permalink( $product->get_id() );
		$whatsapp_url = $this->build_whatsapp_link( sprintf( __( 'Hi! I am interested in %s (%s)', 'whatsapp-commerce-suite' ), $product_name, $product_url ) );

		echo '<div class="wacs-add-to-cart-section">';
		echo '<h3>' . esc_html__( 'Add to cart', 'whatsapp-commerce-suite' ) . '</h3>';
		echo '<a href="' . esc_url( $whatsapp_url ) . '" class="button wacs-whatsapp-button" target="_blank" rel="noopener noreferrer">';
		echo '<span class="wacs-whatsapp-icon">';
		echo '<svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
		echo '</span> ' . esc_html( $button_text );
		echo '</a>';
		echo '</div>';
	}

	public function render_catalog_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'limit'    => -1,
			'columns'  => isset( get_option( 'wacs_settings_catalog', array() )['columns'] ) ? get_option( 'wacs_settings_catalog', array() )['columns'] : 3,
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
			return '<p>' . esc_html__( 'No products found.', 'whatsapp-commerce-suite' ) . '</p>';
		}

		$settings = get_option( 'wacs_settings_catalog', array() );
		$button_text = isset( $settings['button_text'] ) ? $settings['button_text'] : __( 'Order via WhatsApp', 'whatsapp-commerce-suite' );
		$columns = absint( $atts['columns'] );
		if ( ! in_array( $columns, array( 2, 3, 4 ) ) ) {
			$columns = 3;
		}

		ob_start();
		?>
		<h2 class="wacs-catalog-title"><?php esc_html_e( 'Catalog', 'whatsapp-commerce-suite' ); ?></h2>
		<ul class="wacs-catalog products columns-<?php echo esc_attr( $columns ); ?>">
			<?php foreach ( $products as $product ) : ?>
				<li class="wacs-catalog-item product">
					<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" class="woocommerce-LoopProduct-link">
						<?php echo $product->get_image( 'medium' ); ?>
						<h2 class="woocommerce-loop-product__title"><?php echo esc_html( $product->get_name() ); ?></h2>
						<span class="price"><?php echo $product->get_price_html(); ?></span>
					</a>
					<?php
					$whatsapp_url = $this->build_whatsapp_link( sprintf( __( 'Hi! I am interested in %s (%s)', 'whatsapp-commerce-suite' ), $product->get_name(), get_permalink( $product->get_id() ) ) );
					?>
					<a href="<?php echo esc_url( $whatsapp_url ); ?>" class="button wacs-whatsapp-button" target="_blank" rel="noopener noreferrer">
						<span class="wacs-whatsapp-icon">
							<svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
						</span>
						<?php echo esc_html( $button_text ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		return ob_get_clean();
	}

	public function add_floating_button() {
		$general = get_option( 'wacs_settings_general', array() );
		$enabled = isset( $general['floating_enabled'] ) ? $general['floating_enabled'] : 'no';
		if ( 'yes' !== $enabled ) {
			return;
		}
		$default_number = isset( $general['whatsapp_number'] ) ? $general['whatsapp_number'] : '';
		if ( empty( $default_number ) ) {
			return;
		}
		$message = __( 'Hi! I have a question.', 'whatsapp-commerce-suite' );
		$url = 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $default_number ) . '?text=' . rawurlencode( $message );
		echo '<a href="' . esc_url( $url ) . '" class="wacs-floating-button" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Chat on WhatsApp', 'whatsapp-commerce-suite' ) . '">';
		echo '<svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
		echo '</a>';
	}

	private function build_whatsapp_link( $message ) {
		$general = get_option( 'wacs_settings_general', array() );
		$number = isset( $general['whatsapp_number'] ) ? $general['whatsapp_number'] : '';
		if ( empty( $number ) ) {
			$number = '0000000000';
		}
		$clean = preg_replace( '/[^0-9]/', '', $number );
		return 'https://wa.me/' . $clean . '?text=' . rawurlencode( $message );
	}
}
