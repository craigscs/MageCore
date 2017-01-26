<?php

$installer = $this;
$installer->startSetup();
$installer->run("


ALTER TABLE {$installer->getTable('icommerce_scheduler_operation')}
	ADD `save_history` VARCHAR(60)  NOT NULL  DEFAULT '0,1,2,3,4',
	ADD `fail_cnt` INT  NULL  DEFAULT 0;

");

$installer->endSetup();