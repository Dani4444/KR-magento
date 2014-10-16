<?php
class Sisow_NotifyController extends Mage_Core_Controller_Front_Action {

	public function indexAction()
	{
		ob_start();
			echo "POST:\n";
			print_r($_POST);
			echo "\n\n";
			echo "GET:\n";
			print_r($_GET);
			echo "\n\n";
			echo "SERVER:\n";
			print_r($_SERVER);
			echo "\n\n";
			echo date("Y-m-d H:i:s",time());
			$debug_info = ob_get_contents();
		ob_clean();
		
		$debug_email = Mage::getStoreConfig('payment/sisow/debug_email');
		
		if (isset($_GET['trxid'])) {
			$trxid = $_GET['trxid'];
		}
			
		if (isset($_GET['ec'])) {
			$order_id = $ec = $_GET['ec'];
		}

		if (!isset($_GET['notify']) && !isset($_GET['callback'])) {
			$status = $_GET['status'];

			if ($status == 'Success') {
				Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
				return $this->_redirect("checkout/onepage/success", array("_secure" => true));
			}
			else {
				Mage::getSingleton('core/session')->addError('Betaling niet gelukt');
				$waar = Mage::getStoreConfig('payment/sisow/return_on_failure');
				if ($waar == 'home')
					$waar = '/';
				elseif ($waar == 'cart')
					$waar = 'checkout/cart';
				elseif ($waar == 'onepage')
					$waar = 'checkout/onepage';
				else //if ($waar == 'onestep')
					$waar = 'onestepcheckout';
				return $this->_redirect($waar, array("_secure" => true));
			}
			exit;
		}
		
		$base = Mage::getModel('sisow/base');
		$ex = $base->StatusRequest($trxid);
		if ($ex) {
			Mage::getSingleton('core/session')->addError("Sisow: geen communicatie mogelijk ($ex)");
			return $this->_redirect("checkout", array("_secure" => true));
		}

		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

		$mState = Mage_Sales_Model_Order::STATE_CANCELED;
		$mStatus = true;
		$comm = "Betaling gecontroleerd door Sisow.<br />";
		$ret = "/";
		switch ($base->status) {
		  case "Success":
			$mState = Mage_Sales_Model_Order::STATE_PROCESSING;
			$mStatus = Mage::getStoreConfig('payment/sisow/order_status_success');
			if (!$mStatus) {
				$mStatus = Mage_Sales_Model_Order::STATE_PROCESSING;
			}
			$comm .= "Transaction ID: " . $base->trxId . "<br />";
			if ($base->consumerName) {
				$comm .= "Naam: " . $base->consumerName . "<br />";
				$comm .= "Rekening nr.: " . $base->consumerAccount . "<br />";
				$comm .= "Plaatsnaam: " . $base->consumerCity;
			}
			$ret = "sisow/checkout/success";
			/*const TYPE_PAYMENT = 'payment';
			const TYPE_ORDER   = 'order';
			const TYPE_AUTH    = 'authorization';
			const TYPE_CAPTURE = 'capture';
			const TYPE_VOID    = 'void';
			const TYPE_REFUND  = 'refund';*/
			$payment = $order->getPayment();
			if ($payment && $payment->getMethod() == 'sisowob') {
				if ($order->getState() != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
					echo 'OverBoeking not pending (anymore)';
					//exit;
				}
			}
			break;
		  case "Cancelled":
			$mStatus = Mage::getStoreConfig('payment/sisow/order_status_cancelled');
			if (!$mStatus) {
				$mStatus = Mage_Sales_Model_Order::STATE_CANCELED;
			}
			$comm .= "Betaling geannuleerd (Cancelled).";
			break;
		  case "Expired":
			$mStatus = Mage::getStoreConfig('payment/sisow/order_status_expired');
			if (!$mStatus) {
				$mStatus = Mage_Sales_Model_Order::STATE_CANCELED;
			}
			$comm .= "Betaling verlopen (Expired).";
			break;
		  case "Failure":
			$mStatus = Mage::getStoreConfig('payment/sisow/order_status_failure');
			if (!$mStatus) {
				$mStatus = Mage_Sales_Model_Order::STATE_CANCELED;
			}
			$comm .= "Fout in netwerk (Failure).";
			break;
		}

		if ($base->status != "Success" && $order->getState() == Mage_Sales_Model_Order::STATE_CANCELED) {
			exit;
		}

		if ($mState == Mage_Sales_Model_Order::STATE_CANCELED) {
			$order->cancel();
			$order->setState($mState, $mStatus, $comm);
			$order->save();
			echo '$order->setState('. $mState . ', ' . $mStatus . ', ' . $comm . ')';
		}
		elseif ($mState !== null && ($mState != $order->getState() || $mStatus != $order->getStatus())) {
			$order->setState($mState, $mStatus, $comm);
			$order->save();
			echo '$order->setState('. $mState . ', ' . $mStatus . ', ' . $comm . ')';
		}
		else {
			echo '$mState=' . $mState . ' (' . $order->getState() . ') $mStatus=' . $mStatus . ' (' . $order->getStatus() . ')';
		}

		if ($base->status == "Success") {
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
			if ($payment) {
				if (Mage::getStoreConfig('payment/'.$payment->getMethod().'/auto_invoice')) {
					$this->_saveInvoice($order, Mage::getStoreConfig('payment/'.$payment->getMethod().'/auto_invoice'));
				}
			}
			else if (Mage::getStoreConfig('payment/sisow/auto_invoice')) {
				$this->_saveInvoice($order, Mage::getStoreConfig('payment/sisow/auto_invoice'));
			}
		}

		if (isset($_GET['notify']) || isset($_GET['callback'])) {
			exit;
		}

		return $this->_redirect($ret);
	} 
	
	protected function _saveInvoice(Mage_Sales_Model_Order $order, $mail) {
		if ($order->canInvoice()) {
			$invoice = $order->prepareInvoice();
			$invoice->register()->capture();
			Mage::getModel('core/resource_transaction')
				->addObject($invoice)
				->addObject($invoice->getOrder())
				->save();
			if ($mail == 2) {
				$invoice->sendEmail();
			}
			return true;
		}
		return false;
	}
}
?>