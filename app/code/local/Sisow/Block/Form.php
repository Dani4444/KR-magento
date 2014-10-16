<?php
class Sisow_Block_Form extends Mage_Payment_Block_Form
{
	public function getBanks() {
		$base = Mage::getModel('sisow/base');
		$quote = $this->getMethod()->getQuote();
		$tot = '';
		foreach ($quote->getTotals() as $total) { //code => $total) {
			$tot .= 'code='.$total['code'].';';
			$tot .= 'title='.$total['title'].';';
			$tot .= 'value='.$total['value'].';';
		}
		$base->Report('Quote totals: '.$tot);
		/*$arr = get_class_methods(get_class($quote));
		$base->Report('Quote methods: '.implode(';', $arr));*/
		/*include('sisow.php');
		$arr = doIssuerRequest((boolean)Mage::getStoreConfig('payment/sisow/test_mode'), false);*/
		Mage::getModel('sisow/base')->DirectoryRequest($arr, false, (boolean)Mage::getStoreConfig('payment/sisow/test_mode'));
		foreach($arr as $k => $v) {
			$banks[] = array('label' => $v, 'value' => $k);
		}
		return $banks;
	}

	public function __construct() {
		$this->setTemplate('sisow/form.phtml');
		parent::_construct();
	}
}
?>