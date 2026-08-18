<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'wc_whatsapp_logs',
	$wpdb->prefix . 'wc_whatsapp_carts',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

$options = array(
	'wcws_settings_general',
	'wcws_settings_abandoned',
	'wcws_settings_notifications',
	'wcws_settings_catalog',
	'wcws_settings_templates',
	'wcws_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}
