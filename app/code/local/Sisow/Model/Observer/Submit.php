<?php
/**
 * Sisow B.V.
 * http://www.sisow.nl
 *
 * @extension   Sisow
 * @type        Payment method
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Sisow
 * @package     Sisow
 * @copyright   Copyright (c) 2011 Sisow B.V. (http://www.sisow.nl)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Sisow_Model_Observer_Submit
{
	/*
	 * Keep cart after placing order
	 */
	public function sales_model_service_quote_submit_after(Varien_Event_Observer $observer) {
		$method = $observer->getEvent()->getOrder()->getPayment()->getMethod();
		if (substr($method, 0, 5) == 'sisow') {
			if (Mage::getStoreConfig('payment/' . $method . '/keepcart')) {
				$observer->getQuote()->setIsActive(TRUE);
			}
		}
	}

	/*
	 * Create/print Sisow ecare invoice
	 */
	/*public function sales_order_invoice_register(Varien_Event_Observer $observer) {
		Mage::log('entering sales_order_invoice_register');
		$method = $observer->getEvent()->getOrder()->getPayment()->getMethod();
		if (substr($method, 0, 5) == 'sisow') {
			$base = Mage::getModel('sisow/base')->setMerchant(Mage::getStoreConfig('payment/sisow/gebruiker'), Mage::getStoreConfig('payment/sisow/wachtwoord'));
			if (!$base) {
				Mage::log('sales_order_invoice_register: no base');
				exit;
			}
			//$base->merchantId = Mage::getStoreConfig('payment/sisow/gebruiker');
			//$base->merchantKey = Mage::getStoreConfig('payment/sisow/wachtwoord');
			//$arr['payment'] = 'ecare';
			$arr['message'] = 'sales_order_invoice_register ' . $observer->getEvent()->getOrder()->getId();
			$base->send('Report', $arr);
		}
	}*/

	/*
	 * Cancel/credit Sisow ecare invoice
	 */
	/*public function sales_order_invoice_cancel(Varien_Event_Observer $observer) {
		Mage::log('entering sales_order_invoice_cancel');
		$method = $observer->getEvent()->getOrder()->getPayment()->getMethod();
		if (substr($method, 0, 5) == 'sisow') {
			$base = Mage::getModel('sisow/base')->setMerchant(Mage::getStoreConfig('payment/sisow/gebruiker'), Mage::getStoreConfig('payment/sisow/wachtwoord'));
			if (!$base) {
				Mage::log('sales_order_invoice_cancel: no base');
				exit;
			}
			//$base->merchantId = Mage::getStoreConfig('payment/sisow/gebruiker');
			//$base->merchantKey = Mage::getStoreConfig('payment/sisow/wachtwoord');
			//$arr['payment'] = 'ecare';
			$arr['message'] = 'sales_order_invoice_cancel ' . $observer->getEvent()->getOrder()->getId();
			$base->send('Report', $arr);
		}
	}*/
}
