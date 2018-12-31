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

use SDM\Altapay\Api\GatewayInterface;
use SDM\Altapay\Api\OrderLoaderInterface;
use SDM\Altapay\Model\ConstantConfig;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Framework\UrlInterface;
use SDM\Altapay\Request\Address;
use SDM\Altapay\Request\Customer;
use SDM\Altapay\Request\Config;
use SDM\Altapay\Api\Ecommerce\PaymentRequest;
use SDM\Altapay\Api\Test\TestAuthentication;
use SDM\Altapay\Request\OrderLine;
use SDM\Altapay\Exceptions\ClientException;
use SDM\Altapay\Exceptions\ResponseHeaderException;
use SDM\Altapay\Exceptions\ResponseMessageException;

/**
 * Class Gateway
 * @package SDM\Altapay\Model
 */
class Gateway implements GatewayInterface
{
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
     * @var Quote
     */
    private $quote;

    /**
     * Gateway constructor.
     * @param Session $checkoutSession
     * @param UrlInterface $urlInterface
     * @param Order $order
     * @param SystemConfig $systemConfig
     * @param OrderLoaderInterface $orderLoader
     * @param Quote $quote
     */
    public function __construct(
        Session $checkoutSession,
        UrlInterface $urlInterface,
        Order $order,
        SystemConfig $systemConfig,
        OrderLoaderInterface $orderLoader,
        Quote $quote
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->urlInterface = $urlInterface;
        $this->order = $order;
        $this->systemConfig = $systemConfig;
        $this->orderLoader = $orderLoader;
        $this->quote = $quote;
    }

   /**
     * Createrequest to altapay
     * @param int $terminalId
     * @param string $orderId
     * @return array
     */
    public function createRequest(int $terminalId, string $orderId)
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

                $order->setAltapayPaymentFormUrl($response->Url);

                $order->getResource()->save($order);

                //set flag if customer redirect to Altapay
                $this->checkoutSession->setAltapayCustomerRedirect(true);

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
}
