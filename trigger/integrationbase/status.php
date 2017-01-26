<?php
/**
 * Copyright (c) 2009-2014 Vaimo AB
 *
 * Vaimo reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of Vaimo, except as provided by licence. A licence
 * under Vaimo's rights in the Program may be available directly from
 * Vaimo.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    Vaimo
 * @package     Vaimo_IntegrationBase
 * @copyright   Copyright (c) 2009-2014 Vaimo AB
 * @author      Urmo Schmidt <urmo.schmidt@vaimo.com>
 */

header('Content-Type: text/plain; charset=utf-8');
ini_set('memory_limit', '1024M');

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;

require  '../../app/bootstrap.php';
/* set Parameters for application */

$opt['group'] = 'default';
$opt['standaloneProcessStarted'] = '0';
$params = $_SERVER;
/* you can out your store id here */
$params[StoreManager::PARAM_RUN_CODE] = 'admin';
$params[Store::CUSTOM_ENTRY_POINT_PARAM] = true;

/* create application */
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);
/* the applocation
/** @var \Magento\Framework\App\Cron $app */

$app = $bootstrap->createApplication('Magento\Framework\App\Cron', ['parameters' => $opt]);

$_SERVER['SCRIPT_NAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_NAME']);
$_SERVER['SCRIPT_FILENAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_FILENAME']);

//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

/** @var Mage_Core_Model_Resource $resource */
//$resource = Mage::getSingleton('core/resource');

/** @var Varien_Db_Adapter_Pdo_Mysql $read */
//$read = $resource->getConnection('core_read');

$tables = array(
    'integrationbase/attribute' => 'Attributes',
    'integrationbase/creditmemo' => 'Credit Memos',
    'integrationbase/file' => 'Files',
    'integrationbase/invoice' => 'Invoices',
    'integrationbase/link' => 'Links',
    'integrationbase/price' => 'Prices',
    'integrationbase/product' => 'Products',
    'integrationbase/shipment' => 'Shipments',
    'integrationbase/stock' => 'Stock',
);

foreach ($tables as $table => $name) {
    $sql = $read->select()
        ->from($resource->getTableName($table), array('row_status', 'cnt' => 'COUNT(id)'))
        ->group('row_status');

    if ($result = $read->fetchAssoc($sql)) {
        echo $name . "\n------------------\n";

        foreach ($result as $row) {
            echo $row['row_status'] . "\t" . str_pad($row['cnt'], 10, ' ', STR_PAD_LEFT) . "\n";
        }

        echo "------------------\n\n";
    }
}

$bootstrap->run($app);