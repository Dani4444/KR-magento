<?php
class Sisow_Model_SisowEcare extends Mage_Payment_Model_Method_Abstract
{
    /**
    * unique internal payment method identifier
    * 
    * @var string [a-z0-9_]
    */
    protected $_code = 'sisowecare';

    protected $_formBlockType = 'sisow/formecare';
    /**
     * Here are examples of flags that will determine functionality availability
     * of this module to be used by frontend and backend.
     * 
     * @see all flags and their defaults in Mage_Payment_Model_Method_Abstract
     *
     * It is possible to have a custom dynamic logic by overloading
     * public function can* for each flag respectively
     */
     
    /**
     * Availability options
     */
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc = false;
    
    
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }
	
	public function getFee() {
		$fee = Mage::getStoreConfig('payment/sisowecare/payment_fee');
		if (!$fee) {
			return false;
		}
		if ($fee > 0) {
			return $fee;
		}
		$order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		if ($order_id) {
			$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
			$total = $order->getSubtotal();
		}
		else {
			$total = $this->getQuote()->getSubtotal();
		}
		if (!$total) {
			return false;
		}
		$fee = round($total * -$fee / 100.0, 2);
		return $fee;
	}
	
	public function getFirstname()
    {
		$arr = get_class_methods($this->getQuote());
		$base = Mage::getModel('sisow/base');
		$base->Report('Quote methods: '.implode($arr));
		return $this->getQuote()->getBillingAddress()->getFirstname();
	}
    
    public function getOrderPlaceRedirectUrl()
    {
		$day = $_POST['payment']['sisow_day'];
		$month = $_POST['payment']['sisow_month'];
		$year = $_POST['payment']['sisow_year'];
		if ($year > 0 && $year < 100) {
			$year += 1900;
		}

		$url = Mage::getUrl('sisow/checkout/redirect/', array('_secure' => true));
		if (strpos($url, "?")) $url .= '&';
		else $url .= '?';
		$url .= 'method=ecare&dob=' . sprintf('%02d%02d%04d', $day, $month, $year) . '&gender=' . $_POST['payment']['sisow_gender'] . '&initials=' . $_POST['payment']['sisow_initials'];
		return $url;
    }

    /**
     * Set capture transaction ID to invoice for informational purposes
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    /*public function processInvoice($invoice, $payment)
    {
		$paymentTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
		$base = Mage::getModel('sisow/base');
		if ($paymentTransaction) {
			if (!$base->InvoiceRequest($paymentTransaction->getTxnId())) {
				$paymentTransaction->setAdditionalInformation('invoiceNo', $base->invoiceNo)
					->setAdditionalInformation('documentId', $base->documentId)
					->setAdditionalInformation('linkPdf', $base->GetLink(''));
				$comm  = "InvoiceNo: " . $base->invoiceNo . "\r\n";
				$comm .= "PDF: " . $base->GetLink('');
				$payment->getOrder()
					->addStatusHistoryComment($comm)
					->save();
			}
			else {
				$base->Report('processInvoice InvoiceRequest');
				return;
			}
		}
		else
			$base->Report('processInvoice no paymentTx');
        return Mage_Payment_Model_Method_Abstract::processInvoice($invoice, $payment);
    }*/

    /**
     * Set transaction ID into creditmemo for informational purposes
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    /*public function processCreditmemo($creditmemo, $payment)
    {
		$base = Mage::getModel('sisow/base');
		$base->Report('processCreditmemo ' . $payment->getParentTransactionId());
        return Mage_Payment_Model_Method_Abstract::processCreditmemo($creditmemo, $payment);
    }*/
}
?>