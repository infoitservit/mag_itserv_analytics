<?php

class Itserv_Analytics_Block_Checkout_Onepage_Funneltrack extends Mage_Checkout_Block_Onepage {

    public function __construct() {
        parent::__construct();
    }

    
    public function getTracciamentoIsActive() {
        if (Mage::helper('itserv_analytics')->isFunnelTrackActive()) {
            return true;
        }
        return false;
    }
}
