<?php
/**
 * Altapay Module version 3.0.1 for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
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

/**
 * Class RestoreQuote
 *
 * @package SDM\Altapay\Model\Handler
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
    public function __construct(Session $checkoutSession, OrderFactory $orderFactory, QuoteFactory $quoteFactory, ManagerInterface $messageManager, Coupon $coupon, CouponUsage $couponUsage, OrderLoaderInterface $orderLoader, StockManagementInterface $stockManagement, SystemConfig $systemConfig, ResourceConnection $modelResource)
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
    }

    /**
     * @return void
     */
    public function handleQuote()
    {
        //check if customer redirect from altapay
        if ($this->checkoutSession->getAltapayCustomerRedirect()) {
            //get last order Id from inteface
             $orderId = $this->orderLoader->getLastOrderIncrementIdFromSession();
             $order = $this->checkoutSession->getLastRealOrder();
             $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());

            //Default history and message
             $history = __(ConstantConfig::BROWSER_BK_BUTTON_COMMENT);
             $message = __(ConstantConfig::BROWSER_BK_BUTTON_MSG);


             //get transaction details if faliure and redirect to cart
             $getTransactionData = $this->getTransactionData($orderId);
             
              //if fail set message and history
            if (!empty($getTransactionData)) {
                $getTransactionDataDecode = json_decode($getTransactionData);

                if ($getTransactionDataDecode->error_message) {
                    $history = $getTransactionDataDecode->error_message.' '.$getTransactionDataDecode->merchant_error_message;
                    $message = $getTransactionDataDecode->error_message.' '.$getTransactionDataDecode->merchant_error_message;
                }
            }
 
            //set before state set in admin configuration
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode = $order->getStore()->getCode();
            $orderStatus = $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode);

            //echo $orderStatus.'---'.$order->getStatus();exit;
            //if quote id exist and order status is from config
            if ($quote->getId() && $order->getStatus() == $orderStatus) {
                //get quote Id from order and set as active
                $quote->setIsActive(1)->setReservedOrderId(null)->save();
                $this->checkoutSession->replaceQuote($quote)->unsLastRealOrderId();
                //set order status and comments
                $order->setState(Order::STATE_CANCELED);
                $order->setIsNotified(false);
                $order->addStatusHistoryComment($history, Order::STATE_CANCELED);

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
            $this->checkoutSession->unsAltapayCustomerRedirect();
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
        $sql = "SELECT parametersdata FROM sdm_altapay WHERE orderid = '$orderid'";
        return $result = $connection->fetchOne($sql);
    }
}
