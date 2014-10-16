<?php
class Sisow_Block_FormEcare extends Mage_Payment_Block_Form
{
	public function getDob() {
		return false;
		/*$order_id = $this->getCheckout()->getLastRealOrderId();
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		$date = Mage::app()->getLocale()->date($order->getCustomerDob();
		if (!$date) {
			return false;
		}
		else {
			return Mage::app()->getLocale()->date($date, null, null, false)->toString('ddMMyyyy');
		}*/
	}
	
	public function getFirstname()
    {
		$firstname = $this->getMethod()->getFirstname();
		if ($firstname)
			return substr($firstname, 0, 1);
		return '';
	}
	
	public function getFee() {
		return $this->getMethod()->getFee();
	}

	public function __construct() {
		$this->setTemplate('sisow/formecare.phtml');
		parent::_construct();
	}
}
?>