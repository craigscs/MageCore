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

/**
 * @method string getCode()
 * @method Icommerce_Scheduler_Model_Operation setCode(string $value)
 * @method string getComment()
 * @method Icommerce_Scheduler_Model_Operation setComment(string $value)
 * @method int getStatus()
 * @method Icommerce_Scheduler_Model_Operation setStatus(int $value)
 * @method string getNextRun()
 * @method Icommerce_Scheduler_Model_Operation setNextRun(string $value)
 * @method string getLastRun()
 * @method Icommerce_Scheduler_Model_Operation setLastRun(string $value)
 * @method int getLastStatus()
 * @method Icommerce_Scheduler_Model_Operation setLastStatus(int $value)
 * @method int getMasterId()
 * @method Icommerce_Scheduler_Model_Operation setMasterId(int $value)
 * @method int getMasterOrder()
 * @method Icommerce_Scheduler_Model_Operation setMasterOrder(int $value)
 * @method string getSaveHistory()
 * @method Icommerce_Scheduler_Model_Operation setSaveHistory(string $value)
 */

namespace Icommerce\Scheduler\Model;
class Operation extends \Magento\Framework\Model\AbstractModel
{
    protected $_childOperations = null;
    protected $op;

    protected function _construct(\Icommerce\Scheduler\Model\Operation $op)
    {
        $this->op = $op;
        parent::_construct();
        $this->_init('scheduler/operation');
    }

    protected function _afterLoad()
    {
        $this->_childOperations = null;
        return parent::_afterLoad();
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();

        if ($this->getStatus() == \Icommerce\Scheduler\Helper\Data::OPERATION_STATUS_ENABLED && !$this->getMasterId()) {
            if ($nextRunTime = $this->_calculateNexRunTime($this->getRecurrenceInfo())) {
                $this->setNextRun(date('Y-m-d H:i:s', $nextRunTime));
            } else {
                $this->setNextRun('0000-00-00 00:00:00');
            }
        } else {
            $this->setNextRun('0000-00-00 00:00:00');
        }

        return $this;
    }

    protected function _calculateNexRunTime($recurrenceInfo)
    {
        $lastRun = $this->op->load($this->getId())->getLastRun();
        $lastRun = strtotime($lastRun);

        if ($lastRun <= 0) {
            $datePart = getdate(time());
            $lastRun = mktime($datePart['hours'], $datePart['minutes'], 0, $datePart['mon'], $datePart['mday'], $datePart['year']);
        }

        switch ($recurrenceInfo['frequency']) {
            case \Icommerce\Scheduler\Helper\Data::FREQUENCY_ONCE:
                $nextRun = null;
                break;
            case \Icommerce\Scheduler\Helper\Data::FREQUENCY_MINUTE:
                $nextRun = $lastRun;

                while ($nextRun <= time()) {
                    $nextRun += $recurrenceInfo['n'] * 60;
                }

                break;
            case \Icommerce\Scheduler\Helper\Data::FREQUENCY_HOUR:
                $datePart = getdate($lastRun);
                $nextRun = mktime($datePart['hours'], $recurrenceInfo['minute'], 0, $datePart['mon'], $datePart['mday'], $datePart['year']);

                while ($nextRun < time()) {
                    $nextRun += $recurrenceInfo['n'] * 60 * 60;
                }

                break;
            case \Icommerce\Scheduler\Helper\Data::FREQUENCY_DAY:
                $datePart = getdate($lastRun);
                $nextRun = mktime($recurrenceInfo['hour'], $recurrenceInfo['minute'], 0, $datePart['mon'], $datePart['mday'], $datePart['year']);

                while ($nextRun < time()) {
                    $nextRun += $recurrenceInfo['n'] * 60 * 60 * 24;
                }

                break;
            case \Icommerce\Scheduler\Helper\Data::FREQUENCY_WEEK:
                $datePart = getdate($lastRun);
                $nextRun = mktime($recurrenceInfo['hour'], $recurrenceInfo['minute'], 0, $datePart['mon'], $datePart['mday'], $datePart['year']);

                while ($datePart['wday'] != $recurrenceInfo['weekday']) {
                    $nextRun += 60 * 60 * 24;
                    $datePart = getdate($nextRun);
                }

                while ($nextRun < time()) {
                    $nextRun += $recurrenceInfo['n'] * 60 * 60 * 24 * 7;
                }

                break;
            case \Icommerce\Scheduler\Helper\Data::FREQUENCY_MONTH:
                $datePart = getdate($lastRun);
                $nextRun = mktime($recurrenceInfo['hour'], $recurrenceInfo['minute'], 0, $datePart['mon'], $recurrenceInfo['day'], $datePart['year']);

                while ($nextRun < time()) {
                    $datePart = getdate($nextRun);

                    for ($i = 0; $i < $recurrenceInfo['n']; $i++) {
                        $datePart['mon'] += 1;

                        if ($datePart['mon'] > 12) {
                            $datePart['mon'] = 1;
                            $datePart['year'] += 1;
                        }
                    }

                    $daysInMonth = (int)date('t', mktime(0, 0, 0, $datePart['mon'], 1, $datePart['year']));

                    if ($datePart['mday'] > $daysInMonth) {
                        $datePart['mday'] = $daysInMonth;
                    }

                    $nextRun = mktime($datePart['hours'], $datePart['minutes'], 0, $datePart['mon'], $datePart['mday'], $datePart['year']);
                }

                break;
            case \Icommerce\Scheduler\Helper\Data::FREQUENCY_YEAR:
                $datePart = getdate($lastRun);
                $daysInMonth = (int)date('t', mktime(0, 0, 0, $recurrenceInfo['month'], 1, $datePart['year']));

                if ($recurrenceInfo['day'] <= $daysInMonth) {
                    $day = $recurrenceInfo['day'];
                } else {
                    $day = $daysInMonth;
                }

                $nextRun = mktime($recurrenceInfo['hour'], $recurrenceInfo['minute'], 0, $recurrenceInfo['month'], $day, $datePart['year']);

                while ($nextRun < time()) {
                    $datePart = getdate($nextRun);
                    $datePart['year'] += $recurrenceInfo['n'];

                    $daysInMonth = (int)date('t', mktime(0, 0, 0, $datePart['mon'], 1, $datePart['year']));

                    if ($recurrenceInfo['day'] <= $daysInMonth) {
                        $day = $recurrenceInfo['day'];
                    } else {
                        $day = $daysInMonth;
                    }

                    $nextRun = mktime($datePart['hours'], $datePart['minutes'], 0, $datePart['mon'], $day, $datePart['year']);
                }

                break;
            default:
                $nextRun = 0;
                break;
        }

        return $nextRun;
    }

    public function getLabel()
    {
        if ($node = Mage::getConfig()->getNode('global/scheduler_operations/' . $this->getCode())) {
            $node = $node->asArray();
            if (isset($node['label'])) {
                return $node['label'];
            }
        }
        return $this->getCode();
    }

    public function getStatusHtml()
    {
        return Mage::helper('scheduler')->getStatusHtml($this);
    }

    public function getLastStatusHtml()
    {
        return Mage::helper('scheduler')->getLastStatusHtml($this->getLastStatus());
    }

    public function getRecurrenceInfo()
    {
        if ($recurrenceInfo = unserialize($this->getData('recurrence_info'))) {
            if (is_array($recurrenceInfo)) {
                return $recurrenceInfo;
            }
        }

        return array();
    }

    public function getParameters()
    {
        if ($parameters = unserialize($this->getData('parameters'))) {
            if (is_array($parameters)) {
                return $parameters;
            }
        }

        return array();
    }

    public function setRecurrenceInfo($value)
    {
        if (is_array($value) && count($value)) {
            $this->setData('recurrence_info', serialize($value));
        } else {
            $this->setData('recurrence_info', '');
        }

        return $this;
    }

    public function setParameters($value)
    {
        if (is_array($value) && count($value)) {
            $this->setData('parameters', serialize($value));
        } else {
            $this->setData('parameters', '');
        }

        return $this;
    }

    public function getChildOperations()
    {
        if (!$this->_childOperations) {
            $this->_childOperations = $this->getCollection();

            if ($this->getId()) {
                $this->_childOperations->loadByMasterId($this->getId());
            }
        }

        return $this->_childOperations;
    }

    public function hasChildOperations()
    {
        $childOperations = $this->getChildOperations();
        return $childOperations->count() > 0;
    }
}