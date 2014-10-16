<?php

include_once('Mage/Adminhtml/controllers/Sales/OrderController.php');

class Sisow_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController //Mage_Adminhtml_Controller_Action
{
	/**
	 * Refund order
	 */
	public function refundAction()
	{
		if ($order = $this->_initOrder()) {
			$payment = $order->getPayment();
			if (!$payment) {
				$this->_getSession()->addError($this->__('The order has not been refunded, payment not found.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$method = $payment->getMethod();
			if (substr($method, 0, 5) != 'sisow') {
				$this->_getSession()->addError($this->__('The order has not been refunded, not an Sisow iDEAL payment.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$data = $payment->getAdditionalInformation();
			if (!$data || !array_key_exists('trxId', $data) || !$data['trxId']) {
				$this->_getSession()->addError($this->__('The order has not been refunded, transaction not found.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$base = Mage::getModel('sisow/base');
			//$base->Report('refund ' . $transaction->getTxnId());
			//$base->amount = $amount;
			if (($id = $base->RefundRequest($data['trxId'])) != 0) {
				$payment->setAdditionalInformation('refundId', $id)
					->save();
				$comm = 'Order refunded';
				try {
					foreach ($order->getInvoiceCollection() as $invoice) {
						$invoice->cancel();
					}
					$order->cancel();
				} catch (Exception $e) { }
				$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED, $comm);
				$order->save();
				$this->_doMail($order);
				$this->_getSession()->addSuccess($this->__('The order has been refunded.'));
			}
			else
				$this->_getSession()->addError($this->__('The order has not been refunded (' . $id . ') .'));
		}
		else
			$this->_getSession()->addError($this->__('The order has not been refunded.'));
		$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
	}
	
    public function cancelreservationAction()
    {
		if ($order = $this->_initOrder()) {
			$payment = $order->getPayment();
			if (!$payment) {
				$this->_getSession()->addError($this->__('The Sisow ecare reservation was not cancelled, payment not found.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$method = $payment->getMethod();
			if ($method != 'sisowecare') {
				$this->_getSession()->addError($this->__('The Sisow ecare reservation was not cancelled, not an Sisow ecare payment.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$data = $payment->getAdditionalInformation();
			if (!$data || !array_key_exists('trxId', $data) || !$data['trxId']) { //!$transaction) {
				$this->_getSession()->addError($this->__('The Sisow ecare reservation was not cancelled, transaction not found.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$base = Mage::getModel('sisow/base');
			//$base->Report('invoice ' . $transaction->getTxnId());
			if (!$base->CancelReservationRequest($data['trxId'])) {
				$comm = 'Sisow ecare reservation cancelled';
				try {
					$order->cancel();
				} catch (Exception $e) { }
				$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED, $comm);
				$order->save();
				$this->_doMail($order);
				$this->_getSession()->addSuccess($this->__('The Sisow ecare reservation is cancelled.'));
			}
			else {
				$this->_getSession()->addError($this->__('The Sisow ecare reservation was not cancelled.'));
			}
			$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
		}
    }
	
    public function createinvoiceAction()
    {
		if ($order = $this->_initOrder()) {
			$payment = $order->getPayment();
			if (!$payment) {
				$this->_getSession()->addError($this->__('The Sisow ecare invoice was not created, payment not found.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$method = $payment->getMethod();
			if ($method != 'sisowecare') {
				$this->_getSession()->addError($this->__('The Sisow ecare invoice was not created, not an Sisow ecare payment.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$data = $payment->getAdditionalInformation();
			if (!$data || !array_key_exists('trxId', $data) || !$data['trxId']) { //!$transaction) {
				$this->_getSession()->addError($this->__('The Sisow ecare invoice was not created, transaction not found.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$posts = array();
			if (Mage::getStoreConfig('payment/sisowecare/mail_invoice')) {
				$posts['mailinvoice'] = 'true';
			}
			if (Mage::getStoreConfig('payment/sisowecare/payment_link')) {
				$posts['including'] = 'true';
			}
			$base = Mage::getModel('sisow/base');
			//$base->Report('invoice ' . $transaction->getTxnId());
			if (!$base->InvoiceRequest($data['trxId'], $posts)) {
				$payment->setAdditionalInformation('invoiceNo', $base->invoiceNo)
					->setAdditionalInformation('documentId', $base->documentId)
					->setAdditionalInformation('linkPdf', $base->GetLink(''))
					->save();
				$comm  = 'Sisow ecare invoice created.<br/>';
				$comm .= "Sisow ecare invoiceNo: " . $base->invoiceNo . "<br/>";
				//$comm .= "PDF: " . $base->GetLink('');
				$order->addStatusHistoryComment($comm)
					->save();
				$this->_doMail($order);
				$this->_getSession()->addSuccess($this->__('The Sisow ecare invoice is created.'));
			}
			else {
				$this->_getSession()->addError($this->__('The Sisow ecare invoice was not created.'));
			}
			$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
		}
    }
	
    public function creditinvoiceAction()
    {
		if ($order = $this->_initOrder()) {
			$payment = $order->getPayment();
			if (!$payment) {
				$this->_getSession()->addError($this->__('The Sisow ecare invoice was not credited, payment not found.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$method = $payment->getMethod();
			if ($method != 'sisowecare') {
				$this->_getSession()->addError($this->__('The Sisow ecare invoice was not credited, not an Sisow ecare payment.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$data = $payment->getAdditionalInformation();
			if (!$data || !array_key_exists('trxId', $data) || !$data['trxId']) {
				$this->_getSession()->addError($this->__('The Sisow ecare invoice was not credited, transaction not found.'));
				$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			}
			$base = Mage::getModel('sisow/base');
			if (!$base->CreditInvoiceRequest($data['trxId'])) {
				$payment->setAdditionalInformation('creditinvoiceNo', $base->invoiceNo)
					->setAdditionalInformation('creditdocumentId', $base->documentId)
					->setAdditionalInformation('creditlinkPdf', $base->GetLink(''))
					->save();
				$comm  = 'Sisow ecare credit invoice created.<br/>';
				$comm .= "Sisow ecare creditInvoiceNo: " . $base->invoiceNo . "<br/>";
				/*try {
					$order->cancel();
				} catch (Exception $e) { }*/
				foreach ($order->getInvoiceCollection() as $invoice) {
					$invoice->cancel();
				}
				$order->cancel() //$comm, false);
					->setState(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED, $comm)
					->save();
				/*$order->addStatusHistoryComment($comm)
					->save();*/
				$this->_doMail($order);
				$this->_getSession()->addSuccess($this->__('The Sisow ecare invoice is credited.'));
			}
			else {
				$this->_getSession()->addError($this->__('The Sisow ecare invoice was not credited.'));
			}
			$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
		}
    }
	
	private function _doMail($order)
	{
		if (!$order->getEmailSent()) {
			try {
				$order->sendNewOrderEmail();
			} catch (Exception $ex) {  }
		}
		else if (method_exists($order, 'sendOrderUpdateEmail')) {
			try {
				$order->sendOrderUpdateEmail();
			} catch (Exception $ex) {  }
		}
	}
}
?>