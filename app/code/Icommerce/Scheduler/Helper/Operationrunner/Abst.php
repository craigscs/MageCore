<?php
/**
 * Copyright (c) 2009-2015 Vaimo AB
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
 * @copyright   Copyright (c) 2009-2015 Vaimo AB
 * @author      Urmo Schmidt
 */

/**
 * Class Icommerce_Scheduler_Helper_Operationrunner_Abstract
 */
namespace Icommerce\Scheduler\Helper\Operationrunner;
use Icommerce\Scheduler\Model\History;

abstract class Abst extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $help;
    protected $config;
    protected $email;
    protected $his;
    public function __construct(Context $context, \Icommerce\Scheduler\Helper\Data $help,
                                \Magento\Framework\App\Config\ScopeConfigInterface $config,
                                \Icommerce\Scheduler\Model\History $his,
                                \Icommerce\Scheduler\Model\Email $email)
    {
        $this->his = $his;
        $this->email = $email;
        $this->config = $config;
        $this->help = $help;
        parent::__construct($context);
    }

    /**
     * Run operation in a background
     *
     * @param Icommerce_Scheduler_Model_Operation $operation
     * @param bool $runChildren
     * @param bool $verbose
     * @return mixed
     */
    abstract public function runOperation($operation, $runChildren = false, $verbose = false);

    /**
     * Get url to file to execute
     *
     * @param Icommerce_Scheduler_Model_Operation $operation
     * @return string
     * @throws Exception
     */
    protected function _getFileUrlToRun($operation)
    {
        /** @var Icommerce_Scheduler_Helper_Data $schedulerHelper */
        $schedulerHelper = $this->help;
        $schedulerOperations = $schedulerHelper->getDefinedSchedulerOperations();

        $defaultUnsecure = (string)$this->config->getValue((
            'default/' . Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL
        );

        $globalCustomUrl = (string)$this->config->getValue(
            'default/' . Icommerce_Scheduler_Helper_Data::XML_PATH_CUSTOM_CURL_CALL_PATH
        );

        if (trim($globalCustomUrl)) {
            $defaultUnsecure = $globalCustomUrl;
        }

        if ($operation->getUrlOverride() != '') {
            $defaultUnsecure = $operation->getUrlOverride();
        }

        if (!isset($schedulerOperations[$operation->getCode()]['trigger'])) {
            throw new \Exception('Could not get trigger function');
        }

        $trigger = $schedulerOperations[$operation->getCode()]['trigger'];

        return $defaultUnsecure . $trigger;
    }

    /**
     * Get operation parameters
     *
     * @param Icommerce_Scheduler_Model_Operation $operation
     * @return array
     */
    protected function _getOperationParameters($operation)
    {
        $parameters = $this->_getNormalizedParameters($operation->getParameters());
        $parameters['operation_id'] = $operation->getId();

        return $parameters;
    }

    /**
     * Update operation status after execution
     *
     * @param Icommerce_Scheduler_Model_Operation $operation
     * @param array $result
     * @param string $createdAt
     * @param bool $runChildren
     * @param bool $verbose
     */
    protected function _updateOperationStatus($operation, $result, $createdAt, $runChildren, $verbose)
    {
        /** @var Icommerce_Scheduler_Helper_Data $schedulerHelper */
        $schedulerHelper = $this->help;
        $history = $this->_saveHistory(
            $operation,
            $result['status'],
            $createdAt,
            $result['message'],
            $result['result']
        );
        $schedulerHelper->saveMessages($operation, $history->getId(), $result['messages']);

        if ($runChildren && $operation->hasChildOperations()) {
            $operation->setStatus(\Icommerce\Scheduler\Helper\Data::OPERATION_STATUS_WAITING);
        } else {
            $operation->setStatus(\Icommerce\Scheduler\Helper\Data::OPERATION_STATUS_ENABLED);
        }

        if ($result['status'] != \Icommerce\Scheduler\Helper\Data::HISTORY_STATUS_NOTHING_TO_DO
            || !$operation->getLastStatus()
        ) {
            $operation->setLastStatus($result['status']);
        }

        if (isset($result['parameters']) && is_array($result['parameters'])) {
            $parameters = $operation->getParameters();
            foreach ($result['parameters'] as $key => $value) {
                $parameters[$key] = $value;
            }
            $operation->setParameters($parameters);
        }

        $operation->setProgressMin(null);
        $operation->setProgressMax(null);
        $operation->setProgressPos(null);
        $operation->setRunAsap(null);

        if ($history->getStatus() == \Icommerce\Scheduler\Helper\Data::HISTORY_STATUS_FAILED and $operation->getRerun() == 1) {
            if ($operation->getRerunProgress() > $operation->getRerunCount()) {
                $operation->setRunAsap(null);
                $operation->setRerunProgress(null);
            } else {
                $operation->setRunAsap(1);
                $operation->setRerunProgress($operation->getRerunProgress()+1);
            }
        } else {
            $operation->setRunAsap(null);
            $operation->setRerunProgress(null);
        }

        $operation->save();
        $this->_sendEmail($operation, $history);

        if ($verbose) {
            echo $schedulerHelper->getHistoryStatusesOptionArray($result['status']) . "\n";
        }

        if ($runChildren && $operation->hasChildOperations()) {
            foreach ($operation->getChildOperations() as $childOperation) {
                $schedulerHelper->runOperation($childOperation, true, $verbose);
            }

            $operation->setStatus(\Icommerce\Scheduler\Helper\Data::OPERATION_STATUS_ENABLED);
            $operation->save();
        }
    }

    /**
     * Send email with operation status
     *
     * @param Icommerce_Scheduler_Model_Operation $operation
     * @param Icommerce_Scheduler_Model_History $history
     */
    protected function _sendEmail($operation, $history)
    {
        try {
            if ($operation->getEmailEnabled()) {
                $emailStatus = explode(',', $operation->getEmailStatus());

                if (in_array($history->getStatus(), $emailStatus)) {
                    /** @var Icommerce_Scheduler_Model_Email $email */
                    $email = $this->email;
                    $email->setOperation($operation);
                    $email->setHistory($history);
                    $email->send();
                }
            }
        } catch (\Exception $e) {
//            Mage::logException($e);
        }
    }

    /**
     * Save operation history
     *
     * @param Icommerce_Scheduler_Model_Operation $operation
     * @param string $status
     * @param string $createdAt
     * @param string $message
     * @param array $result
     * @return Icommerce_Scheduler_Model_History
     */
    protected function _saveHistory($operation, $status, $createdAt, $message, $result)
    {
        $history = $this->his;
        $history->setOperationId($operation->getId());
        $history->setCreatedAt($createdAt);
        $history->setFinishedAt(date('Y-m-d H:i:s'));
        $history->setStatus($status);
        $history->setMessage($message);
        $history->setResult($result);

        $saveHistory = explode(',', $operation->getSaveHistory());
        if (in_array($status, $saveHistory)) {
            $history->save();
        }

        return $history;
    }

    /**
     * Normalize parameters for request
     *
     * @param array $parameters
     * @return array
     */
    protected function _getNormalizedParameters($parameters)
    {
        if (is_array($parameters)) {
            while (list($key, $value) = each($parameters)) {
                if (is_array($value)) {
                    $parameters[$key] = implode(',', $value);
                }
            }
        }

        return $parameters;
    }

    /**
     * Update result with
     *
     * @param string $line
     * @param array $result
     * @return array
     */
    protected function _updateResultArray($line, $result)
    {
        if (\Icommerce_Utils::parseTriggerLine($line)) {
            $result['messages'][] = array(
                'created_at' => $line['created_at'],
                'status' => $line['status'],
                'message' => $line['message'],
            );

            $result['result'] .= $line['message'] . "\n";
        } elseif (\Icommerce_Utils::parseTriggerResultXml($line)) {
            $result['status'] = $line['status'];
            $result['message'] = $line['message'];
            $result['memory_usage'] = $line['memory_usage'];
            $result['parameters'] = $line['parameters'];
        } else {
            $result['result'] .= trim($line) . "\n";
        }

        return $result;
    }
}
