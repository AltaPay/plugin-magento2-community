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
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use SDM\Altapay\Request\Config;
use SDM\Altapay\Api\Ecommerce\PaymentRequest;
use SDM\Altapay\Api\Test\TestAuthentication;
use SDM\Altapay\Exceptions\ClientException;
use SDM\Altapay\Exceptions\ResponseHeaderException;
use SDM\Altapay\Exceptions\ResponseMessageException;
use SDM\Altapay\Helper\Data;
use SDM\Altapay\Helper\Config as storeConfig;
use SDM\Altapay\Logger\Logger;
use SDM\Altapay\Model\Handler\CustomerHandler;
use SDM\Altapay\Model\Handler\OrderLinesHandler;
use SDM\Altapay\Model\Handler\PriceHandler;
use SDM\Altapay\Model\Handler\DiscountHandler;
use SDM\Altapay\Model\Handler\CreatePaymentHandler;
use SDM\Altapay\Model\TokenFactory;
use Magento\Quote\Model\Quote\Item\AbstractItem;

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
     * Gateway constructor.
     *
     * @param Session              $checkoutSession
     * @param UrlInterface         $urlInterface
     * @param Http                 $request
     * @param Order                $order
     * @param SystemConfig         $systemConfig
     * @param OrderLoaderInterface $orderLoader
     * @param Quote                $quote
     * @param Data                 $helper
     * @param storeConfig          $storeConfig
     * @param Logger               $altapayLogger
     * @param CustomerHandler      $customerHandler
     * @param OrderLinesHandler    $orderLines
     * @param PriceHandler         $priceHandler
     * @param DiscountHandler      $discountHandler
     * @param CreatePaymentHandler $paymentHandler
     * @param TokenFactory         $dataToken
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
        TokenFactory $dataToken
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->urlInterface    = $urlInterface;
        $this->request         = $request;
        $this->order           = $order;
        $this->systemConfig    = $systemConfig;
        $this->orderLoader     = $orderLoader;
        $this->quote           = $quote;
        $this->helper          = $helper;
        $this->storeConfig     = $storeConfig;
        $this->altapayLogger   = $altapayLogger;
        $this->customerHandler = $customerHandler;
        $this->orderLines      = $orderLines;
        $this->priceHandler    = $priceHandler;
        $this->discountHandler = $discountHandler;
        $this->paymentHandler  = $paymentHandler;
        $this->dataToken       = $dataToken;
    }

    /**
     * createRequest to altapay
     *
     * @param int    $terminalId
     * @param string $orderId
     *
     * @return array
     */
    public function createRequest($terminalId, $orderId)
    {
        $order = $this->order->load($orderId);
        if ($order->getId()) {
            $couponCode       = $order->getDiscountDescription();
            $couponCodeAmount = $order->getDiscountAmount();
            $discountAllItems = $this->discountHandler->allItemsHaveDiscount($order->getAllItems());
            $orderLines       = $this->itemOrderLines($couponCodeAmount, $order, $discountAllItems);
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
            if(!empty($this->fixedProductTax($order))){
                $orderLines[] = $this->orderLines->fixedProductTaxOrderLine($this->fixedProductTax($order));
            }
            $request = $this->preparePaymentRequest($order, $orderLines, $orderId, $terminalId);
            if ($request) {
                return $this->sendPaymentRequest($order, $request);
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
        $orderLines       = [];
        $storePriceIncTax = $this->storeConfig->storePriceIncTax();

        foreach ($order->getAllItems() as $item) {
            $productType    = $item->getProductType();
            $originalPrice  = $item->getBaseOriginalPrice();
            $taxPercent     = $item->getTaxPercent();
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
                    $unitPrice           = bcdiv($unitPriceWithoutTax, 1, 2);
                } else {
                    $unitPrice           = $originalPrice;
                    $unitPriceWithoutTax = $originalPrice;
                }
                $dataForPrice         = $this->priceHandler->dataForPrice(
                    $item,
                    $unitPrice,
                    $couponCodeAmount,
                    $this->discountHandler->getItemDiscount($discountAmount, $originalPrice, $item->getQtyOrdered())
                );
                $taxAmount            = $dataForPrice["taxAmount"];
                $catalogDiscount      = $dataForPrice["catalogDiscount"];
                $discount             = $this->discountHandler->orderLineDiscount(
                    $discountAllItems,
                    $dataForPrice["discount"],
                    $catalogDiscount
                );

                $itemTaxAmount        = $taxAmount;
                $orderLines[]         = $this->orderLines->itemOrderLine(
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
                    $unitPriceWithoutTax,
                    $taxAmount,
                    $discount,
                    $couponCodeAmount,
                    $catalogDiscount,
                    $storePriceIncTax,
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
        $requestParams['result']  = ConstantConfig::ERROR;
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
     *
     * @return mixed
     */
    private function preparePaymentRequest($order, $orderLines, $orderId, $terminalId)
    {
        $storeScope = $this->storeConfig->getStoreScope();
        $storeCode  = $order->getStore()->getCode();
        //Test the conn with the Payment Gateway
        $auth     = $this->systemConfig->getAuth($storeCode);
        $api      = new TestAuthentication($auth);
        $response = $api->call();
        if (!$response) {
            return false;
        }
        $terminalName = $this->systemConfig->getTerminalConfig($terminalId, 'terminalname', $storeScope, $storeCode);
        //Transaction Info
        $transactionDetail = $this->helper->transactionDetail($orderId);
        $request           = new PaymentRequest($auth);
        $request->setTerminal($terminalName)
                ->setShopOrderId($order->getIncrementId())
                ->setAmount((float)number_format($order->getGrandTotal(), 2, '.', ''))
                ->setCurrency($order->getOrderCurrencyCode())
                ->setCustomerInfo($this->customerHandler->setCustomer($order))
                ->setConfig($this->setConfig())
                ->setTransactionInfo($transactionDetail)
                ->setSalesTax((float)number_format($order->getTaxAmount(), 2, '.', ''))
                ->setCookie($this->request->getServer('HTTP_COOKIE'));

        $post = $this->request->getPostValue();

        if (isset($post['tokenid'])) {
            $model      = $this->dataToken->create();
            $collection = $model->getCollection()->addFieldToFilter('id', $post['tokenid'])->getFirstItem();
            $data       = $collection->getData();
            if (!empty($data)) {
                $token = $data['token'];
                $request->setCcToken($token);
            }
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
        $quote = $this->quote->loadByIdWithoutStore($order->getQuoteId());
        if ($this->validateQuote($quote)) {
            if ($this->systemConfig->getTerminalConfig($terminalId, 'capture', $storeScope, $storeCode)) {
                $request->setType('subscriptionAndCharge');
            } else {
                $request->setType('subscription');
            }
        }
        // check if auto capture enabled
        if (!$this->validateQuote($quote) && $this->systemConfig->getTerminalConfig($terminalId, 'capture', $storeScope, $storeCode)) {
            $request->setType('paymentAndCapture');
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
        $storeScope = $this->storeConfig->getStoreScope();
        $storeCode  = $order->getStore()->getCode();

        try {
            /** @var \Altapay\Response\PaymentRequestResponse $response */
            $response                 = $request->call();
            $requestParams['result']  = ConstantConfig::SUCCESS;
            $requestParams['formurl'] = $response->Url;
            // set before payment status
            if ($this->systemConfig->getStatusConfig('before', $storeScope, $storeCode)) {
                $this->paymentHandler->setCustomOrderStatus($order, Order::STATE_NEW, 'before');
            }
            // set notification
            $order->addStatusHistoryComment(__(ConstantConfig::REDIRECT_TO_ALTAPAY) . $response->PaymentRequestId);
            $extensionAttribute = $order->getExtensionAttributes();
            if ($extensionAttribute && $extensionAttribute->getAltapayPaymentFormUrl()) {
                $extensionAttribute->setAltapayPaymentFormUrl($response->Url);
            }
            $order->setAltapayPaymentFormUrl($response->Url);
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
    public function fixedProductTax($order){
        $weeTaxAmount = 0;
        foreach ($order->getAllItems() as $item) {
           $weeTaxAmount +=  $item->getWeeeTaxAppliedRowAmount();
        }

       return $weeTaxAmount;
    }

    /**
     * @param AbstractItem $item
     * @return DataObject
     */
    public function getBuyRequestObject(AbstractItem $item)
    {
        /** @var DataObject $request */
        $request = $item->getBuyRequest();
        if (!$request && $item->getQuoteItem()) {
            $request = $item->getQuoteItem()->getBuyRequest();
        }
        if (!$request) {
            $request = new DataObject();
        }

        if (is_array($request)) {
            $request = new DataObject($request);
        }

        return $request;
    }

    /**
     * @param AbstractItem $item
     * @return bool
     */
    public function isSubscription(AbstractItem $item)
    {
        $buyRequest = $this->getBuyRequestObject($item);

        return $buyRequest->getData('subscribe') === 'subscribe';
    }
    /**
     * @param CartInterface $quote
     *
     * @return bool
     */
    public function validateQuote($quote)
    {
        $isRecurring = false;
        $items = $quote->getAllItems();

        /** @var Item $item */
        foreach ($items as $item) {
            if ($this->isSubscription($item)) {
                $isRecurring = true;
                break;
            }
        }

        return $isRecurring;
    }
}
