<?php
class Totm_QuoteRewrite_Model_Mysql4_Recurrentpromo extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("quoterewrite/recurrentpromo", "id");
    }
}