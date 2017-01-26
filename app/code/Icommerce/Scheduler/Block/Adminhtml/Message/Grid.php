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

namespace Icommerce\Scheduler\Block\Adminhtml\Message;
class Grid extends \Magento\Backend\Block\Widget\Grid
{
    protected $help;
    protected $mod;
    public function __construct(\Icommerce\Scheduler\Helper\Data $help,
                                \Icommerce\Scheduler\Model $mod)
    {
        $this->help = $help;
        $this->mod = $mod;
        parent::__construct();
        $this->setId('message_grid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->mod->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'    => __('Id'),
            'align'     => 'right',
            'width'     => '100px',
            'index'     => 'id',

        ));

        $this->addColumn('operation_id', array(
            'header'    => __('Task'),
            'align'     => 'left',
            'width'     => '200px',
            'index'     => 'operation_id',
            'type'      => 'options',
            'options'   => $this->help->getOperationOptionArray(),
            'frame_callback' => array($this, 'decorateOperationId')
        ));

        $this->addColumn('created_at', array(
            'header'    => __('Time'),
            'align'     => 'left',
            'width'     => '200px',
            'index'     => 'created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('message', array(
            'header'    => __('Message'),
            'align'     => 'left',
            'index'     => 'message',
        ));

        $this->addColumn('status', array(
            'header'    => __('Status'),
            'align'     => 'left',
            'width'     => '120px',
            'index'     => 'status',
            'type'      => 'options',
            'options'   => $this->help->getHistoryStatusesOptionArray(),
            'frame_callback' => array($this, 'decorateStatus')
        ));

        $this->addColumn('history_id', array(
            'header'    => __('History'),
            'align'     => 'center',
            'width'     => '60px',
            'index'     => 'history_id',
            'sortable'  => false,
            'filter'    => false,
            'frame_callback' => array($this, 'decorateHistoryId')
        ));

        return parent::_prepareColumns();
    }

    public function decorateOperationId($value, $row, $column, $isExport)
    {
        if ($isExport) {
            return $value;
        }

        return '<a href="' . $this->getUrl('*/scheduler_operation/edit', array('id' => $row->getOperationId())) . '">' . $value . '</a>';
    }

    public function decorateStatus($value, $row, $column, $isExport)
    {
        switch ($row->getStatus()) {
            case \Icommerce\Scheduler\Helper\Data::OPERATION_STATUS_NONE:
                $cell = '';
                break;
            case \Icommerce\Scheduler\Helper\Data::OPERATION_STATUS_ENABLED:
                $cell = '<span class="grid-severity-notice"><span>'.$value.'</span></span>';
                break;
            case \Icommerce\Scheduler\Helper\Data::OPERATION_STATUS_DISABLED:
                $cell = '<span class="grid-severity-critical"><span>'.$value.'</span></span>';
                break;
            case \Icommerce\Scheduler\Helper\Data::OPERATION_STATUS_RUNNING:
                $cell = '<span class="grid-severity-minor"><span>'.$value.'</span></span>';
                break;
            case \Icommerce\Scheduler\Helper\Data::OPERATION_STATUS_WAITING:
                $cell = '<span class="grid-severity-minor"><span>'.$value.'</span></span>';
                break;
            default:
                $cell = $value;
                break;
        }
        return $cell;
    }

    public function decorateHistoryId($value, $row, $column, $isExport)
    {
        if ($isExport) {
            return $value;
        }

        if ($row->getHistoryId()) {
            return '<a href="' . $this->getUrl('*/scheduler_operation/historyView', array('id' => $row->getHistoryId())) . '">' . Mage::helper('scheduler')->__('View') . '</a>';
        }

        return '';
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('scheduler');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => __('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => __('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return null;
    }

}