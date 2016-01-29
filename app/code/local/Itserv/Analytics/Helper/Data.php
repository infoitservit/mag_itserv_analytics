<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * 
* @category    Itserv
* @package     Itserv_Analytics
* @copyright   Copyright (c) 2010-2016 Agenzia Web It Serv, Inc. (http://www.it-serv.it)
* @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* @author      Denis Seghetti <denisseghetti@gmail.com>
 */

/**
 * GoogleAnalytics data helper
 *
 * @category   Mage
 * @package    Mage_GoogleAnalytics
 */
class Itserv_Analytics_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Config paths for using throughout the code
     */
    const XML_ORIGINAL_MAGENTO_ANALYTICS_PATH_ACTIVE        = 'google/analytics/active';
    const XML_PATH_ACTIVE        = 'itserv_analytics_options/analytics/active';
    const XML_PATH_ACCOUNT       = 'itserv_analytics_options/analytics/account';
    const XML_PATH_ANONYMIZATION = 'itserv_analytics_options/analytics/anonymization';
    const XML_PATH_BRAND = 'itserv_analytics_options/analytics/brand';
    const XML_PATH_COSTO_ACQUISTO_TAX = 'itserv_analytics_options/costo_acquisto/tax';
    const XML_PATH_COSTO_ACQUISTO_ATTRIBUTE = 'itserv_analytics_options/costo_acquisto/attribute';
    const XML_PATH_COSTO_ACQUISTO_ID_METRICA = 'itserv_analytics_options/costo_acquisto/id_metrica';

    /**
     * @var google analytics universal tracking code
     */
    const TYPE_UNIVERSAL = 'universal';

    /**
     * Whether GA is ready to use
     *
     * @param mixed $store
     * @return bool
     */
    public function isItservAnalyticsAvailable($store = null)
    {
        $accountId = Mage::getStoreConfig(self::XML_PATH_ACCOUNT, $store);
        return $accountId && Mage::getStoreConfigFlag(self::XML_PATH_ACTIVE, $store);
    }

    /**
     * Whether GA IP Anonymization is enabled
     *
     * @param null $store
     * @return bool
     */
    public function isIpAnonymizationEnabled($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ANONYMIZATION, $store);
    }

    /**
     * Get GA account id
     *
     * @param string $store
     * @return string
     */
    public function getAccountId($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ACCOUNT, $store);
    }
    
    /**
     * Get GA account id
     *
     * @param string $store
     * @return string
     */
    public function getBrandAttributeCode($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BRAND, $store);
    }
    
    /**
     * Get GA account id
     *
     * @param string $store
     * @return string
     */
    public function getCostoAcquistoAttributeCode($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_COSTO_ACQUISTO_ATTRIBUTE, $store);
    }
    
    /**
     * Get GA account id
     *
     * @param string $store
     * @return string
     */
    public function getCostoAcquistoMetricaAnalytics($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_COSTO_ACQUISTO_ID_METRICA, $store);
    }
    
    /**
     * Get GA account id
     *
     * @param string $store
     * @return string
     */
    public function getCostoAcquistoTaxStatus($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_COSTO_ACQUISTO_TAX, $store);
    }
}
