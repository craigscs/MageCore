<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);

$obj = $bootstrap->getObjectManager();
// Set the state (not sure if this is neccessary)
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$pr = $obj->create('Magento\Catalog\Model\ProductRepository');
$file = fopen('shell/import/techspecs.csv', 'r');
$c = 0;
$file2 =fopen('shell/import/links.csv', 'r');
$links = array();
while (($rowData = fgetcsv($file2, 4096)) !== false)
{
    $links[$rowData[3]] = $rowData[2];
}
$currentHeader = array();
$headers = array();
while (($row = fgetcsv($file, 4096)) !== false)
{
    if ($c ==0) {
        $c++;
        continue;
    }
    if(!isset($headers[$row[2]]))
    {
        $headers[$row[2]] = array();
    }
    if(empty($row[5]))
    {
        $currentHeader[$row[2]] = trim($row[4]);
        $headers[$row[2]][] = $currentHeader[$row[2]];
        continue;
    }
    else if(!isset($currentHeader[$row[2]]))
    {
        $currentHeader[$row[2]] = "Misc";
        $headers[$row[2]][] = $currentHeader[$row[2]];
        //scontinue;
    }
    $itemsKey = $row[2]."-".str_replace(" ", "_", $currentHeader[$row[2]]);
    if(!isset($items[$itemsKey]))
    {
        $items[$itemsKey] = array();
    }
    $items[$itemsKey][$row[3]] = array(
        "name" => $row[4],
        "desc" => $row[5],
    );
}
fclose($file);
foreach($items as $headerSku => $items) {
    var_dump($items);
    $headerSku2 = explode("-",$headerSku)[0];
    $ts = array(array('header' => array(
        'header_name' => explode("-",$headerSku)[1],
    ), $items
    ));
    if (isset($links[$headerSku2])) {
        $sku = $links[$headerSku2];
    }
    try {
        $p = $pr->get($sku);
        $p->setData('tech_specs', json_encode($ts));
        $p->getResource()->saveAttribute($p, 'tech_specs');
        printf("SKU " . $sku . " saved.\n");
    } catch (\Exception $e) {
        printf($e->getMessage()."\n");
    }
}