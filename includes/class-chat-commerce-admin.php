<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Chat_Commerce_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Chat Commerce', 'chat-commerce-suite' ),
			__( 'Chat Commerce', 'chat-commerce-suite' ),
			'manage_woocommerce',
			'chat-commerce-suite',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'ccs_settings_group_general', 'ccs_settings_general', array( $this, 'sanitize_general' ) );
		register_setting( 'ccs_settings_group_abandoned', 'ccs_settings_abandoned', array( $this, 'sanitize_abandoned' ) );
		register_setting( 'ccs_settings_group_notifications', 'ccs_settings_notifications', array( $this, 'sanitize_notifications' ) );
		register_setting( 'ccs_settings_group_catalog', 'ccs_settings_catalog', array( $this, 'sanitize_catalog' ) );
		register_setting( 'ccs_settings_group_templates', 'ccs_settings_templates', array( $this, 'sanitize_templates' ) );
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'woocommerce_page_chat-commerce-suite' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'ccs-admin-css', CCS_PLUGIN_URL . 'assets/css/admin.css', array(), CCS_VERSION );
		wp_enqueue_script( 'ccs-admin-js', CCS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), CCS_VERSION, true );
	}

	public function render_settings_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		?>
		<div class="wrap ccs-wrap">
			<h1><?php esc_html_e( 'Chat Commerce Suite', 'chat-commerce-suite' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="?page=chat-commerce-suite&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'chat-commerce-suite' ); ?></a>
				<a href="?page=chat-commerce-suite&tab=templates" class="nav-tab <?php echo $active_tab === 'templates' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Message Templates', 'chat-commerce-suite' ); ?></a>
				<a href="?page=chat-commerce-suite&tab=abandoned" class="nav-tab <?php echo $active_tab === 'abandoned' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Abandoned Cart', 'chat-commerce-suite' ); ?></a>
				<a href="?page=chat-commerce-suite&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Order Notifications', 'chat-commerce-suite' ); ?></a>
				<a href="?page=chat-commerce-suite&tab=catalog" class="nav-tab <?php echo $active_tab === 'catalog' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Catalog', 'chat-commerce-suite' ); ?></a>
				<a href="?page=chat-commerce-suite&tab=logs" class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Logs', 'chat-commerce-suite' ); ?></a>
			</nav>

			<div class="ccs-tab-content">
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
		$general = get_option( 'ccs_settings_general', array() );
		$phone_number_id = isset( $general['phone_number_id'] ) ? $general['phone_number_id'] : '';
		$access_token = isset( $general['access_token'] ) ? $general['access_token'] : '';
		$verify_token = isset( $general['verify_token'] ) ? $general['verify_token'] : '';
		$api_version = isset( $general['api_version'] ) ? $general['api_version'] : 'v17.0';
		$whatsapp_number = isset( $general['whatsapp_number'] ) ? $general['whatsapp_number'] : '';
		$floating_enabled = isset( $general['floating_enabled'] ) ? $general['floating_enabled'] : 'no';
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'ccs_settings_group_general' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="ccs_phone_number_id"><?php esc_html_e( 'Phone Number ID', 'chat-commerce-suite' ); ?></label></th>
					<td><input type="text" id="ccs_phone_number_id" name="ccs_settings_general[phone_number_id]" value="<?php echo esc_attr( $phone_number_id ); ?>" class="regular-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ccs_access_token"><?php esc_html_e( 'Access Token', 'chat-commerce-suite' ); ?></label></th>
					<td><input type="text" id="ccs_access_token" name="ccs_settings_general[access_token]" value="<?php echo esc_attr( $access_token ); ?>" class="regular-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ccs_verify_token"><?php esc_html_e( 'Verify Token', 'chat-commerce-suite' ); ?></label></th>
					<td>
						<input type="text" id="ccs_verify_token" name="ccs_settings_general[verify_token]" value="<?php echo esc_attr( $verify_token ); ?>" class="regular-text" required />
						<p class="description"><?php esc_html_e( 'Used to verify the webhook URL in Meta Business app.', 'chat-commerce-suite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ccs_api_version"><?php esc_html_e( 'API Version', 'chat-commerce-suite' ); ?></label></th>
					<td><input type="text" id="ccs_api_version" name="ccs_settings_general[api_version]" value="<?php echo esc_attr( $api_version ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ccs_whatsapp_number"><?php esc_html_e( 'WhatsApp Number (for links)', 'chat-commerce-suite' ); ?></label></th>
					<td>
						<input type="text" id="ccs_whatsapp_number" name="ccs_settings_general[whatsapp_number]" value="<?php echo esc_attr( $whatsapp_number ); ?>" class="regular-text" placeholder="40712345678" />
						<p class="description"><?php esc_html_e( 'Enter the phone number with country code, no plus or spaces.', 'chat-commerce-suite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Floating Button', 'chat-commerce-suite' ); ?></th>
					<td>
						<label><input type="radio" name="ccs_settings_general[floating_enabled]" value="yes" <?php checked( $floating_enabled, 'yes' ); ?>> <?php esc_html_e( 'Enable', 'chat-commerce-suite' ); ?></label>
						<label><input type="radio" name="ccs_settings_general[floating_enabled]" value="no" <?php checked( $floating_enabled, 'no' ); ?>> <?php esc_html_e( 'Disable', 'chat-commerce-suite' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Webhook URL', 'chat-commerce-suite' ); ?></th>
					<td>
						<code><?php echo esc_url( rest_url( 'chat-commerce-suite/v1/webhook' ) ); ?></code>
						<p class="description"><?php esc_html_e( 'Copy this URL into your Meta Business app under WhatsApp > Configuration > Callback URL.', 'chat-commerce-suite' ); ?></p>
					</td>
				</tr>
			</table>
			<button type="submit" class="submit-button"><?php esc_html_e( 'Save Changes', 'chat-commerce-suite' ); ?></button>
		</form>
		<?php
	}

	private function render_templates_tab() {
		$templates = get_option( 'ccs_settings_templates', array() );
		$default = array(
			'abandoned_cart' => __( 'Hi {first_name}! You left some items in your cart. Complete your order now and get free shipping: {cart_link}', 'chat-commerce-suite' ),
			'order_on_hold'  => __( 'Hi {first_name}, your order #{order_number} is on hold. We will contact you shortly.', 'chat-commerce-suite' ),
			'order_processing' => __( 'Hi {first_name}, your order #{order_number} is now processing. Thank you!', 'chat-commerce-suite' ),
			'order_completed' => __( 'Hi {first_name}, your order #{order_number} has been completed. We hope you enjoy your purchase!', 'chat-commerce-suite' ),
			'order_cancelled' => __( 'Hi {first_name}, your order #{order_number} has been cancelled. If you have any questions, please contact us.', 'chat-commerce-suite' ),
		);
		$templates = wp_parse_args( $templates, $default );
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'ccs_settings_group_templates' ); ?>
			<table class="form-table">
				<?php foreach ( $templates as $key => $value ) : $label = str_replace( '_', ' ', $key ); ?>
					<tr>
						<th scope="row"><label for="ccs_template_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( ucwords( $label ) ); ?></label></th>
						<td>
							<textarea id="ccs_template_<?php echo esc_attr( $key ); ?>" name="ccs_settings_templates[<?php echo esc_attr( $key ); ?>]" class="large-text" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Available placeholders: {first_name}, {last_name}, {order_number}, {cart_link}, {order_total}', 'chat-commerce-suite' ); ?></p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<button type="submit" class="submit-button"><?php esc_html_e( 'Save Changes', 'chat-commerce-suite' ); ?></button>
		</form>
		<?php
	}

	private function render_abandoned_tab() {
		$abandoned = get_option( 'ccs_settings_abandoned', array() );
		$enabled = isset( $abandoned['enabled'] ) ? $abandoned['enabled'] : 'no';
		$delay = isset( $abandoned['delay_minutes'] ) ? $abandoned['delay_minutes'] : 30;
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'ccs_settings_group_abandoned' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Abandoned Cart', 'chat-commerce-suite' ); ?></th>
					<td>
						<label><input type="radio" name="ccs_settings_abandoned[enabled]" value="yes" <?php checked( $enabled, 'yes' ); ?>> <?php esc_html_e( 'Yes', 'chat-commerce-suite' ); ?></label>
						<label><input type="radio" name="ccs_settings_abandoned[enabled]" value="no" <?php checked( $enabled, 'no' ); ?>> <?php esc_html_e( 'No', 'chat-commerce-suite' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ccs_delay"><?php esc_html_e( 'Delay (minutes)', 'chat-commerce-suite' ); ?></label></th>
					<td><input type="number" id="ccs_delay" name="ccs_settings_abandoned[delay_minutes]" value="<?php echo esc_attr( $delay ); ?>" min="5" step="1" class="small-text" /></td>
				</tr>
			</table>
			<button type="submit" class="submit-button"><?php esc_html_e( 'Save Changes', 'chat-commerce-suite' ); ?></button>
		</form>
		<?php
	}

	private function render_notifications_tab() {
		$notifications = get_option( 'ccs_settings_notifications', array() );
		$statuses = wc_get_order_statuses();
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'ccs_settings_group_notifications' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Send notifications for these statuses', 'chat-commerce-suite' ); ?></th>
					<td>
						<div class="ccs-checkbox-list">
							<?php foreach ( $statuses as $status => $label ) : $clean_status = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status; ?>
								<label>
									<input type="checkbox" name="ccs_settings_notifications[statuses][]" value="<?php echo esc_attr( $clean_status ); ?>" <?php checked( isset( $notifications['statuses'] ) && in_array( $clean_status, $notifications['statuses'] ) ); ?> />
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</td>
				</tr>
			</table>
			<button type="submit" class="submit-button"><?php esc_html_e( 'Save Changes', 'chat-commerce-suite' ); ?></button>
		</form>
		<?php
	}

	private function render_catalog_tab() {
		$catalog = get_option( 'ccs_settings_catalog', array() );
		$button_text = isset( $catalog['button_text'] ) ? $catalog['button_text'] : __( 'Order via WhatsApp', 'chat-commerce-suite' );
		$columns = isset( $catalog['columns'] ) ? $catalog['columns'] : 3;
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'ccs_settings_group_catalog' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="ccs_button_text"><?php esc_html_e( 'Button Text', 'chat-commerce-suite' ); ?></label></th>
					<td><input type="text" id="ccs_button_text" name="ccs_settings_catalog[button_text]" value="<?php echo esc_attr( $button_text ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ccs_columns"><?php esc_html_e( 'Catalog Columns', 'chat-commerce-suite' ); ?></label></th>
					<td>
						<select id="ccs_columns" name="ccs_settings_catalog[columns]">
							<option value="2" <?php selected( $columns, 2 ); ?>>2</option>
							<option value="3" <?php selected( $columns, 3 ); ?>>3</option>
							<option value="4" <?php selected( $columns, 4 ); ?>>4</option>
						</select>
					</td>
				</tr>
			</table>
			<button type="submit" class="submit-button"><?php esc_html_e( 'Save Changes', 'chat-commerce-suite' ); ?></button>
		</form>
		<?php
	}

	private function render_logs_tab() {
		global $wpdb;
		$logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ccs_logs ORDER BY id DESC LIMIT 100" );
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'chat-commerce-suite' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'chat-commerce-suite' ); ?></th>
					<th><?php esc_html_e( 'Message', 'chat-commerce-suite' ); ?></th>
					<th><?php esc_html_e( 'Direction', 'chat-commerce-suite' ); ?></th>
					<th><?php esc_html_e( 'Status', 'chat-commerce-suite' ); ?></th>
					<th><?php esc_html_e( 'Date', 'chat-commerce-suite' ); ?></th>
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
							<td><span class="status-<?php echo esc_attr( $log->status ); ?>"><?php echo esc_html( $log->status ); ?></span></td>
							<td><?php echo esc_html( $log->created_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No logs yet.', 'chat-commerce-suite' ); ?></td></tr>
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
