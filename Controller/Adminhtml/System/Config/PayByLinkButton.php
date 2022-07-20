<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Service\OrderService;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use SDM\Altapay\Model\Gateway;

class PayByLinkButton extends Action
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
 
    /**
     * @var ProductFactory
     */
    private $productFactory;
 
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
 
    /**
     * @var CustomerInterfaceFactory
     */
    private $customerInterfaceFactory;
 
    /**
     * @var CartManagementInterface
     */
    private $cartManagementInterface;
 
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepositoryInterface;
 
    /**
     * @var OrderService
     */
    private $orderService;
 
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

     /**
     * @var Order
     */
    protected $order;
    /**
     * Create constructor.
     *
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param CartManagementInterface $cartManagementInterface
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param OrderService $orderService
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerInterfaceFactory,
        CartManagementInterface $cartManagementInterface,
        CartRepositoryInterface $cartRepositoryInterface,
        OrderService $orderService,
        StoreManagerInterface $storeManager,
        JsonFactory $resultJsonFactory,
        Gateway $gateway,
        Order $order
    ) {
        $this->productRepository        = $productRepository;
        $this->productFactory           = $productFactory;
        $this->customerRepository       = $customerRepository;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->cartManagementInterface  = $cartManagementInterface;
        $this->cartRepositoryInterface  = $cartRepositoryInterface;
        $this->orderService             = $orderService;
        $this->storeManager             = $storeManager;
        $this->resultJsonFactory        = $resultJsonFactory;
        $this->order                    = $order;
        $this->gateway                  = $gateway;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $terminalCode = $this->getRequest()->getParam('terminal');
        $orderData = [
            'email'        => $this->getRequest()->getParam('customerEmail'),
            'guest_order'  => true,
            'shipping_address'      => [
                'firstname'            => $this->getRequest()->getParam('customerFirstname'),
                'lastname'             => $this->getRequest()->getParam('customerLastname'),
                'street'               => $this->getRequest()->getParam('street'),
                'city'                 => $this->getRequest()->getParam('city'),
                'country_id'           => $this->getRequest()->getParam('countrycode'),
                'postcode'             => $this->getRequest()->getParam('postalcode'),
                'telephone'            => $this->getRequest()->getParam('phoneno'),
                'save_in_address_book' => 1
            ],
            'items'=> [
                ['product_id'=>'2048','qty'=>1]
            ]
        ];

        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        
        // Initialise Cart
        $cartId = $this->cartManagementInterface->createEmptyCart();
        $cart = $this->cartRepositoryInterface->get($cartId);
        $cart->setStore($store);
        $cart->setCurrency();

        if ($orderData['guest_order']) {
            $cart->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
            $cart->getBillingAddress()->setEmail($orderData['email']);
        }

        // Add items to cart
        foreach ($orderData['items'] as $item) {
            $product = $this->productRepository->getById($item['product_id']);
            $cart->addProduct(
                $product,
                $item['qty']
            );
        }

        // Set billing and shipping addresses
        $cart->getBillingAddress()->addData($orderData['shipping_address']);
        $cart->getShippingAddress()->addData($orderData['shipping_address']);
        $shippingAddress = $cart->getShippingAddress();
        // Set shipping method
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate');

        // Set payment method
        $cart->setPaymentMethod($terminalCode);
        $cart->getPayment()->importData(['method' => $terminalCode]);
        $cart->collectTotals();
        $cart->save();
 
        // Place the order
        $cart = $this->cartRepositoryInterface->get($cart->getId());
        $orderId = $this->cartManagementInterface->placeOrder($cart->getId());
        $order = $this->order->load($orderId);
        $order->setEmailSent(0);

        if($order->getEntityId()){
            $params = $this->gateway->createRequest(
                $terminalCode[strlen($terminalCode)-1],
                $order->getId()
            );
            if($params['result'] === 'success') {
                $message = $params['formurl'];
            }
        }else{
            $message = __('Something went wrong');
        }
        /** @var Json $result */
        $resultData = $this->resultJsonFactory->create();

        return $resultData->setData(['message' => $message ,'orderId' => $order->getRealOrderId() ]);
    }
}