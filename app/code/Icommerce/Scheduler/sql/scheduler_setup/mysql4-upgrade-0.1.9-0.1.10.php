<?php

$installer = $this;
$installer->startSetup();
$installer->run("


ALTER TABLE {$installer->getTable('icommerce_scheduler_operation')}
	ADD `master_id` INT  NULL  DEFAULT 0,
	ADD `master_order` INT  NULL  DEFAULT NULL;

");

$installer->endSetup();