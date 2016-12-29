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
 * @author      Urmo Schmidt <urmo.schmidt@vaimo.com>
 *
 * @method string getSku()
 * @method Vaimo_IntegrationBase_Model_Shipment_Item setSku(string $value)
 * @method float getQty()
 * @method Vaimo_IntegrationBase_Model_Shipment_Item setQty(float $value)
 */

class Vaimo_IntegrationBase_Model_Shipment_Item extends Vaimo_IntegrationBase_Model_Abstract
{
    protected $_shipment;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('integrationbase/shipment_item');
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();
        if ($this->getShipment()) {
            $this->setParentId($this->getShipment()->getId());
        }
        return $this;
    }

    public function setShipment(Vaimo_IntegrationBase_Model_Shipment $shipment)
    {
        $this->_shipment = $shipment;
        $this->setParentId($shipment->getId());
        return $this;
    }

    public function getShipment()
    {
        return $this->_shipment;
    }
}