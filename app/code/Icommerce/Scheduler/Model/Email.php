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
 * @category    Icommerce
 * @package     Icommerce_Scheduler
 * @copyright   Copyright (c) 2009-2013 Vaimo AB
 * @author      Raivo Balins <raivo.balins@vaimo.com>
 * @author      Urmo Schmidt <urmo.schmidt@vaimo.com>
 */

class Icommerce_Scheduler_Model_Email extends Mage_Core_Model_Abstract
{
    protected $_website;
    protected $_operation;
    protected $_history;

    public function setOperation($operation)
    {
        $this->_operation = $operation;
    }

    public function setHistory($history)
    {
        $this->_history = $history;
    }

    public function send()
    {
        $storeId = 0;
        $templateId = $this->_operation->getEmailTemplate() ? $this->_operation->getEmailTemplate() : 'scheduler_email_template';

        /** @var Mage_Core_Model_Email_Template $template */
        $template = Mage::getModel('core/email_template');
        $template->setDesignConfig(array(
            'area'  => 'backend',
            'store' => $storeId
        ));

        $matches = array();
        preg_match('@.*/(\\w+)@', Mage::getBaseDir(), $matches);
        $instance = count($matches) > 1 ? $matches[1] : null;

        $template->sendTransactional(
            $templateId,
            $this->_operation->getEmailSender(),
            explode(',', $this->_operation->getEmailReceiver()),
            null,
            array(
                'operation' => $this->_operation,
                'history' => $this->_history,
                'instance' => $instance,
            )
        );

        return true;
    }
}