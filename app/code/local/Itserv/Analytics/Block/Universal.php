<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Itserv_Analytics_Block_Universal extends Mage_Core_Block_Template {

    protected $accountId;
    protected $product;
    protected $category;
    protected $order;

    public function __construct() {
        parent::__construct();
        if (!Mage::helper('itserv_analytics')->isItservAnalyticsAvailable()) {
            return;
        }

        $accountId = Mage::helper('itserv_analytics')->getAccountId();
        if (empty($accountId) || $accountId == '') {
            return;
        }
        $this->accountId = $accountId;
        $this->product = Mage::registry('current_product');
        $this->category = Mage::registry('current_category');
    }

    protected function getProduttoreProdotto(Mage_Catalog_Model_Product $product) {
        if ($product->getAttributeText(Mage::helper('itserv_analytics')->getBrandAttributeCode())) {
            return $product->getAttributeText(Mage::helper('itserv_analytics')->getBrandAttributeCode());
        } else {
            return '';
        }
    }

    public function getScriptProductDetail() {
        $pagina = Mage::app()->getFrontController()->getAction()->getFullActionName();
        if ($this->product && $this->product->getId() && $pagina == 'catalog_product_view') {
            return <<<HTML
    ga('require', 'ec');
    ga('ec:addProduct', {
    'id': '{$this->jsQuoteEscape($this->product->getSku())}',
    'name': '{$this->jsQuoteEscape($this->product->getName())}',
    'category': '{$this->jsQuoteEscape($this->product->getName())}',
    'brand': '{$this->jsQuoteEscape($this->getProduttoreProdotto($this->product))}',
    });
    ga('ec:setAction', 'detail');
HTML;
        } else {
            return '';
        }
    }

    public function getScriptTransazione() {
        /* $pagina = Mage::app()->getFrontController()->getAction()->getFullActionName();
          if($pagina != 'checkout_onepage_success') {
          return '';
          }
          else {
         * 
         */

        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }
        $collection = Mage::getResourceModel('sales/order_collection')
                ->addFieldToFilter('entity_id', array('in' => $orderIds));
        $result = array();

        foreach ($collection as $order) {
            $result[] = "ga('require', 'ec')";
            $result[] = sprintf("ga('ec:setAction', 'purchase', {
'id': '%s',
'affiliation': '%s',
'revenue': '%s',
'tax': '%s',
'shipping': '%s',
'coupon': '%s'
});", $order->getIncrementId(), $this->jsQuoteEscape(Mage::app()->getStore()->getFrontendName()), $order->getBaseGrandTotal(), $order->getBaseTaxAmount(), $order->getBaseShippingAmount(), $this->jsQuoteEscape($order->coupon_code)
            );
            foreach ($order->getAllVisibleItems() as $item) {
                $result[] = sprintf("ga('ec:addProduct', {
'sku': '%s',
'name': '%s',
'category': '%s',
'price': '%s',
'quantity': '%s'
});", $this->jsQuoteEscape($item->getSku()), $this->jsQuoteEscape($item->getName()), null, // there is no "category" defined for the order item
                        $item->getBasePrice(), $item->getQtyOrdered()
                );
            }
        }

        return implode("\n", $result);
    }

    protected function getUniversalAnalyticsBaseScript() {
        $script = <<<HTML
        <script type="text/javascript">
        //<![CDATA[
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

        //prima parte con creazione dell'istanza analytics
        ga('create', '{$this->jsQuoteEscape($this->accountId)}', 'auto');
        
        //se abilitato, anonimizzo il codice
        {$this->_getAnonymizationCodeUniversal()}
        
        //se siamo nella pagina prodotto, inseriamo l'apposito tag
        {$this->getScriptProductDetail()}
        
        {$this->getScriptTransazione()}
        //concludo inviando la page view
        ga('send', 'pageview');

        //]]>
        </script>
HTML;
        return $script;
    }

    /**
     * Render IP anonymization code for page tracking javascript universal analytics code
     *
     * @return string
     */
    protected function _getAnonymizationCodeUniversal() {
        if (!Mage::helper('itserv_analytics')->isIpAnonymizationEnabled()) {
            return '';
        }
        return "ga('set', 'anonymizeIp', true);";
    }

}
