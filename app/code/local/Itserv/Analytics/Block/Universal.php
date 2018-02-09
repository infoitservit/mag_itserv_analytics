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

    private function getProduttoreProdotto(Mage_Catalog_Model_Product $product) {        
        if ($product->getAttributeText(Mage::helper('itserv_analytics')->getBrandAttributeCode())) {
            return $product->getAttributeText(Mage::helper('itserv_analytics')->getBrandAttributeCode());
        } else {
            return '';
        }
    }

    private function getScriptProductDetail() {
        $pagina = Mage::app()->getFrontController()->getAction()->getFullActionName();
        if ($this->product && $this->product->getId() && $pagina == 'catalog_product_view') {
            return <<<HTML
ga('require', 'ec');
ga('ec:addProduct', {
'id': '{$this->jsQuoteEscape($this->product->getSku())}',
'name': '{$this->jsQuoteEscape($this->product->getName())}',
'category': '{$this->jsQuoteEscape(($this->category) ? $this->category->getName() : null)}',
'brand': '{$this->jsQuoteEscape($this->getProduttoreProdotto($this->product))}',
});
ga('ec:setAction', 'detail');
HTML;
        } else {
            return '';
        }
    }

    private function getScriptProdottoAggiuntoAlCarrello() {
        $product = Mage::getModel('core/session')->getProductToShoppingCart();
        if (empty($product) || !is_object($product)) {
            return;
        }
        $result[] = "ga('require', 'ec')";
        $result[] = sprintf("ga('ec:addProduct', {
'id': '%s',
'name': '%s',
'price': '%s',
'quantity': '%s'
", $this->jsQuoteEscape(
                        $product->getId()), $this->jsQuoteEscape($product->getName()), $product->getPrice(), $product->getQty()
        );
        $result[] = sprintf("});");
        $result[] = sprintf("ga('ec:setAction', 'add');");
        Mage::getModel('core/session')->unsProductToShoppingCart();
        return implode("\n", $result);
    }

    private function getScriptTransazione() {
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
'id': '%s',
'name': '%s',
'category': '%s',
'price': '%s',
'quantity': '%s',
", $this->jsQuoteEscape($item->getSku()), $this->jsQuoteEscape($item->getName()), null, // there is no "category" defined for the order item
                        $item->getBasePrice(), $item->getQtyOrdered()
                );

                //Inserisci costo di acquisto se esiste
                if ($this->getCodiceMetrica()) {
                    $result[] = sprintf("
                                '{$this->getCodiceMetrica()}': '%s'", $this->getCostoAcquisto($item));
                }
                $result[] = sprintf("});");
            }
        }

        return implode("\n", $result);
    }

    public function getUniversalAnalyticsBaseScript() {        
        $script = <<<HTML
<script type="text/javascript">
//<![CDATA[
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', '{$this->jsQuoteEscape($this->accountId)}', 'auto');
{$this->_getAnonymizationCodeUniversal()}
{$this->getScriptProductDetail()}
{$this->getScriptProdottoAggiuntoAlCarrello()}
{$this->getScriptTransazione()}
{$this->getScriptUserId()}
{$this->getGoogleOptimizerSnippet()}
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
    private function _getAnonymizationCodeUniversal() {
        if (!Mage::helper('itserv_analytics')->isIpAnonymizationEnabled()) {
            return '';
        }
        return "ga('set', 'anonymizeIp', true);";
    }

    private function getCostoAcquisto($cart_item) {
        $costo_totale = null;

        if ($codice_attributo = Mage::helper('itserv_analytics')->getCostoAcquistoAttributeCode()) {
            $product_id = Mage::getModel("catalog/product")->getIdBySku($cart_item->getSku());

            //Costo di acquisto del singolo prodotto
            $costo = Mage::getModel("catalog/product")->getResource()->getAttributeRawValue($product_id, $codice_attributo, Mage::app()->getStore());

            if ($costo) {
                //Il costo totale è il costo di acquisto moltiplicato per le quantità acquistate.
                $costo_totale = $costo * $cart_item->getQtyOrdered();

                //Se richiesto dalla configurazione, calcolo il costo applicando le tasse
                if (Mage::helper('itserv_analytics')->getCostoAcquistoTaxStatus() == '1' && $cart_item->getTaxPercent() && $cart_item->getTaxPercent() > 0) {
                    $costo_totale = $costo_totale * (1 + ($cart_item->getTaxPercent() / 100));
                }
            }
        }

        return $costo_totale;
    }

    private function getCodiceMetrica() {
        if ($codice_metrica = Mage::helper('itserv_analytics')->getCostoAcquistoMetricaAnalytics(Mage::app()->getStore())) {
            return $codice_metrica;
        }
    }
    
    private function getScriptUserId() {
        if(Mage::getSingleton('customer/session')->getCustomer()) {
            return "ga('set', 'userId', '".Mage::getSingleton('customer/session')->getCustomer()->getId()."')";
        }
        
        return '';
    }

    private function getGoogleOptimizerSnippet() {
        if ($codice_account_optimizer = Mage::helper('itserv_analytics')->getOptimizerAccountNumber(Mage::app()->getStore())) {
            return "ga('require', '".$codice_account_optimizer."')";
        }
        
        return '';
    }

}
