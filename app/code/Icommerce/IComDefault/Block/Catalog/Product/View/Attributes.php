<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Product description block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Icommerce\IcomDefault\Block\Catatlog\Product\View;

class Attributes extends \Magento\Catalog\Block\Product\View\Attributes
{
    public function getAdditionalData(array $excludeAttr = array())
    {
        if( !Icommerce_Default::getStoreConfig("skip_attribute_auto_price_format") ){
            $data = parent::getAdditionalData( $excludeAttr );
        } else {

            $data = array();
            $product = $this->getProduct();
            $attributes = $product->getAttributes();
            foreach ($attributes as $attribute) {
    //            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {

                    // This check is added since we don't want attributes that does not contain data
                    $icFrontendInput = $attribute->getFrontendInput();
                    $icShowAttrib = true;
                    if( $icFrontendInput == 'select' || $icFrontendInput=="multiselect" ){
                        if( !Icommerce_Default::getOptionValue($product, $attribute->getAttributeCode()) ){
                            $icShowAttrib = false;
                        }
                    }

                    if($icShowAttrib){
                        $value = $attribute->getFrontend()->getValue($product);

                        // TODO this is temporary skipping eco taxes
                        if (is_string($value)) {
                            if (strlen($value) && $product->hasData($attribute->getAttributeCode())) {
                                if ($attribute->getFrontendInput() == 'price') {
                                    $value = Mage::app()->getStore()->convertPrice($value,true);
                                } elseif (!$attribute->getIsHtmlAllowedOnFront()) {
                                    $value = $this->htmlEscape($value);
                                    if( !Icommerce_Default::getStoreConfig("skip_attribute_auto_price_format") ){
                                        if ( stristr($value,".")!="" /*&& number_format($value,2,',', '
    ')!='0,00'*/ ){
                                            // Only do this if we have no alphabetic characters
                                            if( !preg_match("/[a-z]/i",$value) ){
                                                $new_value = number_format($value,2,',', ' ');
                                                    if( $new_value!='0,00' && $new_value!='0.00' ){
                                                        $value = $new_value;
                                                    }
                                            }
                                        }
                                    }
                                }
                                $data[$attribute->getAttributeCode()] = array(
                                   'label' => $attribute->getFrontend()->getLabel(),
                                   'value' => $value,
                                   'code'  => $attribute->getAttributeCode()
                                );
                            }
                        }

                    }
                }
            }
        }

        // This is a fix for Magento bug with non translated option values
        // And to remove empty options from the array of data
        if( $prod = Mage::registry( "current_product" ) ){
            $remove_empty_attribs = !Icommerce_Default::getStoreConfig("product_additional_keep_empty_values");
            foreach( $data as $ix => $d ){
                $acode = $d["code"];
                $attr_label = Icommerce_Default::getAttribLabel($acode);
                $aval = $prod->getData($acode);
                if( $remove_empty_attribs && !$aval ){
                    unset($data[$ix]);
                    continue;
                }
                if( $attr_label!==$d["label"] ){
                    $d["label"] = $attr_label;
                    $opt_val = Icommerce_Default::getOptionValue($prod,$d["code"]);
                    $d["value"] = $opt_val;
                }
            }
        }

        return $data;
    }

    public function getAdditionalDataFaster( array $excludeAttr = array() )
    {
        $attrs = Icommerce_Db::getAssociative(  "SELECT attribute_code, frontend_input FROM eav_attribute as ea
                                                 INNER JOIN catalog_eav_attribute as cea ON ea.attribute_id=cea.attribute_id
                                                 WHERE cea.is_visible_on_front=1" );
        $prod = $this->getProduct();
        $r = array();
        foreach( $attrs as $acode => $fe_inp ){
            $v = $prod->getData($acode);
            if( $v && !in_array($acode,$excludeAttr) ){
                if( strpos($fe_inp,"select")!==FALSE ){
                    $v = Icommerce_Default::getOptionValue($v,$acode);
                } else if( $fe_inp=="price" ) {
                    $v = Mage::app()->getStore()->convertPrice($v,true);
                }
                $r[] = array( "code" => $acode,
                              "label" => Icommerce_Default::getAttribLabel($acode),
                              "value" => $v );
            }
        }

        return $r;
    }

}
