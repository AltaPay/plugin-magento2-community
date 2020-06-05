<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Block\Callback;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;
use SDM\Valitor\Api\OrderLoaderInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;

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
     * Ordersummary constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param OrderLoaderInterface                             $orderLoader
     * @param \Magento\Framework\App\Request\Http              $request
     * @param Order\Config                                     $orderConfig
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Magento\Framework\App\Http\Context              $httpContext
     * @param \Magento\Sales\Api\OrderRepositoryInterface      $orderRepository
     * @param Order\Address\Renderer                           $renderer
     * @param \Magento\Catalog\Model\ProductRepository         $productRepository
     * @param \Magento\Framework\Pricing\Helper\Data           $priceHelper
     * @param array                                            $data
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
    }

    /**
     * Get orderif from param
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
     * @return string
     */
    public function getOrder()
    {
        $orderIncrementId = $this->getOrderId();
        if ($orderIncrementId) {
            return $this->orderLoader->getOrderByOrderIncrementId($orderIncrementId);
        }

        return '';
    }

    /**
     * Format order address
     *
     * @return mixed
     */
    public function getFormattedAddress()
    {
        $order = $this->getOrder();
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
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $order      = $this->getOrder();
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
     * Get Formated Price
     *
     * @param string $price
     *
     * @return mixed
     */
    public function getFormatedPrice($price = '')
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * @return Session
     */
    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }
}
