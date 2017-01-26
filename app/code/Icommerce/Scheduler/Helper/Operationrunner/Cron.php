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

namespace Icommerce\Scheduler\Helper\Operationrunner;
class Cron extends \Icommerce\Scheduler\Helper\Operationrunner\Abst
{
    protected $dl;
    public function __construct(Context $context, \Icommerce\Scheduler\Helper\Data $help, \Magento\Framework\App\Config\ScopeConfigInterface $config, History $his,
                                \Icommerce\Scheduler\Model\Email $email, \Magento\Framework\App\Filesystem\DirectoryList $directory_list)
    {
        $this->dl = $directory_list;
        parent::__construct($context, $help, $config, $his, $email);
    }

    /**
     * Run operation in a background
     *
     * @param Icommerce_Scheduler_Model_Operation $operation
     * @param bool $runChildren
     * @param bool $verbose
     * @throws Exception
     *
     * @return mixed
     */
    public function runOperation($operation, $runChildren = false, $verbose = false)
    {
        $createdAt = date('Y-m-d H:i:s');
        try {
            $parameters = $this->_getOperationParameters($operation);
            $url = $this->_getFileUrlToRun($operation);

            $result = $this->_doAsyncCurl(
                $url,
                $operation->getAuthenticationType(),
                $operation->getUsername(),
                $operation->getPassword(),
                $parameters
            );

            $this->_updateOperationStatus($operation, $result, $createdAt, $runChildren, $verbose);
        } catch (\Exception $e) {
            $status = \Icommerce\Scheduler\Helper\Data::HISTORY_STATUS_FAILED;
            $message = $e->getMessage();
            $result = isset($result['result']) ? $result['result'] : '';
            $this->_saveHistory($operation, $status, $createdAt, $message, $result);
            $operation->setStatus(\Icommerce\Scheduler\Helper\Data::OPERATION_STATUS_DISABLED);
            $operation->setLastStatus($status);
            $operation->save();

            throw $e;
        }

        return $result;
    }

    /**
     * Do async curl request
     *
     * @param string $url
     * @param int $authenticationType
     * @param string $username
     * @param string $password
     * @param array $parameters
     * @return array
     */

    protected function _doAsyncCurl($url, $authenticationType = 0, $username = '', $password = '', $parameters = array())
    {
        $tempFile = $this->dl->getPath('tmp') . DS . 'scheduler_' . getmypid() . '.log';

        //create a temp file
        $fp = fopen($tempFile, 'wb');

        //create a normal cURL handle
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);

        /** @var string $cookies */
        $cookies = trim($this->scopeConfig->getValue('scheduler/settings/cookies', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        // pass debugging cookies to scheduler task
        if (Mage::getIsDeveloperMode() && !empty($cookies)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookies);
        }

        if ($authenticationType) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, $authenticationType);
            curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        }

        //create the multiple cURL handle
        $mh = curl_multi_init();

        //add the normal cURL handle
        curl_multi_add_handle($mh, $ch);

        $active = null;

        //execute the handles
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        $i = 0;
        while ($active && $mrc == CURLM_OK) {
            $i++;

            // for approx. every 20-25 second send keepalive
            if ($i % 200 == 0) {
                \Icommerce_Db::dbKeepalive();
            }

            // #1 wait 0.1 second, see https://bugs.php.net/bug.php?id=63411
            // #2 https://bugs.php.net/bug.php?id=61141
            // in php >5.3.18 versions curl_multi_select doesn't block
            if (curl_multi_select($mh, 5.0) == -1) {
                usleep(100000);
            }

            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        //close the handles
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        fflush($fp);
        fclose($fp);
        curl_multi_close($mh);

        $result = $this->_processTriggerResult($tempFile);
        @unlink($tempFile);

        return $result;
    }

    /**
     * Process result of request (updating history etc)
     *
     * @param string $filename
     * @return array
     */
    protected function _processTriggerResult($filename)
    {
        $result = array(
            'status' => \Icommerce\Scheduler\Helper\Data::HISTORY_STATUS_NONE,
            'message' => '',
            'memory_usage' => 0,
            'result' => '',
            'messages' => array(),
        );

        $fp = fopen($filename, 'r');

        while ($line = fgets($fp)) {
            $result = $this->_updateResultArray($line, $result);
        }

        fclose($fp);

        return $result;
    }
}
