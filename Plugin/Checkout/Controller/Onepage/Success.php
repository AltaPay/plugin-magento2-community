<?php
namespace SDM\Altapay\Plugin\Checkout\Controller\Onepage;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderColl;


class Success
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /** @var \Magento\Sales\Model\OrderFactory **/
    protected $_orderFactory;

    public static $table = 'sales_order';


    protected $orderColl;
    protected $collectionFactory;
    /**
     * 
     * Success constructor.
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order $orderFactory,
        OrderColl $orderColl
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->orderColl = $orderColl;
    }

    /**
     * @param \Magento\Checkout\Controller\Onepage\Success $subject
     */
    public function beforeExecute(\Magento\Checkout\Controller\Onepage\Success $subject)
    {
        $hash = $subject->getRequest()->getParam('order_id', false);
        if (!$hash) {
            return;
        }
        $collectionData = $this->orderColl->create()->addFieldToSelect(
            'increment_id'
        )->addFieldToFilter(
            'altapay_order_hash',
            $hash
        );
        $collectionInfo = $collectionData->getData();
        foreach ($collectionInfo as $data) {
            $orderId = $data['increment_id'];
            if ($orderId && is_numeric($orderId)) {
                $order = $this->_orderFactory->loadByIncrementId($orderId);
                if ($order && $order->getId() && $order->getAltapayOrderHash() !== null) { 
                    $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
                    $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                    $this->_checkoutSession->setLastOrderId($order->getId());
                    $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
                    $this->_checkoutSession->setLastOrderStatus($order->getStatus());
                    $order->setAltapayOrderHash(null);
                    $order->getResource()->save($order);
                }
                
            }
        }
    }
}