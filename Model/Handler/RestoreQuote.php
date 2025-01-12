<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Handler;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use SDM\Altapay\Api\OrderLoaderInterface;
use SDM\Altapay\Model\SystemConfig;
use Magento\Framework\App\ResourceConnection;
use SDM\Altapay\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductFactory;

class RestoreQuote
{
    /**
     * Checkout session
     *
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Order factory
     *
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var ResourceConnection
     */
    protected $modelResource;

    /**
     * @var Logger
     */
    protected $altapayLogger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var ProductFactory
     */
    protected $product;

    /**
     * RestoreQuote Constructor
     *
     * @param Session               $checkoutSession
     * @param OrderFactory          $orderFactory
     * @param QuoteFactory          $quoteFactory
     * @param ManagerInterface      $messageManager
     * @param OrderLoaderInterface  $orderLoader
     * @param SystemConfig          $systemConfig
     * @param ResourceConnection    $modelResource
     * @param Logger                $altapayLogger
     * @param ScopeConfigInterface  $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Cart                  $cart
     * @param ProductFactory        $product
     */
    public function __construct(
        Session $checkoutSession,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        ManagerInterface $messageManager,
        OrderLoaderInterface $orderLoader,
        SystemConfig $systemConfig,
        ResourceConnection $modelResource,
        Logger $altapayLogger,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Cart $cart,
        ProductFactory $product
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory    = $orderFactory;
        $this->quoteFactory    = $quoteFactory;
        $this->messageManager  = $messageManager;
        $this->orderLoader     = $orderLoader;
        $this->systemConfig    = $systemConfig;
        $this->modelResource   = $modelResource;
        $this->altapayLogger   = $altapayLogger;
        $this->scopeConfig     = $scopeConfig;
        $this->storeManager    = $storeManager;
        $this->cart            = $cart;
        $this->product         = $product;
    }

    /**
     * @return void
     */
    public function handleQuote()
    {
        //check if customer redirect from altapay
        if ($this->checkoutSession->getAltapayCustomerRedirect()) {
            //get last order Id from interface
            $orderId    = $this->orderLoader->getLastOrderIncrementIdFromSession();
            $order      = $this->checkoutSession->getLastRealOrder();
            $quote      = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode  = $order->getStore()->getCode();

            //get transaction details if failure and redirect to cart
            $transactionData = $this->getTransactionData($orderId);
            if (!empty($transactionData)) {
                $shouldShowCardholderMessage = false;
                $message = "Error with the Payment.";
                $cardholderErrorMessage = $transactionData['customer_error_message'];
                if (isset($transactionData['card_holder_message_must_be_shown'])) {
                    $shouldShowCardholderMessage = (bool)($transactionData['card_holder_message_must_be_shown'] === "true");
                }
                $cardErrorMsgConfig = (bool)$this->scopeConfig->getValue(
                    'payment/sdm_altapay_config/error_message/enable',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $this->storeManager->getStore()->getCode()
                );
                if ($cardholderErrorMessage && ($shouldShowCardholderMessage || $cardErrorMsgConfig)) {
                    $message = $cardholderErrorMessage;
                }
                //show fail message
                $this->messageManager->addErrorMessage($message);
            }

            //set before state set in admin configuration
            $statusBefore = $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode);
            $statusCancel = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);

            //if quote id exist and order status is from config
            if ($quote->getId() && $this->verifyOrderStatus($statusBefore, $order->getStatus(), $statusCancel)) {
                // Restore quote to load cart items
                $this->checkoutSession->restoreQuote();
            }
            $this->checkoutSession->unsAltapayCustomerRedirect();
        }
    }

    /**
     * @param $orderId
     *
     * @return mixed
     */
    public function getTransactionData($orderId)
    {
        $connection = $this->modelResource->getConnection();
        $table      = $this->modelResource->getTableName('sdm_altapay');
        $sql        = $connection->select()
            ->from(
                $table,
                [
                    'card_holder_message_must_be_shown',
                    'customer_error_message',
                    'merchant_error_message'
                ]
            )
            ->where('orderid = ?', $orderId);

        return $connection->fetchRow($sql);
    }

    /**
     * @param $statusBefore
     * @param $currentStatus
     * @param $statusCancel
     *
     * @return bool
     */
    public function verifyOrderStatus($statusBefore, $currentStatus, $statusCancel)
    {
        if (!empty($statusBefore) || !empty($statusCancel)) {
            if ($statusBefore == $currentStatus || $statusCancel == $currentStatus) {
                return true;
            }
        }

        return false;
    }
}
