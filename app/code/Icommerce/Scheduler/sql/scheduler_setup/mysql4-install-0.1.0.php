<?php

$installer = $this;
$installer->startSetup();
$installer->run("

CREATE TABLE {$installer->getTable('icommerce_scheduler_operation')} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Id',
  `code` varchar(100) NOT NULL COMMENT 'Code',
  `name` varchar(255) NOT NULL COMMENT 'Name',
  `comment` varchar(255) DEFAULT NULL COMMENT 'Comment',
  `status` smallint(6) NOT NULL COMMENT 'Status',
  `recurrence_info` text COMMENT 'Recurrence Information',
  `next_run` datetime NOT NULL COMMENT 'Next Run Time',
  `last_run` datetime NOT NULL COMMENT 'Last Run Time',
  `last_status` smallint(6) NOT NULL COMMENT 'Last Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Icommerce Scheduler Operations';


CREATE TABLE {$installer->getTable('icommerce_scheduler_history')} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Id',
  `operation_id` int(10) unsigned NOT NULL COMMENT 'Operation Id',
  `created_at` datetime NOT NULL COMMENT 'Created',
  `finished_at` datetime NOT NULL COMMENT 'Finished',
  `status` smallint(6) NOT NULL COMMENT 'Status',
  `message` varchar(255) DEFAULT NULL COMMENT 'Message',
  `result` mediumblob COMMENT 'Result',
  PRIMARY KEY (`id`),
  KEY `FK_ICOMMERCE_SCHEDULER_HISTORY_OPERATION` (`operation_id`),
  CONSTRAINT `FK_ICOMMERCE_SCHEDULER_HISTORY_OPERATION` FOREIGN KEY (`operation_id`) REFERENCES `icommerce_scheduler_operation` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Icommerce Scheduler History';

");

$installer->endSetup();