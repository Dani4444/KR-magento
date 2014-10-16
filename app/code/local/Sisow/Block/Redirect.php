<?php
class Sisow_Block_Redirect extends Mage_Core_Block_Abstract
{
	public $payment_method=NULL;

	public function getQuote() {
		return $this->getCheckout()->getQuote();
	}

	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}

	protected function _toHtml() {
		$order_id = $this->getCheckout()->getLastRealOrderId();
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		$billto = $order->getBillingAddress();
		if (!$billto) {
			$billto = $this->getQuote()->getBillingAddress();
		}
		$shipto = $order->getShippingAddress();
		if (!$shipto) {
			$shipto = $this->getQuote()->getShippingAddress();
			if (!$shipto) {
				$shipto = $billto;
			}
		}

		/*if ($order->getState() != Mage_Sales_Model_Order::STATE_NEW) { // || $order->getStatus() != 'pending') {
			header('Location: ' . Mage::getUrl('/'));
			exit;
		}*/

		//include('sisow.php');

		$method = $_GET['method'];
		
		$m2 = 'sisow';
		switch ($method) {
			case 'sofort':
				$m2 = 'sisowde';
				break;
			case 'overboeking':
				$m2 = 'sisowob';
				break;
			case 'mistercash':
				$m2 = 'sisowmc';
				break;
			case 'podium':
				$m2 = 'sisowpc';
				break;
			case 'webshop':
				$m2 = 'sisowwg';
				break;
			case 'ecare':
				$m2 = 'sisowecare';
				break;
			case 'sisowpp':		// PayPal Express Checkout
			case 'sisowppcc':	// PayPal CreditCard
				$m2 = $method;
				break;
		}

		if (Mage::getStoreConfig('payment/' . $m2 . '/test_mode')) { // || !array_key_exists('bank', $_GET)) {
			$bank = '99';
		}
		else {
			$bank = $_GET['bank'];
		}

   		//$gebruiker = Mage::getStoreConfig('payment/sisow/gebruiker');
		//$wachtwoord = Mage::getStoreConfig('payment/sisow/wachtwoord');
		$txid = '';

		$storeId = Mage::app()->getStore()->getId();
		$websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

		$store = new Mage_Adminhtml_Model_System_Store();
		$store_name = strtolower($store->getStoreName($storeId));

		//$content[] = $store_name;

		foreach ($order->getAllItems() as $item) {
			$content[] = $item['name'] . ' x ' . $item->getQtyToShip();
			//$p['message'] = $item->getName() . ';' . $item->getQtyToShip() . ';' . $item->getPrice() . ';' . $item->getTaxAmount() . ';' . $item->getWeight(); // . ';' . $item[''];
			//curl_post('Report', $p);
		}
		
		$oms = Mage::getStoreConfig('payment/sisow/prefix');
		if (!$oms || $oms == "") $oms = implode($content, ";");
		else $oms .= " " . $order_id;

		$base = Mage::getModel('sisow/base');
		if ($method != 'sisow') {
			if ($method == 'sisowpp') $base->payment = 'paypalec';
			else if ($method == 'sisowppcc') $base->payment = 'paypalcc';
			else $base->payment = $method;
		}
		$base->issuerId = $bank;
		$base->amount = $order->getGrandTotal();
		if ($method == 'overboeking') {
			$base->purchaseId = $order->getCustomerId() . $order_id;
			$base->entranceCode = $order_id;
		}
		else
			$base->purchaseId = $order_id;
		$base->description = $oms;
		$base->notifyUrl = Mage::getUrl('sisow/notify', array('_secure' => true));
		$base->returnUrl = Mage::getUrl('sisow/notify', array('_secure' => true));
		
		$custid = $order->getCustomerId();
		if (!$custid)
			$custid = $this->getQuote()->getCustomerId();
		$email = $order->getCustomerEmail();
		if (!$email)
			$email = $this->getQuote()->getBillingAddress()->getEmail();
		
		$posts = array();
		$posts['ipaddress'] = $_SERVER['REMOTE_ADDR'];
		$posts['customer'] = $custid; //$order->getCustomerId();
		// billing information
		$posts['billing_mail'] = $email; //$order->getCustomerEmail();
		$posts['billing_firstname'] = $billto->getFirstname();
		$posts['billing_lastname'] = $billto->getLastname();
		//$posts['company'] = $billto->get();
		$posts['billing_address1'] = $billto->getStreet1();
		$posts['billing_address2'] = $billto->getStreet2();
		$posts['billing_zip'] = $billto->getPostcode();
		$posts['billing_city'] = $billto->getCity();
		$posts['billing_country'] = $billto->getCountry();
		$posts['billing_countrycode'] = $billto->getCountryId();
		$posts['billing_phone'] = $billto->getTelephone();
		// shipping information
		$posts['shipping_mail'] = $email; //$order->getCustomerEmail();
		$posts['shipping_firstname'] = $shipto->getFirstname();
		$posts['shipping_lastname'] = $shipto->getLastname();
		//$posts['company'] = $shipto->get();
		$posts['shipping_address1'] = $shipto->getStreet1();
		$posts['shipping_address2'] = $shipto->getStreet2();
		$posts['shipping_zip'] = $shipto->getPostcode();
		$posts['shipping_city'] = $shipto->getCity();
		$posts['shipping_country'] = $shipto->getCountry();
		$posts['shipping_countrycode'] = $shipto->getCountryId();
		$posts['shipping_phone'] = $shipto->getTelephone();
		$posts['items'] = implode(';', $content);
		//$posts['shipping'] = $billto->get();
		//$posts['handling'] = $billto->get();
		
		$i = 1;
		foreach ($this->getQuote()->getTotals() as $total) {
			$posts['total_code_' . $i] = $total['code'];
			$posts['total_title_' . $i] = $total['title'];
			$posts['total_value_' . $i] = $total['value'];
			$i++;
		}
		
		if ($method == 'ecare' || $method == 'ebill' || $method == 'overboeking' || $method == 'sisowpp') {
			if ($method == 'ecare') {
				$posts['birthdate'] = $_GET['dob'];
				$posts['gender'] = $_GET['gender'];
				$posts['initials'] = $_GET['initials'];
			}
			$i = 1;
			foreach ($order->getAllVisibleItems() as $item) {
				$id = $item->getProductId();
				$product = Mage::getModel('catalog/product')->load($id);
				$qty = (int)($item->getQtyOrdered() ? $item->getQtyOrdered() : $item->getQty());
				$posts['product_id_' . $i] = $id;
				$posts['product_description_' . $i] = $item['name'];
				$posts['product_quantity_' . $i] = $qty;
				//$posts['product_weight_' . $i] = round($prod['weight'] * 1000, 0);
				$posts['product_tax_' . $i] = round($item->getTaxAmount() * 100, 0);
				$posts['product_taxrate_' . $i] = round($item->getTaxPercent() * 100, 0);
				$posts['product_netprice_' . $i] = round($item->getPrice() * 100, 0);
				$posts['product_price_' . $i] = round($item->getPriceInclTax() * 100, 0);
				$posts['product_nettotal_' . $i] = round($item->getRowTotal() * 100, 0);
				$posts['product_total_' . $i] = round($item->getRowTotalInclTax() * 100, 0);
				$i++;
			}
			$shipping = $order->getShippingAmount();
			if ($shipping > 0) {
				$shiptax = $shipping + $order->getShippingTaxAmount();
				$posts['product_id_' . $i] = 'shipping';
				$posts['product_description_' . $i] = 'Verzendkosten';
				$posts['product_quantity_' . $i] = 1;
				$posts['product_weight_' . $i] = 0;
				$posts['product_tax_' . $i] = round($order->getShippingTaxAmount() * 100, 0);
				//$posts['product_taxrate_' . $i] = round($order->getShippingTaxRate() * 100, 0);
				$posts['product_taxrate_' . $i] = round($this->_getShippingTaxRate($order) * 100, 0);
				$posts['product_netprice_' . $i] = round($shipping * 100, 0);
				$posts['product_price_' . $i] = round($shiptax * 100, 0);
				$posts['product_nettotal_' . $i] = round($shipping * 100, 0);
				$posts['product_total_' . $i] = round($shiptax * 100, 0);
				$i++;
			}
			if (($fee = Mage::getStoreConfig('payment/' . $m2 . '/payment_fee'))) {
				if ($fee < 0) {
					$fee = round($order->getSubtotal() * -$fee / 100.0, 2);
				}
				$rate = $this->_getShippingTaxRate($order);
				$feetax = round($fee * $rate / 100, 2);
				$posts['product_id_' . $i] = 'paymentfee';
				$posts['product_description_' . $i] = 'Payment Fee';
				$posts['product_quantity_' . $i] = 1;
				$posts['product_weight_' . $i] = 0;
				$posts['product_tax_' . $i] = round($feetax * 100, 0);
				$posts['product_taxrate_' . $i] = round($rate * 100, 0);
				$posts['product_netprice_' . $i] = round($fee * 100, 0);
				$posts['product_price_' . $i] = round(($fee + $feetax) * 100, 0);
				$posts['product_nettotal_' . $i] = round($fee * 100, 0);
				$posts['product_total_' . $i] = round(($fee + $feetax) * 100, 0);
				$base->amount += $fee + $feetax;
			}
			if ($method == 'ecare') {
				if (Mage::getStoreConfig('payment/sisowecare/test_mode')) {
					$posts['testmode'] = 'true';
				}
				if (Mage::getStoreConfig('payment/sisowecare/auto_invoice')) {
					$posts['makeinvoice'] = 'true';
					if (Mage::getStoreConfig('payment/sisowecare/auto_invoice') == 2) {
						$posts['mailinvoice'] = 'true';
						if (Mage::getStoreConfig('payment/sisowecare/payment_link')) {
							$posts['including'] = 'true';
						}
					}
				}
			}
			if ($method == 'overboeking') {
				if (Mage::getStoreConfig('payment/sisowob/days')) {
					$posts['days'] = Mage::getStoreConfig('payment/sisowob/days');
				}
				if (Mage::getStoreConfig('payment/sisowob/payment_link')) {
					$posts['including'] = 'true';
				}
			}
			//$posts['customer'] = $order->getCustomerId();
			//$posts['fifty'] = 'true';
		}

		$ex = $base->TransactionRequest($posts);
		if ($method == 'overboeking') {
			if ($ex) {
				$order->cancel();
				$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED, $ex . ' ' . $base->errorMessage); //, $comm);
				$order->save();
				Mage::getSingleton('core/session')->addError('Betalen met Sisow OverBoeking is (nu) niet mogelijk. Probeer met een andere betaalmethode af te rekenen.');
				$waar = Mage::getStoreConfig('payment/sisow/return_on_failure');
				if ($waar == 'home')
					$waar = '/';
				elseif ($waar == 'cart')
					$waar = 'checkout/cart';
				elseif ($waar == 'onepage')
					$waar = 'checkout/onepage';
				else //if ($waar == 'onestep')
					$waar = 'onestepcheckout';
				$url = Mage::getUrl($waar); //'checkout/onepage');
			}
			else {
				$payment = $order->getPayment();
				$comm = 'Sisow OverBoeking created.<br />';
				$comm .= 'Transaction ID: ' . $base->trxId . '<br/>';
				$st = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
				$payment->setAdditionalInformation('trxId', $base->trxId)
					->setAdditionalInformation('documentId', $base->documentId)
					->setAdditionalInformation('linkPdf', $base->GetLink(''))
					->save();
				$order->setState($st, $st, $comm);
				$order->save();
				if ($method == 'overboeking' && Mage::getStoreConfig('payment/sisowob/orderemail')) {
					try {
						$order->sendNewOrderEmail();
					} catch (Exception $ex) {  }
				}
				$url = Mage::getUrl("sisow/checkout/success");
			}
		}
		else if ($method == 'ecare') {
			if ($base->issuerUrl) {
				if (strpos($base->issuerUrl, 'RestPay') > 0) {
					header('Location: ' . $base->issuerUrl);
					exit;
				}
				$payment = $order->getPayment();
				if ($base->invoiceNo) {
					$comm = 'Sisow ecare invoice created.<br/>';
					$comm .= 'Transaction ID: ' . $base->trxId . '<br/>';
					if ($fee > 0) {
						$comm .= 'Sisow ecare payment fee ' . $fee . '<br/>';
					}
					$comm .= 'Sisow ecare invoiceNo ' . $base->invoiceNo . '<br/>';
					//$comm .= 'documentId ' . $base->documentId . '<br/>';
					//$comm .= 'linkPdf ' . $base->GetLink('');
					$st = Mage_Sales_Model_Order::STATE_COMPLETE;
					$payment->setAdditionalInformation('trxId', $base->trxId)
						->setAdditionalInformation('invoiceNo', $base->invoiceNo)
						->setAdditionalInformation('documentId', $base->documentId)
						->setAdditionalInformation('linkPdf', $base->GetLink(''))
						->save();
				}
				else {
					$comm = 'Sisow ecare reservation created.<br />';
					$comm .= 'Transaction ID: ' . $base->trxId . '<br/>';
					if ($fee > 0) {
						$comm .= 'Sisow ecare payment fee ' . $fee . '<br />';
					}
					$st = Mage_Sales_Model_Order::STATE_PROCESSING;
					$payment->setAdditionalInformation('trxId', $base->trxId)
						->setAdditionalInformation('fee', $fee)
						->setAdditionalInformation('rate', $rate)
						->setAdditionalInformation('feetax', $feetax)
						->save();
				}
				$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $st, $comm);
				/*if ($fee > 0) {
					$order->setShippingAmount($order->getShippingAmount() + $fee);
				}*/
				$order->save();
				if ($base->invoiceNo) { //Mage::getStoreConfig('payment/sisowecare/auto_invoice')) {
					if ($order->canInvoice()) {
						$invoice = $order->prepareInvoice();
						$invoice->register()->capture();
						Mage::getModel('core/resource_transaction')
							->addObject($invoice)
							->addObject($invoice->getOrder())
							->save();
					}
				}
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
				$url = Mage::getUrl("sisow/checkout/success");
			}
			else {
				$order->cancel();
				$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED); //, $comm);
				$order->save();
				//Mage::getSingleton('core/session')->addError(urldecode(parseFromXml('errormessage', $result)));
				Mage::getSingleton('core/session')->addError('Betalen met Sisow ecare is (nu) niet mogelijk. Probeer met een andere betaalmethode af te rekenen.');
				$waar = Mage::getStoreConfig('payment/sisow/return_on_failure');
				if ($waar == 'home')
					$waar = '/';
				elseif ($waar == 'cart')
					$waar = 'checkout/cart';
				elseif ($waar == 'onepage')
					$waar = 'checkout/onepage';
				else //if ($waar == 'onestep')
					$waar = 'onestepcheckout';
				$url = Mage::getUrl($waar); //'checkout/onepage');
			}
		}
		else if ($ex) {
			if ($base->errorMessage) {
				Mage::getSingleton('core/session')->addError($base->errorMessage);
			}
			else {
				Mage::getSingleton('core/session')->addError("Sisow: geen communicatie mogelijk ($ex)");
			}
			$waar = Mage::getStoreConfig('payment/sisow/return_on_failure');
			if ($waar == 'home')
				$waar = '/';
			elseif ($waar == 'cart')
				$waar = 'checkout/cart';
			elseif ($waar == 'onepage')
				$waar = 'checkout/onepage';
			else //if ($waar == 'onestep')
				$waar = 'onestepcheckout';
			$url = Mage::getUrl($waar); //'checkout/onepage');
		}
		else {
			$url = $base->issuerUrl;
			if (!$url) {
				Mage::getSingleton('core/session')->addError($base->errorMessage); //urldecode(parseFromXml('errormessage', $result)));
				$waar = Mage::getStoreConfig('payment/sisow/return_on_failure');
				if ($waar == 'home')
					$waar = '/';
				elseif ($waar == 'cart')
					$waar = 'checkout/cart';
				elseif ($waar == 'onepage')
					$waar = 'checkout/onepage';
				else //if ($waar == 'onestep')
					$waar = 'onestepcheckout';
				$url = Mage::getUrl($waar); //'checkout/onepage');
			}
			else {
				$order->getPayment()->setAdditionalInformation('trxId', $base->trxId)->save();
				$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
				$order->save();
			}
		}
		header('Location: ' . $url);
		exit;
		
		$html = '<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE"><META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE"><html><body>';
		$html.= $this->__(Mage::getStoreConfig('payment/sisow/redirect'));
		//$html.= '<script type="text/javascript">document.location="' . $tmp_result->GetURLResult . '";</script>';
		$html.= '<script type="text/javascript">document.location="' . $url . '";</script>';
		$html.= '</body></html>';
		return $html;
	}
	
	private function _getShippingTaxRate($order)
	{
	        // Load the customer so we can retrevice the correct tax class id
        	$customer = Mage::getModel('customer/customer')
	            ->load($order->getCustomerId());
        	$taxClass = Mage::getStoreConfig(
	            'tax/classes/shipping_tax_class',
        	    $order->getStoreId()
	        );
        	$calculation = Mage::getSingleton('tax/calculation');
	        $request = $calculation->getRateRequest(
        	    $order->getShippingAddress(),
	            $order->getBillingAddress(),
        	    $customer->getTaxClassId(),
	            Mage::app()->getStore($order->getStoreId())
        	);
	        return $calculation->getRate($request->setProductClassId($taxClass));
	}
}