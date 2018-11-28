<?php
/**
 * Altapay Module version 3.0.1 for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */
namespace SDM\Altapay\Model;

use Altapay\Api\Ecommerce\Callback;
use Altapay\Api\Ecommerce\PaymentRequest;
use Altapay\Api\Test\TestAuthentication;
use Altapay\Exceptions\ClientException;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Exceptions\ResponseMessageException;
use Altapay\Request\Address;
use Altapay\Request\Config;
use Altapay\Request\Customer;
use Altapay\Request\OrderLine;
use Altapay\Response\CallbackResponse;
use SDM\Altapay\Model\ConstantConfig;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use SDM\Altapay\Logger\Logger;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use SDM\Altapay\Api\TransactionRepositoryInterface;
use SDM\Altapay\Api\OrderLoaderInterface;

/**
 * Class Generator
 * @package SDM\Altapay\Model
 */
class Generator
{
    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

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
     * Generator constructor.
     * @param Quote $quote
     * @param UrlInterface $urlInterface
     * @param PaymentData $paymentData
     * @param Session $checkoutSession
     * @param Http $request
     * @param Order $order
     * @param OrderSender $orderSender
     * @param SystemConfig $systemConfig
     * @param Logger $altapayLogger
     * @param TransactionRepositoryInterface $transactionRepository
     * @param OrderLoaderInterface $orderLoader
     */
    public function __construct(
        Quote $quote,
        UrlInterface $urlInterface,
        PaymentData $paymentData,
        Session $checkoutSession,
        Http $request,
        Order $order,
        OrderSender $orderSender,
        SystemConfig $systemConfig,
        Logger $altapayLogger,
        TransactionRepositoryInterface $transactionRepository,
        OrderLoaderInterface $orderLoader
    ) {
        $this->quote = $quote;
        $this->urlInterface = $urlInterface;
        $this->paymentData = $paymentData;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->systemConfig = $systemConfig;
        $this->altapayLogger = $altapayLogger;
        $this->transactionRepository = $transactionRepository;
        $this->orderLoader = $orderLoader;
    }

    /**
     * Generate parameters
     *
     * @param int $terminalId
     * @param string $orderId
     * @return array
     */
    public function createRequest($terminalId, $orderId)
    {
        $order = $this->order->load($orderId);
        if ($order->getId()) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode = $order->getStore()->getCode();
            //Test the conn with the Payment Gateway
            $auth = $this->systemConfig->getAuth($storeCode);
            $api = new TestAuthentication($auth);
            $response = $api->call();

            $terminalName = $this->systemConfig->getTerminalConfig($terminalId, 'terminalname', $storeScope, $storeCode);
            if (! $response) {
                $this->restoreOrderFromOrderId($order->getIncrementId());
                $requestParams['result'] = __(ConstantConfig::ERROR);
                $requestParams['message'] = __(ConstantConfig::AUTH_MESSAGE);
                return $requestParams;
            }

            $request = new PaymentRequest($auth);
            $request
                ->setTerminal($terminalName)
                ->setShopOrderId($order->getIncrementId())
                ->setAmount((float) $order->getGrandTotal())
                ->setCurrency($order->getOrderCurrencyCode())
                ->setCustomerInfo($this->setCustomer($order))
                ->setConfig($this->setConfig());

            if ($fraud = $this->systemConfig->getTerminalConfig($terminalId, 'fraud', $storeScope, $storeCode)) {
                $request->setFraudService($fraud);
            }

            if ($lang = $this->systemConfig->getTerminalConfig($terminalId, 'language', $storeScope, $storeCode)) {
                $langArr = explode('_', $lang, 2);
                if (isset($langArr[0])) {
                    $language = $langArr[0];
                    $request->setLanguage($language);
                }
            }

            if ($this->systemConfig->getTerminalConfig($terminalId, 'capture', $storeScope, $storeCode)) {
                $request->setType('paymentAndCapture');
            }

            $orderlines = [];
            /** @var \Magento\Sales\Model\Order\Item $item */
            foreach ($order->getAllVisibleItems() as $item) {
                $taxAmount = ($item->getQtyOrdered() * $item->getPriceInclTax()) - ($item->getQtyOrdered() * $item->getPrice());
                $orderline = new OrderLine(
                    $item->getName(),
                    $item->getSku(),
                    $item->getQtyOrdered(),
                    $item->getPrice()
                );
                $orderline->setGoodsType('item');
                $orderline->taxAmount = $taxAmount;
                //$orderline->taxPercent = $item->getTaxPercent();
                $orderlines[] = $orderline;
            }
            if ($order->getDiscountAmount() > 0) {
                // Handling price reductions
                $orderline = new OrderLine(
                    $order->getDiscountDescription(),
                    'discount',
                    1,
                    $order->getDiscountAmount()
                );
                $orderline->setGoodsType('handling');
                $orderlines[] = $orderline;
            }

            // Handling orderline
            $data = $order->getShippingMethod(true);
            $orderlines[] = (new OrderLine(
                $data['method'],
                $data['carrier_code'],
                1,
                $order->getShippingInclTax()
            ))->setGoodsType('shipment');
            $request->setOrderLines($orderlines);
            try {
                /** @var \Altapay\Response\PaymentRequestResponse $response */
                $response = $request->call();
                $requestParams['result'] = __(ConstantConfig::SUCCESS);
                $requestParams['formurl'] = $response->Url;
                // set before payment status
                $this->setCustomOrderStatus($order, Order::STATE_NEW, 'before');
                // set notification
                $order->addStatusHistoryComment(__(ConstantConfig::REDIRECT_TO_ALTAPAY) . $response->PaymentRequestId);

                $extensionAttribute = $order->getExtensionAttributes();
                if ($extensionAttribute && $extensionAttribute->getAltapayPaymentFormUrl()) {
                    $extensionAttribute->setAltapayPaymentFormUrl($response->Url);
                }

                $order->setAltapayPaymentFormUrl($response->Url);

                $order->getResource()->save($order);

                return $requestParams;
            } catch (ClientException $e) {
                $requestParams['result'] = __(ConstantConfig::ERROR);
                $requestParams['message'] = $e->getResponse()->getBody();
            } catch (ResponseHeaderException $e) {
                $requestParams['result'] = __(ConstantConfig::ERROR);
                $requestParams['message'] = $e->getHeader()->ErrorMessage;
            } catch (ResponseMessageException $e) {
                $requestParams['result'] = __(ConstantConfig::ERROR);
                $requestParams['message'] = $e->getMessage();
            } catch (\Exception $e) {
                $requestParams['result'] = __(ConstantConfig::ERROR);
                $requestParams['message'] = $e->getMessage();
            }

            $this->restoreOrderFromOrderId($order->getIncrementId());
            return $requestParams;
        }

        $this->restoreOrderFromOrderId($order->getIncrementId());
        $requestParams['result']  = __(ConstantConfig::ERROR);
        $requestParams['message'] = __(ConstantConfig::ERROR_MESSAGE);
        return $requestParams;
    }

    /**
     * @param $orderId
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function restoreOrderFromOrderId($orderId)
    {
        $order = $this->orderLoader->getOrderByOrderIncrementId($orderId);
        if ($order->getId()) {
            $quote = $this->quote->loadByIdWithoutStore($order->getQuoteId());
            $quote
                ->setIsActive(1)
                ->setReservedOrderId(null);
            $quote->getResource()->save($quote);
            $this->checkoutSession->replaceQuote($quote);
        }
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
            $this->altapayLogger->addDebugLog('[restoreOrderFromRequest] Response correct', $response);
            $order = $this->orderLoader->getOrderByOrderIncrementId($response->shopOrderId);
            if ($order->getQuoteId()) {
                $this->altapayLogger->addDebugLog('[restoreOrderFromRequest] Order quote id', $order->getQuoteId());
                if ($quote = $this->quote->loadByIdWithoutStore($order->getQuoteId())) {
                    $this->altapayLogger->addDebugLog('[restoreOrderFromRequest] Quote found', $order->getQuoteId());
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
            $formUrl = $order->getAltapayPaymentFormUrl();
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
        $formUrl = '';
        $historyComment = __(ConstantConfig::CONSUMER_PAYMENT_FAILED);
        $transInfo = null;
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->orderLoader->getOrderByOrderIncrementId($response->shopOrderId);
            $formUrl = $order->getAltapayPaymentFormUrl();
            $transInfo = sprintf(
                "Transaction ID: %s - Payment ID: %s - Credit card token: %s",
                $response->transactionId,
                $response->paymentId,
                $response->creditCardToken
            );
        }
        //TODO: fetch the MerchantErrorMessage and use it as historyComment
        $this->handleOrderStateAction($request, Order::STATE_PENDING_PAYMENT, Order::STATE_PENDING_PAYMENT, $historyComment, $transInfo);
        return $formUrl;
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
        //$config->setCallbackVerifyOrder($this->urlInterface->getDirectUrl(ConstantConfig::VERIFY_ORDER));
        $config->setCallbackForm($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_CALLBACK));
        return $config;
    }

    /**
     * @param Order $order
     * @return Customer
     */
    private function setCustomer(Order $order)
    {
        $billingAddress = new Address();
        if ($order->getBillingAddress()) {
            $address = $order->getBillingAddress()->convertToArray();
            $billingAddress->Email = $order->getBillingAddress()->getEmail();
            $billingAddress->Firstname = $address['firstname'];
            $billingAddress->Lastname = $address['lastname'];
            $billingAddress->Address = $address['street'];
            $billingAddress->City = $address['city'];
            $billingAddress->PostalCode = $address['postcode'];
            $billingAddress->Region = $address['region'] ?: '0';
            $billingAddress->Country = $address['country_id'];
        }
        $customer = new Customer($billingAddress);

        if ($order->getShippingAddress()) {
            $address = $order->getShippingAddress()->convertToArray();
            $shippingAddress = new Address();
            $shippingAddress->Email = $order->getShippingAddress()->getEmail();
            $shippingAddress->Firstname = $address['firstname'];
            $shippingAddress->Lastname = $address['lastname'];
            $shippingAddress->Address = $address['street'];
            $shippingAddress->City = $address['city'];
            $shippingAddress->PostalCode = $address['postcode'];
            $shippingAddress->Region = $address['region'] ?: '0';
            $shippingAddress->Country = $address['country_id'];
            $customer->setShipping($shippingAddress);
        }

        if ($order->getBillingAddress()) {
            $customer->setEmail($order->getBillingAddress()->getEmail());
            $customer->setPhone($order->getBillingAddress()->getTelephone());
        } elseif ($order->getShippingAddress()) {
            $customer->setEmail($order->getShippingAddress()->getEmail());
            $customer->setPhone($order->getShippingAddress()->getTelephone());
        }

        return $customer;
    }

    /**
     * @return Session
     */
    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }
}
