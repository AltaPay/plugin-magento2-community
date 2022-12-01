<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use SDM\Altapay\Api\GatewayInterface;
use SDM\Altapay\Api\OrderLoaderInterface;
use Altapay\Api\Payments\CardWalletAuthorize;
use SDM\Altapay\Model\ApplePayOrder;
use Altapay\Request\Config;
use Altapay\Api\Ecommerce\PaymentRequest;
use Altapay\Api\Test\TestAuthentication;
use Altapay\Exceptions\ClientException;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Exceptions\ResponseMessageException;
use SDM\Altapay\Helper\Data;
use SDM\Altapay\Helper\Config as storeConfig;
use SDM\Altapay\Logger\Logger;
use SDM\Altapay\Model\Handler\CustomerHandler;
use SDM\Altapay\Model\Handler\OrderLinesHandler;
use SDM\Altapay\Model\Handler\PriceHandler;
use SDM\Altapay\Model\Handler\DiscountHandler;
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
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Service\InvoiceService;

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
     * @var PriceHandler
     */
    private $priceHandler;
    /**
     * @var DiscountHandler
     */
    private $discountHandler;
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
     * @var TransactionFactory
     */
    private $transactionFactory;
    
    /**
     * @var InvoiceService
     */
    private $invoiceService;
    
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
     * @param PriceHandler                   $priceHandler
     * @param DiscountHandler                $discountHandler
     * @param CreatePaymentHandler           $paymentHandler
     * @param TokenFactory                   $dataToken
     * @param ApplePayOrder                  $applePayOrder
     * @param StoreManagerInterface          $storeManager
     * @param Random                         $random
     * @param TransactionRepositoryInterface $transactionRepository
     * @param TransactionFactory             $transactionFactory
     * @param InvoiceService                 $invoiceService
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
        PriceHandler $priceHandler,
        DiscountHandler $discountHandler,
        CreatePaymentHandler $paymentHandler,
        TokenFactory $dataToken,
        ApplePayOrder $applePayOrder,
        StoreManagerInterface $storeManager,
        Random $random,
        TransactionRepositoryInterface $transactionRepository,
        TransactionFactory $transactionFactory,
        InvoiceService $invoiceService
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
        $this->priceHandler          = $priceHandler;
        $this->discountHandler       = $discountHandler;
        $this->paymentHandler        = $paymentHandler;
        $this->dataToken             = $dataToken;
        $this->applePayOrder         = $applePayOrder;
        $this->storeManager          = $storeManager;
        $this->random                = $random;
        $this->transactionRepository = $transactionRepository;
        $this->transactionFactory    = $transactionFactory;
        $this->invoiceService        = $invoiceService;
    }

    /**
     * createRequest to altapay
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
            $couponCode = $order->getDiscountDescription();
            $couponCodeAmount = $order->getDiscountAmount();
            $discountAllItems = $this->discountHandler->allItemsHaveDiscount($order->getAllItems());
            $orderLines = $this->itemOrderLines($couponCodeAmount, $order, $discountAllItems);
            if ($this->orderLines->sendShipment($order) && !empty($order->getShippingMethod(true))) {
                $orderLines[] = $this->orderLines->handleShipping($order, $discountAllItems, true);
                //Shipping Discount Tax Compensation Amount
                $compAmount = $this->discountHandler->hiddenTaxDiscountCompensation($order, $discountAllItems, true);
                if ($compAmount > 0 && $discountAllItems == false) {
                    $orderLines[] = $this->orderLines->compensationOrderLine(
                        "Shipping compensation",
                        "comp-ship",
                        $compAmount
                    );
                }
            }
            if ($discountAllItems && abs($couponCodeAmount) > 0) {
                $orderLines[] = $this->orderLines->discountOrderLine($couponCodeAmount, $couponCode);
            }
            if (!empty($this->fixedProductTax($order))) {
                $orderLines[] = $this->orderLines->fixedProductTaxOrderLine($this->fixedProductTax($order));
            }
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
            $couponCode = $order->getDiscountDescription();
            $couponCodeAmount = $order->getDiscountAmount();
            $discountAllItems = $this->discountHandler->allItemsHaveDiscount($order->getAllItems());
            $orderLines = $this->itemOrderLines($couponCodeAmount, $order, $discountAllItems);
            if ($this->orderLines->sendShipment($order) && !empty($order->getShippingMethod(true))) {
                $orderLines[] = $this->orderLines->handleShipping($order, $discountAllItems, true);
                //Shipping Discount Tax Compensation Amount
                $compAmount = $this->discountHandler->hiddenTaxDiscountCompensation($order, $discountAllItems, true);
                if ($compAmount > 0 && $discountAllItems == false) {
                    $orderLines[] = $this->orderLines->compensationOrderLine(
                        "Shipping compensation",
                        "comp-ship",
                        $compAmount
                    );
                }
            }
            if ($discountAllItems && abs($couponCodeAmount) > 0) {
                $orderLines[] = $this->orderLines->discountOrderLine($couponCodeAmount, $couponCode);
            }
            if (!empty($this->fixedProductTax($order))) {
                $orderLines[] = $this->orderLines->fixedProductTaxOrderLine($this->fixedProductTax($order));
            }
            $request = $this->preparePaymentRequest($order, $orderLines, $orderId, $terminalId, $providerData);
            if ($request) {
                $response = $request->call();
                $this->applePayOrder->handleCardWalletPayment($response, $order);

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
     * @return Config
     */
    private function setConfig()
    {
        $config = new Config();
        $config->setCallbackOk($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_OK));
        $config->setCallbackFail($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_FAIL));
        $config->setCallbackRedirect($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_REDIRECT));
        $config->setCallbackOpen($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_OPEN));
        $config->setCallbackNotification($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_NOTIFICATION));
        $config->setCallbackForm($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_CALLBACK));

        return $config;
    }

    /**
     * @param $couponCodeAmount
     * @param $order
     * @param $discountAllItems
     *
     * @return array
     */
    private function itemOrderLines($couponCodeAmount, $order, $discountAllItems)
    {
        $orderLines = [];
        $storePriceIncTax = $this->storeConfig->storePriceIncTax();

        foreach ($order->getAllItems() as $item) {
            $productType = $item->getProductType();
            $originalPrice = $item->getBaseOriginalPrice();
            $taxPercent = $item->getTaxPercent();
            $discountAmount = $item->getBaseDiscountAmount();
            $parentItemType = "";
            if ($item->getParentItem()) {
                $parentItemType = $item->getParentItem()->getProductType();
            }
            if ($productType != "bundle" && $parentItemType != "configurable") {

                if ($originalPrice == 0) {
                    $originalPrice = $item->getPriceInclTax();
                }

                if ($storePriceIncTax) {
                    $unitPriceWithoutTax = $this->priceHandler->getPriceWithoutTax($originalPrice, $taxPercent);
                    $unitPrice = bcdiv($unitPriceWithoutTax, 1, 2);
                } else {
                    $unitPrice = $originalPrice;
                }
                $dataForPrice = $this->priceHandler->dataForPrice(
                    $item,
                    $unitPrice,
                    $couponCodeAmount,
                    $this->discountHandler->getItemDiscount($discountAmount, $originalPrice, $item->getQtyOrdered()),
                    $discountAllItems
                );
                $taxAmount = $dataForPrice["taxAmount"];
                $catalogDiscount = $dataForPrice["catalogDiscount"];
                $discount = $this->discountHandler->orderLineDiscount(
                    $discountAllItems,
                    $dataForPrice["discount"],
                    $catalogDiscount
                );

                $itemTaxAmount = $taxAmount;
                $orderLines[] = $this->orderLines->itemOrderLine(
                    $item,
                    $unitPrice,
                    $discount,
                    $itemTaxAmount,
                    $order,
                    true
                );
                $roundingCompensation = $this->priceHandler->compensationAmountCal(
                    $item,
                    $unitPrice,
                    $taxAmount,
                    $discount,
                    true
                );
                // check if rounding compensation amount, send in the separate orderline
                if ($roundingCompensation > 0 || $roundingCompensation < 0) {
                    $orderLines[] = $this->orderLines->compensationOrderLine(
                        "Compensation Amount",
                        "comp-" . $item->getItemId(),
                        $roundingCompensation
                    );
                }
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
        //Authenticate the connection with the Payment Gateway
        $auth = $this->systemConfig->getAuth($storeCode);
        $api = new TestAuthentication($auth);
        $response = $api->call();
        if (!$response) {
            return false;
        }
        $terminalName = $this->systemConfig->getTerminalConfig($terminalId, 'terminalname', $storeScope, $storeCode);
        $isApplePay = $this->systemConfig->getTerminalConfig($terminalId, 'isapplepay', $storeScope, $storeCode);
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
            $data = $this->getToken($post['tokenid'], null, $order->getCustomerId());
        }
        elseif (isset($post['transaction_id']) && $post['type'] === "verifyCard") {
                $data = $this->getToken(null, $post['transaction_id'], $order->getCustomerId());
        }
    
        if (!empty($data)) {
            $request = new ReservationOfFixedAmount($auth);
            $token   = $data['token'];
            $request->setCreditCardToken($token);
            $request->setAgreement(
                $this->agreementDetail(
                    $quote->getAllItems(),
                    $baseUrl,
                    $data['agreement_type'],
                    $data['agreement_id']
                )
            );
            $isReservation = true;
        }

        $request->setTerminal($terminalName)
            ->setShopOrderId($order->getIncrementId())
            ->setAmount((float)number_format($order->getGrandTotal(), 2, '.', ''))
            ->setCurrency($order->getOrderCurrencyCode())
            ->setCustomerInfo($this->customerHandler->setCustomer($order, $isReservation))
            ->setTransactionInfo($transactionDetail)
            ->setCookie($this->request->getServer('HTTP_COOKIE'))
            ->setSaleReconciliationIdentifier($this->random->getUniqueHash());
        
        if(!$isReservation) {
            $request->setConfig($this->setConfig())->setSalesTax((float)number_format($order->getTaxAmount(), 2, '.', ''));
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
            $request->setAgreement($this->agreementDetail($quote->getAllItems(), $baseUrl, "recurring", null));
        }
        // check if auto capture enabled
        if (!$this->helper->validateQuote($quote) && $this->systemConfig->getTerminalConfig($terminalId, 'capture', $storeScope, $storeCode)) {
            $request->setType('paymentAndCapture');
        }
        if (isset($post['savecard']) && $post['savecard'] != false) {
            $request->setType('verifyCard');
        }
        //set orderlines to the request
        $request->setOrderLines($orderLines);

        return $request;
    }

    /**
     * Send payment request to the altapay.
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
            $max_date       = '';
            $latestTransKey = 0;
            if (isset($response->Transactions)) {
                foreach ($response->Transactions as $key => $value) {
                    if ($value->AuthType === "subscription_payment" && $value->CreatedDate > $max_date) {
                        $max_date       = $value->CreatedDate;
                        $latestTransKey = $key;
                    }
                }
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
            $order->getResource()->save($order);
            //set flag if customer redirect to Altapay
            $this->checkoutSession->setAltapayCustomerRedirect(true);
            return $requestParams;
        } catch (ClientException $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] = $e->getResponse()->getBody();
        } catch (ResponseHeaderException $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] = $e->getHeader()->ErrorMessage;
        } catch (ResponseMessageException $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] = $e->getMessage();
        } catch (\Exception $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] = $e->getMessage();
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
     * @param $items
     * @param $baseUrl
     * @param $agreementType
     * @param $agreementId
     *
     * @return array
     */
    private function agreementDetail($items, $baseUrl, $agreementType, $agreementId = null)
    {
        $agreementDetails = [];
        if ($items) {
            if ($agreementId) {
                $agreementDetails['id'] = $agreementId;
            }
            $agreementDetails['type'] = $agreementType;
            if ($agreementType === "unscheduled") {
                $agreementDetails['unscheduled_type'] = 'incremental';
            } else {
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
            $parametersData  = json_encode($request);
            $transactionData = json_encode($response);
            $this->transactionRepository->addTransactionData(
                $order->getIncrementId(),
                $transaction->TransactionId,
                $transaction->PaymentId,
                $transactionData,
                $parametersData
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
                $this->createInvoice($order, $requireCapture);
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
     * @param Order $order
     * @param bool  $requireCapture
     *
     * @return void
     */
    public function createInvoice(Order $order, bool $requireCapture = false)
    {
        if (filter_var($requireCapture, FILTER_VALIDATE_BOOLEAN) === true) {
            $captureType = Invoice::CAPTURE_ONLINE;
        } else {
            $captureType = Invoice::CAPTURE_OFFLINE;
        }

        if (!$order->getInvoiceCollection()->count()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase($captureType);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $transaction = $this->transactionFactory->create()->addObject($invoice)
                                ->addObject($invoice->getOrder());
            $transaction->save();
        }
    }

    /**
     * @param string $tokenId
     * @param int $transId
     * @param int $customerId
     *
     * @return mixed
     */
    private function getToken($tokenId = null, $transId = null, $customerId)
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
}
