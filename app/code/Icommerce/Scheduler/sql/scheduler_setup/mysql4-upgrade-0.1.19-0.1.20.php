<?php

$installer = $this;
$installer->startSetup();
$installer->run("


ALTER TABLE {$installer->getTable('icommerce_scheduler_operation')}
	ADD `url_override` VARCHAR(60)   DEFAULT NULL  COMMENT 'Select another URL than default';

");

$installer->endSetup();