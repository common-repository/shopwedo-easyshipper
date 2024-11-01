<?php
/**
 * Adding a shipping method for parcelshops, including the Options panel
 *
 * @link       https://www.shopwedo.com
 * @since      1.1.6
 *
 * @package    shopwedo-easyshipper
 */

// Prevent direct file access
defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'WC_ShopwedoShippingMethod' ) ) {
	class WC_ShopwedoShippingMethod extends WC_Shipping_Method {

		public function __construct($instance_id = 0) {
			$this->id                 	= 'shopwedo_service';
			$this->instance_id          = absint( $instance_id );
			$this->method_title 		= 'Pick-up Locations (ShopWeDo)';
			$this->method_description 	= __('Pick-up Location Finder', LOCATR_PLUGIN_DOMAIN);
			$this->countries 	  		= $this->id.'_countries';
			$this->supports             = array(
				'shipping-zones',
				'settings',
				'instance-settings',
				'instance-settings-modal',
			);
			$this->init();
		}

		function init() {
			// Load the settings API
			$this->_init_settings_form_fields();
			$this->_init_instance_form_fields();
			$this->init_settings(); // This is part of the settings API. Loads settings you previously init.


			/*$this->api_shop_id 				= $this->get_option('api_shop_id');
			$this->api_shop_key 			= $this->get_option('api_shop_key');

			$this->integration_instance_id  = $this->get_option('integration_instance_id');

			// $this->base_cost 				= $this->get_option('base_cost');
			// $this->free_shipping			= $this->get_option('free_shipping');

			$this->gmaps_enabled    		= $this->get_option('gmaps_enabled', false);
			$this->gmaps_api_key    		= $this->get_option('gmaps_api_key');
			$this->gmaps_marker_clusterer	= $this->get_option('gmaps_marker_clusterer');

			$this->shopwedo_map_zoom    		= $this->get_option('shopwedo_map_zoom');
			$this->shopwedo_map_styles  		= $this->get_option('shopwedo_map_styles');
			$this->shopwedo_map_center_lat  	= $this->get_option('shopwedo_map_center_lat');
			$this->shopwedo_map_center_lng  	= $this->get_option('shopwedo_map_center_lng');*/

			$this->enabled					= $this->get_option('enabled');
			$this->title 					= $this->get_option('title', $this->title);

			// Save settings in admin if you have any defined
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			// add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_countries' ) );	
		}

		/* 
		* Custom settings
		*/
		private function _init_settings_form_fields(){
			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable Pick-up Shipping Method', LOCATR_PLUGIN_DOMAIN),
					'type' => 'checkbox',
					'default' => true,
					'label' => __('Enable Shopwedo Pick-up Location Shipping Method', LOCATR_PLUGIN_DOMAIN),
					'disabled' => $this->supports('shipping-zones'),
				),
				'title' => array(
					'title' 		=> __( 'Shipping Method\'s Name', LOCATR_PLUGIN_DOMAIN ),
					'type' 			=> 'text',
					'description' 	=> __( 'This controls the title which the user sees during checkout.', LOCATR_PLUGIN_DOMAIN ),
					'default'		=> __( $this->title, LOCATR_PLUGIN_DOMAIN ),
					'desc_tip'		=> true,
					'placeholder' => 'Pick-up Location',
				),
				'api_shop_id' => array(
					'title' => 'Shopwedo/ShopWeDo Shop ID',
					'type' => 'number',
					'description' => __('This is the Shop ID you got from ShopWeDo to login to their API', LOCATR_PLUGIN_DOMAIN),
					'desc_tip' => true,
					'placeholder' => '',
				),
				'api_shop_key' => array(
					'title' => 'Shopwedo/ShopWeDo Shop\'s API Key',
					'type' => 'text',
					'description' => __('This is the key you got from ShopWeDo to login to their API', LOCATR_PLUGIN_DOMAIN),
					'desc_tip' => true,
					'placeholder' => '',
				),
				'integration_instance_id' => array(
					'title' => __('Integration Instance Identifier', LOCATR_PLUGIN_DOMAIN),
					'type' => 'text',
					'description' => __('This is the integration identifier you got from ShopWeDo after adding WooCommerce as an instance. You can find this ID on the Shopwedo/ShopWeDo back-end. It\'ll be in the form of "XXX-XXX-XXX-XXX". This is needed to show the "Generate Label" button inside an order detail page, otherwise it\'ll fallback on a legacy URL which might not work for you.', LOCATR_PLUGIN_DOMAIN),
					'desc_tip' => true,
					'placeholder' => '________-____-____-____-____________',
				),
				'gmaps_api_key' => array(
					'title' => 'Google Maps API-key',
					'type' => 'text',
					'description' => __('Your Google Maps api key', LOCATR_PLUGIN_DOMAIN),
					'desc_tip' => true,
					'placeholder' => __('Go to Google API Console: https://console.developers.google.com/', LOCATR_PLUGIN_DOMAIN),
				),
				'shopwedo_map_center_lng' => array(
					'title' => __( 'Map Center Longitude (Fallback)', LOCATR_PLUGIN_DOMAIN ),
					'type' => 'text',
					'default' => '',
					// 'step' => 0.00001,
					'description' => __('Map\'s initial longitude point', LOCATR_PLUGIN_DOMAIN),
					'desc_tip' => true,
					'placeholder' => '0.0',
				),
				'shopwedo_map_center_lat' => array(
					'title' => __( 'Map Center Latitude (Fallback)', LOCATR_PLUGIN_DOMAIN ),
					'type' => 'text',
					'default' => '',
					// 'step' => 0.00001,
					'description' => __('Map\'s initial latitude point', LOCATR_PLUGIN_DOMAIN),
					'desc_tip' => true,
					'placeholder' => '0.0',
				),
				'shopwedo_map_zoom' => array(
					'title' => __( 'Map Zoom Level', LOCATR_PLUGIN_DOMAIN ),
					'type' => 'number',
					'default' => 10,
					'min' => 0,
					'max' => 19,
					'description' => __('Map\'s initial zoom level, expected value is from 0 to 19. Recommended value ~ 10.', LOCATR_PLUGIN_DOMAIN),
					'desc_tip' => true
				),
				'shopwedo_map_styles' => array(
					'title' => __( 'Google Map Styles', LOCATR_PLUGIN_DOMAIN ),
					'type' => 'textarea',
					'description' => __('Maps styling in Json-object. Can be obtained from Eg. https://snazzymaps.com/', LOCATR_PLUGIN_DOMAIN),
					'desc_tip' => true
				),
				'gmaps_enabled' => array(
					'title' => __( 'Enable/Disable Google Maps integration', LOCATR_PLUGIN_DOMAIN ),
					'type' => 'checkbox',
					'description' => __('Enable the Google Maps integration with this shipping method', LOCATR_PLUGIN_DOMAIN),
					'default'=>false,
					'desc_tip' => true
				),
				'gmaps_marker_clusterer' => array(
					'title' => __( 'Map Marker clustering', LOCATR_PLUGIN_DOMAIN ),
					'type' => 'checkbox',
					'description' => __('When a lot of Pick-up locations are in the same spot at the current zoom level, you can automatically let it cluster some of the pins.', LOCATR_PLUGIN_DOMAIN),
					'default'=>false,
					'desc_tip' => true
				),
				/*'base_cost' => array(
					'title' 		=> __( 'Base cost', LOCATR_PLUGIN_DOMAIN ),
					'type' 			=> 'text',
					'description' 	=> __( 'This sets the default cost of the Parcel Service when no other price is found.', 'woocommerce' ),
					'default'		=> 5,
					'desc_tip'		=> true,
				),*/
				/*'free_shipping' => array(
					'title' 		=> __( 'Free shipping from', LOCATR_PLUGIN_DOMAIN ),
					'type' 			=> 'text',
					'description' 	=> __( 'Sets an amount that grants free shipping after the cart price equals or is greater than the set price. Empty or 0 means no free shipping.', 'woocommerce' ),
					'default'		=> 0,
					'desc_tip'		=> true,
				),*/
				/*'countries' 	=> array(
					'type' => 'countries'
				)*/
			);
		}

		/**
		 * Instance based settings
		 */
		private function _init_instance_form_fields() {
			
			$this->instance_form_fields = array(
				/*'enabled' => array(
					'title' 		=> __( 'Enable/Disable', LOCATR_PLUGIN_DOMAIN ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'Enable this shipping method', LOCATR_PLUGIN_DOMAIN ),
					'default' 		=> 'yes',
				),*/
				'title' => array(
					'title' 		=> __( 'Method Title', LOCATR_PLUGIN_DOMAIN ),
					'type' 			=> 'text',
					'description' 	=> __( 'This controls the title which the user sees during checkout.', LOCATR_PLUGIN_DOMAIN ),
					'default'		=> __( $this->title, LOCATR_PLUGIN_DOMAIN ),
					'desc_tip'		=> true
				),
				'free_shipping' => array(
					'title' 		=> __( 'Free shipping from', LOCATR_PLUGIN_DOMAIN ),
					'type' 			=> 'text',
					'description' 	=> __( 'Sets an amount that grants free shipping after the cart price equals or is greater than the set price. Empty or 0 means no free shipping.', LOCATR_PLUGIN_DOMAIN ),
					'default'		=> 0,
					'desc_tip'		=> true
				),
				'cost' => array(
					'title' 		=> __( 'Cost', LOCATR_PLUGIN_DOMAIN ),
					'type' 			=> 'text',
					'description' 	=> __( 'This sets the default cost of the Parcel Service when no other price is found.', LOCATR_PLUGIN_DOMAIN ),
					'default'		=> 0,
					'desc_tip'		=> true
				),
			);

		}

		/**
		 * calculate_shipping function.
		 * @param array $package (default: array())
		 */
		public function calculate_shipping( $package = array() ) {
			global $woocommerce;

			$free_shipping = $this->get_instance_option('free_shipping', 0);
			$price = $this->get_instance_option('cost', 0);
			$rateTitle = $this->get_instance_option('title', $this->title);

			// Check if free shipping is enabled
			if( !empty($free_shipping) && is_numeric($free_shipping) ){
				if( $package['contents_cost'] >= $free_shipping ){
					$price = 0;
				}
			}
			if($price < 0) {
				$price = 0;
			}

			$this->add_rate(array(
				'id'    => $this->id.':'.$this->instance_id,
				'label' => $rateTitle,
				'cost'  => $price,
				'taxes' => '',
				'calc_tax' => 'per_order'
			));
		}

		/*public function admin_options() {
		?>
		<h2><?php _e('Shopwedo Service', LOCATR_PLUGIN_DOMAIN); ?></h2>
		<table class="form-table">
		<?php $this->generate_settings_html(); ?>
		</table> 
		<?php
		}*/

		// Save our api url, username & password to options
		/*public function process_api(){
			echo '<pre>',var_dump($_POST),'</pre>';

			// $api_url 		= sanitize_text_field($_POST['api']);
			$shopId 	= sanitize_text_field($_POST['api_shop_id']);
			$shopKey 	= sanitize_text_field($_POST['api_shop_key']);

			// update_option( 'api_url', $api_url );
			update_option( 'api_shop_id', $shopId );
			update_option( 'api_shop_key', $shopKey );
		}*/

		/*public function process_countries(){
			if(!empty($_POST['countries'])){
				$countries = $_POST['countries'];
				$countries_new = array();
				$items = count( $countries['country'] );
				for($i=0;$i<$items;$i++){
					$countries_new[] = array(
						'country' => $countries['country'][$i],
						'country_name' => $countries['country_name'][$i],
						'price' => $countries['price'][$i],
					);
				}
				update_option( $this->countries, $countries_new );
			} else {
				update_option( $this->countries, array() );
			}
		}*/

		/*public function generate_countries_html(){
			global $woocommerce;

			ob_start();
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label><?= __('Countries', LOCATR_PLUGIN_DOMAIN)?></label>
				<td class="forminp">
					<fieldset>
					<?php
				 	// $countries_obj = new WC_Countries();
				  //   $countries = $countries_obj->__get('countries');
				    $countries = $woocommerce->countries->get_allowed_countries();

					$c = get_option('shopwedo_service_countries', false);
//					$base_cost = get_option('shopwedo_service_base_cost', true);
					?>
					<table class="countriesShopwedoService wc-shipping-zone-methods widefat">
						<thead>
							<tr>
								<th class="wc-shipping-zone-method-title"><?php echo __('Country', LOCATR_PLUGIN_DOMAIN); ?></th>
								<th colspan="2" class="wc-shipping-zone-method-description"><?php echo __('Price', LOCATR_PLUGIN_DOMAIN); ?></th>
							</tr>
						</thead>
						<tfoot style="border-top-width: 3px;">
							<tr>
								<td><?php
								woocommerce_form_field('shopwedo_country_select', array(
									    'type'       => 'select',
									    'class'      => array( 'chzn-drop' ),
									    //'label'    => __('Select a country', LOCATR_PLUGIN_DOMAIN),
									    'options'    => $countries,
									    //'required' => true
								    )
							    );
								?></td><td><?php
								echo get_option('woocommerce_currency');
								echo '<input type="number" class="input-text " name="shopwedo_country_cost" id="shopwedo_country_cost" placeholder="Price" value="">';
							 //    woocommerce_form_field('shopwedo_country_cost', array(
								//     	'type' => 'number',
								//     	'placeholder'    => __('Price', LOCATR_PLUGIN_DOMAIN),
								// 	    //'label'    => __('Set a price', LOCATR_PLUGIN_DOMAIN),
								// 	    //'required' => true,
								//     )
								// );
								?></td><td><p class="form-row"><label> </label><button type="button" class="button-primary addCountryButton"><?= __('Add Country', LOCATR_PLUGIN_DOMAIN) ?></button></p></td>
							</tr>
							<tr id="countriesChangedMessage" class="hidden">
								<td colspan="3">
									<div class="warning"><p><?php echo __('Caution: the changes you\'ve made weren\'t saved yet!', LOCATR_PLUGIN_DOMAIN); ?></p></div>
								</td>
							</tr>
						</tfoot>
						<tbody><?php
						if(!empty($c)){
							foreach( $c as $country ){
						?><tr data-country="<?=$country['country']?>">
								<td>
									<?=$country['country_name']?> (<?=$country['country']?>)
									<input type="hidden" value="<?=$country['country']?>" name="countries[country][]">
									<input type="hidden" value="<?=$country['country_name']?>" name="countries[country_name][]">
								</td>
								<td>
									<?php echo get_option('woocommerce_currency'); ?> <input type="text" value="<?=$country['price']?>" name="countries[price][]">
								</td>
								<td>
									<button type="button" class="button-primary deleteCountry" title="<?php echo __('Remove Country', LOCATR_PLUGIN_DOMAIN); ?>">X</button>
								</td>
							</tr><?php
							}
						}
						?></tbody>
					</table>
				</fieldset>
			</td>
		</tr>

		<script>
		jQuery(document).ready(function(){

			var base_cost = jQuery('#woocommerce_shopwedo_service_base_cost').val().replace(',','.');
			jQuery('#shopwedo_country_cost').attr('placeholder', base_cost);
			jQuery('.countriesShopwedoService tbody').find('input[name="countries[price][]"]').attr('placeholder', base_cost);
			jQuery('#woocommerce_shopwedo_service_base_cost').on('change', function(){
				base_cost = jQuery(this).val().replace(',','.');
				jQuery('#shopwedo_country_cost').attr('placeholder', base_cost);
				jQuery('.countriesShopwedoService tbody').find('input[name="countries[price][]"]').attr('placeholder', base_cost);
			})


			jQuery('.addCountryButton').on('click', function(){

				if( lol = jQuery('.countriesShopwedoService tbody').find('tr[data-country="'+jQuery('#shopwedo_country_select').val()+'"]').length ) {
					//console.log(lol);
					alert(jQuery('#shopwedo_country_select option:selected').text() +' ('+jQuery('#shopwedo_country_select').val()+') is already in the list.');
					jQuery('#shopwedo_country_select').focus();

				//} else if( !(jQuery('#shopwedo_country_cost').val().length) ){

				//	alert('Please provide a price.');
				//	jQuery('#shopwedo_country_cost').focus();

				} else {

					jQuery('.countriesShopwedoService tbody').append('<tr data-country="'+jQuery('#shopwedo_country_select').val()+'">\
						<td>'
							+ jQuery('#shopwedo_country_select option:selected').text() +' ('+jQuery('#shopwedo_country_select').val()+')'
							+ '<input type="hidden" value="'+jQuery('#shopwedo_country_select').val()+'" name="countries[country][]">'
							+ '<input type="hidden" value="'+jQuery('#shopwedo_country_select option:selected').text()+'" name="countries[country_name][]">'
						+'</td>\
						<td>'
							//+'&euro; <input type="hidden" value="'+jQuery('#shopwedo_country_cost').val().replace(',','.')+'" name="countries[price][]">'
							+ '<?php echo esc_html(get_option('woocommerce_currency')); ?> ' + '<input type="text" name="countries[price][]" value="'+jQuery('#shopwedo_country_cost').val().replace(',','.')+'" placeholder="'+base_cost+'">'
						+'</td>\
						<td>\
							<button type="button" class="button-primary deleteCountry" title="<?php echo esc_html__('Remove Country', LOCATR_PLUGIN_DOMAIN); ?>">X</button>\
						</td>\
					</tr>');

					jQuery('#shopwedo_country_cost').val();
					jQuery('#countriesChangedMessage').removeClass('hidden');
				}

			});

			jQuery('body').on('click', '.deleteCountry', function(e){
				e.preventDefault();
				jQuery(this).closest('tr').remove();
				jQuery('#countriesChangedMessage').removeClass('hidden');
			});
		});
		</script>
		<?php
			return ob_get_clean();
		}*/
	}
}