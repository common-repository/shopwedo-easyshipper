<?php
/**
 * General E-commerce Translate
 *
 * This object will provide translating for specific/known platforms.
 *
 *   - Prestashop: https://www.prestashop.com/
 * 
 * @author: support@shopwedo.com
 * @version: 1.0
 * @package: EcomGeneral Classes
 * 
 * Note: Original code from DIS package by Michiel Van Gucht <michiel.vangucht@dpd.be>
 */
 
class GeneralTranslate 
{

  public static function t($data)
  {
    /** 
     * PRESTASHOP
     */
    if(defined('_PS_VERSION_'))
    {
      switch(substr(_PS_VERSION_, 0, 3))
      {
        
        //case '1.4':
        //case '1.5':
        case '1.6':
          return $data;
          // if(is_callable(array('TranslateCore', 'getModuleTranslation')))
          // { 
            // // Context::getContext();
          // }
          break;
      }
    }
  }

}