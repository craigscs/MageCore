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

class Vaimo_IntegrationBase_Model_Resource_Invoice_Comment_Collection extends Magento\Catalog\Model\ResourceModel\AbstractCollection
{
    protected $_invoice;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('integrationbase/invoice_comment');
    }

    public function setInvoice($invoice)
    {
        $this->_invoice = $invoice;
        $invoiceId = $invoice->getId();

        if ($invoiceId) {
            $this->addFieldToFilter('parent_id', $invoice->getId());
        } else {
            $this->_totalRecords = 0;
            $this->_setIsLoaded(true);
        }

        return $this;
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();

        foreach ($this as $comment) {
            if ($this->_invoice) {
                $comment->setInvoice($this->_invoice);
            }
        }

        $this->resetItemsDataChanged();

        return $this;
    }
}