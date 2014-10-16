<?php
/**
 * Magento
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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml sales order view
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Sisow_Block_OrderView extends Mage_Adminhtml_Block_Sales_Order_View
{

    public function __construct()
    {
        parent::__construct();

		$order = $this->getOrder();
		
		//if ($order->getState() == Mage_Sales_Model_Order::STATE_CANCELED)
		//	return;
			
		$payment = $order->getPayment();
		if (!$payment)
			return;
		
		$method = $payment->getMethod();
		if (substr($method, 0, 5) != 'sisow')
			return;
		
		$data = $payment->getAdditionalInformation();

		if ($method == 'sisow' && ($order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING || $order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE)) {
			$onclickJs = 'deleteConfirm(\''
				. Mage::helper('sales')->__('Are you sure? This order will be refunded')
				. '\', \'' . $this->_getRefundUrl() . '\');';
			$this->_addButton('sisow_refund', array(
				'label'    => 'Sisow refund', //Mage::helper('sales')->__('Edit'),
				'onclick'  => $onclickJs,
			));
			return;
		}
		
		if ($method != 'sisowecare')
			return;

		if ($data && array_key_exists('linkPdf', $data)) {
            $onclickJs = 'window.open(\'' . $data['linkPdf'] . '\', \'_blank\');';
            $this->_addButton('ecare_print', array(
                'label'    => 'Print ecare', //Mage::helper('sales')->__('Edit'),
                'onclick'  => $onclickJs,
            ));
			if (array_key_exists('creditlinkPdf', $data)) {
				$onclickJs = 'window.open(\'' . $data['creditlinkPdf'] . '\', \'_blank\');';
				$this->_addButton('creditecare_print', array(
					'label'    => 'Print ecare credit', //Mage::helper('sales')->__('Edit'),
					'onclick'  => $onclickJs,
				));
			}
			else {
				$onclickJs = 'deleteConfirm(\''
					. Mage::helper('sales')->__('Are you sure? The invoice will be credited')
					. '\', \'' . $this->_getCreditUrl() . '\');';
				$this->_addButton('ecare_credit', array(
					'label'    => 'Credit ecare', //Mage::helper('sales')->__('Edit'),
					'onclick'  => $onclickJs,
				));
			}
		}
		else if ($order->getState() != Mage_Sales_Model_Order::STATE_CANCELED) {
            $onclickJs = 'deleteConfirm(\''
                . Mage::helper('sales')->__('Are you sure? The invoice will be created')
                . '\', \'' . $this->_getCreateUrl() . '\');';
            $this->_addButton('ecare_create', array(
                'label'    => 'Create ecare', //Mage::helper('sales')->__('Edit'),
                'onclick'  => $onclickJs,
            ));
            $onclickJs = 'deleteConfirm(\''
                . Mage::helper('sales')->__('Are you sure? The reservation and order will be canceled')
                . '\', \'' . $this->_getCancelUrl() . '\');';
            $this->_addButton('ecare_cancel', array(
                'label'    => 'Cancel ecare', //Mage::helper('sales')->__('Edit'),
                'onclick'  => $onclickJs,
            ));
        }
    }

	// RefundRequest
    public function _getRefundUrl()
    {
        return $this->getUrl('*/*/refund');
    }

	// CancelReservationRequest
    public function _getCancelUrl()
    {
        return $this->getUrl('*/*/cancelreservation');
    }

	// InvoiceRequest
    public function _getCreateUrl()
    {
        return $this->getUrl('*/*/createinvoice');
    }

	// CreditInvoiceRequest
    public function _getCreditUrl()
    {
        return $this->getUrl('*/*/creditinvoice');
    }
}
