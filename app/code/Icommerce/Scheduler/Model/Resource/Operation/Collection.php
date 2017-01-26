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
 * @package     Icommerce_Scheduler
 * @copyright   Copyright (c) 2009-2012 Vaimo AB
 * @author      Urmo Schmidt
 */

class Icommerce_Scheduler_Model_Resource_Operation_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('scheduler/operation');
    }

    /**
     * Add next run filter to collection
     *
     * @return $this
     */
    public function addNextRunFilter()
    {
        $this->getSelect()->where('((next_run != 0 AND next_run <= ?) OR run_asap = 1)', date('Y-m-d H:i:s'));
        return $this;
    }

    /**
     * Add operation code filter (there might be multiple operations that use same code - and have different configuration)
     *
     * @param string|array $code
     * @return object $this
     */
    public function addCodeFilter($code)
    {
        $this->getSelect()
            ->where(is_array($code) ? 'code IN (?)' : 'code = ?', $code);

        return $this;
    }

    /**
     * Add operation master id filter to collection
     *
     * @param int $masterId
     * @return $this
     */
    public function addMasterOperationFilter($masterId)
    {
        $this->getSelect()
            ->where('master_id = ?', $masterId)
            ->order('master_order', Zend_Db_Select::SQL_ASC);

        return $this;
    }

    /**
     * Add operation status filter to collection
     *
     * @param int|array $status
     * @return $this
     */
    public function addStatusFilter($status)
    {
        if (is_array($status)) {
            $this->getSelect()->where('status IN (?)', $status);
        } else {
            $this->getSelect()->where('status = ?', $status);
        }

        return $this;
    }

    /**
     * Loads all operations that's next run is in past or now, status is enabled and is not a child operation
     *
     * @return Varien_Data_Collection_Db
     */
    public function loadByNextRun()
    {
        $this->addNextRunFilter();
        $this->addStatusFilter(Icommerce_Scheduler_Helper_Data::OPERATION_STATUS_ENABLED);

        return $this->load();
    }

    /**
     * Loads all operations for master operation where status is enabled
     *
     * @param int $masterId
     * @return Varien_Data_Collection_Db
     */
    public function loadByMasterId($masterId)
    {
        $this->addStatusFilter(Icommerce_Scheduler_Helper_Data::OPERATION_STATUS_ENABLED);
        $this->addMasterOperationFilter($masterId);

        return $this->load();
    }

}