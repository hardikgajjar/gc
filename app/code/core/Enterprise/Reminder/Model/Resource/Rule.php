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
 * @package     Enterprise_Reminder
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Reminder data resource model
 *
 * @category    Enterprise
 * @package     Enterprise_Reminder
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Reminder_Model_Resource_Rule extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Rule websites table name
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
        $this->_init('enterprise_reminder/rule', 'rule_id');
        $this->_websiteTable = $this->getTable('enterprise_reminder/website');
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
     * @deprecated after 1.10.0.0 - please use quoteInto of current adapter
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
     * Prepare object data for saving
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (!$object->getActiveFrom()) {
            $object->setActiveFrom(new Zend_Db_Expr('NULL'));
        } else {
            if ($object->getActiveFrom() instanceof Zend_Date) {
                $object->setActiveFrom($object->getActiveFrom()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
            }
        }

        if (!$object->getActiveTo()) {
            $object->setActiveTo(new Zend_Db_Expr('NULL'));
        } else {
            if ($object->getActiveTo() instanceof Zend_Date) {
                $object->setActiveTo($object->getActiveTo()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
            }
        }
        parent::_beforeSave($object);
    }

    /**
     * Perform actions after object save
     * return Mage_Core_Model_Mysql4_Abstract
     *
     * @param Mage_Core_Model_Abstract $rule
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _afterSave(Mage_Core_Model_Abstract $rule)
    {
        if ($rule->hasData('website_ids')) {
            $this->_saveWebsiteIds($rule);
        }
        if ($rule->hasData('store_templates')) {
            $this->_saveStoreData($rule);
        }
        return parent::_afterSave($rule);
    }

    /**
     * Save all website ids associated to rule
     *
     *
     * @param Mage_Core_Model_Abstract $rule
     * @return Enterprise_Reminder_Model_Resource_Rule
     */
    protected function _saveWebsiteIds($rule)
    {
        $adapter = $this->_getWriteAdapter();
        $adapter->delete($this->_websiteTable, array('rule_id = ?' => $rule->getId()));
        $insertData = array();
        foreach ($rule->getWebsiteIds() as $websiteId) {
            $insertData[] = array(
                'website_id' => $websiteId,
                'rule_id'    => $rule->getId()
            );
        }
        $adapter->insertMultiple($this->_websiteTable, $insertData);

        return $this;
    }

    /**
     * Get website ids associated to the rule id
     *
     * @param int $ruleId
     * @return array
     */
    public function getWebsiteIds($ruleId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->_websiteTable, 'website_id')
            ->where('rule_id = :rule_id');
        return $this->_getReadAdapter()->fetchCol($select, array('rule_id' => $ruleId));
    }

    /**
     * Save store templates
     *
     *
     * @param Mage_Core_Model_Abstract $rule
     * @return Enterprise_Reminder_Model_Resource_Rule
     */
    protected function _saveStoreData($rule)
    {
        $adapter = $this->_getWriteAdapter();
        $templateTable = $this->getTable('enterprise_reminder/template');
        $adapter->delete($templateTable, array('rule_id = ?' => $rule->getId()));

        $labels = $rule->getStoreLabels();
        $descriptions = $rule->getStoreDescriptions();
        $insertData = array();
        foreach ($rule->getStoreTemplates() as $storeId => $templateId) {
            if (!$templateId) {
                continue;
            }
            if (!is_numeric($templateId)) {
                $templateId = null;
            }
            $insertData[] = array(
                'rule_id'     => $rule->getId(),
                'store_id'    => $storeId,
                'template_id' => $templateId,
                'label'       => $labels[$storeId],
                'description' => $descriptions[$storeId]
            );
        }

        if (!empty($insertData)) {
            $adapter->insertMultiple($templateTable, $insertData);
        }

        return $this;
    }

    /**
     * Get store data assigned to reminder rule
     *
     * @param int $ruleId
     * @return array
     */
    public function getStoreData($ruleId)
    {
        $templateTable = $this->getTable('enterprise_reminder/template');
        $select = $this->createSelect()
            ->from($templateTable, array('store_id', 'template_id', 'label', 'description'))
            ->where('rule_id = :rule_id');
        return $this->_getReadAdapter()->fetchAll($select, array('rule_id' => $ruleId));
    }

    /**
     * Get store data (labels and descriptions) assigned to reminder rule.
     * If labels and descriptions are not specified it will be replaced with default values.
     *
     * @param int $ruleId
     * @param int $storeId
     * @return array
     */
    public function getStoreTemplateData($ruleId, $storeId)
    {
        $templateTable = $this->getTable('enterprise_reminder/template');
        $ruleTable = $this->getTable('enterprise_reminder/rule');
        $adapter = $this->_getReadAdapter();

        $select = $this->createSelect()
            ->from(
                array('t' => $templateTable),
                array(
                    'template_id',
                    'label' =>
                        $adapter->getCheckSql('t.label IS NOT NULL', 't.label', 'r.default_label'),
                    'description' =>
                        $adapter->getCheckSql('t.description IS NOT NULL', 't.description', 'r.default_description')
                )
            )
            ->join(
                array('r' => $ruleTable),
                'r.rule_id = t.rule_id',
                array()
            );

        $select->where('t.rule_id = :rule_id');
        $select->where('t.store_id = :store_id');

        return $adapter->fetchRow($select, array('rule_id' => $ruleId, 'store_id' => $storeId));
    }

    /**
     * Get comparison condition for rule condition operator which will be used in SQL query
     *
     * @param string $operator
     * @return string
     */
    public function getSqlOperator($operator)
    {
        switch ($operator) {
            case '==':
                return '=';
            case '!=':
                return '<>';
            case '{}':
                return 'LIKE';
            case '!{}':
                return 'NOT LIKE';
            case 'between':
                return 'BETWEEN %s AND %s';
            case '>':
            case '<':
            case '>=':
            case '<=':
                return $operator;
            default:
                Mage::throwException(Mage::helper('enterprise_reminder')->__('Unknown operator specified.'));
        }
    }

    /**
     * Create string for select "where" condition based on field name, comparison operator and vield value
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return string
     */
    public function createConditionSql($field, $operator, $value)
    {
        $sqlOperator = $this->getSqlOperator($operator);
        $adapter = $this->_getReadAdapter();

        $condition = '';
        switch ($operator) {
            case '{}':
            case '!{}':
                if (is_array($value)) {
                    if (!empty($value)) {
                        $sqlOperator = ($operator == '{}') ? 'IN' : 'NOT IN';
                        $condition = $adapter->quoteInto($field . ' ' . $sqlOperator . ' (?)', $value);
                    }
                } else {
                    $condition = $adapter->quoteInto($field. ' ' . $sqlOperator . ' ?', '%' . $value . '%');
                }
                break;
            case 'between':
                $condition = $field . ' ' . sprintf($sqlOperator,
                    $adapter->quote($value['start']), $adapter->quote($value['end']));
                break;
            default:
                $condition = $adapter->quoteInto($field . ' ' . $sqlOperator . ' ?', $value);
                break;
        }
        return $condition;
    }

    /**
     * Deactivate already matched customers before new matching process
     *
     * @param int $ruleId
     * @return Enterprise_Reminder_Model_Resource_Rule
     */
    public function deactivateMatchedCustomers($ruleId)
    {
        $this->_getWriteAdapter()->update(
            $this->getTable('enterprise_reminder/coupon'),
            array('is_active' => '0'),
            array('rule_id = ?' => $ruleId)
        );
        return $this;
    }

    /**
     * Try to associate reminder rule with matched customers.
     * If customer was added earlier, update is_active column.
     *
     * @param Enterprise_Reminder_Model_Rule $rule
     * @param Mage_SalesRule_Model_Rule $salesRule
     * @param int $websiteId
     * @param int $threshold
     * @return Enterprise_Reminder_Model_Resource_Rule
     */
    public function saveMatchedCustomers($rule, $salesRule, $websiteId, $threshold = null)
    {
        $rule->setConditions(null);
        $rule->afterLoad();
        $select = $rule->getConditions()->getConditionsSql(null, $websiteId);

        if (!$rule->getConditionSql()) {
            return $this;
        }

        if ($threshold) {
            $select->where('c.emails_failed IS NULL OR c.emails_failed < ? ', $threshold);
        }

        $i = 0;
        $ruleId = $rule->getId();
        $adapter = $this->_getWriteAdapter();
        $currentDate = $this->formatDate(time());
        $dataToInsert = array();

        $stmt = $adapter->query($select, array('rule_id' => $ruleId));

        try {
            $adapter->beginTransaction();

            while ($row = $stmt->fetch()) {
                if (empty($row['coupon_id']) && $salesRule) {
                    $coupon = $salesRule->acquireCoupon();
                    $couponId = ($coupon !== null) ? $coupon->getId() : null;
                } else {
                    $couponId = $row['coupon_id'];
                }

                $dataToInsert[] = array(
                    'rule_id'       => $ruleId,
                    'coupon_id'     => $couponId,
                    'customer_id'   => $row['entity_id'],
                    'associated_at' => $currentDate,
                    'is_active'     => '1'
                );
                $i++;

                if (($i % 1000) == 0) {
                    $this->_saveMatchedCustomerData($dataToInsert);
                    $adapter->commit();
                    $adapter->beginTransaction();
                    $dataToInsert = array();
                }
            }
            $this->_saveMatchedCustomerData($dataToInsert);
            $adapter->commit();

        } catch (Exception $e) {
            $adapter->rollBack();
            throw $e;
        }

        return $this;
    }

    /**
     * Save data by matched customer coupons
     *
     * @param array $data
     */
    protected function _saveMatchedCustomerData($data)
    {
        if ($data) {
            $table = $this->getTable('enterprise_reminder/coupon');
            $this->_getWriteAdapter()->insertOnDuplicate($table, $data, array('is_active'));
        }
    }

    /**
     * Return list of customers for notification process.
     * This process can be initialized system cron or by admin for some rule
     *
     * @param int|null $limit
     * @param int|null $ruleId
     * @return array
     */
    public function getCustomersForNotification($limit = null, $ruleId = null)
    {
        $adapter     = $this->_getReadAdapter();
        $couponTable = $this->getTable('enterprise_reminder/coupon');
        $ruleTable   = $this->getTable('enterprise_reminder/rule');
        $logTable    = $this->getTable('enterprise_reminder/log');

        $currentDate = $this->formatDate(time());

        $select = $this->createSelect()->from(
            array('c' => $couponTable),
            array('customer_id', 'coupon_id', 'rule_id')
        );

        $select->join(
            array('r' => $ruleTable),
            'c.rule_id = r.rule_id',
            array('schedule' => 'schedule')
        );

        $select->joinLeft(
            array('l' => $logTable),
            'c.rule_id = l.rule_id AND c.customer_id = l.customer_id',
            array()
        );

        if ($ruleId) {
            $select->where('c.rule_id = ?', $ruleId);
        }

        $select->where('c.is_active = 1');
        $select->group(array('c.customer_id', 'c.rule_id'));
        $select->columns(array(
            'log_sent_at_max' => 'MAX(l.sent_at)',
            'log_sent_at_min' => 'MIN(l.sent_at)'
        ));

        $_helper = Mage::getResourceHelper('enterprise_reminder');
        $findInSetSql = $adapter->prepareSqlCondition(
            'schedule',
            array('finset' => $_helper->getDateDiff('log_sent_at_min', $adapter->formatDate($currentDate)))
        );
        $select->having('log_sent_at_max IS NULL OR (' . $findInSetSql . ' AND '
            . $_helper->getDateDiff('log_sent_at_max', $adapter->formatDate($currentDate)) . ' = 0)');

        if ($limit) {
            $select->limit($limit);
        }

        $sql = $_helper->getQueryUsingAnalyticFunction($select);
        return $adapter->fetchAll($sql);
    }

    /**
     * Add notification log row after letter was successfully sent.
     *
     * @param int $ruleId
     * @param int $customerId
     * @return Enterprise_Reminder_Model_Resource_Rule
     */
    public function addNotificationLog($ruleId, $customerId)
    {
        $data = array(
            'rule_id'     => $ruleId,
            'customer_id' => $customerId,
            'sent_at'     => $this->formatDate(time())
        );

        $this->_getWriteAdapter()->insert($this->getTable('enterprise_reminder/log'), $data);
        return $this;
    }

    /**
     * Update failed email counter.
     *
     * @param int $ruleId
     * @param int $customerId
     * @return Enterprise_Reminder_Model_Resource_Rule
     */
    public function updateFailedEmailsCounter($ruleId, $customerId)
    {
        $this->_getWriteAdapter()->update($this->getTable('enterprise_reminder/coupon'),
            array('emails_failed' => new Zend_Db_Expr('emails_failed + 1')),
            array('rule_id = ?'   => $ruleId, 'customer_id = ?' => $customerId)
        );
        return $this;
    }

    /**
     * Return count of reminder rules assigned to specified sales rule.
     *
     * @param int $salesRuleId
     * @return int
     */
    public function getAssignedRulesCount($salesRuleId)
    {
        $select = $this->createSelect()->from(
            array('r' => $this->getTable('enterprise_reminder/rule')),
            array(new Zend_Db_Expr('count(1)'))
        );
        $select->where('r.salesrule_id = :salesrule_id');
        return $this->_getReadAdapter()->fetchOne($select, array('salesrule_id' => $salesRuleId));
    }
}
