<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Valitor
 * @category  payment
 * @package   valitor
 */

namespace SDM\Valitor\Model;

use SDM\Valitor\Api\Ecommerce\Callback;
use SDM\Valitor\Request\Config;
use SDM\Valitor\Response\CallbackResponse;
use SDM\Valitor\Model\ConstantConfig;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use SDM\Valitor\Logger\Logger;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use SDM\Valitor\Api\TransactionRepositoryInterface;
use SDM\Valitor\Api\OrderLoaderInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Service\InvoiceService;

/**
 * Class Generator
 *
 * @package SDM\Valitor\Model
 */
class Generator
{
    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var PaymentData
     */
    private $paymentData;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var Order
     */
    private $order;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    private $_invoiceService;
    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Logger
     */
    private $valitorLogger;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    private $transactionFactory;

    /**
     * Generator constructor.
     *
     * @param Quote                          $quote
     * @param PaymentData                    $paymentData
     * @param Session                        $checkoutSession
     * @param Http                           $request
     * @param Order                          $order
     * @param OrderSender                    $orderSender
     * @param SystemConfig                   $systemConfig
     * @param Logger                         $valitorLogger
     * @param TransactionRepositoryInterface $transactionRepository
     * @param OrderLoaderInterface           $orderLoader
     * @param TransactionFactory             $transactionFactory
     * @param InvoiceService                 $invoiceService
     */
    public function __construct(
        Quote $quote,
        PaymentData $paymentData,
        Session $checkoutSession,
        Http $request,
        Order $order,
        OrderSender $orderSender,
        SystemConfig $systemConfig,
        Logger $valitorLogger,
        TransactionRepositoryInterface $transactionRepository,
        OrderLoaderInterface $orderLoader,
        TransactionFactory $transactionFactory,
        InvoiceService $invoiceService
    ) {
        $this->quote                 = $quote;
        $this->paymentData           = $paymentData;
        $this->checkoutSession       = $checkoutSession;
        $this->request               = $request;
        $this->order                 = $order;
        $this->orderSender           = $orderSender;
        $this->_invoiceService       = $invoiceService;
        $this->systemConfig          = $systemConfig;
        $this->valitorLogger         = $valitorLogger;
        $this->transactionRepository = $transactionRepository;
        $this->transactionFactory    = $transactionFactory;
        $this->orderLoader           = $orderLoader;
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool
     * @throws \Exception
     */
    public function restoreOrderFromRequest(RequestInterface $request)
    {
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $this->valitorLogger->addDebugLog('[restoreOrderFromRequest] Response correct', $response);
            $order = $this->orderLoader->getOrderByOrderIncrementId($response->shopOrderId);
            if ($order->getQuoteId()) {
                $this->valitorLogger->addDebugLog('[restoreOrderFromRequest] Order quote id', $order->getQuoteId());
                if ($quote = $this->quote->loadByIdWithoutStore($order->getQuoteId())) {
                    $this->valitorLogger->addDebugLog('[restoreOrderFromRequest] Quote found', $order->getQuoteId());
                    $quote->setIsActive(1)->setReservedOrderId(null)->save();
                    $this->checkoutSession->replaceQuote($quote);

                    return true;
                }
            }
        }

        return false;
    }

    public function createInvoice($order)
    {
        if (!$order->getInvoiceCollection()->count()) {
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $transactionSave = $this->transactionFactory
                ->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();
        }
    }

    /**
     * @param RequestInterface $request
     */
    public function handleNotificationAction(RequestInterface $request)
    {
        $this->completeCheckout(__(ConstantConfig::NOTIFICATION_CALLBACK), $request);
    }

    /**
     * @param RequestInterface $request
     * @param                  $responseStatus
     */
    public function handleCancelStatusAction(RequestInterface $request, $responseStatus)
    {
        $stateWhenRedirectCancel  = Order::STATE_CANCELED;
        $statusWhenRedirectCancel = Order::STATE_CANCELED;
        $responseComment          = __(ConstantConfig::CONSUMER_CANCEL_PAYMENT);
        if ($responseStatus != 'cancelled') {
            $responseComment = __(ConstantConfig::UNKNOWN_PAYMENT_STATUS_MERCHANT);
        }
        $historyComment = __(ConstantConfig::CANCELLED) . '|' . $responseComment;
        //TODO: fetch the MerchantErrorMessage and use it as historyComment
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order             = $this->loadOrderFromCallback($response);
            $storeCode         = $order->getStore()->getCode();
            $storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $orderStatusCancel = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);

            if ($orderStatusCancel) {
                $statusWhenRedirectCancel = $orderStatusCancel;
            }
            $this->handleOrderStateAction(
                $request,
                $stateWhenRedirectCancel,
                $statusWhenRedirectCancel,
                $historyComment
            );

            //save failed transaction data
            $parametersData  = json_encode($request->getPostValue());
            $transactionData = json_encode($response);
            $this->transactionRepository->addTransactionData(
                $order->getIncrementId(),
                $response->transactionId,
                $response->paymentId,
                $transactionData,
                $parametersData
            );
        }
    }

    /**
     * @param RequestInterface $request
     *
     * @return $formUrl
     */
    public function handleFailStatusRedirectFormAction(RequestInterface $request)
    {
        //TODO:refactor this method
        $formUrl   = null;
        $transInfo = null;
        $callback  = new Callback($request->getPostValue());
        $response  = $callback->call();
        if ($response) {
            $order   = $this->orderLoader->getOrderByOrderIncrementId($response->shopOrderId);
            $formUrl = $order->getValitorPaymentFormUrl();
            if ($formUrl) {
                $order->addStatusHistoryComment(__(ConstantConfig::DECLINED_PAYMENT_FORM));
            } else {
                $order->addStatusHistoryComment(__(ConstantConfig::DECLINED_PAYMENT_SECTION));
            }
            $order->setState(Order::STATE_PENDING_PAYMENT);
            $order->getResource()->save($order);
        }

        return $formUrl;
    }


    /**
     * @param RequestInterface $request
     * @param                  $msg
     * @param                  $merchantErrorMsg
     * @param                  $responseStatus
     *
     * @throws \Exception
     */
    public function handleFailedStatusAction(RequestInterface $request, $msg, $merchantErrorMsg, $responseStatus)
    {
        $historyComment = $responseStatus . '|' . $msg;
        if (!is_null($merchantErrorMsg)) {
            $historyComment = $responseStatus . '|' . $msg . '|' . $merchantErrorMsg;
        }
        $transInfo = null;
        $callback  = new Callback($request->getPostValue());
        $response  = $callback->call();
        if ($response) {
            $order     = $this->loadOrderFromCallback($response);
            $transInfo = sprintf(
                "Transaction ID: %s - Payment ID: %s - Credit card token: %s",
                $response->transactionId,
                $response->paymentId,
                $response->creditCardToken
            );

            //check if order status set in configuration
            $stateWhenRedirectFail  = Order::STATE_CANCELED;
            $statusWhenRedirectFail = Order::STATE_CANCELED;
            $storeCode              = $order->getStore()->getCode();
            $storeScope             = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $orderStatusCancel      = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);

            if ($orderStatusCancel) {
                $statusWhenRedirectFail = $orderStatusCancel;
            }

            $this->handleOrderStateAction(
                $request,
                $stateWhenRedirectFail,
                $statusWhenRedirectFail,
                $historyComment,
                $transInfo
            );

            //save failed transaction data
            $parametersData  = json_encode($request->getPostValue());
            $transactionData = json_encode($response);
            $this->transactionRepository->addTransactionData(
                $order->getIncrementId(),
                $response->transactionId,
                $response->paymentId,
                $transactionData,
                $parametersData
            );
        }
    }

    /**
     * @param CallbackResponse $response
     *
     * @return Order
     */
    private function loadOrderFromCallback(CallbackResponse $response)
    {
        return $this->loadOrderFromOrderId($response->shopOrderId);
    }

    /**
     * @param string $orderId
     *
     * @return Order
     */
    private function loadOrderFromOrderId($orderId)
    {
        $order = $this->order->loadByIncrementId($orderId);

        return $order;
    }

    /**
     * @param RequestInterface $request
     * @param string           $orderState
     * @param string           $orderStatus
     * @param string           $historyComment
     * @param null             $transactionInfo
     *
     * @return bool
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function handleOrderStateAction(
        RequestInterface $request,
        $orderState = Order::STATE_NEW,
        $orderStatus = Order::STATE_NEW,
        $historyComment = "Order state changed",
        $transactionInfo = null
    ) {
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->loadOrderFromCallback($response);
            $order->setState($orderState);
            $order->setIsNotified(false);
            if (!is_null($transactionInfo)) {
                $order->addStatusHistoryComment($transactionInfo);
            }
            $order->addStatusHistoryComment($historyComment, $orderStatus);
            $order->getResource()->save($order);

            return true;
        }

        return false;
    }

    /**
     * @param RequestInterface $request
     */
    public function handleOkAction(RequestInterface $request)
    {
        $this->completeCheckout(__(ConstantConfig::OK_CALLBACK), $request);
    }

    /**
     * @param                  $comment
     * @param RequestInterface $request
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function completeCheckout($comment, RequestInterface $request)
    {
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order      = $this->orderLoader->getOrderByOrderIncrementId($response->shopOrderId);
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode  = $order->getStore()->getCode();
            if ($order->getId()) {
                // @todo Write data to DB
                $payment = $order->getPayment();
                $payment->setPaymentId($response->paymentId);
                $payment->setLastTransId($response->transactionId);
                $payment->setCcTransId($response->creditCardToken);
                $payment->save();
                $currentStatus        = $order->getStatus();
                $orderHistories       = $order->getStatusHistories();
                $latestHistoryComment = array_pop($orderHistories);
                $prevStatus           = $latestHistoryComment->getStatus();
                $sendMail             = true;
                if (strpos($comment, ConstantConfig::NOTIFICATION_CALLBACK) !== false
                    && $currentStatus == $prevStatus
                ) {
                    $sendMail = false;
                }
                //If the product is shipping product then check
                $shippedProduct = false;
                if (!$order->getEmailSent() && $sendMail == true) {
                    $this->orderSender->send($order);
                }
                foreach ($order->getAllVisibleItems() as $item) {
                    $productType = $item->getProductType();
                    if ($productType != 'virtual' && $productType != 'downloadable') {
                        $shippedProduct = true;
                    }
                }
                //unset redirect if success
                $this->checkoutSession->unsValitorCustomerRedirect();
                //save transaction data
                $parametersData  = json_encode($request->getPostValue());
                $transactionData = json_encode($response);
                $this->transactionRepository->addTransactionData(
                    $order->getIncrementId(),
                    $response->transactionId,
                    $response->paymentId,
                    $transactionData,
                    $parametersData
                );

                $isCaptured = false;
                foreach (SystemConfig::getTerminalCodes() as $terminalName) {
                    if ($this->systemConfig->getTerminalConfigFromTerminalName(
                            $terminalName,
                            'terminalname',
                            $storeScope,
                            $storeCode
                        ) === $response->Transactions[0]->Terminal
                    ) {
                        $isCaptured = $this->systemConfig->getTerminalConfigFromTerminalName(
                            $terminalName,
                            'capture',
                            $storeScope,
                            $storeCode
                        );
                        break;
                    }
                }
                $orderStatusAfterPayment = $this->systemConfig->getStatusConfig(
                    'process',
                    $storeScope,
                    $storeCode
                );
                $orderStatus_capture     = $this->systemConfig->getStatusConfig('autocapture', $storeScope, $storeCode);

                if ($isCaptured) {
                    if ($orderStatus_capture == "complete") {
                        if ($shippedProduct) {
                            $this->setCustomOrderStatus($order, Order::STATE_COMPLETE, 'autocapture');
                            $order->addStatusHistoryComment(__(ConstantConfig::PAYMENT_COMPLETE));
                        } else {
                            $order->addStatusToHistory($orderStatus_capture, ConstantConfig::PAYMENT_COMPLETE, false);
                        }
                    } else {
                        $this->setCustomOrderStatus($order, Order::STATE_PROCESSING, 'process');
                    }
                } else {
                    if ($orderStatusAfterPayment) {
                        $this->setCustomOrderStatus($order, $orderStatusAfterPayment, 'process');
                    } else {
                        $this->setCustomOrderStatus($order, Order::STATE_PROCESSING, 'process');
                    }
                }
                $order->addStatusHistoryComment($comment);
                $order->addStatusHistoryComment(
                    sprintf(
                        "Transaction ID: %s - Payment ID: %s - Credit card token: %s",
                        $response->transactionId,
                        $response->paymentId,
                        $response->creditCardToken
                    )
                );
                $order->setIsNotified(false);
                $order->getResource()->save($order);
                if ($response->type == "paymentAndCapture") {
                    $this->createInvoice($order);
                }
            }
        }
    }

    /**
     * @param Order $order
     * @param       $state
     * @param       $statusKey
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function setCustomOrderStatus(Order $order, $state, $statusKey)
    {
        $order->setState($state);
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode  = $order->getStore()->getCode();
        if ($status = $this->systemConfig->getStatusConfig($statusKey, $storeScope, $storeCode)) {
            $order->setStatus($status);
        }
        $order->getResource()->save($order);
    }

    /**
     * @return Session
     */
    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }
}
