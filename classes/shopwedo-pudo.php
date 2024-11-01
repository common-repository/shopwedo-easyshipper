<?php
/**
 * PUDO/Pick-up Locations API
 * 
 * @link       https://www.shopwedo.com
 * @since      1.1.6
 *
 * @package    shopwedo-easyshipper
 */

// Prevent direct file access
defined( 'ABSPATH' ) or exit;

// include_once 'lib/general/cache.php';
// include_once 'lib/general/logger.php';
include_once 'lib/ShopWeDo/shopwedo-api.php'; // ShopWeDoApi

class ShopwedoPudo
{
  
  /**
   * API
   * @access public
   * @var ShopWeDoApi
   */
  public $api;
  
  /**
   * The results
   * @access public
   * @var array
   */
  public $result;
  
  /**
   * @param ShopWeDoApi $swd_api
   */
  public function __construct(ShopWeDoApi $swd_api)  
  {
    $this->result = false;
    $this->api = $swd_api;
  }
  
  /**
   * Search shops around a geolocation point or address
   * @param array $data
   * @return stdClass Containing the geoencoded center and the shops around it.
   */
  public function search($data = array())
  {
    if(!empty($data['address']['zip']) && !empty($data['address']['country'])) { // those are manadatory..        
        try {
          $this->api->setMethod('pudo');
          $result = $this->api->get($data);
        } catch (Exception $e) {
          $result = array('error'=>'Couldn\'t load parcelshop API.');
        }
        $this->result = $result;
        return $this->result;
    }
    return false;
  }
  
  private function cleanHours($shop) {
    foreach($shop->openingHours as $key => $day) {
      if($day->openMorning == $day->closeMorning) {
        $shop->openingHours[$key]->openMorning = '';
        $shop->openingHours[$key]->closeMorning = '';
      }
      if($day->closeMorning == $day->openAfternoon) {
        $shop->openingHours[$key]->closeMorning = '';
        $shop->openingHours[$key]->openAfternoon = '';
      }
      if($day->openAfternoon == $day->closeAfternoon) {
        $shop->openingHours[$key]->openAfternoon = '';
        $shop->openingHours[$key]->closeAfternoon = '';
      }
    }
    return $shop;
  }
  
  private function filter($shop, $data)
  {
    if(isset($data['DayOfWeek']) && isset($data['TimeOfDay'])) {
      $day = $shop->openingHours[$data['DayOfWeek']];
      return $this->dayOpen($day) && $this->timeOpen($day, $data['TimeOfDay']);
    } elseif (isset($data['DayOfWeek']))  {
      return $this->dayOpen($shop->openingHours[$data['DayOfWeek']]);
    } elseif (isset($data['TimeOfDay'])) {
      foreach($shop->openingHours as $day) {
        if($this->timeOpen($day, $data['TimeOfDay']))
          return true;
      }
    }
    return true;
  }
  
  private function dayOpen($day) {
    return !($day->openMorning == $day->closeMorning
      && $day->openMorning == $day->openAfternoon
      && $day->openMorning == $day->closeAfternoon);
  }
  
  private function timeOpen($day, $time) {
    return ($day->openMorning <= $time && $time < $day->closeMorning) 
      || ($day->openAfternoon <= $time && $time < $day->closeAfternoon);
  }
  
  /**
   * Geo-encode an address
   * @param string $query The address/place to look for
   * @return stdClass First result of the geoCoding
   */
  private function getGoogleMapsCenter($query = null) {
    if(empty($query) || !is_string($query)) return false;

    $url = 'https://maps.googleapis.com/maps/api/geocode/json?'
            . http_build_query(
                array(
                  'address' => $query,
                  'sensor' => false
                )
              );

    if(
      $body = wp_remote_retrieve_body( wp_remote_get( esc_url_raw( $url ) ) )
    ){
      if($obj = json_decode($body)){
        if(!empty($obj->results)){
          $firstObjResult = reset($obj->results);
          $result = new stdClass();
          $result->lat = $firstObjResult->geometry->location->lat;
          $result->lng = $firstObjResult->geometry->location->lng;
          return $result;
        }
      }
    }
    return false;
  }
}