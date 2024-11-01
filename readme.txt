=== ShopWeDo e-fulfilment ===
Contributors: shopwedo
Tags: woocommerce,carriers,delivery,service point,shipping,webshop,pickup,fulfilment,fulfillment,inventory
Requires at least: 4.5.x
Tested up to: 5.2.2
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This official ShopWeDo plugin connects your WooCommerce store to the ShopWeDo fulfilment platform so we can pick, pack and ship your orders.
It also adds a wide range of shipping carriers and methods to your store.

This plugin is free to use and offers the following features:
* Connect your store to the ShopWeDo fulfilment platform.
* Sync ShopWeDo stock with WooCommerce automatically.
* Sync WooCommerce orders automatically to the ShopWeDo WMS for fulfillment.
* Sync shipping information (e.g. track and trace) and stock updates back to your WooCommerce store once an order is fulfilled.
* On top of home delivery we integrate thousands of european pickup points in your stores checkout page (Bpost, Postnl, DPD, DHL, GLS, ...)

== Installation ==
Dependency: WooCommerce 2.6 & up

1. Upload & Install the plugin to the wp-content/plugins directory (or via the Admin panel)
2. Activate plugin through the Plugins screen in the WordPress Admin panel
3. Navigate to the WooCommerce->Settings->Shipping
4. Open tab \"Shipping Methods\"
5. Open \"Pick-up locations (ShopWeDo)\" from the submenu
6. Click \"enable\"

- Shipping Methods Name:
This is the name being shown as a Shipping Method in the checkout

- Shop ID:
Your ShopWeDo Shop ID (see FAQ)

- Shop API Key:
Your ShopWeDo API Key (see FAQ)

- Integration Instance Identifier:
This is an identifier of an existing App Instance in the ShopWeDo back-end to identify the connection being used.
eg: XXX-XXX-XXX-XXX-XXX

- Google Maps API-key:
Needed to show the interactive map

- Base cost:
This is the cost for the Shipping Method, and is used as a fallback value when a country has no explicit value set.

- Free Shipping:
This is the minimum checkout-amount when this Shipping Method becomes free.
(0 or empty = free shipping functionality disabled)

- Countries:
Configure tariffs per country, enabling the Shipping Method for those countries.

- Map Marker Clustering
This function enabled a clustering of pick-up locations that are close to each other.

- Map Center Latitude (Fallback) & Map Center Longitude (Fallback)
These are fallback values, to automatically center the Google Map when no coordinates are known.
e.g. Belgium: 51 Lat, 4 Long  / Netherlands: 52 Lat, 5 Long

- Map Zoom Level
This is the default zoom level for the Google Map. It\'s advised to use a value between 10 and 15.

- Google Map Styling
A JSON-object/Javascript Object representing the Google Map Styling. (See FAQ)

= Confirming a good installation =
A customer should be able to select the \"Pick-up Locations\" Shipping Method at checkout, as well as a specific pick-up location.

== Frequently Asked Questions ==
= Which carriers are available in the parcelshop integration? =
The ShopWeDo platform will provide parcelshops from the following carriers: Bpost, DHL Parcel, DPD, Kariboo

= Does this plugin work with other platforms, other than ShopWeDo? =
No, this plug-in isn\'t made to be compatible with other plugins or integrations, other than to be used by customers of ShopWeDo e-fulfilment.

= Where can I find my ShopWeDo API credentials? =
Please contact support@shopwedo.com for your API credentials, to receive your Shop ID and API Key.

= What is a Pick-up Location? =
These are service locations that accept packages to be retrieved by the customer at a later date. e.g. A local newspaper store

= Where can I find my Google Maps Javascript API key? =
You can find all information regarding this at the [Google Maps Platform Console](https://developers.google.com/maps/documentation/javascript/get-api-key)

= Where to generate a style for the Google Map? =
You can generate a JSON-object with your styles over at [Snazzy Maps](https://snazzymaps.com/)

== Screenshots ==
1. Plugin related configuration
2. Settings for the checkout integration of the "Pick-up Location" Shipping Method

== Changelog ==
= 1.2.0 =
- Added Shipping Zones support

= 1.1.7.1 =
- Retrieve CURL with wp_remote_* functions

= 1.1.7 =
- Fixed checkout form validation where customers could get sent to the payment-page without selecting an actual pick-up location.
- Clean-up & restructured Javascript for checkout. WooCommerce depecrated function(s) for customer data updated.

= 1.1.6 =
Branding changes, release to Wordpress Plugin directory
Changed the \"sendr_service\" key to \"shopwedo_service\"

= 1.1.5 =
Hotfix: Removed some aesthetic and functional errors regarding old \"Sendr\"-notices.

= 1.1.4 =
Hotfix: A platform-update resulted in a move of our API endpoints.

= 1.1.3 =
Bugfix: Infinite page-loading loop after finishing a payment. (Javascript error)

= 1.1.1 =
- New integration for ShopWeDo App Instances
- Added a \"sendr_service\" custom field to an WC Order\'s API output

== Upgrade Notice ==
= 1.1.4 =
API endpoint got updated, please update before Jan 1, 2019