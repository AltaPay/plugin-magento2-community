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
use SDM\Altapay\Model\ConstantConfig;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\Usage as CouponUsage;
use SDM\Altapay\Api\OrderLoaderInterface;
use Magento\CatalogInventory\Api\StockManagementInterface;
use SDM\Altapay\Model\SystemConfig;
use Magento\Framework\App\ResourceConnection;
use SDM\Altapay\Logger\Logger;

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
     * @var Coupon
     */
    private $coupon;
    /**
     * @var CouponUsage
     */
    private $couponUsage;

    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;

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
     * RestoreQuote Constructor
     *
     * @param Session                  $checkoutSession
     * @param OrderFactory             $orderFactory
     * @param QuoteFactory             $quoteFactory
     * @param ManagerInterface         $messageManager
     * @param Coupon                   $coupon
     * @param CouponUsage              $couponUsage
     * @param OrderLoaderInterface     $orderLoader
     * @param StockManagementInterface $stockManagement
     * @param SystemConfig             $systemConfig
     * @param ResourceConnection       $modelResource
     * @param Logger                   $altapayLogger
     */
    public function __construct(
        Session $checkoutSession,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        ManagerInterface $messageManager,
        Coupon $coupon,
        CouponUsage $couponUsage,
        OrderLoaderInterface $orderLoader,
        StockManagementInterface $stockManagement,
        SystemConfig $systemConfig,
        ResourceConnection $modelResource,
        Logger $altapayLogger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory    = $orderFactory;
        $this->quoteFactory    = $quoteFactory;
        $this->messageManager  = $messageManager;
        $this->coupon          = $coupon;
        $this->couponUsage     = $couponUsage;
        $this->orderLoader     = $orderLoader;
        $this->stockManagement = $stockManagement;
        $this->systemConfig    = $systemConfig;
        $this->modelResource   = $modelResource;
        $this->altapayLogger   = $altapayLogger;
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

            //Default history and message
            $history        = __(ConstantConfig::BROWSER_BK_BUTTON_COMMENT);
            $message        = __(ConstantConfig::BROWSER_BK_BUTTON_MSG);
            $browserBackBtn = false;

            //get transaction details if failure and redirect to cart
            $getTransactionData = $this->getTransactionData($orderId);

            //if fail set message and history
            if (!empty($getTransactionData)) {
                $getTransactionDataDecode = json_decode($getTransactionData);
                if (isset($getTransactionDataDecode->error_message)) {
                    $message = $getTransactionDataDecode->error_message;
                }
            } else {
                $browserBackBtn = true;
            }

            //set before state set in admin configuration
            $statusBefore = $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode);
            $statusCancel = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);

            //if quote id exist and order status is from config
            if ($quote->getId() && $this->verifyOrderStatus($statusBefore, $order->getStatus(), $statusCancel)) {
                //get quote Id from order and set as active
                $quote->setIsActive(1)->setReservedOrderId(null)->save();
                $this->checkoutSession->replaceQuote($quote)->unsLastRealOrderId();

                if (empty($statusCancel)) {
                    $statusCancel = Order::STATE_CANCELED;
                }

                //set order status and comments
                $order->setState(Order::STATE_CANCELED);
                $order->setIsNotified(false);
                if ($browserBackBtn) {
                    $order->addStatusHistoryComment($history, $statusCancel);
                }
                //if coupon applied revert it
                $this->resetCouponAfterCancellation($order);
                //revert quantity when cancel order
                $this->revertOrderQty($order);
                $order->getResource()->save($order);
                //show fail message
                $this->messageManager->addErrorMessage($message);
            }
            $this->checkoutSession->unsAltapayCustomerRedirect();
        }
    }

    /**
     * Reset the coupon usage when canceled.
     *
     * @param $order
     */
    public function resetCouponAfterCancellation($order)
    {
        if ($order->getCouponCode()) {
            $this->coupon->load($order->getCouponCode(), 'code');
            if ($this->coupon->getId()) {
                $this->coupon->setTimesUsed($this->coupon->getTimesUsed() - 1);
                $this->coupon->save();
                $customerId = $order->getCustomerId();
                if ($customerId) {
                    $this->couponUsage->updateCustomerCouponTimesUsed($customerId, $this->coupon->getId(), false);
                }
            }
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
                                 ->from($table, ['parametersdata'])
                                 ->where('orderid = ?', $orderId);

        return $connection->fetchOne($sql);
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

    /**
     * @param $order
     */
    public function revertOrderQty($order)
    {
        foreach ($order->getAllItems() as $item) {
            $item->setQtyCanceled($item['qty_ordered']);
            $item->save();  
            $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced());
            if ($item->getId() && $item->getProductId() && empty($item->getChildrenItems()) && $qty) {
                $this->stockManagement->backItemQty($item->getProductId(), $qty, $item->getStore()->getWebsiteId());
            }
        }
    }
}
