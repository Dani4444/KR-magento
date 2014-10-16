<?php
class Sisow_Block_FormOvb extends Mage_Payment_Block_Form
{
	public function __construct() {
		$this->setTemplate('sisow/formovb.phtml');
		parent::_construct();
	}

	public function getDataOvb() {
		$order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId(); //$this->getCheckout()->getLastRealOrderId();
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		$data = array();
		$data['purchaseid'] = $order->getCustomerId() . $order->getRealOrderId();
		$data['amount'] = $this->getMethod()->getCheckout()->getQuote()->getGrandTotal();
		return $data;
	}
}
?>