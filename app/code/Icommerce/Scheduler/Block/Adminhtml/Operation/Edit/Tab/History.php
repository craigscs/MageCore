<?php

namespace Icommerce\Scheduler\Block\Adminhtml\Operation\Edit\Tab;
class History extends \Magento\Backend\Block\Widget\Form\Grid
{
    protected $help;
    protected $col;
    protected $reg;
    public function __construct(\Icommerce\Scheduler\Helper\Data $help,
                                \Icommerce\Scheduler\Model\History $col,
                                \Magento\Framework\Registry $registry)
    {
        $this->reg = $registry;
        $this->col = $col;
        $this->help = $help;
        parent::__construct();
        $this->setId('history_grid');

        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);

        $this->setId('scheduler_history_grid');
        $this->setTitle(__('Task History'));
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        /** @var $collection Icommerce_Scheduler_Model_Resource_History_Collection */
        $collection = $this->col->getCollection();
        $collection->addFieldToSelect(array('created_at', 'finished_at', 'status', 'message'));
        $collection->addFieldToFilter('operation_id', $this->reg->registry('operation_data')->getId());
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

        $this->addColumn('created_at', array(
            'header'    => __('Started'),
            'align'     => 'left',
            'width'     => '200px',
            'index'     => 'created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('finished_at', array(
            'header'    => __('Finished'),
            'align'     => 'left',
            'width'     => '200px',
            'index'     => 'finished_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('message', array(
            'header'    => __('Message'),
            'align'     => 'left',
            'index'     => 'message',
        ));

        $this->addColumn('history_status', array(
            'header'    => __('Status'),
            'align'     => 'left',
            'width'     => '120px',
            'index'     => 'status',
            'type'      => 'options',
            'options'   => $this->help->getHistoryStatusesOptionArray(),
            'frame_callback' => array($this, 'decorateStatus')
        ));

        return parent::_prepareColumns();
    }

    public function decorateStatus($value, $row, $column, $isExport)
    {
        return $this->help->getLastStatusHtml($row->getStatus());
    }

    public function getRowClickCallback()
    {
        return <<<JS
function rowClick(grid, event) {
    var element = Event.findElement(event, 'tr');
    SchedulerTools.openDialog(element.title);
}
JS;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/historyView', array('id' => $row->getId()));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/history', array('_current' => true));
    }

    public function getAdditionalJavaScript()
    {
        return <<<JS
SchedulerTools = {
    openDialog: function(url) {
        var win = new Window({
            className:'magento',
            url:url,
            title:'History Result',
            width:1000,
            height:600,
            destroyOnClose:true
        });
        win.showCenter(true);
        new Ajax.Updater('modal_dialog_message', url, {evalScripts: true});
    }
}
JS;
    }
}