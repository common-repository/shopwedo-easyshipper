/**
 * ShopWeDo Pick-up Locations
 * Checkout integration & pick-up locations map
 *
 * @link       https://www.shopwedo.com
 * @since      1.1.8
 *
 * @package    shopwedo-easyshipper
 */

var shopwedo_service_set_class = 'setParcelShop';
var shopwedo_overlay_id = 'shopwedo_service_overlay';

var shopwedo_map_id = 'shopwedo_service_map';
var shopwedo_map = null;

var shopwedo_carrier_pins = {
    'bpost' : shopwedo_plugin_assets_url+'img/map-pins/bpost.png',
    'dpd' : shopwedo_plugin_assets_url+'img/map-pins/dpd.png',
    'postnl' : shopwedo_plugin_assets_url+'img/map-pins/postnl.png',
    'kariboo' : shopwedo_plugin_assets_url+'img/map-pins/kariboo.png',
    'dhl' : shopwedo_plugin_assets_url+'img/map-pins/dhl.png',
    'dhlexpress' : shopwedo_plugin_assets_url+'img/map-pins/dhl.png',
    'dhlparcel' : shopwedo_plugin_assets_url+'img/map-pins/dhl.png',
}

var shopwedo_service_current_index = null;
var shopwedo_service_current_data = null;
var shopwedo_service_checkout_data_container_id = 'shopwedo_service';
var shopwedo_service_checkout_selected_container_id = 'shopwedo_service_selected';
var shopwedo_service_checkout_fields_key = 'shopwedo_service';
var shopwedo_service_list_class = 'shopwedo_service_list';

var shopwedo_plugin_assets_url = shopwedo_plugin_assets_url || null;
var shopwedo_map_results = typeof shopwedo_map_results == 'object' && shopwedo_map_results instanceof Array ? shopwedo_map_results : null;

jQuery(document).ready(function($){

    // Add shopwedo_service_map & overlay, if it doesn't exist yet
    if( $('#'+shopwedo_map_id).length == 0 ){

        /*$searchForm = $('<div/>').append(
                $('<input/>')
                    .addClass('text-input')
                    .attr('placeholder', 'ZIP')
                    .attr('name', 'zipcode')
            );*/

        $shopwedo_map_element = $('<div />').attr('id',shopwedo_map_id);
        if( $('#'+shopwedo_overlay_id).length == 0 ){
            $shopwedo_overlay_element = $('<div />').attr('id', shopwedo_overlay_id).append($shopwedo_map_element);
            // $shopwedo_overlay_element.append($searchForm);
            $('body').prepend($shopwedo_overlay_element);
        } else {
            $('#'+shopwedo_overlay_id).append($shopwedo_map_element);
        }
    }

    $('body')
        .on('click', '.shopwedo_service_button-open_map', function(e){
            e.preventDefault();
            $('#'+shopwedo_overlay_id).show();
            $('#'+shopwedo_map_id).show();
            initShopwedoMap( $('#'+shopwedo_map_id)[0] );
        })
        .on('click', '#'+shopwedo_map_id, function(e){
            e.stopPropagation();
        })
        .on('click', '#'+shopwedo_overlay_id, function(){
            $('#'+shopwedo_overlay_id).hide();
        })
        .on('click', '.'+shopwedo_service_set_class, function(e){
            e.preventDefault();
            shopwedo_service_set($(this).data('index'));
        })
        .on('click', '.shopwedo_service_button-open_map_other', function(){
            shopwedo_service_set();
        });

})

function initShopwedoSettings() {
    if(
        typeof shopwedo_map_center == 'undefined'
        || typeof shopwedo_map_center != 'object'
        || !shopwedo_map_center.hasOwnProperty('lng')
        || !shopwedo_map_center.hasOwnProperty('lat')
    ){
        shopwedo_map_center = {lat:0,lng:0};
    } else {
        if(
            typeof shopwedo_map_center.lat == 'undefined'
            || !shopwedo_map_center.hasOwnProperty('lat')
            || isNaN(shopwedo_map_center.lat)
        ){
            shopwedo_map_center.lat = 0;
        }
        if(
            typeof shopwedo_map_center.lng == 'undefined'
            || !shopwedo_map_center.hasOwnProperty('lng')
            || isNaN(shopwedo_map_center.lng)
        ){
            shopwedo_map_center.lng = 0;
        }
    }
    if(
        typeof shopwedo_map_zoom == 'undefined'
        || isNaN(shopwedo_map_zoom)
    ){
        shopwedo_map_zoom = 10;
    }
}

function initShopwedoMap( map ) {
    //console.log(shopwedo_map_center);
    initShopwedoSettings()
    var settings = {
        div: '#'+shopwedo_map_id,
        lat: shopwedo_map_center.lat,
        lng: shopwedo_map_center.lng,
        zoom: shopwedo_map_zoom,
    };
    //console.log(typeof MarkerClusterer);
    if (typeof MarkerClusterer == 'function'){
        settings.markerClusterer = function(map) {
          clustererOptions = {
            gridSize: 15,
            imagePath: shopwedo_plugin_assets_url+'img/map-clusterer/m'
          }
          return new MarkerClusterer(map, [], clustererOptions);
        };
        if (typeof settings.markerClusterer == 'MarkerClusterer') {
          this.markerClusterer = settings.markerClusterer.apply(this, [this.map]);
        }
    }
    if(typeof shopwedo_map_styles != 'undefined'){
        settings.styles = shopwedo_map_styles;
    }
    shopwedo_map = new GMaps(settings);
    if(
        typeof shopwedo_map_results != 'undefined'
        && shopwedo_map_results.hasOwnProperty('length')
        && shopwedo_map_results.length
    ){
        shopwedo_map_results.forEach(function(marker, i){
            if(i==0) shopwedo_map.setCenter(marker.pugoLatitude, marker.pugoLongitude);
            markerData = _generateMarkerObj(marker, i);
            if(
                markerData
                && markerData.hasOwnProperty('lat')
                && markerData.hasOwnProperty('lng')
            ){
                shopwedo_map.addMarker(markerData);    
            } else {
                console.log('Marker Skipped', marker, i);
            }
            
        });
    }
}

function shopwedo_service_set(index){
    $serviceList = jQuery('.'+shopwedo_service_list_class);
    $selectedContainer = jQuery('#'+shopwedo_service_checkout_selected_container_id);
    if(
        index!=undefined
        && typeof shopwedo_map_results != 'undefined'
        && shopwedo_map_results.hasOwnProperty('length')
        && shopwedo_map_results.length
        && shopwedo_map_results.hasOwnProperty(index)
    ){
        shopwedo_service_current_index = index;
        shopwedo_service_current_data = shopwedo_map_results[index];
        jQuery('.'+shopwedo_service_list_class).find('[data-index]').removeClass('active');
        $newActive = jQuery('.'+shopwedo_service_list_class).find('[data-index="'+index+'"]');
        $newActive.addClass('active');
        jQuery('.'+shopwedo_service_list_class).hide();
        /*(($newActive.outerHeight(true) - $newActive.innerHeight()) / 2)*/
        /*$serviceList.scrollTop($serviceList.position().top + $newActive.position().top);*/
        shopwedo_service_set_formfields(shopwedo_service_current_data);
    } else {
        shopwedo_service_current_index = null;
        shopwedo_service_current_data = null;
        $serviceList.find('[data-index]').removeClass('active');
        jQuery('.'+shopwedo_service_list_class).show();
        shopwedo_service_set_formfields();
    }
    $serviceList.scrollTop();
    jQuery('#'+shopwedo_overlay_id).hide();
}

function shopwedo_service_set_formfields(fields) {
    $dataContainer = jQuery('#'+shopwedo_service_checkout_data_container_id);
    $selectedContainer = jQuery('#'+shopwedo_service_checkout_selected_container_id);
    if(fields != undefined){
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[carrier]"]').val(fields.pugoCarrier);
        $selectedContainer.find('.carrier').text(fields.pugoCarrier);
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[identifier]"]').val(fields.pugoId);
        $selectedContainer.find('.identifier').text(fields.pugoId);
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[name]"]').val(fields.pugoName);
        $selectedContainer.find('.name').text(fields.pugoName);

        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[address][street]"]').val(fields.pugoAddress.street);
        $selectedContainer.find('.address-street').text(fields.pugoAddress.street);
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[address][number]"]').val(fields.pugoAddress.number);
        $selectedContainer.find('.address-number').text(fields.pugoAddress.number);
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[address][city]"]').val(fields.pugoAddress.city);
        $selectedContainer.find('.address-city').text(fields.pugoAddress.city);
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[address][zip]"]').val(fields.pugoAddress.zip);
        $selectedContainer.find('.address-zip').text(fields.pugoAddress.zip);
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[address][country]"]').val(fields.pugoAddress.countryIso2);
        $selectedContainer.find('.address-country').text(fields.pugoAddress.countryIso2);

        $selectedContainer.removeClass('hidden');
        jQuery('.shopwedo_service_button-open_map').hide();
        jQuery('.shopwedo_service_button-open_map_other').show();
    } else {
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[carrier]"]').val('');
        $selectedContainer.find('.carrier').text('');
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[identifier]"]').val('');
        $selectedContainer.find('.identifier').text('');
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[name]"]').val('');
        $selectedContainer.find('.name').text('');

        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[address][street]"]').val('');
        $selectedContainer.find('.address-street').text('');
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[address][number]"]').val('');
        $selectedContainer.find('.address-number').text('');
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[address][city]"]').val('');
        $selectedContainer.find('.address-city').text('');
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[address][zip]"]').val('');
        $selectedContainer.find('.address-zip').text('');
        $dataContainer.find('input[name="'+shopwedo_service_checkout_fields_key+'[address][country]"]').val('');
        $selectedContainer.find('.address-country').text('');

        $selectedContainer.addClass('hidden');
        jQuery('.shopwedo_service_button-open_map').show();
        jQuery('.shopwedo_service_button-open_map_other').hide();
    }
}

function _generateMarkerObj(info, index) {
    console.log('_generateMarkerObj', index || '?index?', info || '?info?');

    if(
        !info.hasOwnProperty('pugoLatitude')
        || !info.hasOwnProperty('pugoLongitude')
    ) {
        console.log('Missing Lat/Long', index, info);
        return false;
    } else {
        var markerContent = '<div class="infoWindow">\
                                <strong>'+info.pugoName+'</strong>\
                                <p><strong>'+info.pugoCarrier+'</strong><br/><small>'+info.pugoId+'</small></p>'
                                + (
                                    info.hasOwnProperty('pugoAddress')
                                    && info.pugoAddress.hasOwnProperty('street')
                                    && info.pugoAddress.hasOwnProperty('number')
                                    && info.pugoAddress.hasOwnProperty('zip')
                                    && info.pugoAddress.hasOwnProperty('countryIso2')
                                    ? '<address>' + info.pugoAddress.street + ' ' + info.pugoAddress.number + '<br/>' + info.pugoAddress.zip + ' ' + info.pugoAddress.city + '<br/>' + info.pugoAddress.countryIso2 + '</address>'
                                    : ''
                                )
                                + '<button class="'+shopwedo_service_set_class+' btn btn-success" type="button" data-index="'+index+'" data-carrier="'+info.pugoCarrier+'" data-identifier="'+info.pugoId+'">Choose</button>\
                            </div>';
        var markerObj = {
          lat: info.pugoLatitude,
          lng: info.pugoLongitude,
          title: info.pugoName + ' ' + info.pugoCarrier + '['+info.pugoId+']',
          infoWindow: {
            content: markerContent
          },
        };
        if(info.pugoId != undefined) {
            var icon_url = false;
            if(shopwedo_carrier_pins.hasOwnProperty(info.pugoCarrier)){
                icon_url = shopwedo_carrier_pins[info.pugoCarrier];
            }
            if(icon_url){
                markerObj.icon = {
                size: new google.maps.Size(32, 32),
                url: icon_url
              };
          }
        }
        return markerObj;

    }

    return false;
}