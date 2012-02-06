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
 * @package     Enterprise_CustomerSegment
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * CustomerSegment data resource model
 *
 * @category    Enterprise
 * @package     Enterprise_CustomerSegment
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_CustomerSegment_Model_Resource_Segment extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Segment websites table name
     *
     * @var string
     */
    protected $_websiteTable;

    /**
     * Intialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('enterprise_customersegment/segment', 'segment_id');
        $this->_websiteTable = $this->getTable('enterprise_customersegment/website');
    }

    /**
     * Get website ids associated to the segment id
     *
     * @param int $segmentId
     * @return array
     */
    public function getWebsiteIds($segmentId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->_websiteTable, 'website_id')
            ->where('segment_id = :segment_id');
        return $this->_getReadAdapter()->fetchCol($select, array(':segment_id' => $segmentId));
    }

    /**
     * Perform actions after object save
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Enterprise_CustomerSegment_Model_Resource_Segment
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $id = $object->getId();
        $this->_getWriteAdapter()->delete(
            $this->getTable('enterprise_customersegment/event'),
            array('segment_id = ?' => $id)
        );
        if ($object->getMatchedEvents() && is_array($object->getMatchedEvents())) {
            foreach ($object->getMatchedEvents() as $event) {
                $data = array(
                    'segment_id' => $id,
                    'event'      => $event,
                );
                $this->_getWriteAdapter()->insert($this->getTable('enterprise_customersegment/event'), $data);
            }
        }

        if ($object->hasData('website_ids')) {
            $this->_saveWebsiteIds($object);
        }

        return parent::_afterSave($object);
    }

    /**
     * Save all website ids associated to segment
     *
     *
     * @param Enterprise_CustomerSegment_Model_Segment $segment
     * @return Enterprise_CustomerSegment_Model_Resource_Segment
     */
    protected function _saveWebsiteIds($segment)
    {
        $adapter = $this->_getWriteAdapter();
        $adapter->delete($this->_websiteTable, array('segment_id=?'=>$segment->getId()));
        foreach ($segment->getWebsiteIds() as $websiteId) {
            $adapter->insert($this->_websiteTable, array(
                'website_id' => $websiteId,
                'segment_id' => $segment->getId()
            ));
        }
        return $this;
    }

    /**
     * Get select query result
     *
     * @param Varien_Db_Select|string $sql
     * @param array $bindParams array of binded variables
     * @return int
     */
    public function runConditionSql($sql, $bindParams)
    {
        return $this->_getReadAdapter()->fetchOne($sql, $bindParams);
    }

    /**
     * Get empty select object
     *
     * @return Varien_Db_Select
     */
    public function createSelect()
    {
        return $this->_getReadAdapter()->select();
    }

    /**
     * Quote parameters into condition string
     *
     * @param string $string
     * @param string | array $param
     * @return string
     */
    public function quoteInto($string, $param)
    {
        return $this->_getReadAdapter()->quoteInto($string, $param);
    }

    /**
     * Get comparison condition for rule condition operator which will be used in SQL query
     * depending of database we using
     *
     * @param string $operator
     * @return string
     */
    public function getSqlOperator($operator)
    {
        return Mage::getResourceHelper('enterprise_customersegment')
                ->getSqlOperator($operator);
    }

    /**
     * Create string for select "where" condition based on field name, comparison operator and field value
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return string
     */
    public function createConditionSql($field, $operator, $value)
    {
        $sqlOperator = $this->getSqlOperator($operator);
        $condition = '';

        if (!is_array($value)) {
            $prepareValues = explode(',', $value);
            if (count($prepareValues) <= 1) {
                $value = $prepareValues[0];
            } else {
                $value = array();
                foreach ($prepareValues as $val) {
                    $value[] = trim($val);
                }
            }
        }
        switch ($operator) {
            case '{}':
            case '!{}':
                if (is_array($value)) {
                    if (!empty($value)) {
                        $condition = array();
                        foreach ($value as $val) {
                            $condition[] = $this->_getReadAdapter()->quoteInto(
                                $field . ' ' . $sqlOperator . ' ?', '%' . $val . '%'
                            );
                        }
                        $condition = implode(' AND ', $condition);
                    }
                } else {
                    $condition = $this->_getReadAdapter()->quoteInto(
                        $field . ' ' . $sqlOperator . ' ?', '%' . $value . '%'
                    );
                }
                break;
            case '()':
            case '!()':
                if (is_array($value) && !empty($value)) {
                    $condition = $this->_getReadAdapter()->quoteInto(
                        $field . ' ' . $sqlOperator . ' (?)', $value
                    );
                }
                break;
            case '[]':
            case '![]':
                if (is_array($value) && !empty($value)) {
                    $conditions = array();
                    foreach ($value as $v) {
                        $conditions[] = $this->_getReadAdapter()->prepareSqlCondition(
                            $field, array('finset' => $this->_getReadAdapter()->quote($v))
                        );
                    }
                    $condition  = sprintf('(%s)=%d', join(' AND ', $conditions), $operator == '[]' ? 1 : 0);
                } else {
                    if ($operator == '[]') {
                        $condition = $this->_getReadAdapter()->prepareSqlCondition(
                            $field, array('finset' => $this->_getReadAdapter()->quote($value))
                        );
                    } else {
                        $condition = 'NOT (' . $this->_getReadAdapter()->prepareSqlCondition(
                            $field, array('finset' => $this->_getReadAdapter()->quote($value))
                        ) . ')';
                    }
                }
                break;
            case 'between':
                $condition = $field . ' ' . sprintf($sqlOperator,
                    $this->_getReadAdapter()->quote($value['start']), $this->_getReadAdapter()->quote($value['end']));
                break;
            default:
                $condition = $this->_getReadAdapter()->quoteInto($field . ' ' . $sqlOperator . ' ?', $value);
                break;
        }
        return $condition;
    }

    /**
     * Delete association between customer and segment for specific segment
     *
     * @param Enterprise_CustomerSegment_Model_Segment $segment
     * @return Enterprise_CustomerSegment_Model_Resource_Segment
     */
    public function deleteSegmentCustomers($segment)
    {
        $this->_getWriteAdapter()->delete(
            $this->getTable('enterprise_customersegment/customer'),
            array('segment_id=?' => $segment->getId())
        );
        return $this;
    }

    /**
     * Save customer ids mutched by segment SQL select on specific website
     *
     * @param Enterprise_CustomerSegment_Model_Segment $segment
     * @param int $websiteId
     * @param string $select
     * @return Enterprise_CustomerSegment_Model_Resource_Segment
     */
    public function saveCustomersFromSelect($segment, $websiteId, $select)
    {
        $table = $this->getTable('enterprise_customersegment/customer');
        $adapter = $this->_getWriteAdapter();
        $segmentId = $segment->getId();
        $now = $this->formatDate(time());

        $data = array();
        $count= 0;
        $stmt = $adapter->query($select);
        while ($row = $stmt->fetch()) {
            $data[] = array(
                'segment_id'    => $segmentId,
                'customer_id'   => $row['entity_id'],
                'website_id'    => $websiteId,
                'added_date'    => $now,
                'updated_date'  => $now,
            );
            $count++;
            if ($count>1000) {
                $count = 0;
                $adapter->insertMultiple($table, $data);
                $data = array();
            }
        }
        if ($count>0) {
            $adapter->insertMultiple($table, $data);
        }
        return $this;
    }

    /**
     * Count customers in specified segments
     *
     * @param int $segmentId
     * @return int
     */
    public function getSegmentCustomersQty($segmentId)
    {
        $adapter = $this->_getReadAdapter();
        return (int)$adapter->fetchOne(
            $adapter->select()
                ->from($this->getTable('enterprise_customersegment/customer'), array('COUNT(DISTINCT customer_id)'))
                ->where('segment_id = :segment_id'),
            array(':segment_id' => (int)$segmentId)
        );
    }

    /**
     * save CustomerSegments from select
     *
     * @deprecated after 1.6.0.0 - please use saveCustomersFromSelect
     *
     * @param Enterprise_CustomerSegment_Model_Segment $segment
     * @param Varien_Db_Select $select
     * @return Enterprise_CustomerSegment_Model_Resource_Segment
     */
    public function saveSegmentCustomersFromSelect($segment, $select)
    {
        $table = $this->getTable('enterprise_customersegment/customer');
        $adapter = $this->_getWriteAdapter();
        $segmentId = $segment->getId();
        $now = $this->formatDate(time());

        $adapter->delete($table, $adapter->quoteInto('segment_id=?', $segmentId));

        $data = array();
        $count= 0;
        $stmt = $adapter->query($select);
        while ($row = $stmt->fetch()) {
            $data[] = array(
                'segment_id'    => $segmentId,
                'customer_id'   => $row['entity_id'],
                'added_date'    => $now,
                'updated_date'  => $now,
            );
            $count++;
            if ($count>1000) {
                $count = 0;
                $adapter->insertMultiple($table, $data);
                $data = array();
            }
        }
        if ($count>0) {
            $adapter->insertMultiple($table, $data);
        }
        return $this;
    }
}
