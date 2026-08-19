=== Chat Commerce Suite ===
Contributors: byot
Tags: whatsapp, woocommerce, chat, order notifications, abandoned cart
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turn your WooCommerce store into a WhatsApp sales channel with catalog, cart, abandoned cart recovery, and order notifications.

== Description ==

Chat Commerce Suite integrates your WooCommerce store with the WhatsApp Business Cloud API. It adds:

* WhatsApp order button on product pages.
* Shortcode for a product catalog with WhatsApp ordering.
* Automated abandoned cart recovery messages.
* Order status notifications sent directly to customers.
* A webhook to receive and respond to customer messages with simple rule-based commands.
* Clean, professional admin interface.

== Installation ==

1. Upload the `chat-commerce-suite` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to WooCommerce > Chat Commerce and enter your WhatsApp Business Cloud API credentials.
4. Configure the webhook URL in your Meta Business app.

== Frequently Asked Questions ==

= Where do I get WhatsApp Business Cloud API credentials? =

Create a Meta Business app, add the WhatsApp product, and obtain a Phone Number ID and an access token from the dashboard.

= How do I set up the webhook? =

In your Meta app, go to WhatsApp > Configuration, set the Callback URL to `https://yoursite.com/wp-json/chat-commerce-suite/v1/webhook` and the Verify Token to the value you set in the plugin settings.

== Changelog ==

= 1.0.0 =
* Initial release.
