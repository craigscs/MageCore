<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);

$obj = $bootstrap->getObjectManager();
// Set the state (not sure if this is neccessary)
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');
$pr = $obj->create('Magento\Catalog\Model\ProductRepository');

$import = $obj->create('Gradus\Importer\Model\Imports');
$message = '';
$run = 'features.php';
$import->setData('process', $run);
$import->setData('succeeded', 1);

$file = fopen('shell/import/features.csv', 'r');
$c = 0;

$file2 =fopen('shell/import/links.csv', 'r');
$links = array();
while (($rowData = fgetcsv($file2, 4096)) !== false)
{
    $links[$rowData[3]] = $rowData[2];
}
while (($rowData = fgetcsv($file, 4096)) !== false)
{
    if ($c ==0) {
        $c++;
        continue;
    }
    if (!isset($productData[$rowData[2]])) {
        $productData[$rowData[2]] = array();
    }
    $productData[$rowData[2]][$rowData[3]] = array(
        "name" => $rowData[4] ?: "",
        "desc" => $rowData[6]
    );
}
fclose($file);
foreach ($productData as $sku => $value) {
    if (isset($links[$sku])) {
        $sku = $links[$sku];
    }
    try {
        $p = $pr->get($sku);
        $p->setData('features', json_encode($value));
        $p->getResource()->saveAttribute($p, 'features');
        printf("SKU " . $sku . " saved.\n");
        $message .= "SKU " . $sku . " saved";
    } catch (\Exception $e) {
        printf($e->getMessage()."\n");
        $message .= $e->getMessage();
    }
}
$import->setData('message', $message);
$import->save();