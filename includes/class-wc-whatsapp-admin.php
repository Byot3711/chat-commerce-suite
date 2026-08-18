<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_WhatsApp_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'WhatsApp Suite', 'wc-whatsapp-suite' ),
			__( 'WhatsApp Suite', 'wc-whatsapp-suite' ),
			'manage_woocommerce',
			'wc-whatsapp-suite',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'wcws_settings_group_general', 'wcws_settings_general', array( $this, 'sanitize_general' ) );
		register_setting( 'wcws_settings_group_abandoned', 'wcws_settings_abandoned', array( $this, 'sanitize_abandoned' ) );
		register_setting( 'wcws_settings_group_notifications', 'wcws_settings_notifications', array( $this, 'sanitize_notifications' ) );
		register_setting( 'wcws_settings_group_catalog', 'wcws_settings_catalog', array( $this, 'sanitize_catalog' ) );
		register_setting( 'wcws_settings_group_templates', 'wcws_settings_templates', array( $this, 'sanitize_templates' ) );
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'woocommerce_page_wc-whatsapp-suite' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wcws-admin-css', WCWS_PLUGIN_URL . 'assets/css/admin.css', array(), WCWS_VERSION );
		wp_enqueue_script( 'wcws-admin-js', WCWS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), WCWS_VERSION, true );
	}

	public function render_settings_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		?>
		<div class="wrap wcws-wrap">
			<h1><?php esc_html_e( 'WhatsApp Commerce Suite', 'wc-whatsapp-suite' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="?page=wc-whatsapp-suite&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'wc-whatsapp-suite' ); ?></a>
				<a href="?page=wc-whatsapp-suite&tab=templates" class="nav-tab <?php echo $active_tab === 'templates' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Message Templates', 'wc-whatsapp-suite' ); ?></a>
				<a href="?page=wc-whatsapp-suite&tab=abandoned" class="nav-tab <?php echo $active_tab === 'abandoned' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Abandoned Cart', 'wc-whatsapp-suite' ); ?></a>
				<a href="?page=wc-whatsapp-suite&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Order Notifications', 'wc-whatsapp-suite' ); ?></a>
				<a href="?page=wc-whatsapp-suite&tab=catalog" class="nav-tab <?php echo $active_tab === 'catalog' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Catalog', 'wc-whatsapp-suite' ); ?></a>
				<a href="?page=wc-whatsapp-suite&tab=logs" class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Logs', 'wc-whatsapp-suite' ); ?></a>
			</nav>

			<div class="wcws-tab-content">
				<?php
				switch ( $active_tab ) {
					case 'general':
						$this->render_general_tab();
						break;
					case 'templates':
						$this->render_templates_tab();
						break;
					case 'abandoned':
						$this->render_abandoned_tab();
						break;
					case 'notifications':
						$this->render_notifications_tab();
						break;
					case 'catalog':
						$this->render_catalog_tab();
						break;
					case 'logs':
						$this->render_logs_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	private function render_general_tab() {
		$general = get_option( 'wcws_settings_general', array() );
		$phone_number_id = isset( $general['phone_number_id'] ) ? $general['phone_number_id'] : '';
		$access_token = isset( $general['access_token'] ) ? $general['access_token'] : '';
		$verify_token = isset( $general['verify_token'] ) ? $general['verify_token'] : '';
		$api_version = isset( $general['api_version'] ) ? $general['api_version'] : 'v17.0';
		$whatsapp_number = isset( $general['whatsapp_number'] ) ? $general['whatsapp_number'] : '';
		$floating_enabled = isset( $general['floating_enabled'] ) ? $general['floating_enabled'] : 'no';
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'wcws_settings_group_general' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="wcws_phone_number_id"><?php esc_html_e( 'Phone Number ID', 'wc-whatsapp-suite' ); ?></label></th>
					<td><input type="text" id="wcws_phone_number_id" name="wcws_settings_general[phone_number_id]" value="<?php echo esc_attr( $phone_number_id ); ?>" class="regular-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wcws_access_token"><?php esc_html_e( 'Access Token', 'wc-whatsapp-suite' ); ?></label></th>
					<td><input type="text" id="wcws_access_token" name="wcws_settings_general[access_token]" value="<?php echo esc_attr( $access_token ); ?>" class="regular-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wcws_verify_token"><?php esc_html_e( 'Verify Token', 'wc-whatsapp-suite' ); ?></label></th>
					<td>
						<input type="text" id="wcws_verify_token" name="wcws_settings_general[verify_token]" value="<?php echo esc_attr( $verify_token ); ?>" class="regular-text" required />
						<p class="description"><?php esc_html_e( 'Used to verify the webhook URL in Meta Business app.', 'wc-whatsapp-suite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wcws_api_version"><?php esc_html_e( 'API Version', 'wc-whatsapp-suite' ); ?></label></th>
					<td><input type="text" id="wcws_api_version" name="wcws_settings_general[api_version]" value="<?php echo esc_attr( $api_version ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wcws_whatsapp_number"><?php esc_html_e( 'WhatsApp Number (for links)', 'wc-whatsapp-suite' ); ?></label></th>
					<td>
						<input type="text" id="wcws_whatsapp_number" name="wcws_settings_general[whatsapp_number]" value="<?php echo esc_attr( $whatsapp_number ); ?>" class="regular-text" placeholder="40712345678" />
						<p class="description"><?php esc_html_e( 'Enter the phone number with country code, no plus or spaces.', 'wc-whatsapp-suite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Floating Button', 'wc-whatsapp-suite' ); ?></th>
					<td>
						<label><input type="radio" name="wcws_settings_general[floating_enabled]" value="yes" <?php checked( $floating_enabled, 'yes' ); ?>> <?php esc_html_e( 'Enable', 'wc-whatsapp-suite' ); ?></label><br>
						<label><input type="radio" name="wcws_settings_general[floating_enabled]" value="no" <?php checked( $floating_enabled, 'no' ); ?>> <?php esc_html_e( 'Disable', 'wc-whatsapp-suite' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Webhook URL', 'wc-whatsapp-suite' ); ?></th>
					<td>
						<code><?php echo esc_url( rest_url( 'wc-whatsapp-suite/v1/webhook' ) ); ?></code>
						<p class="description"><?php esc_html_e( 'Copy this URL into your Meta Business app under WhatsApp > Configuration > Callback URL.', 'wc-whatsapp-suite' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	private function render_templates_tab() {
		$templates = get_option( 'wcws_settings_templates', array() );
		$default = array(
			'abandoned_cart' => __( 'Hi {first_name}! You left some items in your cart. Complete your order now and get free shipping: {cart_link}', 'wc-whatsapp-suite' ),
			'order_on_hold'  => __( 'Hi {first_name}, your order #{order_number} is on hold. We will contact you shortly.', 'wc-whatsapp-suite' ),
			'order_processing' => __( 'Hi {first_name}, your order #{order_number} is now processing. Thank you!', 'wc-whatsapp-suite' ),
			'order_completed' => __( 'Hi {first_name}, your order #{order_number} has been completed. We hope you enjoy your purchase!', 'wc-whatsapp-suite' ),
			'order_cancelled' => __( 'Hi {first_name}, your order #{order_number} has been cancelled. If you have any questions, please contact us.', 'wc-whatsapp-suite' ),
		);
		$templates = wp_parse_args( $templates, $default );
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'wcws_settings_group_templates' ); ?>
			<table class="form-table">
				<?php foreach ( $templates as $key => $value ) : $label = str_replace( '_', ' ', $key ); ?>
					<tr>
						<th scope="row"><label for="wcws_template_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( ucwords( $label ) ); ?></label></th>
						<td>
							<textarea id="wcws_template_<?php echo esc_attr( $key ); ?>" name="wcws_settings_templates[<?php echo esc_attr( $key ); ?>]" class="large-text" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Available placeholders: {first_name}, {last_name}, {order_number}, {cart_link}, {order_total}', 'wc-whatsapp-suite' ); ?></p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	private function render_abandoned_tab() {
		$abandoned = get_option( 'wcws_settings_abandoned', array() );
		$enabled = isset( $abandoned['enabled'] ) ? $abandoned['enabled'] : 'no';
		$delay = isset( $abandoned['delay_minutes'] ) ? $abandoned['delay_minutes'] : 30;
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'wcws_settings_group_abandoned' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Abandoned Cart', 'wc-whatsapp-suite' ); ?></th>
					<td>
						<label><input type="radio" name="wcws_settings_abandoned[enabled]" value="yes" <?php checked( $enabled, 'yes' ); ?>> <?php esc_html_e( 'Yes', 'wc-whatsapp-suite' ); ?></label>
						<label><input type="radio" name="wcws_settings_abandoned[enabled]" value="no" <?php checked( $enabled, 'no' ); ?>> <?php esc_html_e( 'No', 'wc-whatsapp-suite' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wcws_delay"><?php esc_html_e( 'Delay (minutes)', 'wc-whatsapp-suite' ); ?></label></th>
					<td><input type="number" id="wcws_delay" name="wcws_settings_abandoned[delay_minutes]" value="<?php echo esc_attr( $delay ); ?>" min="5" step="1" class="small-text" /></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	private function render_notifications_tab() {
		$notifications = get_option( 'wcws_settings_notifications', array() );
		$statuses = wc_get_order_statuses();
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'wcws_settings_group_notifications' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Send notifications for these statuses', 'wc-whatsapp-suite' ); ?></th>
					<td>
						<?php foreach ( $statuses as $status => $label ) : $clean_status = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status; ?>
							<label style="display:block; margin-bottom:5px;">
								<input type="checkbox" name="wcws_settings_notifications[statuses][]" value="<?php echo esc_attr( $clean_status ); ?>" <?php checked( isset( $notifications['statuses'] ) && in_array( $clean_status, $notifications['statuses'] ) ); ?> />
								<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	private function render_catalog_tab() {
		$catalog = get_option( 'wcws_settings_catalog', array() );
		$button_text = isset( $catalog['button_text'] ) ? $catalog['button_text'] : __( 'Order via WhatsApp', 'wc-whatsapp-suite' );
		$columns = isset( $catalog['columns'] ) ? $catalog['columns'] : 3;
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'wcws_settings_group_catalog' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="wcws_button_text"><?php esc_html_e( 'Button Text', 'wc-whatsapp-suite' ); ?></label></th>
					<td><input type="text" id="wcws_button_text" name="wcws_settings_catalog[button_text]" value="<?php echo esc_attr( $button_text ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wcws_columns"><?php esc_html_e( 'Catalog Columns', 'wc-whatsapp-suite' ); ?></label></th>
					<td>
						<select id="wcws_columns" name="wcws_settings_catalog[columns]">
							<option value="2" <?php selected( $columns, 2 ); ?>>2</option>
							<option value="3" <?php selected( $columns, 3 ); ?>>3</option>
							<option value="4" <?php selected( $columns, 4 ); ?>>4</option>
						</select>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	private function render_logs_tab() {
		global $wpdb;
		$logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wc_whatsapp_logs ORDER BY id DESC LIMIT 100" );
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'wc-whatsapp-suite' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'wc-whatsapp-suite' ); ?></th>
					<th><?php esc_html_e( 'Message', 'wc-whatsapp-suite' ); ?></th>
					<th><?php esc_html_e( 'Direction', 'wc-whatsapp-suite' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wc-whatsapp-suite' ); ?></th>
					<th><?php esc_html_e( 'Date', 'wc-whatsapp-suite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $logs ) : ?>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td><?php echo esc_html( $log->id ); ?></td>
							<td><?php echo esc_html( $log->phone ); ?></td>
							<td><?php echo esc_html( wp_trim_words( $log->message, 20 ) ); ?></td>
							<td><?php echo esc_html( $log->direction ); ?></td>
							<td><?php echo esc_html( $log->status ); ?></td>
							<td><?php echo esc_html( $log->created_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No logs yet.', 'wc-whatsapp-suite' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	public function sanitize_general( $input ) {
		$sanitized = array();
		$sanitized['phone_number_id'] = sanitize_text_field( $input['phone_number_id'] );
		$sanitized['access_token'] = sanitize_text_field( $input['access_token'] );
		$sanitized['verify_token'] = sanitize_text_field( $input['verify_token'] );
		$sanitized['api_version'] = sanitize_text_field( $input['api_version'] );
		$sanitized['whatsapp_number'] = sanitize_text_field( $input['whatsapp_number'] );
		$sanitized['floating_enabled'] = isset( $input['floating_enabled'] ) && 'yes' === $input['floating_enabled'] ? 'yes' : 'no';
		return $sanitized;
	}

	public function sanitize_abandoned( $input ) {
		$sanitized = array();
		$sanitized['enabled'] = isset( $input['enabled'] ) && 'yes' === $input['enabled'] ? 'yes' : 'no';
		$sanitized['delay_minutes'] = absint( $input['delay_minutes'] );
		return $sanitized;
	}

	public function sanitize_notifications( $input ) {
		$sanitized = array();
		$sanitized['statuses'] = isset( $input['statuses'] ) ? array_map( 'sanitize_key', $input['statuses'] ) : array();
		return $sanitized;
	}

	public function sanitize_catalog( $input ) {
		$sanitized = array();
		$sanitized['button_text'] = sanitize_text_field( $input['button_text'] );
		$sanitized['columns'] = absint( $input['columns'] );
		return $sanitized;
	}

	public function sanitize_templates( $input ) {
		$sanitized = array();
		foreach ( $input as $key => $value ) {
			$sanitized[ sanitize_key( $key ) ] = sanitize_textarea_field( $value );
		}
		return $sanitized;
	}
}
