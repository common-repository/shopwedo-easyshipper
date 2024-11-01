<?php
/**
 * ShopWeDo centralized logic
 *
 * @link       https://www.shopwedo.com
 * @since      1.1.6
 *
 * @package    shopwedo-easyshipper
 */

// Prevent direct file access
defined('ABSPATH') or exit;
define('LOCATR_PLUGIN_DOMAIN', 'shopwedo-easyshipper');

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

if (
	is_plugin_active( 'woocommerce/woocommerce.php' )
) {

	/**
	 * Add gmap js logic
	 */
	function shopwedo_load_scripts()
	{
		$options = get_option('woocommerce_shopwedo_service_settings');
		wp_enqueue_script('woocommerce-shopwedo-google-api', 'https://maps.googleapis.com/maps/api/js?'.http_build_query(array('key'=>$options['gmaps_api_key'], 'v'=>'3.exp')));
		wp_register_script('woocommerce-shopwedo-map-vendor-googlemaps-markerclusterer', plugins_url( 'assets/vendor/googlemaps/js-marker-clusterer/src/markerclusterer.js', __DIR__ ), array('woocommerce-shopwedo-google-api'), '1.0', true);
		wp_register_script('woocommerce-shopwedo-map-vendor-gmaps', plugins_url( 'assets/vendor/gmaps/gmaps.min.js', __DIR__ ), array('woocommerce-shopwedo-google-api'), '0.4.25', true);
		wp_register_script('woocommerce-shopwedo-map', plugins_url( 'assets/js/woocommerce-shopwedo-map.js', __DIR__ ), array('woocommerce-shopwedo-map-vendor-gmaps'), /*'1.0'*/rand(0, 1337), true);
		wp_add_inline_script('woocommerce-shopwedo-map', 'var shopwedo_plugin_assets_url="'.plugins_url( '../assets/', __FILE__ ).'";', 'before');
	    if ( is_checkout() ) {
	    	if(isset($options['gmaps_marker_clusterer']) && $options['gmaps_marker_clusterer'] == "yes"){
	    		wp_enqueue_script( 'woocommerce-shopwedo-map-vendor-googlemaps-markerclusterer' );	
	    	}
	    	wp_enqueue_script( 'woocommerce-shopwedo-map-vendor-gmaps' );
	    	wp_enqueue_script( 'woocommerce-shopwedo-map' );
	    	wp_enqueue_style('woocommerce-shopwedo-style', plugins_url( 'assets/css/woocommerce-shopwedo.css', __DIR__ ));
		}
	}
	add_action('wp_enqueue_scripts', 'shopwedo_load_scripts');

	/**
	 * Add shipping service
	 */
	function shopwedo_shippingmethod(){
		if ( !class_exists('WC_ShopwedoShippingMethod') ) {
			include plugin_dir_path(__FILE__) . 'shopwedo-shipping-method.php';
		}	
	}
	add_action( 'woocommerce_shipping_init', 'shopwedo_shippingmethod' );

	/**
	 * Register shipping method
	 */
	function add_shopwedo_service( $methods ) {
		$methods['shopwedo_service'] = 'WC_ShopwedoShippingMethod';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_shopwedo_service' );

	// Calculate distance function
	/*function distance($lat1, $lon1, $lat2, $lon2, $unit) {

	  $theta = $lon1 - $lon2;
	  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	  $dist = acos($dist);
	  $dist = rad2deg($dist);
	  $miles = $dist * 60 * 1.1515;
	  $unit = strtoupper($unit);if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {


	  if ($unit == "K") {
	    return ($miles * 1.609344);
	  } else if ($unit == "N") {
	      return ($miles * 0.8684);
	    } else {
	        return $miles;
	      }
	}*/

	/**
	 * Add Shopwedo to services
	 */
	function add_shopwedo_service_table(){
		global $woocommerce;

		$shipping_methods_chosen = $woocommerce->session->get('chosen_shipping_methods');
		$shipping_methods_matched = array_filter($shipping_methods_chosen, function($val){
									    return strpos($val, 'shopwedo_service') === 0;
									});

		if(!empty($shipping_methods_matched)){

			echo '<tr><td colspan="2"><h3>'.__('Select a Pick-up Location', LOCATR_PLUGIN_DOMAIN).'</h3>';
			
			$results = false;

			$qry_address = array();
			if(
				isset($woocommerce->customer)
			) {
				if($qry_street = $woocommerce->customer->get_shipping_address()){
					$qry_address['street'] = $qry_street;
				}
				if($qry_zip = $woocommerce->customer->get_shipping_postcode()){
					$qry_address['zip'] = $qry_zip;
				} elseif($qry_zip = $woocommerce->customer->get_billing_postcode()){
					$qry_address['zip'] = $qry_zip;
				}
				if($qry_country = $woocommerce->customer->get_shipping_country()){
					$qry_address['country'] = $qry_country;
				} elseif($qry_country = $woocommerce->customer->get_billing_country()){
					$qry_address['country'] = $qry_country;
				}
			}
			$qry_address = array_filter($qry_address);

			if(
				!empty($qry_address)
			){
				include plugin_dir_path( __FILE__ ) . 'shopwedo-pudo.php';
				$options = get_option('woocommerce_shopwedo_service_settings');

				$swd_api = new ShopWeDoApi($options['api_shop_id'], $options['api_shop_key']);
				$shopwedo_parcel = new ShopwedoPudo($swd_api);

				$request = $shopwedo_parcel->search(array('address' => $qry_address));
				if(!empty($request['results'])) {
					$results = $request['results'];
				}

				if(WP_DEBUG){
				?>
				<!--<pre style="max-width: 100%;overflow: scroll"><?php var_dump($options); ?></pre>
				<pre style="max-width: 100%;overflow: scroll"><?php var_dump($results); ?></pre>-->
				<?php
				}
				?>
				<div id="shopwedo_service_selected" class="shopwedo_service_current_pickup_location hidden">
					<strong class="carrier"></strong> <strong class="identifier"></strong>
					<address>
						<strong class="name"></strong><br>
						<span class="address-street"></span> <span class="address-number"></span><br>
						<span class="address-zip"></span> <span class="address-city"></span>
					</address>
				</div>
	
				<?php
				if(!empty($results)) {
				?>
				<button type="button" class="button shopwedo_service_button-open_map"><?= __('Choose your Pick-up Location on the map', LOCATR_PLUGIN_DOMAIN) ?></button>
				<button type="button" class="button alt shopwedo_service_button-open_map_other"><?= __('Choose another Pick-up Location', LOCATR_PLUGIN_DOMAIN) ?></button>
				<div class="shopwedo_service_resultset">
					<ul class="shopwedo_service_list">
						<?php
						foreach($results as $index => $location){
							echo '<li class="shopwedo_service_pudo_location" data-index="'.$index.'">'
								.'<button type="button" class="setParcelShop button alt pull-right" data-index="'.$index.'" data-carrier="'.$location['pugoCarrier'].'" data-identifier="'.$location['pugoId'].'">'.__('Choose', LOCATR_PLUGIN_DOMAIN).'</button>'
								.'<p>'
								.'<strong>'.$location['pugoName'].'</strong>'
								.'<br/>'
								//.($location['pugoDistance']*1000).'km away<br />'
								.'<em><small>'.$location['pugoCarrier'].':'.$location['pugoId'].'</em></small><br />'
								//.'<em>'.implode(',', array_keys($location)).'<em><br>'
								//.'<em>'.implode(',', array_keys($location['pugoAddress'])).'<em><br>'
								.'<address>'
									.$location['pugoAddress']['street'].' '.$location['pugoAddress']['number'].'<br>'
									.$location['pugoAddress']['zip'].' '.$location['pugoAddress']['city'].', '.$location['pugoAddress']['countryIso2'].'<br>'
								.'</address>'
								// .'<p>'
								// .$location['pugoLatitude'].' Latitude/'
								// .$location['pugoLongitude'].' Longitude'
								// .'</p>'
								// .'<em>'.implode(',', array_keys($location['pugoHours'])).'<em><br>'
								.'</p>'
							.'</li>';
						}
						?>
					</ul>
				<?php
				} else { 
					echo '<p>'.__('No locations were found, please try another region or query.', LOCATR_PLUGIN_DOMAIN).'</p>';
				}
				?>
				</div>

				<!--<section class="shopwedo_service_reposition">
					<p class="form-row form-row-first">
						<input type="text" class="input-text shopwedo_service_reposition_field" value="<?php echo $woocommerce->customer->get_shipping_postcode(); ?>" placeholder="<?php echo __('Postal Code / ZIP', LOCATR_PLUGIN_DOMAIN); ?>" name="shopwedo_service_reposition_zip">
					</p>
					<p class="form-row form-row-last">
						<button type="button" value="shopwedo_service_reposition" class="button alt" class="shopwedo_service_reposition_button"><?php echo __('Change location', LOCATR_PLUGIN_DOMAIN); ?></button>
					</p>
				</section>-->

				<!--<div id="shopwedo_service_map"></div>-->

				<!-- Hidden fields - Shopwedo Service -->
				<div style="border:2px dashed red;" id="shopwedo_service" class="shopwedo_service_current_data <?=(!WP_DEBUG ? 'hidden' : false)?>">
					<input type="text" name="shopwedo_service[carrier]" placeholder="[carrier]" readonly="readonly">
					<input type="text" name="shopwedo_service[identifier]" placeholder="[identifier]" readonly="readonly">
					<input type="text" name="shopwedo_service[name]" placeholder="[name]" readonly="readonly">
					<input type="text" name="shopwedo_service[address][street]" placeholder="[address][street]" readonly="readonly">
					<input type="text" name="shopwedo_service[address][number]" placeholder="[address][number]" readonly="readonly">
					<input type="text" name="shopwedo_service[address][zip]" placeholder="[address][zip]" readonly="readonly">
					<input type="text" name="shopwedo_service[address][city]" placeholder="[address][city]" readonly="readonly">
					<input type="text" name="shopwedo_service[address][country]" placeholder="[address][country]" readonly="readonly">
				</div>
				<!-- / -->

				<script>
					<?php
					$shopwedo_map_center = array(
						'lat' => (!empty($options['shopwedo_map_center_lat']) && is_numeric($options['shopwedo_map_center_lat'])? $options['shopwedo_map_center_lat'] : '0'),
						'lng' => (!empty($options['shopwedo_map_center_lng']) && is_numeric($options['shopwedo_map_center_lng'])? $options['shopwedo_map_center_lng'] : '0'),
					);
					echo 'var shopwedo_map_center = ' . json_encode($shopwedo_map_center) . ';'.PHP_EOL;
					echo 'var shopwedo_map_zoom = ' . (!empty($options['shopwedo_map_zoom']) && is_numeric($options['shopwedo_map_zoom']) ? abs($options['shopwedo_map_zoom']) : '10' ) . ';'.PHP_EOL;

					echo 'var shopwedo_map_styles = ' . (!empty($options['shopwedo_map_styles'])? json_encode(json_decode($options['shopwedo_map_styles'])) : 'null' ) . ';'.PHP_EOL;
					echo 'var shopwedo_map_results = ' . (!empty($results)? json_encode($results) : '[]' ) . ';'.PHP_EOL;
					//if($shopwedo_plugin_assets_url = plugins_url( '../assets/', __FILE__ )){ echo 'var shopwedo_plugin_assets_url="'.plugins_url( '../assets/', __FILE__ ).'";'; }
					?>
				</script>

			<?php
			} else {
				echo '<div class="woocommerce-error small"><p>'.__('Please enter your address before selecting a Pick-up Location.', LOCATR_PLUGIN_DOMAIN).'</p></div>';
				// might need to check for address change
			}
			echo '</td></tr>';
		}

		/*echo '<script>';
		if($shopwedo_plugin_assets_url = plugins_url( '../assets/', __FILE__ )){ echo 'var shopwedo_plugin_assets_url="'.$shopwedo_plugin_assets_url.'";'; }
		echo '</script>';*/
	}
	add_action('woocommerce_review_order_after_order_total', 'add_shopwedo_service_table');

	/**
	 * Form submit and validation
	 */
	function shopwedo_service_save_shopwedo_parcel_shop( $order_id ) {
		global $woocommerce;
		if (!empty( $_POST['shopwedo_service'] )) {
			update_post_meta( $order_id, 'shopwedo_service', json_encode(wc_clean($_POST['shopwedo_service'])) ); // full shopwedo_service object, in json format
	    }
	}
	add_action('woocommerce_checkout_update_order_meta', 'shopwedo_service_save_shopwedo_parcel_shop' );

	/**
	 * add button to generate label @ Shopwedo/ShopWeDo
	 */
	function shopwedo_get_label_button_order_detail($postId) {
		$current_order = new WC_Order( $postId );
		$options = get_option('woocommerce_shopwedo_service_settings');
		if(
			!empty($options['integration_instance_id'])
			&& _shopwedo_isValidInstanceId($options['integration_instance_id'])
			&& $instanceId = $options['integration_instance_id']
		){
			$parameters = array(
				'ext' => 'instance',
				'instance' => $instanceId,
				'order' => $current_order->id,
			);
			$generateLabelUrl = 'https://admin.shopwedo.com/shipper/add?'. http_build_query($parameters);
			echo '<li class="wide">'
					. '<a class="button black" name="order" type="button" href="'.$generateLabelUrl.'" target="_blank" style="display: block; text-align: left;">'
						. '<span class="dashicons dashicons-media-default"></span>'
						. __( 'Generate Label at ShopWeDo', LOCATR_PLUGIN_DOMAIN )
					. '</a>'
				. '</li>';
		}
	}
	add_action('woocommerce_order_actions_end', 'shopwedo_get_label_button_order_detail');

	/**
	 * Helper function: is valid Shopwedo/ShopWeDo Instance ID
	 */
	if(!function_exists('_shopwedo_isValidInstanceId')){
		function _shopwedo_isValidInstanceId($instanceId=false) {
			return (!empty($instanceId) && preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $instanceId)); // 
		}
	}
	
	/**
	 * Validates if a Parcelshop has been selected
	 */
	function shopwedo_service_parcel_shop_validation() {
		global $woocommerce; 

		// If chosen for Shopwedo Service and parcel id is not found in hidden field or radio button, we show an error
		$shipping_methods_chosen = !empty($_POST['shipping_method']) ? $_POST['shipping_method'] : array();
		$shipping_methods_matched = array_filter($shipping_methods_chosen, function($val){
									    return strpos($val, 'shopwedo_service') === 0;
									});
		if(
			!empty($shipping_methods_matched)
			&& (
				empty($_POST['shopwedo_service'])
				|| empty($_POST['shopwedo_service']['carrier'])
				|| !is_string($_POST['shopwedo_service']['carrier'])
				|| empty($_POST['shopwedo_service']['identifier'])
				|| !is_string($_POST['shopwedo_service']['identifier'])
			)
		){
			wc_add_notice( __( 'Please choose a Pick-up location.', LOCATR_PLUGIN_DOMAIN ), 'error' );
		}
	}
	add_action('woocommerce_checkout_process', 'shopwedo_service_parcel_shop_validation');

	/**
	 * Add chosen Parcel Shop to Order Details - frontend
	 */
	function shopwedo_service_show_id_on_order_details($post){
		$shopwedo_service_meta = get_post_meta($post->id, 'shopwedo_service', true);
		$shopwedo_service = json_decode($shopwedo_service_meta, true);
		?>
			<h2><?php echo __('Chosen Pick-up Location', LOCATR_PLUGIN_DOMAIN);?></h2>
			<address><strong><?=$shopwedo_service['name']?></strong><br />
			<?=$shopwedo_service['address']['street'].' '.$shopwedo_service['address']['number']?><br />
			<?=$shopwedo_service['address']['zip'].' '.$shopwedo_service['address']['city'].', '.$shopwedo_service['address']['country']?></address>
			<p><small><em class="shopwedo_service_carrier"><?=strtoupper($shopwedo_service['carrier']).'</em>:<em class="shopwedo_service_identifier">'.$shopwedo_service['identifier']?></em></small></p>
		<?php
	}
	add_action('woocommerce_order_details_after_order_table', 'shopwedo_service_show_id_on_order_details');

	/**
	 * Add shopwedo_service details from order into admin overview
	 */
	function shopwedo_service_add_shipping_details_admin(){
		global $post;
		$current_order = new WC_Order( $post->ID );

		$shipping_method = $current_order->get_items('shipping'); // 2be debugged
		foreach( $shipping_method as $el ){
			$shipping_id = $el['method_id'];
		}
		$shipping_method = reset(explode(':', $shipping_id));

		if( strpos($shipping_method, 'shopwedo_service') === 0 ){
			if($shopwedo_service_meta = get_post_meta($current_order->id, 'shopwedo_service', true)) {
				if($shopwedo_service = json_decode($shopwedo_service_meta, true)){
					?>
					<div class="pickup_address">
						<h4><?=__('Chosen Pick-up Location', LOCATR_PLUGIN_DOMAIN);?></h4>
						<em><?=$shopwedo_service['carrier'].':'.$shopwedo_service['identifier']?></em>
						<p><strong><?=$shopwedo_service['name']?></strong><br/>
						<?=$shopwedo_service['address']['street'].' '.$shopwedo_service['address']['number']?><br />
						<?=$shopwedo_service['address']['zip'].' '.$shopwedo_service['address']['city'].', '.$shopwedo_service['address']['country']?></p>
					</div>
					<style>
					.order_data_column:last-child .address > * {
					    /*display: none;
					    opacity: .7;*/
					    color: rgba(0,0,0,.4)!important;
					}
					.order_data_column:last-child:hover .address > * {
					    /*display: none;
					    opacity: .7;*/
					    color: rgba(0,0,0,1)!important;
					}
					</style>
					<?php
				}
			}
		
		}
	}
	add_action('woocommerce_admin_order_data_after_shipping_address', 'shopwedo_service_add_shipping_details_admin');

	/**
	 * Add shipping details of the shopwedo_service to email transactions
	 */
	function shopwedo_service_add_shipping_details_email( $order, $is_admin ){
		//global $post;
		$current_order = new WC_Order( $order->id );

		$shipping_method = $current_order->get_items('shipping');
		foreach( $shipping_method as $el ){
			$shipping_id = $el['method_id'];
		}
		$shipping_method = $shipping_id;

		if( $shipping_method == 'shopwedo_service' ){
			$shopwedo_service_meta = get_post_meta($post->id, 'shopwedo_service', true);
			$shopwedo_service = json_decode($shopwedo_service_meta, true);
		?>
		<h2><?=__('Chosen Pick-up Location', LOCATR_PLUGIN_DOMAIN);?></h2>
		<p><strong><?=$shopwedo_service['name']?></strong> (<?=$shopwedo_service['carrier'].':'.$shopwedo_service['identifier']?>)<br />
		<?=$shopwedo_service['address']['street'].' '.$shopwedo_service['address']['number']?><br />
		<?=$shopwedo_service['address']['zip'].' '.$shopwedo_service['address']['city'].', '.$shopwedo_service['address']['country']?></p>
		<?php
		}
	}
	add_action('woocommerce_email_after_order_table', 'shopwedo_service_add_shipping_details_email', 10, 2);

	/**
	 * Download page for 
	 */
	/*function shopwedo_service_add_shipment_label_download_page(){
		add_submenu_page(null, 'shopwedo_download_shipment_label', 'shopwedo_download_shipment_label', 'read', 'shopwedo_download_shipment_label');
	}
	add_action('admin_menu', 'shopwedo_service_add_shipment_label_download_page');
	if( isset($_GET['page']) && $_GET['page'] == 'shopwedo_download_shipment_label' ){
		include 'woocommerce-shopwedo-download-shipment-label.php';
	}*/

	/**
	 * Hide shipping address on parcel emails
	 */
	function remove_shipping_address_in_email( $shipping_methods ){
		$shipping_methods[] = 'shopwedo_service';
		return $shipping_methods;
	}           
	add_filter('woocommerce_order_hide_shipping_address', 'remove_shipping_address_in_email');

	/**
	 * Change address when parcel method
	 */
	function change_address_parcel_method($address, $order){
		// YES! Let's change output address
		if($shopwedo_service = get_post_meta($order->id, 'shopwedo_service', true)){
			if($shopwedo_service_fields = json_decode($shopwedo_service, true)) {
				return array(
					// 'first_name'    => false,//$order->get_billing_first_name(),
					// 'last_name'     => false,//$order->get_billing_last_name(),
					'company'       => $shopwedo_service_fields['name'],
					'address_1'     => $shopwedo_service_fields['address']['street'],
					'address_2'     => $shopwedo_service_fields['address']['number'],
					'city'          => $shopwedo_service_fields['address']['city'],
					'state'         => false,
					'postcode'      => $shopwedo_service_fields['address']['zip'],
					'country'       => $shopwedo_service_fields['address']['country']
				);
			}
		}
		return $address;
	}
	add_filter('woocommerce_order_formatted_shipping_address', 'change_address_parcel_method', 10, 2);

	/**
	 * add custom shopwedo_service field to order's API output
	 */
	function shopwedo_service_add_to_api_fields() {
		register_rest_field('shop_order', 'shopwedo_service', array(
           'get_callback'    => 'get_post_custom_for_api',
           'schema'          => null,
        ));
	}
	add_action('rest_api_init', 'shopwedo_service_add_to_api_fields' );
	function get_post_custom_for_api( $object ) {
	    $post_id = $object['id'];
	    $custom_fields = get_post_custom( $post_id );
	    if(!empty($custom_fields['shopwedo_service']) && count($custom_fields['shopwedo_service'])){
	    	return json_decode(reset($custom_fields['shopwedo_service']),true);
	    }
	}

} else {
	/**
	 * WooCommerce DEPENDENCY notice
	 */
	function shopwedo_depends_admin_notice__error() {
		$class = 'notice notice-error is-dismissible';
		$message = __( 'ShopWeDo plugin depends on <strong>WooCommerce</strong>, please install or activate.', LOCATR_PLUGIN_DOMAIN);
		printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
	}
	add_action('admin_notices', 'shopwedo_depends_admin_notice__error');
}