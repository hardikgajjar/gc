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
 * @package     Mage_Cron
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

$installer = $this;

$installer->startSetup();

$installer->run("

SET NAMES utf8;

SET SQL_MODE='';

SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

/*Table structure for table `cron_schedule` */

-- DROP TABLE IF EXISTS {$this->getTable('cron_schedule')};

CREATE TABLE {$this->getTable('cron_schedule')} (
  `schedule_id` int(10) unsigned NOT NULL auto_increment,
  `task_name` int(10) unsigned NOT NULL default '0',
  `schedule_status` tinyint(4) NOT NULL default '0',
  `schedule_type` tinyint(4) NOT NULL default '0',
  `schedule_cmd` text NOT NULL,
  `schedule_comments` text NOT NULL,
  `created_at` datetime NOT NULL default '0000-00-00 00:00:00',
  `scheduled_at` datetime NOT NULL default '0000-00-00 00:00:00',
  `executed_at` datetime NOT NULL default '0000-00-00 00:00:00',
  `finished_at` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`schedule_id`),
  KEY `task_name` (`task_name`),
  KEY `scheduled_at` (`scheduled_at`,`schedule_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `cron_schedule` */

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;

    ");

$installer->endSetup();