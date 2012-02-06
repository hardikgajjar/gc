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
 * @category    Enterprise
 * @package     Enterprise_Rma
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Grid column widget for rendering action grid cells
 *
 * @category    Enterprise
 * @package     Enterprise_Rma
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Rma_Block_Adminhtml_Rma_Edit_Tab_Items_Grid_Column_Renderer_Reasonselect
    extends Enterprise_Rma_Block_Adminhtml_Rma_Edit_Tab_Items_Grid_Column_Renderer_Abstract
{
    /**
     * Renders column as select when it is editable
     *
     * @param   Varien_Object $row
     * @return  string
     */
    protected function _getEditableView(Varien_object $row)
    {
        $rmaItemAttribute = Mage::getModel('enterprise_rma/item_form')
            ->setFormCode('default')
            ->getAttribute('reason_other');

        $selectName = 'items[' . $row->getId() . '][' . $this->getColumn()->getId() . ']';
        $html = '<select name="'. $selectName .'" class="action-select reason required-entry">';
        $value = $row->getData($this->getColumn()->getIndex());
        $html.= '<option value=""></option>';
        foreach ($this->getColumn()->getOptions() as $val => $label){
            $selected = ( ($val == $value && (!is_null($value))) ? ' selected="selected"' : '' );
            $html.= '<option value="' . $val . '"' . $selected . '>' . $label . '</option>';
        }
        if ($rmaItemAttribute && $rmaItemAttribute->getId()) {
            $selected = ($value == 0 && $row->getReasonOther() != '' ? ' selected="selected"' : '' );
            $html.='<option value="other"' . $selected . '>'.$rmaItemAttribute->getStoreLabel().'</option>';
        }
        $class = 'input-text ' . $this->getColumn()->getInlineCss();
        $inputHtml = '<input type="text" ';
        $inputHtml .= 'name="items[' . $row->getId() . '][reason_other]" ';
        $inputHtml .= 'value="' . $row->getReasonOther() . '"';
        $inputHtml .= 'class="' . $class . '" ';
        $inputHtml .= 'style="display:none"/>';
        $html.='</select>'.$inputHtml;
        return $html;
    }

    /**
     * Renders column as select when it is not editable
     *
     * @param   Varien_Object $row
     * @return  string
     */
    protected function _getNonEditableView(Varien_object $row)
    {
        /** @var $rmaItemAttribute Enterprise_Rma_Model_Item_Attribute */
        $rmaItemAttribute = Mage::getModel('enterprise_rma/item_form')
            ->setFormCode('default')
            ->getAttribute('reason_other');
        $value = $row->getData($this->getColumn()->getIndex());
        if ($value == 0 && $row->getReasonOther() != '') {
            if ($rmaItemAttribute && $rmaItemAttribute->getId()) {
                $html = $rmaItemAttribute->getStoreLabel().':&nbsp;';
            } else {
                $html = '';
            }
            if (strlen($row->getReasonOther())>18) {
                $html .= '<a class="item_reason_other">' . substr($row->getReasonOther(),0,15) .'...'.'</a>';
                $html .= '<input type="hidden" name="items[' . $row->getId() . ']'
                    .'[' . $rmaItemAttribute->getAttributeCode() . ']" value="' . $row->getReasonOther() . '" />';
            } else {
                $html .= $row->getReasonOther();
            }
        } else {
            return $this->_getValue($row);
        }
        return $html;
    }
}
