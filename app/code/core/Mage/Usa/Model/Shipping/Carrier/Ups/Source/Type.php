<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
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
 * @package     Mage_Usa
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 *
 * Usa Ups type action Dropdown source
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Usa_Model_Shipping_Carrier_Ups_Source_Type
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'UPS', 'label' => Mage::helper('usa')->__('United Parcel Service')),
            #array('value' => Mage_Paypal_Model_Api_Abstract::PAYMENT_TYPE_ORDER, 'label' => Mage::helper('usa')->__('Order')),
            array('value' => 'UPS_XML', 'label' => Mage::helper('usa')->__('United Parcel Service XML')),
        );
    }
}