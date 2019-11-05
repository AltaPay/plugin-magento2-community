<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SDM\Valitor\Model\Handler;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use SDM\Valitor\Model\ConstantConfig;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\Usage as CouponUsage;
use SDM\Valitor\Api\OrderLoaderInterface;
use Magento\CatalogInventory\Api\StockManagementInterface;
use SDM\Valitor\Model\SystemConfig;
use Magento\Framework\App\ResourceConnection;
use SDM\Valitor\Logger\Logger;

/**
 * Class RestoreQuote
 *
 * @package SDM\Valitor\Model\Handler
 */
class RestoreQuote
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
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
    protected $valitorLogger;

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
     */
    public function __construct(Session $checkoutSession, OrderFactory $orderFactory, QuoteFactory $quoteFactory, ManagerInterface $messageManager, Coupon $coupon, CouponUsage $couponUsage, OrderLoaderInterface $orderLoader, StockManagementInterface $stockManagement, SystemConfig $systemConfig, ResourceConnection $modelResource,Logger $valitorLogger)
    {
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
        $this->valitorLogger = $valitorLogger;
    }

    /**
     * @return void
     */
    public function handleQuote()
    {
        //check if customer redirect from valitor
        if ($this->checkoutSession->getValitorCustomerRedirect()) {
            //get last order Id from inteface
            $orderId = $this->orderLoader->getLastOrderIncrementIdFromSession();
            $order = $this->checkoutSession->getLastRealOrder();
            $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode = $order->getStore()->getCode();

            //Default history and message
            $history = __(ConstantConfig::BROWSER_BK_BUTTON_COMMENT);
            $message = __(ConstantConfig::BROWSER_BK_BUTTON_MSG);


            //get transaction details if faliure and redirect to cart
            $getTransactionData = $this->getTransactionData($orderId);

            //if fail set message and history
            if (!empty($getTransactionData)) {
                $getTransactionDataDecode = json_decode($getTransactionData);

                if (isset($getTransactionDataDecode->error_message)) {
                    $history = $getTransactionDataDecode->error_message;
                    $message = $getTransactionDataDecode->error_message;
                }
            }
 
            //set before state set in admin configuration
            $orderStatusBefore = $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode);
            $orderStatusCancel = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);
            $orderStatusCancelUpdate = Order::STATE_CANCELED;
            $orderStateCancelUpdate = Order::STATE_CANCELED;

            //if quote id exist and order status is from config
            if ($quote->getId() && $this->verifyIfOrderStatus($orderStatusBefore, $order->getStatus(), $orderStatusCancel)) {
                //get quote Id from order and set as active
                $quote->setIsActive(1)->setReservedOrderId(null)->save();
                $this->checkoutSession->replaceQuote($quote)->unsLastRealOrderId();
                
                if ($orderStatusCancel) {
                    $orderStatusCancelUpdate = $orderStatusCancel;
                }
                
                //set order status and comments
                $order->setState($orderStateCancelUpdate);
                $order->setIsNotified(false);
                $order->addStatusHistoryComment($history, $orderStatusCancelUpdate);

                //if coupon applied revert it
                if ($order->getCouponCode()) {
                    $this->resetCouponAfterCancellation($order);
                }

                //revert quantity when cancel order
                $orderItems = $order->getAllItems();
                foreach ($orderItems as $item) {
                    $children = $item->getChildrenItems();
                    $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();
                    if ($item->getId() && $item->getProductId() && empty($children) && $qty) {
                        $this->stockManagement->backItemQty($item->getProductId(), $qty, $item->getStore()->getWebsiteId());
                    }
                }

                $order->getResource()->save($order);
                //show fail message
                $this->messageManager->addErrorMessage($message);
            }
            $this->checkoutSession->unsValitorCustomerRedirect();
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @throws \Exception
     */
    public function resetCouponAfterCancellation($order)
    {
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

    public function getTransactionData($orderid)
    {
        $connection = $this->modelResource->getConnection();
        $sql = "SELECT parametersdata FROM sdm_valitor WHERE orderid = '$orderid'";
        return $result = $connection->fetchOne($sql);
    }
    
    /**
    * @param orderStatusConfig
    * @param currentOrderStatus
    */
    public function verifyIfOrderStatus($orderStatusConfigBefore, $currentOrderStatus, $orderStatusConfigCancel)
    {
        if (!is_null($orderStatusConfigBefore)) {
            if ($orderStatusConfigBefore == $currentOrderStatus) {
                return true;
            }
        }
        
        if (!is_null($orderStatusConfigCancel)) {
            if ($orderStatusConfigCancel == $currentOrderStatus) {
                return true;
            }
        }

        return false;
    }
}
