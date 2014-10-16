<?php
class Sisow_CheckoutController extends Mage_Core_Controller_Front_Action
{
	protected $_order;
	
	public function redirectAction()
	{
		$this->getResponse()
			->setHeader('Content-type', 'text/html; charset=utf8')
			->setBody($this->getLayout()->createBlock('sisow/redirect')->toHtml());
	}
	
	public function successAction()
	{
		foreach (Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item ) {
			Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
		}
		return $this->_redirect('checkout/onepage/success');
	}
}