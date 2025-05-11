<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use Magento\Bundle\Model\Product\Price;
use SDM\Altapay\Api\GatewayInterface;
use SDM\Altapay\Api\OrderLoaderInterface;
use Altapay\Api\Payments\CardWalletAuthorize;
use SDM\Altapay\Model\ApplePayOrder;
use Altapay\Request\Config;
use Altapay\Api\Ecommerce\PaymentRequest;
use Altapay\Exceptions\ClientException;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Exceptions\ResponseMessageException;
use SDM\Altapay\Helper\Data;
use SDM\Altapay\Helper\Config as storeConfig;
use SDM\Altapay\Logger\Logger;
use SDM\Altapay\Model\Handler\CustomerHandler;
use SDM\Altapay\Model\Handler\OrderLinesHandler;
use SDM\Altapay\Model\Handler\CreatePaymentHandler;
use SDM\Altapay\Model\TokenFactory;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Altapay\Api\Payments\ApplePayWalletAuthorize;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Math\Random;
use Altapay\Api\Payments\ReservationOfFixedAmount;
use SDM\Altapay\Api\TransactionRepositoryInterface;
use Altapay\Api\Others\Terminals;
use Magento\Framework\Event\ManagerInterface;

/**
 * Class Gateway
 * Handle the create payment related functionality.
 */
class Gateway implements GatewayInterface
{
    /**
     * @var Helper Data
     */
    private $helper;
    /**
     * @var Helper Config
     */
    private $storeConfig;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var SystemConfig
     */
    private $systemConfig;
    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var UrlInterface
     */
    private $urlInterface;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var Quote
     */
    private $quote;
    /**
     * @var Logger
     */
    protected $altapayLogger;
    /**
     * @var CustomerHandler
     */
    private $customerHandler;
    /**
     * @var OrderLinesHandler
     */
    private $orderLines;
    /**
     * @var CreatePaymentHandler
     */
    private $paymentHandler;
    /**
     * @var TokenFactory
     */
    private $dataToken;
    /**
     * @var ApplePayOrder
     */
    private $applePayOrder;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;
    /**
     * @var Random
     */
    private $random;

    /**
     * @var ManagerInterface
     */
    protected $_eventManager;
    /**
     * Gateway constructor.
     *
     * @param Session                        $checkoutSession
     * @param UrlInterface                   $urlInterface
     * @param Http                           $request
     * @param Order                          $order
     * @param SystemConfig                   $systemConfig
     * @param OrderLoaderInterface           $orderLoader
     * @param Quote                          $quote
     * @param Data                           $helper
     * @param storeConfig                    $storeConfig
     * @param Logger                         $altapayLogger
     * @param CustomerHandler                $customerHandler
     * @param OrderLinesHandler              $orderLines
     * @param CreatePaymentHandler           $paymentHandler
     * @param TokenFactory                   $dataToken
     * @param ApplePayOrder                  $applePayOrder
     * @param StoreManagerInterface          $storeManager
     * @param Random                         $random
     * @param TransactionRepositoryInterface $transactionRepository
     * @param ManagerInterface               $eventManager
     */
    public function __construct(
        Session $checkoutSession,
        UrlInterface $urlInterface,
        Http $request,
        Order $order,
        SystemConfig $systemConfig,
        OrderLoaderInterface $orderLoader,
        Quote $quote,
        Data $helper,
        storeConfig $storeConfig,
        Logger $altapayLogger,
        CustomerHandler $customerHandler,
        OrderLinesHandler $orderLines,
        CreatePaymentHandler $paymentHandler,
        TokenFactory $dataToken,
        ApplePayOrder $applePayOrder,
        StoreManagerInterface $storeManager,
        Random $random,
        TransactionRepositoryInterface $transactionRepository,
        ManagerInterface $eventManager
    )
    {
        $this->checkoutSession       = $checkoutSession;
        $this->urlInterface          = $urlInterface;
        $this->request               = $request;
        $this->order                 = $order;
        $this->systemConfig          = $systemConfig;
        $this->orderLoader           = $orderLoader;
        $this->quote                 = $quote;
        $this->helper                = $helper;
        $this->storeConfig           = $storeConfig;
        $this->altapayLogger         = $altapayLogger;
        $this->customerHandler       = $customerHandler;
        $this->orderLines            = $orderLines;
        $this->paymentHandler        = $paymentHandler;
        $this->dataToken             = $dataToken;
        $this->applePayOrder         = $applePayOrder;
        $this->storeManager          = $storeManager;
        $this->random                = $random;
        $this->transactionRepository = $transactionRepository;
        $this->_eventManager         = $eventManager;
    }

    /**
     * createRequest to AltaPay
     *
     * @param int $terminalId
     * @param string $orderId
     *
     * @return array|mixed
     */
    public function createRequest($terminalId, $orderId)
    {
        $order = $this->order->load($orderId);
        if ($order->getId()) {
            $orderLines = $this->getItemOrderLines($order);
            $this->_eventManager->dispatch('additional_orderline_event_observer',
                [
                    'order_lines'   => &$orderLines,
                    'quote_id'  => $order->getQuoteId()
                ]
            );
            $request = $this->preparePaymentRequest($order, $orderLines, $orderId, $terminalId, null);
            if ($request) {
                return $this->sendPaymentRequest($order, $request);
            }
        }

        return $this->restoreOrderAndReturnError($order);
    }

    /**
     * @param $terminalId
     * @param $orderId
     * @param $providerData
     *
     * @return mixed
     */
    public function createRequestApplepay($terminalId, $orderId, $providerData)
    {
        $order = $this->order->load($orderId);
        if ($order->getId()) {
            $orderLines = $this->getItemOrderLines($order);
            $order->setModuleVersion($this->helper->getModuleVersion());
            $order->getResource()->save($order);

            $request = $this->preparePaymentRequest($order, $orderLines, $orderId, $terminalId, $providerData);
            if ($request) {
                try {
                    $response = $request->call();
                    $this->applePayOrder->handleCardWalletPayment($response, $order);
                } catch (ClientException $e) {
                    $response['result']  = ConstantConfig::ERROR;
                    $response['message'] = $e->getResponse()->getBody();
                } catch (\Exception $e) {
                    $response['result']  = ConstantConfig::ERROR;
                    $response['message'] = $e->getMessage();
                }

                return $response;
            }
        }

        return $this->restoreOrderAndReturnError($order);
    }

    /**
     * @param $orderId
     *
     * @throws AlreadyExistsException
     */
    public function restoreOrderFromOrderId($orderId)
    {
        $order = $this->orderLoader->getOrderByOrderIncrementId($orderId);
        if ($order->getId()) {
            $quote = $this->quote->loadByIdWithoutStore($order->getQuoteId());
            $quote->setIsActive(1)->setReservedOrderId(null);
            $quote->getResource()->save($quote);
            $this->checkoutSession->replaceQuote($quote);
        }
    }

    /**
     * @param $storeScope
     * @param $storeCode
     * @return Config
     */
    private function setConfig($storeScope, $storeCode)
    {
        $config = new Config();
        $config->setCallbackOk($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_OK));
        $config->setCallbackFail($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_FAIL));
        $config->setCallbackRedirect($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_REDIRECT));
        $config->setCallbackOpen($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_OPEN));
        $config->setCallbackNotification($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_NOTIFICATION));

        $layout = $this->systemConfig->getLayoutConfig('option', $storeScope, $storeCode);
        if($layout === "custom_layout") {
            $config->setCallbackForm($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_EXTERNAL_CALLBACK));
        } else {
            $config->setCallbackForm($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_CALLBACK));
        }

        return $config;
    }

    /**
     * @param $order
     * @return array
     */
    public function itemOrderLines($order)
    {
        $orderLines = [];
        foreach ($order->getAllItems() as $item) {
            $productType = $item->getProductType();
            $parentItemType = "";
            if ($item->getParentItem()) {
                $parentItemType = $item->getParentItem()->getProductType();
            }

            if (
                ($productType != "bundle" && $parentItemType != "configurable") ||
                ($productType === "bundle" && $item->getProduct()->getPriceType() == Price::PRICE_TYPE_FIXED)
            ) {
                $orderLines[] = $this->orderLines->itemOrderLine($item, $order, true);
            }
        }

        return $orderLines;
    }

    /**
     * @param $order
     *
     * @return mixed
     */
    private function restoreOrderAndReturnError($order)
    {
        $this->restoreOrderFromOrderId($order->getIncrementId());
        $requestParams['result'] = ConstantConfig::ERROR;
        $requestParams['message'] = __(ConstantConfig::ERROR_MESSAGE);

        return $requestParams;
    }

    /**
     * Prepare request to the altapay, sets the necessary parameters.
     *
     * @param $order
     * @param $orderLines
     * @param $orderId
     * @param $terminalId
     * @param $providerData
     *
     * @return bool|PaymentRequest|CardWalletAuthorize
     */
    private function preparePaymentRequest($order, $orderLines, $orderId, $terminalId, $providerData)
    {
        $quote = $this->quote->loadByIdWithoutStore($order->getQuoteId());
        $storeScope = $this->storeConfig->getStoreScope();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $storeCode = $order->getStore()->getCode();
        $isReservation = false;
        $auth = $this->systemConfig->getAuth($storeCode);
        $terminalName = $this->systemConfig->getTerminalConfig($terminalId, 'terminalname', $storeScope, $storeCode);
        $isApplePay = $this->systemConfig->getTerminalConfig($terminalId, 'isapplepay', $storeScope, $storeCode);
        $agreementConfig = $this->systemConfig->getTerminalConfig($terminalId, 'agreementtype', $storeScope, $storeCode);
        $unscheduledTypeConfig = $this->systemConfig->getTerminalConfig($terminalId, 'unscheduledtype', $storeScope, $storeCode);
        $savecardtoken = $this->systemConfig->getTerminalConfig($terminalId, 'savecardtoken', $storeScope, $storeCode);
        $data = null;
        //Transaction Info
        $transactionDetail = $this->helper->transactionDetail($orderId);
        $payment = $order->getPayment();
        $post = $this->request->getPostValue();
        $request = new PaymentRequest($auth);
        if ($isApplePay) {
            $request = new CardWalletAuthorize($auth);
            $request->setProviderData($providerData);
        }

        if (!empty($post['tokenid'])) {
            $data = $this->getToken($order->getCustomerId(), $post['tokenid'], null);
        }
        elseif (isset($post['transaction_id']) && $post['type'] === "verifyCard") {
                $data = $this->getToken($order->getCustomerId(), null, $post['transaction_id']);
        }

        if ($savecardtoken && !empty($data)) {
            $request = new ReservationOfFixedAmount($auth);
            $token   = $data['token'];
            $request->setCreditCardToken($token);
            $request->setAgreement(
                $this->agreementDetail(
                    $payment,
                    $quote->getAllItems(),
                    $baseUrl,
                    $data['agreement_type'],
                    $data['agreement_id'],
                    $data['agreement_unscheduled']
                )
            );
            $isReservation = true;
        }
        $baseCurrency = $this->storeConfig->useBaseCurrency();
        $grandTotal = $baseCurrency ? $order->getBaseGrandTotal() : $order->getGrandTotal();
        $currencyCode = $baseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();

        $request->setTerminal($terminalName)
            ->setShopOrderId($order->getIncrementId())
            ->setAmount(round($grandTotal, 2))
            ->setCurrency($currencyCode)
            ->setCustomerInfo($this->customerHandler->setCustomer($order, $isReservation))
            ->setTransactionInfo($transactionDetail)
            ->setCookie($this->request->getServer('HTTP_COOKIE'))
            ->setSaleReconciliationIdentifier($this->random->getUniqueHash())
            ->setConfig($this->setConfig($storeScope, $storeCode));
        
        if(!$isReservation) {
            $request->setSalesTax(round($order->getTaxAmount(), 2));
        }
        if ($fraud = $this->systemConfig->getTerminalConfig($terminalId, 'fraud', $storeScope, $storeCode)) {
            $request->setFraudService($fraud);
        }
        if ($lang = $this->systemConfig->getTerminalConfig($terminalId, 'language', $storeScope, $storeCode)) {
            $langArr = explode('_', $lang, 2);
            if (isset($langArr[0])) {
                $request->setLanguage($langArr[0]);
            }
        }
        if ($this->helper->validateQuote($quote)) {
            if ($this->systemConfig->getTerminalConfig($terminalId, 'capture', $storeScope, $storeCode)) {
                $request->setType('subscriptionAndCharge');
            } else {
                $request->setType('subscription');
            }
        }

        // check if auto capture enabled
        if (!$this->helper->validateQuote($quote) && $this->systemConfig->getTerminalConfig($terminalId, 'capture', $storeScope, $storeCode)) {
            $request->setType('paymentAndCapture');
        }

        $shouldSaveCard = isset($post['savecard']) && $post['savecard'] && $savecardtoken;
        if ($shouldSaveCard) {
            $isCreditCard = false;
            $nature       = $this->terminalNature($auth, $terminalName);
            if (count($nature) == 1 && $nature[0]->Nature === "CreditCard") {
                $isCreditCard = true;
            }
            if ($isCreditCard) {
                $isRecurringProduct = $this->helper->validateQuote($quote);
            
                if ($agreementConfig === "recurring" || $agreementConfig === "instalment") {
                    if ($isRecurringProduct) {
                        $request->setAgreement($this->agreementDetail($payment, $quote->getAllItems(), $baseUrl, $agreementConfig));
                    }
                } elseif (empty($agreementConfig) || $agreementConfig === "unscheduled") {
                    $request->setAgreement($this->agreementDetail($payment, $quote->getAllItems(), $baseUrl, "unscheduled", null, $unscheduledTypeConfig));
                    $request->setType('verifyCard');
                }
            }
        }

        $totalCompensationAmount = $this->orderLines->totalCompensationAmount($orderLines, $grandTotal);

        if ($totalCompensationAmount > 0 || $totalCompensationAmount < 0) {
            $orderLines[] = $this->orderLines->compensationOrderLine(
                "Compensation Amount",
                "comp-amount",
                $totalCompensationAmount
            );
        }

        //set orderlines to the request
        $request->setOrderLines($orderLines);

        return $request;
    }

    /**
     * Send payment request to the AltaPay.
     *
     * @param $order
     * @param $request
     *
     * @return mixed
     */
    private function sendPaymentRequest($order, $request)
    {
        $storeScope    = $this->storeConfig->getStoreScope();
        $storeCode     = $order->getStore()->getCode();
        $isReservation = false;
        try {
            /** @var PaymentRequestResponse $response */
            $response       = $request->call();
            $responseUrl    = $response->Url;
            $paymentId      = $response->PaymentRequestId;
            $latestTransKey = 0;
            if (isset($response->Transactions)) {
                $latestTransKey = $this->helper->getLatestTransaction($response->Transactions, 'subscription_payment');
            }
            if (strtolower($response->Result) === "success" && $responseUrl == null) {
                $this->handleReservation($order, $response, $request, $latestTransKey);
                $isReservation = true;
                if (isset($response->Transactions[$latestTransKey])) {
                    $paymentId = $response->Transactions[$latestTransKey]->PaymentId;
                }
                $responseUrl = 'onepage/success';
            }
            $requestParams['result']  = ConstantConfig::SUCCESS;
            $requestParams['formurl'] = $responseUrl;
            // set before payment status
            if (!$isReservation && $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode)) {
                $this->paymentHandler->setCustomOrderStatus($order, Order::STATE_NEW, 'before');
                // set notification
                $order->addStatusHistoryComment(__(ConstantConfig::REDIRECT_TO_ALTAPAY) . $paymentId);
            }
            $extensionAttribute = $order->getExtensionAttributes();
            if ($extensionAttribute && $extensionAttribute->getAltapayPaymentFormUrl()) {
                $extensionAttribute->setAltapayPaymentFormUrl($responseUrl);
            }
            $order->setAltapayPaymentFormUrl($responseUrl);
            $order->setAltapayPriceIncludesTax($this->storeConfig->storePriceIncTax());
            $order->setModuleVersion($this->helper->getModuleVersion());
            $order->getResource()->save($order);
            //set flag if customer redirect to AltaPay
            $this->checkoutSession->setAltapayCustomerRedirect(true);
            return $requestParams;
        } catch (ClientException $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] = __(ConstantConfig::ERROR_MESSAGE);
            $this->altapayLogger->addCriticalLog('PaymentRequest Exception:' , $e->getMessage());
        } catch (ResponseHeaderException $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] =  __(ConstantConfig::ERROR_MESSAGE);
            $this->altapayLogger->addCriticalLog('PaymentRequest Exception:' , $e->getHeader()->ErrorMessage);
        } catch (ResponseMessageException $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] =  __(ConstantConfig::ERROR_MESSAGE);
            $this->altapayLogger->addCriticalLog('PaymentRequest Exception:' , $e->getMessage());
        } catch (\Exception $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] =  __(ConstantConfig::ERROR_MESSAGE);
            $this->altapayLogger->addCriticalLog('PaymentRequest Exception:' , $e->getMessage());
        }

        $this->restoreOrderFromOrderId($order->getIncrementId());
        
        return $requestParams;
    }

    /**
     * @param $order
     *
     * @return float|int
     */
    private function fixedProductTax($order)
    {
        $weeTaxAmount = 0;
        foreach ($order->getAllItems() as $item) {
            $weeTaxAmount += $item->getWeeeTaxAppliedRowAmount();
        }

        return $weeTaxAmount;
    }
    
    /**
     * @param $payment
     * @param $items
     * @param $baseUrl
     * @param $agreementType
     * @param $agreementId
     * @param $unscheduledType
     * @return array
     */
    private function agreementDetail($payment, $items, $baseUrl, $agreementType = null, $agreementId = null, $unscheduledType = null)
    {
        $agreementDetails = [];
        if ($items && !empty($agreementType)) {
            if ($agreementId) {
                $agreementDetails['id'] = $agreementId;
            }
            $agreementDetails['type'] = $agreementType;
            if ($agreementType === "unscheduled") {
                $agreementDetails['unscheduled_type'] = $unscheduledType;
            }
            if ($agreementType === "recurring") {
                $agreementDetails['adminUrl'] = $baseUrl . 'amasty_recurring/customer/subscriptions/';
                /** @var Item $item */
                foreach ($items as $item) {
                    $buyRequest = $this->helper->getBuyRequestObject($item);
                    if ($buyRequest->getData('am_subscription_end_type') === 'amrec-end-date') {
                        $expiryDate = date("Ymd", strtotime($buyRequest->getData('am_rec_end_date')));
                        $agreementDetails['expiry'] = $expiryDate;
                    }
                }
            }
        }
        $payment->setAdditionalInformation('agreement_detail', $agreementDetails);
        $payment->save();

        return $agreementDetails;
    }
    
    /**
     * @param $order
     * @param $response
     * @param $request
     * @param $latestTransKey
     *
     * @return void
     */
    private function handleReservation($order, $response, $request, $latestTransKey)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode  = $order->getStore()->getCode();
        $comment    = 'Reservation callback from Altapay';
        if (isset($response->Transactions[$latestTransKey])) {
            $transaction = $response->Transactions[$latestTransKey];
            $payment     = $order->getPayment();
            $payment->setPaymentId($transaction->PaymentId);
            $payment->setLastTransId($transaction->TransactionId);
            $payment->setCcTransId($transaction->CreditCardToken);
            $payment->setAdditionalInformation('payment_type', $transaction->AuthType);
            $payment->save();
            //save transaction data
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
        $orderStatusAfterPayment = $this->systemConfig->getStatusConfig('process', $storeScope, $storeCode);
        $orderStatusCapture      = $this->systemConfig->getStatusConfig('autocapture', $storeScope, $storeCode);
        $setOrderStatus          = true;
        $orderState              = Order::STATE_PROCESSING;
        $statusKey               = 'process';
        
        if ($this->isCaptured($response, $storeCode, $storeScope, $latestTransKey))
        {
            if ($orderStatusCapture == "complete") {
                if ($this->orderLines->sendShipment($order)) {
                    $orderState = Order::STATE_COMPLETE;
                    $statusKey  = 'autocapture';
                    $order->addStatusHistoryComment(__(ConstantConfig::PAYMENT_COMPLETE));
                } else {
                    $setOrderStatus = false;
                    $order->addStatusToHistory($orderStatusCapture,
                        ConstantConfig::PAYMENT_COMPLETE, false);
                }
            }
        } else {
            if ($orderStatusAfterPayment) {
                $orderState = $orderStatusAfterPayment;
            }
        }

        if ($setOrderStatus) {
            $this->paymentHandler->setCustomOrderStatus($order, $orderState, $statusKey);
        }
        $order->addStatusHistoryComment($comment);
        $order->setIsNotified(false);
        $order->getResource()->save($order);
    
        if (isset($response->Transactions[$latestTransKey])) {
            $paymentType = $response->Transactions[$latestTransKey]->AuthType ?? '';
            $requireCapture = $response->Transactions[$latestTransKey]->RequireCapture ?? '';
            $transStatus = $response->Transactions[$latestTransKey]->TransactionStatus ?? '';
            if (strtolower($paymentType) === 'paymentandcapture'
                || strtolower($paymentType) === 'subscriptionandcharge'
                || ($paymentType === 'subscription_payment' && $transStatus === 'captured')
            ) {
                $this->paymentHandler->createInvoice($order);
            }
        }

    }
    
    /**
     * @param $response
     * @param $storeCode
     * @param $storeScope
     * @param $latestTransKey
     *
     * @return bool
     */
    private function isCaptured(
        $response,
        $storeCode,
        $storeScope,
        $latestTransKey
    ) {
        $isCaptured = false;
        foreach (SystemConfig::getTerminalCodes() as $terminalName) {
            $terminalConfig =
                $this->systemConfig->getTerminalConfigFromTerminalName(
                    $terminalName,
                    'terminalname',
                    $storeScope,
                    $storeCode
                );
            if (isset($response->Transactions[$latestTransKey]->Terminal) && $terminalConfig === $response->Transactions[$latestTransKey]->Terminal) {
                $isCaptured =
                    $this->systemConfig->getTerminalConfigFromTerminalName(
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
     * @param int $customerId
     * @param string $tokenId
     * @param int $transId
     *
     * @return mixed
     */
    private function getToken($customerId, $tokenId = null, $transId = null)
    {
        $model      = $this->dataToken->create();
        $collection = $model->getCollection()
            ->addFieldToFilter('customer_id', $customerId);
        
        if ($transId == null) {
            $collection->addFieldToFilter('id', $tokenId);
        } else {
            $collection->addFieldToFilter('agreement_id', $transId);
        }
        
        return $collection->getFirstItem()->getData();
    }

    /**
     * Retrieve the nature of the selected terminal
     * 
     * @param object $auth
     * @param string $selectedTerminal
     * 
     * @return array An array of nature objects
     */
    private function terminalNature($auth, $selectedTerminal)
    {
        $call     = new Terminals($auth);
        $response = $call->call();
        foreach ($response->Terminals as $terminal) {
            if($terminal->Title == $selectedTerminal) {
                return $terminal->Natures;
            }
        }
        return [];
    }

    /**
     * @param $order
     * @return array
     */
    public function getItemOrderLines($order)
    {
        $baseCurrency = $this->storeConfig->useBaseCurrency();
        $total        = $baseCurrency ? $order->getBaseGrandTotal() : $order->getGrandTotal();
        $orderLines   = $this->itemOrderLines($order);
        if ($order->getShippingAmount() > 0 || $order->getShippingTaxAmount() > 0) {
            $orderLines[] = $this->orderLines->shippingOrderLine($order, true);
        }
        $couponCodeAmount = $baseCurrency ? $order->getBaseDiscountAmount() : $order->getDiscountAmount();
        if (abs($couponCodeAmount) > 0) {
            $couponCode   = $order->getDiscountDescription();
            $orderLines[] = $this->orderLines->discountOrderLine($couponCodeAmount, $couponCode);
        }
        if (!empty($this->fixedProductTax($order))) {
            $orderLines[] = $this->orderLines->fixedProductTaxOrderLine($this->fixedProductTax($order));
        }
        $totalCompensationAmount = $this->orderLines->totalCompensationAmount($orderLines, $total);
        if ($totalCompensationAmount > 0 || $totalCompensationAmount < 0) {
            $orderLines[] = $this->orderLines->compensationOrderLine(
                "Compensation Amount",
                "comp-amount",
                $totalCompensationAmount
            );
        }
        return $orderLines;
    }
}
