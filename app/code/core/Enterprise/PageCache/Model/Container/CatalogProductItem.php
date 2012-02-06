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
 * @package     Enterprise_PageCache
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Placeholder container for catalog product items
 */
class Enterprise_PageCache_Model_Container_CatalogProductItem
    extends Enterprise_PageCache_Model_Container_Advanced_Abstract
{
    const BLOCK_NAME_RELATED           = 'CATALOG_PRODUCT_ITEM_RELATED';
    const BLOCK_NAME_UPSELL            = 'CATALOG_PRODUCT_ITEM_UPSELL';

    /**
     * Parent (container) block
     *
     * @var null|Enterprise_TargetRule_Block_Catalog_Product_List_Abstract
     */
    protected $_parentBlock = null;

    /**
     * Current item id
     *
     * @var null|int
     */
    protected $_itemId = null;

    /**
     * Container position in list
     *
     * @var null|int
     */
    protected $_itemPosition = null;

    /**
     * Data shared between all instances of current container
     *
     * @var null|array
     */
    protected static $_sharedInfoData = array(
        self::BLOCK_NAME_RELATED => array(
            'first'  => true,
            'info'   => null,
        ),
        self::BLOCK_NAME_UPSELL => array(
            'first'  => true,
            'info'   => null,
        ),
    );

    /**
     * Get parent block type
     *
     * @return null|string
     */
    protected function _getListBlockType()
    {
        $blockName = $this->_placeholder->getName();
        if ($blockName == self::BLOCK_NAME_RELATED) {
            return 'enterprise_targetrule/catalog_product_list_related';
        } elseif ($blockName == self::BLOCK_NAME_UPSELL) {
            return 'enterprise_targetrule/catalog_product_list_upsell';
        }

        return null;
    }

    /**
     * Returns cache identifier for informational data about product lists
     *
     * @return string
     */
    protected function _getInfoCacheId()
    {
        return 'CATALOG_PRODUCT_LIST_SHARED_'
            . md5($this->_placeholder->getName());
    }

    /**
     * Saves informational cache, containing parameters used to show lists.
     *
     * @return Enterprise_PageCache_Model_Container_CatalogProductItem
     */
    protected function _saveInfoCache()
    {
        $placeholderName = $this->_placeholder->getName();
        if (is_null(self::$_sharedInfoData[$placeholderName]['info'])) {
            return $this;
        }

        $data = serialize(self::$_sharedInfoData[$placeholderName]['info']);
        $id = $this->_getInfoCacheId();
        $tags = array(Enterprise_PageCache_Model_Processor::CACHE_TAG);
        $lifetime = $this->_placeholder->getAttribute('cache_lifetime');
        if (!$lifetime) {
            $lifetime = false;
        }
        Enterprise_PageCache_Model_Cache::getCacheInstance()->save($data, $id, $tags, $lifetime);
        return $this;
    }

    /**
     * Get shared info param
     *
     * @param string|null $key
     * @return mixed
     */
    protected function _getSharedParam($key = null)
    {
        $placeholderName = $this->_placeholder->getName();
        $info = self::$_sharedInfoData[$placeholderName]['info'];
        if (is_null($info)) {
            $infoCacheId = $this->_getInfoCacheId();
            $data = Enterprise_PageCache_Model_Cache::getCacheInstance()->load($infoCacheId);
            $info = $data ? unserialize($data) : array();
            self::$_sharedInfoData[$placeholderName]['info'] = $info;
        }

        if (is_null($key)) {
            return $info;
        }

        return isset($info[$key]) ? $info[$key] : null;
    }

    /**
     * Set shared info param
     *
     * @param string $key
     * @param mixed $value
     * @return Enterprise_PageCache_Model_Container_CatalogProductItem
     */
    protected function _setSharedParam($key, $value)
    {
        $placeholderName = $this->_placeholder->getName();
        if (is_null(self::$_sharedInfoData[$placeholderName]['info'])) {
            $this->_getSharedParam();
        }
        self::$_sharedInfoData[$placeholderName]['info'][$key] = $value;

        return $this;
    }

    /**
     * Get parent (container) block
     *
     * @return false|Enterprise_TargetRule_Block_Catalog_Product_List_Abstract
     */
    protected function _getParentBlock()
    {
        if (is_null($this->_parentBlock)) {
            $blockType = $this->_getListBlockType();
            $this->_parentBlock = $blockType ? Mage::app()->getLayout()->createBlock($blockType) : false;
        }

        return $this->_parentBlock;
    }

    /**
     * Get next item id
     *
     * @return int|null
     */
    protected function _getItemId()
    {
        if (is_null($this->_itemId)) {
            // get all ids
            $ids = $this->_getSharedParam('ids');
            if (!$ids && !is_array($ids)) {
                $parentBlock = $this->_getParentBlock();
                if ($parentBlock) {
                    $productId = $this->_getProductId();
                    if ($productId && !Mage::registry('product')) {
                        $product = Mage::getModel('catalog/product')
                            ->setStoreId(Mage::app()->getStore()->getId())
                            ->load($productId);
                        if ($product) {
                            Mage::register('product', $product);
                        }
                    }
                    $ids = Mage::registry('product') ? $parentBlock->getAllIds() : array();
                    $this->_setSharedParam('shuffled', $parentBlock->isShuffled());
                }
                if (!$ids) {
                    $ids = array();
                }
                $this->_setSharedParam('ids', $ids);
            }

            // preparations for first container
            $placeholderName = $this->_placeholder->getName();
            if (self::$_sharedInfoData[$placeholderName]['first']) {
                self::$_sharedInfoData[$placeholderName]['first'] = false;
                if (!isset(self::$_sharedInfoData[$placeholderName]['cursor'])) {
                    self::$_sharedInfoData[$placeholderName]['cursor'] = 0;
                }
                // check for shuffled
                if ($this->_getSharedParam('shuffled') && !empty($ids)) {
                    shuffle($ids);
                    $this->_setSharedParam('ids', $ids);
                }
            }

            if (is_null($this->_itemPosition)) {
                $this->_itemPosition = self::$_sharedInfoData[$placeholderName]['cursor'];
            }

            $this->_itemId = isset($ids[$this->_itemPosition]) ? $ids[$this->_itemPosition] : false;
        }

        return $this->_itemId;
    }

    /**
     * Pop current item id
     *
     * @return int
     */
    protected function _popItem()
    {
        if (!isset(self::$_sharedInfoData[$this->_placeholder->getName()]['cursor'])) {
            self::$_sharedInfoData[$this->_placeholder->getName()]['cursor'] = 0;
        }
        if (is_null($this->_itemPosition)) {
            $this->_itemPosition = self::$_sharedInfoData[$this->_placeholder->getName()]['cursor'];
        }
        return self::$_sharedInfoData[$this->_placeholder->getName()]['cursor']++;
    }

    /**
     * Generate and apply container content in controller after application is initialized
     *
     * @param string $content
     * @return bool
     */
    public function applyInApp(&$content)
    {
        $result = parent::applyInApp($content);
        $this->_saveInfoCache();
        return $result;
    }

    /**
     * Check if could be applied without application
     *
     * @param string $content
     * @return bool
     */
    public function applyWithoutApp(&$content)
    {
        // check if item ids were not generated before
        $ids = $this->_getSharedParam('ids');
        if (is_null($ids)) {
            $this->_popItem();
            return false;
        }

        $result = parent::applyWithoutApp($content);
        $this->_popItem();

        return $result;
    }


    /**
     * Get container individual additional cache id
     *
     * @return string | false
     */
    protected function _getAdditionalCacheId()
    {
        return md5('PRODUCT_ITEM_' . $this->_getItemId());
    }

    /**
     * Get cache identifier
     *
     * @return string
     */
    protected function _getCacheId()
    {
        return md5('CONTAINER_CATALOG_PRODUCT_LIST_' . $this->_getListBlockType());
    }

    /**
     * Render element that was not cached
     *
     * @return false|string
     */
    protected function _renderBlock()
    {
        $itemId = $this->_getItemId();
        if (!$itemId) {
            return '';
        }

        $item = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($itemId);

        $block = $this->_placeholder->getAttribute('block');
        $template = $this->_placeholder->getAttribute('template');

        $block = new $block;
        $block->setTemplate($template);
        $block->setLayout(Mage::app()->getLayout());
        $block->setItem($item);
        Mage::dispatchEvent('render_block', array('block' => $block, 'placeholder' => $this->_placeholder));

        return $block->toHtml();
    }
}
