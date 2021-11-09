<?php
namespace SDM\Altapay\Plugin\Checkout\Controller\Onepage;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderColl;
use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;

class Success
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $orderColl;

    public static $table = 'sales_order';

    /**
     * Success constructor.
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order $orderFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $OrderColl
     */
    public function __construct(
        Registry $coreRegistry,
        Session $checkoutSession,
        Order $orderFactory,
        OrderColl $orderColl
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderColl = $orderColl;
    }

    /**
     * @param \Magento\Checkout\Controller\Onepage\Success $subject
     */
    public function beforeExecute(\Magento\Checkout\Controller\Onepage\Success $subject)
    {
        $hash = $subject->getRequest()->getParam('success_token', false);
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
                $order = $this->orderFactory->loadByIncrementId($orderId);
                if ($order && $order->getId() && $order->getAltapayOrderHash() !== null) { 
                    $this->checkoutSession->setLastQuoteId($order->getQuoteId());
                    $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                    $this->checkoutSession->setLastOrderId($order->getId());
                    $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
                    $this->checkoutSession->setLastOrderStatus($order->getStatus());
                    $order->setAltapayOrderHash(null);
                    $order->getResource()->save($order);
                }
                
            }
        }
    }
}