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
 */

/**
 * Used in creating options for config value selection
 *
 */
class Sisow_Model_Returnonerror
{

	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value' => 'home', 'label' => 'Home'),
			array('value' => 'cart', 'label' => 'Cart'),
			array('value' => 'onepage', 'label' => 'OnePage'),
			array('value' => 'onestep', 'label' => 'OneStepCheckout')
		);
	}

}
