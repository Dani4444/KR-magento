<?php
class Sisow_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    /**
    * unique internal payment method identifier
    * 
    * @var string [a-z0-9_]
    */
    protected $_code = 'sisow';

    protected $_formBlockType = 'sisow/form';
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
    
    public function getOrderPlaceRedirectUrl()
    {
		$bank = $_POST['payment']['sisow_bank'];
		//$method = $_POST['payment']['method'];

		$url = Mage::getUrl('sisow/checkout/redirect/', array('_secure' => true));
		if (!strpos($url, "?")) $url .= '?bank=' . $bank . '&method=';
		else $url .= '&bank=' . $bank . '&method=';
		return $url;
    }

    /**
     * Check void availability
     *
     * @param   Varien_Object $invoicePayment
     * @return  bool
     */
    public function canVoid(Varien_Object $payment)
    {
        return $this->_canVoid;
    }

	public function void(Varien_Object $payment)
	{
		$base = Mage::getModel('sisow/base');
		//$base->Report('void');
		//$base->Report('void ' . $payment->getTransactionId());
		$base->RefundRequest($payment->getTransactionId());
		//$payment->getOrder()
		return $this;
	}
	
    /**
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund()
    {
        return $this->_canRefund;
    }

    /**
     * Refund capture
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return Sisow_Model_PaymentMethod
     */
    public function refund(Varien_Object $payment, $amount)
    {
		$base = Mage::getModel('sisow/base');
		//$base->Report('refund ' . $payment->getParentTransactionId() . ' ' . $amount);
		$base->amount = $amount;
		$base->RefundRequest($payment->getParentTransactionId());
        return $this;
    }
	
    public function capture(Varien_Object $payment, $amount)
    {
        return $this;
    }
}
?>