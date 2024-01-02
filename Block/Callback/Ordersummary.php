<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Block\Callback;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;
use SDM\Altapay\Api\OrderLoaderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use SDM\Altapay\Model\SystemConfig;

class Ordersummary extends \Magento\Framework\View\Element\Template
{
    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    protected $renderer;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data $priceHelper
     */
    protected $priceHelper;

    /**
     * @var \Magento\Checkout\Model\Session;
     */
    private $checkoutSession;
    /**
     * @var ScopeConfigInterface
     */
    protected $_appConfigScopeConfigInterface;
    /**
     * @var \Magento\Theme\Block\Html\Header\Logo
     */
    protected $_logo;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlInterface;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Order summary constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param \Magento\Framework\App\Request\Http $request
     * @param Order\Config $orderConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param Order\Address\Renderer $renderer
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param ScopeConfigInterface $appConfigScopeConfigInterface
     * @param \Magento\Theme\Block\Html\Header\Logo $logo
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param SystemConfig $systemConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        OrderLoaderInterface $orderLoader,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Address\Renderer $renderer,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        ScopeConfigInterface $appConfigScopeConfigInterface,
        \Magento\Theme\Block\Html\Header\Logo $logo,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        SystemConfig $systemConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderLoader                    = $orderLoader;
        $this->request                        = $request;
        $this->orderConfig                    = $orderConfig;
        $this->checkoutSession                = $checkoutSession;
        $this->httpContext                    = $httpContext;
        $this->orderRepository                = $orderRepository;
        $this->renderer                       = $renderer;
        $this->productRepository              = $productRepository;
        $this->priceHelper                    = $priceHelper;
        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
        $this->_logo                          = $logo;
        $this->_urlInterface                  = $urlInterface;
        $this->priceCurrency                  = $priceCurrency;
        $this->systemConfig                   = $systemConfig;
    }

    /**
     * Get order id from param
     *
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->request->getParam('shop_orderid');
    }

    /**
     * Load order
     *
     * @return Order|null
     */
    public function getOrder()
    {
        $orderIncrementId = $this->getOrderId();

        if (!$orderIncrementId) {
            return null;
        }

        return $this->orderLoader->getOrderByOrderIncrementId($orderIncrementId);
    }

    /**
     * Format order address
     *
     * @return mixed
     */
    public function getFormattedAddress()
    {
        $order = $this->getOrder();

        if(!$order){
            return '';
        }

        if ($order->getShippingAddress()) {
            return $this->renderer->format($order->getShippingAddress(), 'html');
        } else {
            return $this->renderer->format($order->getBillingAddress(), 'html');
        }
    }

    /**
     * Get order payment title
     *
     * @return string
     */
    public function getPaymentMethodtitle()
    {
        $order          = $this->getOrder();
        $terminalTitle  = "";
        
        if($order){
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeId    = $order->getStore()->getId();
            $payment    = $order->getPayment();
            $method     = $payment->getMethodInstance();
            $title      = $method->getConfigData('title', $storeId);
            $terminalID = $payment->getMethod();
            if ($title == null) {
                $terminalTitle = $this->_appConfigScopeConfigInterface
                    ->getValue('payment/' . $terminalID . '/terminalname', $storeScope);
            } else {
                $terminalTitle = $title;
            }
        }

        return $terminalTitle;
    }

    /**
     * Load product from productId
     *
     * @param $id
     *
     * @return mixed
     */
    public function getProductById($id)
    {
        return $this->productRepository->getById($id);
    }

    /**
     * Get Formatted Price
     *
     * @param string $price
     *
     * @return mixed
     */
    public function getFormatedPrice($price = '')
    {
        $order          = $this->getOrder();
        $currencyCode   = null;

        if($order){
            $currencyCode = $order->getOrderCurrencyCode();
        }

        return $this->priceCurrency->format($price, false, 2, null, $currencyCode);
    }

    /**
     * @return Session
     */
    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }
    
    /**
     * Get logo image URL
     *
     * @return string
     */
    public function getLogoSrc()
    {
        $logoFile = $this->systemConfig->getLayoutConfig('logo_checkout');
        $path = 'sales/store/logo_checkout';


        if (!empty($logoFile)) {
            return $this->_urlInterface
                    ->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]) . $path . '/' . $logoFile;
        }

        return $this->_logo->getLogoSrc();
    }
    
    /**
     * Check if order summery prices includes tax
     *
     * @return string
     */
    public function orderSummeryInclTax()
    {
        
        return $this->systemConfig->getLayoutConfig('order_summery_incl_tax');
    }

    /**
     * Get site URL
     * @return mixed
     */
    public function getCurrentUrl()
    {
        return $this->_urlInterface->getUrl();
    }
}
