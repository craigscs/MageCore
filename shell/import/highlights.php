<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);

$obj = $bootstrap->getObjectManager();
// Set the state (not sure if this is neccessary)
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$pr = $obj->create('Magento\Catalog\Model\ProductRepository');
$file = fopen('shell/import/highlights.csv', 'r');
$c = 0;
$file2 =fopen('shell/import/links.csv', 'r');
$links = array();
while (($rowData = fgetcsv($file2, 4096)) !== false)
{
    $links[$rowData[3]] = $rowData[2];
}
while (($row = fgetcsv($file, 4096)) !== false)
{
    if ($c ==0) {
        $c++;
        continue;
    }
    if(!isset($productData[$row[2]]))
    {
        $productData[$row[2]] = array();
    }
        $productData[$row[2]][$row[3]] = $row[4];
}
fclose($file);
foreach ($productData as $sku => $value) {
    if (isset($links[$sku])) {
        $sku = $links[$sku];
    }
    try {
        $p = $pr->get($sku);
        $p->setData('highlights', json_encode($value));
        $p->getResource()->saveAttribute($p, 'highlights');
        printf("SKU " . $sku . " saved.\n");
    } catch (\Exception $e) {
        printf($e->getMessage()."\n");
    }
}