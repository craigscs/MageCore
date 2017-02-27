<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);

$obj = $bootstrap->getObjectManager();
$proc = $obj->get('Magento\Catalog\Model\Product\Gallery\Processor');
// Set the state (not sure if this is neccessary)
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$pr = $obj->create('Magento\Catalog\Model\ProductRepository');
$file = fopen('shell/import/images.csv', 'r');
$c = 0;
$file2 =fopen('shell/import/links.csv', 'r');
$links = array();
while (($rowData = fgetcsv($file2, 4096)) !== false)
{
    $links[$rowData[3]] = $rowData[2];
}
while (($row = fgetcsv($file)) !== FALSE) {
    if ($c ==0) {
        $c++;
        continue;
    }
    if(!isset($productData[$row[1]]))
    {
        $productData[$row[1]] = array();
    }
    $productData[$row[1]][$row[2]] = array(
        "caption" => $row[3],
        "pos" => $row[2],
        "name" => $row[4],
        'file' => $row[6]
    );
}
fclose($file);
foreach ($productData as $sku => $value) {
    try {
        if (isset($links[$sku])) {
            $sku = $links[$sku];
        }
        $p = $pr->get($sku);
        $count = 0;
        foreach ($value as $v) {
            $content = file_get_contents($v['file']);
            file_put_contents("pub/media/imp/" . $v['name'], $content);
            $imagePath = $v['name'];
            $gal = $p->getMediaGalleryImages();
            $pro = null;
            $skip = false;
            foreach ($gal as $g) {
                $name2 = explode("/", $g['file']);
                $name = array_pop($name2);
                if ($name == $v['file']) {
                    $skip = true;
                }
                $pro = $g;
            }
            if ($skip) {
                printf("Skipping item it already is in product");
                continue;
            }
            if ($count == 0) {
                $p->addImageToMediaGallery('imp/' . $imagePath, array('image', 'small_image', 'thumbnail'), false, false);
                $count++;
            } else {
                $p->addImageToMediaGallery('imp/' . $imagePath, array(), false, false);
            }
            $proc->updateImage($p, $pro['file'], array('label' => $v['caption'], 'position' => $v['pos']));
        }
        $p->save();
        printf("Saved sku: ".$sku."\n");
    } catch (\Exception $e) {
        printf($e->getMessage()."\n");
    }
}