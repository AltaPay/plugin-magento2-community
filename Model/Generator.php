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

/**
 * Class Generator
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
     * Generator constructor.
     * @param Quote $quote
     * @param PaymentData $paymentData
     * @param Session $checkoutSession
     * @param Http $request
     * @param Order $order
     * @param OrderSender $orderSender
     * @param SystemConfig $systemConfig
     * @param Logger $valitorLogger
     * @param TransactionRepositoryInterface $transactionRepository
     * @param OrderLoaderInterface $orderLoader
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
        OrderLoaderInterface $orderLoader
    ) {
        $this->quote = $quote;
        $this->paymentData = $paymentData;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->systemConfig = $systemConfig;
        $this->valitorLogger = $valitorLogger;
        $this->transactionRepository = $transactionRepository;
        $this->orderLoader = $orderLoader;
    }

    /**
     * @param RequestInterface $request
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
                    $quote
                        ->setIsActive(1)
                        ->setReservedOrderId(null)
                        ->save();
                    $this->checkoutSession->replaceQuote($quote);
                    return true;
                }
            }
        }

        return false;
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
     */
    public function handleCancelStatusAction(RequestInterface $request)
    {
        $historyComment = __(ConstantConfig::CONSUMER_CANCEL_PAYMENT);
        //TODO: fetch the MerchantErrorMessage and use it as historyComment
        $this->handleOrderStateAction($request, Order::STATE_CANCELED, Order::STATE_CANCELED, $historyComment);
    }

    /**
     * @param RequestInterface $request
     */
    public function handleFailStatusRedirectFormAction(RequestInterface $request)
    {
        //TODO:refactor this method
        $formUrl = null;
        $transInfo = null;
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->orderLoader->getOrderByOrderIncrementId($response->shopOrderId);
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
     */
    public function handleFailedStatusAction(RequestInterface $request)
    {
        $historyComment = __(ConstantConfig::CONSUMER_PAYMENT_FAILED);
        $transInfo = null;
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->orderLoader->getOrderByOrderIncrementId($response->shopOrderId);
            $transInfo = sprintf(
                "Transaction ID: %s - Payment ID: %s - Credit card token: %s",
                $response->transactionId,
                $response->paymentId,
                $response->creditCardToken
            );
            
            //save transaction data for failure
            $parametersData = json_encode($request->getPostValue());
            $transactionData = json_encode($response);
            $this->transactionRepository->addTransactionData($response->shopOrderId, $response->transactionId, $response->paymentId, $transactionData, $parametersData);
        }

        $customFirstOrderStatus = $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode);
        if ($customFirstOrderStatus) {
            $orderStatus = $customFirstOrderStatus;
        } else {
            $orderStatus = Order::STATE_PENDING_PAYMENT;
        }

        $this->handleOrderStateAction($request, Order::STATE_PENDING_PAYMENT, $orderStatus, $historyComment, $transInfo);
    }

    /**
     * @param RequestInterface $request
     * @param string $orderState
     * @param string $orderStatus
     * @param string $historyComment
     * @param null $transactionInfo
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function handleOrderStateAction(
        RequestInterface $request,
        $orderState = Order::STATE_PENDING_PAYMENT,
        $orderStatus = Order::STATE_PENDING_PAYMENT,
        $historyComment = "Order state changed",
        $transactionInfo = null
    ) {
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->orderLoader->getOrderByOrderIncrementId($response->shopOrderId);
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
     * @param $comment
     * @param RequestInterface $request
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function completeCheckout($comment, RequestInterface $request)
    {
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->orderLoader->getOrderByOrderIncrementId($response->shopOrderId);
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode = $order->getStore()->getCode();
            if ($order->getId()) {
                // @todo Write data to DB
                $payment = $order->getPayment();
                $payment->setPaymentId($response->paymentId);
                $payment->setLastTransId($response->transactionId);
                $payment->setCcTransId($response->creditCardToken);
                $payment->save();
            }

            if (!$order->getEmailSent()) {
                $this->orderSender->send($order);
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

            //save transaction data
            $parametersData = json_encode($request->getPostValue());
            $transactionData = json_encode($response);
            $this->transactionRepository->addTransactionData($order->getIncrementId(), $response->transactionId, $response->paymentId, $transactionData, $parametersData);
            
            $isCaptured = false;
            foreach (SystemConfig::getTerminalCodes() as $terminalName) {
                if ($this->systemConfig->getTerminalConfigFromTerminalName(
                    $terminalName,
                    'terminalname',
                    $storeScope,
                    $storeCode
                ) === $response->Transactions[0]->Terminal
                ) {
                    $isCaptured = $this->systemConfig->getTerminalConfigFromTerminalName($terminalName, 'capture', $storeScope, $storeCode);
                    break;
                }
            }

            if ($isCaptured) {
                $this->setCustomOrderStatus($order, Order::STATE_COMPLETE, 'complete');
                $order->addStatusHistoryComment(__(ConstantConfig::PAYMENT_COMPLETE));
            } else {
                $this->setCustomOrderStatus($order, Order::STATE_PROCESSING, 'process');
            }

            $order->setIsNotified(false);
            $order->getResource()->save($order);
        }
    }

    /**
     * @param Order $order
     * @param $state
     * @param $statusKey
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function setCustomOrderStatus(Order $order, $state, $statusKey)
    {
        $order->setState($state);
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode = $order->getStore()->getCode();
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
