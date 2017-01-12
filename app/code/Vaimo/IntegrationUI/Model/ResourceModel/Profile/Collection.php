<?php
/**
 * Copyright (c) 2009-2012 Vaimo AB
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
 * @package     Vaimo_IntegrationUI
 * @copyright   Copyright (c) 2009-2012 Vaimo AB
 * @author      Urmo Schmidt
 */

namespace Vaimo\IntegrationUI\Model\ResourceModel\Profile;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model and model
     *
     */
    protected function _construct()
    {
        $this->_init('Vaimo\IntegrationUI\Model\Profile', 'Vaimo\IntegrationUI\Model\ResourceModel\Profile');
    }

    /**
     * Filter collection by specified store ids
     *
     * @param array|int $storeIds
     * @return Vaimo_IntegrationUI_Model_Resource_Profile_Collection
     */
    public function addStoreFilter($storeIds)
    {
        $this->getSelect()->where('main_table.store_id IN (?)', array(0, $storeIds));
        return $this;
    }

    public function toOptionArray()
    {
        return $this->_toOptionArray('profile_id', 'name');
    }
}
