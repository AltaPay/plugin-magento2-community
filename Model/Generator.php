<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Altapay\Api\Ecommerce\Callback;
use Altapay\Response\CallbackResponse;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Store\Model\ScopeInterface;
use SDM\Altapay\Logger\Logger;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use SDM\Altapay\Api\TransactionRepositoryInterface;
use SDM\Altapay\Api\OrderLoaderInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Service\InvoiceService;
use SDM\Altapay\Model\Handler\OrderLinesHandler;
use SDM\Altapay\Model\Handler\CreatePaymentHandler;
use Magento\Checkout\Model\Cart;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use SDM\Altapay\Model\TokenFactory;
use SDM\Altapay\Helper\Data;
use SDM\Altapay\Model\ReconciliationIdentifierFactory;
use SDM\Altapay\Model\Handler\CreateCreditMemo;
use Magento\Sales\Api\OrderRepositoryInterface;
/**
 * Class Generator
 * Handle the create payment related functionality.
 */
class Generator
{
    /**
     * @var Quote
     */
    private $quote;

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
     * @var InvoiceService
     */
    private $invoiceService;
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
    private $altapayLogger;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;
    /**
     * @var OrderLinesHandler
     */
    private $orderLines;
    /**
     * @var CreatePaymentHandler
     */
    private $paymentHandler;

    /**
     * @var StockStateInterface
     */
    private $stockItem;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var Cart
     */
    private $modelCart;

    /**
     * @var TokenFactory
     */
    private $dataToken;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ReconciliationIdentifierFactory
     */
    protected $reconciliation;

    /**
     * @var CreateCreditMemo
     */
    protected $creditMemo;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Generator constructor.
     *
     * @param Quote                           $quote
     * @param Session                         $checkoutSession
     * @param Http                            $request
     * @param Order                           $order
     * @param OrderSender                     $orderSender
     * @param SystemConfig                    $systemConfig
     * @param Logger                          $altapayLogger
     * @param TransactionRepositoryInterface  $transactionRepository
     * @param OrderLoaderInterface            $orderLoader
     * @param TransactionFactory              $transactionFactory
     * @param InvoiceService                  $invoiceService
     * @param OrderLinesHandler               $orderLines
     * @param CreatePaymentHandler            $paymentHandler
     * @param StockStateInterface             $stockItem
     * @param StockRegistryInterface          $stockRegistry
     * @param Cart                            $modelCart
     * @param TokenFactory                    $dataToken
     * @param Data                            $helper
     * @param ReconciliationIdentifierFactory $reconciliation
     * @param CreateCreditMemo                $creditMemo
     */
    public function __construct(
        Quote $quote,
        Session $checkoutSession,
        Http $request,
        Order $order,
        OrderSender $orderSender,
        SystemConfig $systemConfig,
        Logger $altapayLogger,
        TransactionRepositoryInterface $transactionRepository,
        OrderLoaderInterface $orderLoader,
        TransactionFactory $transactionFactory,
        InvoiceService $invoiceService,
        OrderLinesHandler $orderLines,
        CreatePaymentHandler $paymentHandler,
        StockStateInterface $stockItem,
        StockRegistryInterface $stockRegistry,
        Cart $modelCart,
        TokenFactory $dataToken,
        Data $helper,
        ReconciliationIdentifierFactory $reconciliation,
        CreateCreditMemo $creditMemo,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->quote                 = $quote;
        $this->checkoutSession       = $checkoutSession;
        $this->request               = $request;
        $this->order                 = $order;
        $this->orderSender           = $orderSender;
        $this->invoiceService        = $invoiceService;
        $this->systemConfig          = $systemConfig;
        $this->altapayLogger         = $altapayLogger;
        $this->transactionRepository = $transactionRepository;
        $this->transactionFactory    = $transactionFactory;
        $this->orderLoader           = $orderLoader;
        $this->orderLines            = $orderLines;
        $this->paymentHandler        = $paymentHandler;
        $this->stockItem             = $stockItem;
        $this->stockRegistry         = $stockRegistry;
        $this->modelCart             = $modelCart;
        $this->dataToken             = $dataToken;
        $this->helper                = $helper;
        $this->reconciliation        = $reconciliation;
        $this->creditMemo            = $creditMemo;
        $this->orderRepository       = $orderRepository;
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
            $this->altapayLogger->addDebugLog('[restoreOrderFromRequest] Response correct', $response);
            $order = $this->orderLoader->getOrderByOrderIncrementId($response->shopOrderId);
            if ($order->getQuoteId()) {
                $this->altapayLogger->addDebugLog('[restoreOrderFromRequest] Order quote id', $order->getQuoteId());
                if ($quote = $this->quote->loadByIdWithoutStore($order->getQuoteId())) {
                    $this->altapayLogger->addDebugLog('[restoreOrderFromRequest] Quote found', $order->getQuoteId());
                    $quote->setIsActive(1)->setReservedOrderId(null)->save();
                    $this->checkoutSession->replaceQuote($quote);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $order
     *
     * @return void
     */
    public function createInvoice($order)
    {
        if (!$order->getInvoiceCollection()->count()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $transaction = $this->transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
            $transaction->save();
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
        $responseComment = __(ConstantConfig::CONSUMER_CANCEL_PAYMENT);
        if ($responseStatus != 'cancelled') {
            $responseComment = __(ConstantConfig::UNKNOWN_PAYMENT_STATUS_MERCHANT);
        }
        $historyComment = __(ConstantConfig::CANCELLED) . '|' . $responseComment;

        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->loadOrderFromCallback($response);
            $orderTransactionId = $order->getPayment()->getLastTransId();
            // Return if the incoming transaction id is different from order transaction id
            if (!empty($orderTransactionId) && $orderTransactionId != $response->transactionId) return;

            $this->handleOrderStateAction($request, Order::STATE_PENDING_PAYMENT, $historyComment);
            //save failed transaction data
            $this->saveTransactionData($response, $order);
        }
    }

    /**
     * @param $response
     * @param $order
     */
    private function saveTransactionData($response, $order)
    {
        $latestTransKey = $this->helper->getLatestTransaction($response->Transactions);
        if (isset($response->Transactions[$latestTransKey])) {
            $transaction    = $response->Transactions[$latestTransKey];
            $this->transactionRepository->addTransactionData(
                $order->getIncrementId(),
                $response->transactionId ?? null,
                $response->paymentId ?? null,
                $transaction->Terminal ?? null,
                $response->requireCapture ?? null,
                $response->paymentStatus ?? null,
                $response->nature ?? null,
                $response->Result ?? null,
                $response->CardHolderMessageMustBeShown ?? null,
                $response->CardHolderErrorMessage ?? null,
                $response->MerchantErrorMessage ?? null,
                $transaction->FraudRiskScore ?? null,
                $transaction->FraudExplanation ?? null,
                $transaction->FraudRecommendation ?? null
            );
        }
    }

    /**
     * @param RequestInterface $request
     * @param                  $msg
     * @param                  $merchantErrorMsg
     * @param                  $responseStatus
     *
     * @throws \Exception
     */
    public function handleFailedStatusAction(
        RequestInterface $request,
        $msg,
        $merchantErrorMsg,
        $responseStatus
    ) {
        $historyComment = $responseStatus . '|' . $msg;
        if (!empty($merchantErrorMsg)) {
            $historyComment = $historyComment . '|' . $merchantErrorMsg;
        }
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->loadOrderFromCallback($response);
            $orderTransactionId = $order->getPayment()->getLastTransId();
            // Return if the incoming transaction id is different from order transaction id
            if (!empty($orderTransactionId) && $orderTransactionId != $response->transactionId) return;
            $reservationAmount = $this->getReservedAmount($response);
            // Check if the payment status is "error" and if the reservation amount is greater than 0.
            if ($response->status === "error" && $reservationAmount > 0) {
                return;
            }
            $transInfo = $this->getTransactionInfoFromResponse($response);
            $this->handleOrderStateAction($request, Order::STATE_PENDING_PAYMENT, $historyComment, $transInfo);
            //save failed transaction data
            $this->saveTransactionData($response, $order);
        }
    }

    /**
     * @param CallbackResponse $response
     *
     * @return Order
     */
    private function loadOrderFromCallback(CallbackResponse $response)
    {
        return $this->order->loadByIncrementId($response->shopOrderId);
    }

    /**
     * @param RequestInterface $request
     * @param string           $orderState
     * @param string           $historyComment
     * @param null             $transactionInfo
     *
     * @return bool
     */
    public function handleOrderStateAction(
        RequestInterface $request,
        $orderState = Order::STATE_NEW,
        $historyComment = "Order state changed",
        $transactionInfo = null
    ) {
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->loadOrderFromCallback($response);
            $orderTransactionId = $order->getPayment()->getLastTransId();
            // Return if the incoming transaction id is different from order transaction id
            if(!empty($orderTransactionId) && $orderTransactionId != $response->transactionId) return;
            $this->checkoutSession->setAltapayCustomerRedirect(true);
            $order->setState($orderState);
            $order->setStatus($orderState);
            $order->setIsNotified(false);
            if ($transactionInfo !== null) {
                $order->addStatusHistoryComment($transactionInfo);
            }
            $order->addStatusHistoryComment($historyComment);
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
     * @throws AlreadyExistsException
     */
    private function completeCheckout($comment, RequestInterface $request)
    {
        $callback       = new Callback($request->getPostValue());
        $response       = $callback->call();
        if ($response) {
            $order = $this->orderLoader->getOrderByOrderIncrementId($response->shopOrderId);
            if (in_array($order->getStatus(), $this->getAllowedStatuses(), true)) {
                if (!$order->getEmailSent()) {
                    $this->orderSender->send($order);
                }
                return;
            }
            $paymentType = $response->type;
            $paymentStatus = strtolower($response->paymentStatus);
            $transactionId = $response->transactionId;
            $payment = $order->getPayment();

            // Return if the incoming transaction id is different from order transaction id
            if(!empty($payment->getLastTransId()) && $payment->getLastTransId() != $transactionId) return;

            if ($paymentStatus === 'released') {
                $this->handleCancelStatusAction($request, $response->status);
                return false;
            }

            $latestTransKey = $this->helper->getLatestTransaction($response->Transactions);
            $transaction = $response->Transactions[$latestTransKey];
            $status = $response->status;

            if ($status === 'succeeded' && $paymentStatus === 'bank_payment_refunded'
                && $transactionId == $payment->getLastTransId()) {

                // Create Credit Memo
                $this->creditMemo->createCreditMemo($order->getId());

                $this->saveReconciliationData($transaction, $order);

                return false;
            }

            if ($paymentType === 'subscriptionAndCharge' && $status === 'succeeded') {
                $authType    = $transaction->AuthType;
                $transStatus = $transaction->TransactionStatus;

                if (isset($transaction) && $authType === 'subscription_payment' && $transStatus !== 'captured') {
                    $responseComment = __(ConstantConfig::CONSUMER_CANCEL_PAYMENT);
                    $historyComment = __(ConstantConfig::CANCELLED) . '|' . $responseComment;
                    $this->handleOrderStateAction($request, Order::STATE_PENDING_PAYMENT, $historyComment);
                    //save failed transaction data
                    $this->saveTransactionData($response, $order);

                    return false;
                }
            }

            $storeScope = ScopeInterface::SCOPE_STORE;
            $storeCode = $order->getStore()->getCode();
            if ($order->getId()) {
                //Update stock quantity
                if ($order->getState() == 'canceled') {
                    $this->updateStockQty($order);
                }
                $this->resetCanceledQty($order);
                $agreementConfig = $this->getConfigValue($response, $storeScope, $storeCode, "agreementtype");
                $unscheduledTypeConfig = $this->getConfigValue($response, $storeScope, $storeCode, "unscheduledtype");
                $saveCardToken = $this->getConfigValue($response, $storeScope, $storeCode, "savecardtoken");
                $unscheduledType = null;
                $lastFourDigits = '';
                $cardType = '';
                $expires = '';
                if (isset($response->Transactions[$latestTransKey])) {
                    $transaction = $response->Transactions[$latestTransKey];
                    $lastFourDigits = $transaction->CardInformation->LastFourDigits;
                    if (isset($transaction->CreditCardExpiry->Month, $transaction->CreditCardExpiry->Year)) {
                        $expires = $transaction->CreditCardExpiry->Month . '/' . $transaction->CreditCardExpiry->Year;
                    }
                    if (isset($transaction->PaymentSchemeName)) {
                        $cardType = $transaction->PaymentSchemeName;
                    }
                    if ($saveCardToken && (empty($agreementConfig) || $agreementConfig === "unscheduled")) {
                        $agreementType = "unscheduled";
                    } else {
                        $agreementType = $agreementConfig;
                    }
                    if($agreementType == "unscheduled"){
                        $unscheduledType = $unscheduledTypeConfig;
                    }
                    if ($response->type === "verifyCard") {
                        $model = $this->dataToken->create();
                        $model->addData([
                            "customer_id" => $order->getCustomerId(),
                            "payment_id" => $response->paymentId,
                            "token" => $response->creditCardToken,
                            "agreement_id" => $transactionId,
                            "agreement_type" => $agreementType,
                            "agreement_unscheduled" => $unscheduledType,
                            "masked_pan" => $lastFourDigits,
                            "currency_code" => $order->getOrderCurrencyCode(),
                            "expires" => $expires,
                            "card_type" => $cardType
                        ]);
                        try {
                            $model->save();
                        } catch (Exception $e) {
                            $this->altapayLogger->addCriticalLog('Exception', $e->getMessage());
                        }
                    }

                    $this->saveReconciliationData($transaction, $order);
                }
                $payment->setPaymentId($response->paymentId);
                $payment->setLastTransId($transaction->TransactionId);
                $payment->setCcTransId($response->creditCardToken);
                $payment->setAdditionalInformation('cc_token', $response->creditCardToken);
                $payment->setAdditionalInformation('last_four_digits', $lastFourDigits);
                $payment->setAdditionalInformation('expires', $expires);
                $payment->setAdditionalInformation('card_type', $cardType);
                $payment->setAdditionalInformation('payment_type', $paymentType);
                $payment->setAdditionalInformation('require_capture', $response->requireCapture);
                $payment->save();
                //send order confirmation email
                $this->sendOrderConfirmationEmail($order);
                //unset redirect if success
                $this->checkoutSession->unsAltapayCustomerRedirect();
                //save transaction data
                $this->saveTransactionData($response, $order);

                $orderStatusAfterPayment = $this->systemConfig->getStatusConfig('process', $storeScope, $storeCode);
                $orderStatusCapture = $this->systemConfig->getStatusConfig('autocapture', $storeScope, $storeCode);
                $setOrderStatus  = true;
                $orderState = Order::STATE_PROCESSING;
                $statusKey  = 'process';
                if ($this->isCaptured($response, $storeCode, $storeScope, $latestTransKey) && $orderStatusCapture == "complete")
                {
                    if ($this->orderLines->sendShipment($order)) {
                        $orderState = Order::STATE_COMPLETE;
                        $statusKey  = 'autocapture';
                        $order->addStatusHistoryComment(__(ConstantConfig::PAYMENT_COMPLETE));
                    } else {
                        $setOrderStatus = false;
                        $order->addStatusToHistory($orderStatusCapture, ConstantConfig::PAYMENT_COMPLETE, false);
                    }
                } elseif ($orderStatusAfterPayment) {
                    $orderState = $orderStatusAfterPayment;
                }
                if ($setOrderStatus && $order->getStatus() === $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode)) {
                    $this->paymentHandler->setCustomOrderStatus($order, $orderState, $statusKey);
                } elseif ($setOrderStatus && $order->getStatus() === 'canceled' && !in_array($paymentStatus, ['epayment_cancelled', 'released'])) {
                    try {
                        if ($order->isCanceled() || $order->getStatus() == 'canceled') {
                            $productStockQty = [];
                            foreach ($order->getAllVisibleItems() as $item) {
                                $productStockQty[$item->getProductId()] = $item->getQtyCanceled();
                                foreach ($item->getChildrenItems() as $child) {
                                    $productStockQty[$child->getProductId()] = $item->getQtyCanceled();
                                    $child->setQtyCanceled(0);
                                    $child->setTaxCanceled(0);
                                    $child->setDiscountTaxCompensationCanceled(0);
                                }
                                $item->setQtyCanceled(0);
                                $item->setTaxCanceled(0);
                                $item->setDiscountTaxCompensationCanceled(0);
                            }

                            $order->setSubtotalCanceled(0);
                            $order->setBaseSubtotalCanceled(0);
                            $order->setTaxCanceled(0);
                            $order->setBaseTaxCanceled(0);
                            $order->setShippingCanceled(0);
                            $order->setBaseShippingCanceled(0);
                            $order->setDiscountCanceled(0);
                            $order->setBaseDiscountCanceled(0);
                            $order->setTotalCanceled(0);
                            $order->setBaseTotalCanceled(0);
                            $order->setState(Order::STATE_PROCESSING);
                            $order->setStatus(Order::STATE_PROCESSING);
                            $comment = __("The order was un-canceled by the callback");
                            $order->addStatusHistoryComment($comment, false);

                            /* Reverting inventory (uncommented because it is handled by the Business Central integration) */
                            $itemsForReindex = $this->stockManagement->registerProductsSale(
                                $productStockQty,
                                $order->getStore()->getWebsiteId()
                            );
                            $productIds = [];
                            foreach ($itemsForReindex as $item) {
                                $item->save();
                                $productIds[] = $item->getProductId();
                            }
                            if (!empty($productIds)) {
                                $this->stockIndexerProcessor->reindexList($productIds);
                            }
                            $order->setInventoryProcessed(true);
                        }
                    } catch (\Exception $e) {
                        $this->altapayLogger->addCriticalLog('Exception', $e->getMessage());
                    }
                }
                if (!$this->hasOrderComment($order, $comment)) {
                    $order->addStatusHistoryComment($comment);
                }
                $transactionComment = $this->getTransactionInfoFromResponse($response);
                if (!$this->hasOrderComment($order, $transactionComment)) {
                    $order->addStatusHistoryComment($transactionComment);
                }
                $order->setIsNotified(false);
                try {
                    $this->orderRepository->save($order);
                } catch (\Exception $e) {
                    $this->orderRepository->save($order);
                }

                if (strtolower($paymentType) === 'paymentandcapture' || strtolower($paymentType) === 'subscriptionandcharge') {
                    $this->createInvoice($order);
                }
            }
        }
    }

    /**
     * @param $response
     *
     * @return string
     */
    private function getTransactionInfoFromResponse($response)
    {
        return sprintf(
            "Transaction ID: %s",
            $response->transactionId
        );
    }

    /**
     * @param $response
     * @param $storeCode
     * @param $storeScope
     * @param $latestTransKey
     * @return bool|\Magento\Payment\Model\MethodInterface
     */
    private function isCaptured($response, $storeCode, $storeScope, $latestTransKey)
    {
        $isCaptured = false;
        foreach (SystemConfig::getTerminalCodes() as $terminalName) {
            $terminalConfig = $this->systemConfig->getTerminalConfigFromTerminalName(
                $terminalName,
                'terminalname',
                $storeScope,
                $storeCode
            );
            if ($terminalConfig === $response->Transactions[$latestTransKey]->Terminal) {
                $isCaptured = $this->systemConfig->getTerminalConfigFromTerminalName(
                    $terminalName,
                    'capture',
                    $storeScope,
                    $storeCode
                );
                break;
            }
        }

        return $isCaptured;
    }

    /**
     * Send order confirmation email if not already sent.
     *
     * @param $order
     */
    public function sendOrderConfirmationEmail($order)
    {
        if ($order->getEmailSent()) {
            return;
        }
        
        $this->orderSender->send($order);
    }

    /**
     * @param RequestInterface $request
     * @param                  $avsCode
     * @param                  $historyComment
     *
     * @return bool
     */
    public function avsCheck(RequestInterface $request, $avsCode, $historyComment)
    {
        $checkRejectionCase = false;
        $callback           = new Callback($request->getPostValue());
        $response           = $callback->call();
        if ($response) {
            $order                 = $this->loadOrderFromCallback($response);
            $storeScope            = ScopeInterface::SCOPE_STORE;
            $storeCode             = $order->getStore()->getCode();
            $transInfo             = $this->getTransactionInfoFromResponse($response);
            $isAvsEnabled          = $this->terminalConfiguration($response, $storeCode, $storeScope, 'avscontrol');
            $isAvsEnforced         = $this->terminalConfiguration($response, $storeCode, $storeScope, 'enforceavs');
            $getAcceptedAvsResults = $this->getAcceptedAvsResults($response, $storeCode, $storeScope);

            if ($isAvsEnabled) {
                if ($isAvsEnforced && empty($avsCode)) {
                    $checkRejectionCase = true;
                } elseif (stripos($getAcceptedAvsResults ?? '', $avsCode) === false) {
                    $checkRejectionCase = true;
                }
            }
            if ($checkRejectionCase) {
                //Save payment info in order to retrieve it for release operation
                if ($order->getId()) {
                    $this->savePaymentData($response, $order);
                }
                $this->handleOrderStateAction($request, Order::STATE_PENDING_PAYMENT, $historyComment, $transInfo);
                //save failed transaction data
                $this->saveTransactionData($response, $order);
            }
        }

        return $checkRejectionCase;
    }

    /**
     * @param $response
     * @param $storeCode
     * @param $storeScope
     * @param $configField
     *
     * @return bool
     */
    private function terminalConfiguration($response, $storeCode, $storeScope, $configField)
    {
        $isEnabled = false;
        foreach (SystemConfig::getTerminalCodes() as $terminalName) {
            $terminalConfig = $this->systemConfig->getTerminalConfigFromTerminalName(
                $terminalName,
                'terminalname',
                $storeScope,
                $storeCode
            );
            if ($terminalConfig === $response->Transactions[$this->getLatestTransaction($response)]->Terminal) {
                $isEnabled = $this->systemConfig->getTerminalConfigFromTerminalName(
                    $terminalName,
                    $configField,
                    $storeScope,
                    $storeCode
                );
                break;
            }
        }

        return $isEnabled;
    }

    /**
     * @param $response
     * @param $storeCode
     * @param $storeScope
     *
     * @return |null
     */
    private function getAcceptedAvsResults($response, $storeCode, $storeScope)
    {
        $acceptedAvsResults = null;
        foreach (SystemConfig::getTerminalCodes() as $terminalName) {
            $terminalConfig = $this->systemConfig->getTerminalConfigFromTerminalName(
                $terminalName,
                'terminalname',
                $storeScope,
                $storeCode
            );
            if ($terminalConfig === $response->Transactions[$this->getLatestTransaction($response)]->Terminal) {
                $acceptedAvsResults = $this->systemConfig->getTerminalConfigFromTerminalName(
                    $terminalName,
                    'avs_acceptance',
                    $storeScope,
                    $storeCode
                );
                break;
            }
        }

        return $acceptedAvsResults;
    }

    /**
     * @param $response
     * @param $order
     */
    private function savePaymentData($response, $order)
    {
        $payment = $order->getPayment();
        $payment->setPaymentId($response->paymentId);
        $payment->setLastTransId($response->transactionId);
        $payment->setAdditionalInformation('payment_type', $response->type);
        $payment->save();
    }

    /**
     * @param $order
     * return void
     */
    protected function updateStockQty($order)
    {
        $cart = $this->modelCart;
        $quoteItems = $this->checkoutSession->getQuote()->getItemsCollection();
        foreach ($order->getAllItems() as $item) {
            $stockQty  = $this->stockItem->getStockQty($item->getProductId(), $item->getStore()->getWebsiteId());
            $qty       = $stockQty - $item->getQtyOrdered();
            $stockItem = $this->stockRegistry->getStockItemBySku($item['sku']);
            $stockItem->setQty($qty);
            $stockItem->setIsInStock((bool)$qty);
            $this->stockRegistry->updateStockItemBySku($item['sku'], $stockItem);
        }
        foreach($quoteItems as $item)
        {
            $cart->removeItem($item->getId())->save();
        }
    }

    /**
     * @param $order
     *
     * @return void
     */
    private function resetCanceledQty($order) {
        foreach ($order->getAllItems() as $item) {
            if ($item->getQtyCanceled() > 0) {
                $item->setQtyCanceled($item->getQtyToCancel());
                $item->save();
            }
        }
    }

    /**
     * @param $response
     *
     * @return int|string
     */
    private function getLatestTransaction($response) {
        $max_date = '';
        $latestTransKey = 0;
        foreach ($response->Transactions as $key=>$value) {
            if ($value->AuthType === "subscription_payment" && $value->CreatedDate > $max_date) {
                $max_date = $value->CreatedDate;
                $latestTransKey = $key;
            }
        }
        return $latestTransKey;
    }

    /**
     * @param $response
     * @param $storeScope
     * @param $storeCode
     * @param $field
     *
     * @return null
     */
    private function getConfigValue(
        $response,
        $storeScope,
        $storeCode,
        $field = null
    ) {
        $configData = null;
        foreach (SystemConfig::getTerminalCodes() as $terminalName) {
            $terminalConfig = $this->systemConfig->getTerminalConfigFromTerminalName(
                    $terminalName,
                    'terminalname',
                    $storeScope,
                    $storeCode
                );
            if ($terminalConfig === $response->Transactions[$this->getLatestTransaction($response)]->Terminal
            ) {

                $configData = $this->systemConfig->getTerminalConfigFromTerminalName(
                        $terminalName,
                        $field,
                        $storeScope,
                        $storeCode
                    );
                break;
            }
        }

        return $configData;
    }

    /**
     * @param RequestInterface $request
     * @param string $fraudStatus
     * @param string $message
     *
     * @return bool
     */
    public function fraudCheck(RequestInterface $request, $fraudStatus, $message)
    {
        $callback   = new Callback($request->getPostValue());
        $response   = $callback->call();

        if ($response) {
            $order                 = $this->loadOrderFromCallback($response);
            $storeScope            = ScopeInterface::SCOPE_STORE;
            $storeCode             = $order->getStore()->getCode();
            $fraudConfig           = $this->systemConfig->getFraudConfig('enable_fraud', $storeScope, $storeCode);
            $enableReleaseRefund   = $this->systemConfig->getFraudConfig('enable_release_refund', $storeScope, $storeCode);
            $transInfo             = $this->getTransactionInfoFromResponse($response);
            if ($fraudConfig && $enableReleaseRefund && $fraudStatus === "deny") {
                // Save payment info in order to retrieve it for release operation
                if ($order->getId()) {
                    $this->savePaymentData($response, $order);
                }
                $this->handleOrderStateAction($request, Order::STATE_PAYMENT_REVIEW, $message, $transInfo);
                //save failed transaction data
                $this->saveTransactionData($response, $order);

                return true;
            }

            return false;
        }
    }
    
    /**
     * Get the CardHolderErrorMessage from the request object.
     *
     * @param RequestInterface $request
     *
     * @return string|null Returns the CardHolderErrorMessage, or null if it doesn't exist.
     */
    public function getCardHolderErrorMessage(RequestInterface $request)
    {
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        
        return $response->CardHolderErrorMessage ?? null;
    }

    /**
     * @param $transaction
     * @param $order
     * @return void
     */
    public function saveReconciliationData($transaction, $order)
    {
        $reconciliationData = $transaction->ReconciliationIdentifiers ?? '';

        if ($reconciliationData && is_array($reconciliationData)) {
            $model = $this->reconciliation->create();

            foreach ($reconciliationData as $value) {
                $collection = $this->helper->getReconciliationData($order->getIncrementId(), $value->Id);
                if (!$collection->getSize()) {
                    $model->addData([
                        "order_id" => $order->getIncrementId(),
                        "identifier" => $value->Id,
                        "type" => $value->Type
                    ]);
                }
            }
            $model->save();
        }
    }

    /**
     * Retrieves the reserved amount from the response object.
     *
     * @param $response
     * @return mixed
     */
    private function getReservedAmount($response) {
        $latestTransKey = $this->helper->getLatestTransaction($response->Transactions);
        $transaction    = $response->Transactions[$latestTransKey];

        return $transaction->ReservedAmount;
    }

    /**
     * Compare stored transaction ID with the incoming transaction ID.
     *
     * @param string $shopOrderId
     * @param string $incomingTransactionId
     * @return bool
     */
    public function validateTransactionId($shopOrderId, $incomingTransactionId)
    {
        $storedTransactionId = $this->transactionRepository->getTransactionDataByOrderId($shopOrderId);

        // Check if the stored transaction ID is not null and matches the incoming transaction ID
        if (!empty($storedTransactionId) && $storedTransactionId !== $incomingTransactionId) {
            return false;
        }

        return true;
    }
    /**
     * Check if the given comment already exists in the order's status history.
     *
     * @param Order $order
     * @param string $commentText
     * @return bool
     */
    private function hasOrderComment(Order $order, $commentText)
    {
        foreach ($order->getStatusHistoryCollection() as $history) {
            if (trim($history->getComment()) === trim($commentText)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get a list of statuses that allow order email sending.
     *
     * @return array
     */
    public function getAllowedStatuses(): array
    {
        return ['processing', 'complete'];
    }
}
