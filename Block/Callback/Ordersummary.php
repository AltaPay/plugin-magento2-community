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
namespace SDM\Altapay\Block\Callback;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;
use SDM\Altapay\Api\OrderLoaderInterface;

/**
 * Class Ordersummary
 * @package SDM\Altapay\Block\Callback
 */
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
     * Ordersummary constructor.
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
        array $data = []
    ) {
        
        parent::__construct($context, $data);
        $this->orderLoader = $orderLoader;
        $this->request = $request;
        $this->orderConfig = $orderConfig;
        $this->checkoutSession = $checkoutSession;
        $this->httpContext = $httpContext;
        $this->orderRepository = $orderRepository;
        $this->renderer = $renderer;
        $this->productRepository = $productRepository;
        $this->priceHelper=$priceHelper;
    }


    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->request->getParam('shop_orderid');
    }


    /**
     * @return string
     */
    public function getOrder()
    {
        $orderIncrementId = $this->getOrderId();
        if ($orderIncrementId) {
            $order = $this->orderLoader->getOrderByOrderIncrementId($orderIncrementId);
            return $order;
        }
        
        return '';
    }


    /**
     * @param string $address
     * @return mixed
     */
    public function getFormatedShippingAddress()
    {
        $order = $this->getOrder();
        return $this->renderer->format($order->getShippingAddress(), 'html');
    }

    
    /**
     * Get order payment title
     * @return string
     */
    public function getPaymentMethodtitle()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        return $method->getTitle();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getProductById($id)
    {
        return $this->productRepository->getById($id);
    }


    /**
     * @param string $price
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
