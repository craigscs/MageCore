<?php

namespace Icommerce\Order\Model;

class Observer {
    protected $om;
    protected $head_help;
    public function __construct(\Magento\Framework\App\ObjectManager $om,
                                Magento\Framework\HTTP\Header $head) {
        $this->om = $om;
        $this->head_help = $head;
    }

    /*protected $_export_no;

    public function getExportNo() {
        if ($this->_export_no === null) {
            $this->_export_no = Icommerce_Log::getNextSeqNo("var/visma-export");
        }
        return $this->_export_no;
    }*/

    protected function dispatch( $event_name, $order ){
        if( !$order ){
            // How do we know the last order...?
            // $order = ...;
        }
        if ($order && $order instanceof Magento\Framework\DataObject) {
            $incrementId = $order->getIncrementId();
        } else {
            $incrementId = '';
        }
        Icommerce_Default::logAppend('icorder->dispatch(): ' . $event_name . ' ' . $incrementId, 'var/icorder.log');
        $manager = $this->om->get('Magento\Framework\Event\ManagerInterface');
        $manager->dispatch($event_name, array("order" => $order));
    }

    // Order has been submitted and paid by customer
    public function dispatchSuccess( $order ){
        $this->dispatch( "ic_order_success", $order );
    }

    // Order has been submitted and paid by customer
    public function dispatchCaptured( $order ){
        $this->dispatch( "ic_order_captured", $order );
    }

    // Order has been cancelled by customer
    public function dispatchCancelled( $order ){
        $this->dispatch( "ic_order_cancel", $order );
    }

    // Order has been aborted by customer
    public function dispatchAborted( $order ){
        $this->dispatch( "ic_order_abort", $order );
    }

    // 3rd party is invoking a callback function - do we need this ?
    public function dispatchCallback( $order ){
        $this->dispatch( "ic_order_callback", $order );
    }


    // Hook for orders that are paid with non 3:rd party payment methods to emit ic_order_success
    public function onSaveOrderAfter($observer) {
        $e = $observer->getEvent();
        $order = $e->getData("order");
        $quote = $e->getData("quote");

        // Extract payment method
        $payments = $order->getPaymentsCollection();
        $method = "";
        // # Could there be more than 1 payment?
        foreach ($payments as $p) {
            $method = $p->getData("method");
        }

         //to be able to export all orders we set methods that should be excluded instead, the excluded methods dispatch this event on their own
        /*$methods_no_3rd_party = array(
            "invoicecost" => true,
            "invoicecost2" => true,
            "bankpayment" => true,
            "kreditor_invoice" => true,
            "kreditor_partpayment" => true,
            "checkmo" => true,
            "purchaseorder" => true
        );*/
        $excluded_payment_methods = array(
            'dibs' => true,
            'auriga' => true,
            'paypal_standard' => true,
            'payson' => true,
            // AR: added for paypal pro payment
            'paypal_direct' => true,
            'hosted_pro' => true
        );

        if( $method && !isset($excluded_payment_methods[$method]) ){
            $this->dispatchSuccess( $order );
        }

        return $this;
    }

    public function onSaveQuoteAfter($observer)
    {
        // This check is for compability reason, we dont want to completely remove this method
        if (!$this->getConfigDataFlag('paypal_dispatch_success_on_return')) {
            return;
        }

        $requestUri = $this->head_help->getRequestUri(true);

        if (is_numeric(strpos($requestUri, '/paypal/standard/success/'))){
            $orderId = $this->om->get('Magento\Checkout\Model\Session');
            if ($orderId){
                $order = $this->om->create('Magento\Sales\Model\Order')->load($orderId);
                if ($order){
                    $this->dispatchSuccess($order);
                }
            }
        }
    }

    /**
     * This observer is for the Paypal payment methods and that ic_order_success is correctly dispatched.
     * When a payment is done in Paypal, the IPN sends a message to /paypal/ipn/ and updates the order and sets the
     * status to 'processing' if it's ok.
     * To get this observer to work, the customer must set the Paypal IPN to active and enter the url,
     * www.site.com/paypal/ipn/ in the Paypal admin.
     *
     * @param $observer
     * @return mixed
     */
    public function onSalesOrderSaveAfter($observer)
    {
        $requestUri = $this->head_help->getRequestUri(true);
        if (strpos($requestUri, '/paypal/ipn/') === false){
            return;
        }

        $event = $observer->getEvent();

        /* @var $order Mage_Sales_Model_Order */
        $order = $event->getOrder();
        if ($order == null) {
            return;
        }

        $payment = $order->getPayment();
        if ($payment == null) {
            return;
        }

        $status = $order->getStatus();

        $paypalMethods = array(
            'paypal_standard' => true,
            'paypal_direct' => true,
            'hosted_pro' => true,
            'paypal_express' => true,
            'paypaluk_direct' => true,
            'paypaluk_express' => true,
            'verisign' => true,
            'paypal_billing_agreement' => true,
            'payflow_link' => true,
        );
        $paymentMethod = $payment->getMethod();

        if (isset($paypalMethods[$paymentMethod]) && strtolower($status) == 'processing') {
            $this->dispatchSuccess($order);
        }
    }

    public function getConfigDataFlag($field)
    {
        return Mage::getStoreConfigFlag('icorder/payments/'.$field, Mage::app()->getStore());
    }

    public function salesOrderCancel(Varien_Event_Observer $observer)
    {
        $treatanswer = false;
        $payment = $observer->getEvent()->getPayment();

        // Throwing the exception below, for orders where we're still pending payment.. causes the
        // cancel action to fail at all times. Filter out that case.
        // If we're still in payment pending, it makes no sense to cancel the payment, we have none.
        // This is not possible for Klarna, but for simplicity we put it here (applies to other
        // hosted payment methods though).
        if( $order = $payment->getOrder() ){
            if( $order->getState()=="pending_payment" ){
                $oid = $order->getData("increment_id");
                Icommerce_Default::logAppend( "Skipping tryCancel on order that is still pending payment ($oid)", "var/ic_order-cancel-skip.log" );
                return;
            }
        }

        if ($this->getConfigDataFlag('cancel')) {
            if ($payment->getMethod()=='dibs') {
                $dibs = Mage::getModel('dibs/dibs');
                if ($dibs && method_exists($dibs,'tryCancel')) {
                    $ares = $dibs->tryCancel($payment);
                    $treatanswer = true;
                }
            }
            if ($payment->getMethod()=='kreditor_invoice') {
                $klarna = Mage::getModel('kreditor/klarna_invoice');
                if ($klarna && method_exists($klarna,'tryCancel')) {
                    $ares = $klarna->tryCancel($payment);
                    $treatanswer = true;
                }
            }
            if ($payment->getMethod()=='kreditor_partpayment') {
                $klarna = Mage::getModel('kreditor/klarna_partpayment');
                if ($klarna && method_exists($klarna,'tryCancel')) {
                    $ares = $klarna->tryCancel($payment);
                    $treatanswer = true;
                }
            }
            if ($payment->getMethod()=='klarnacheckout') {
                $klarna = Mage::getModel('kreditor/klarna_invoice');
                if ($klarna && method_exists($klarna,'tryCancel')) {
                    $ares = $klarna->tryCancel($payment);
                    $treatanswer = true;
                }
            }
            if ($payment->getMethod() == 'resursbank'){
                $resursbank = Mage::getModel('resursbank/resursbank');
                if ($resursbank && method_exists($resursbank, 'tryCancel')){
                    $ares = $resursbank->tryCancel($payment);
                    $treatanswer = true;
                }
            }
        }
        if ($treatanswer) {
            if ($ares[0]==0) {
                if ($ares[1]!="") {
                    Mage::getSingleton('adminhtml/session')->addSuccess($ares[1]);
                }
            } elseif ($ares[0]>1) {
//              Mage::getSingleton('adminhtml/session')->addWarning($ares[1]);
                throw new Mage_Core_Exception($ares[1]);
            } elseif ($ares[0]<0) {
//              Mage::getSingleton('adminhtml/session')->addError($ares[1]);
                throw new Mage_Core_Exception($ares[1]);
            }
            $payment->getOrder()->addStatusToHistory($payment->getOrder()->getStatus(), $ares[1]);
        }
    }

/* Both messages are called when refunding an order, so only one should be listened to....
    public function salesOrderRefund(Varien_Event_Observer $observer)
    {
        $treatanswer = false;
        $creditmemo = $observer->getEvent()->getCreditmemo();
        if ($this->getConfigDataFlag('refund')) {
            $payment = $creditmemo->getOrder()->getPayment();
            if ($payment->getMethod()=='dibs') {
                $dibs = Mage::getModel('dibs/dibs');
                if ($dibs && method_exists($dibs,'tryRefund')) {
                    $ares = $dibs->tryRefund($payment,$creditmemo->getGrandTotal());
                    $treatanswer = true;
                }
            }
            if ($payment->getMethod()=='kreditor_invoice') {
                $klarna = Mage::getModel('kreditor/klarna_invoice');
                if ($klarna && method_exists($klarna,'tryRefund')) {
                    $ares = $klarna->tryRefund($payment,$creditmemo->getGrandTotal());
                    $treatanswer = true;
                }
            }
            if ($payment->getMethod()=='kreditor_partpayment') {
                $klarna = Mage::getModel('kreditor/klarna_partpayment');
                if ($klarna && method_exists($klarna,'tryRefund')) {
                    $ares = $klarna->tryRefund($payment,$creditmemo->getGrandTotal());
                    $treatanswer = true;
                }
            }
        }
        if ($treatanswer) {
            if ($ares[0]==0) {
                Mage::getSingleton('adminhtml/session')->addSuccess($ares[1]);
            } elseif ($ares[0]>1) {
//              Mage::getSingleton('adminhtml/session')->addWarning($ares[1]);
                throw new Mage_Core_Exception($ares[1]);
            } elseif ($ares[0]<0) {
//              Mage::getSingleton('adminhtml/session')->addError($ares[1]);
                throw new Mage_Core_Exception($ares[1]);
            }
            $payment->getOrder()->addStatusToHistory($payment->getOrder()->getStatus(), $ares[1]);
        }
    }
*/
    public function salesPaymentRefund(Varien_Event_Observer $observer)
    {
        $treatanswer = false;
        $creditmemo = $observer->getEvent()->getCreditmemo();
        if ($this->getConfigDataFlag('refund')) {
            $payment = $observer->getEvent()->getPayment();
            if ($payment->getMethod()=='dibs') {
                $dibs = Mage::getModel('dibs/dibs');
                if ($dibs && method_exists($dibs,'tryRefund')) {
                    $ares = $dibs->tryRefund($payment,$creditmemo->getBaseGrandTotal());
                    $treatanswer = true;
                }
            }
            if ($payment->getMethod()=='kreditor_invoice') {
                $klarna = Mage::getModel('kreditor/klarna_invoice');
                if ($klarna && method_exists($klarna,'tryRefund')) {
                    $ares = $klarna->tryRefund($payment,$creditmemo->getGrandTotal());
                    $treatanswer = true;
                }
            }
            if ($payment->getMethod()=='kreditor_partpayment') {
                $klarna = Mage::getModel('kreditor/klarna_partpayment');
                if ($klarna && method_exists($klarna,'tryRefund')) {
                    $ares = $klarna->tryRefund($payment,$creditmemo->getGrandTotal());
                    $treatanswer = true;
                }
            }
            if ($payment->getMethod()=='klarnacheckout') {
                $klarna = Mage::getModel('kreditor/klarna_invoice');
                if ($klarna && method_exists($klarna,'tryRefund')) {
                    $ares = $klarna->tryRefund($payment,$creditmemo->getGrandTotal());
                    $treatanswer = true;
                }
            }
            if ($payment->getMethod() == 'resursbank'){
                $resursbank = Mage::getModel('resursbank/resursbank');
                if ($resursbank && method_exists($resursbank, 'tryRefund')){
                    $ares = $resursbank->tryRefund($payment, $creditmemo->getGrandTotal());
                    $treatanswer = true;
                }
            }
        }
        if ($treatanswer) {
            if ($ares[0]==0) {
                Mage::getSingleton('adminhtml/session')->addSuccess($ares[1]);
            } elseif ($ares[0]>1) {
//              Mage::getSingleton('adminhtml/session')->addWarning($ares[1]);
                throw new Mage_Core_Exception($ares[1]);
            } elseif ($ares[0]<0) {
//              Mage::getSingleton('adminhtml/session')->addError($ares[1]);
                throw new Mage_Core_Exception($ares[1]);
            }
            $payment->getOrder()->addStatusToHistory($payment->getOrder()->getStatus(), $ares[1]);
        }
    }

}
