<?php
/**
 * Copyright (c) 2009-2013 Vaimo AB
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
 * @copyright   Copyright (c) 2009-2013 Vaimo AB
 * @author      Urmo Schmidt
 */

header('Content-Type: text/plain; charset=utf-8');
ini_set('memory_limit', '512M');

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;

require '../../app/bootstrap.php';
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

// why is customer/session here? because in enterprise, product delete causes customer/session to load,
// which will throw exception: headers already sent, if there is anything already sent,
// and in here we are continuously outputting stuff
//Mage::getSingleton('customer/session');

//$limit = Mage::app()->getRequest()->getParam('max_count', 0);
//$operationId = Mage::app()->getRequest()->getParam('operation_id', 0);

/** @var $import Vaimo_IntegrationBase_Model_Import_Product */
$import = $bootstrap->getObjectManager()->create('Vaimo\IntegrationBase\Model\Import\Product');
$import->setObjectManager($bootstrap->getObjectManager());
//$import = Mage::getSingleton('integrationbase/import_product');
$import->run(100, 1);

$bootstrap->run($app);