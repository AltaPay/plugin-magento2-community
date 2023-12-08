<?php
namespace SDM\Altapay\Plugin\Checkout\Controller\Onepage;

use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;

class Success
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var CollectionFactory
     */
    protected $salesOrderCollection;

    public static $table = 'sales_order';

    /**
     * Success constructor.
     * @param Registry $coreRegistry
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param CollectionFactory $salesOrderCollection
     */
    public function __construct(
        Registry $coreRegistry,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        CollectionFactory $salesOrderCollection
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->salesOrderCollection = $salesOrderCollection;
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
        $collectionData = $this->salesOrderCollection->create()->addFieldToSelect(
            'increment_id'
        )->addFieldToFilter(
            'altapay_order_hash',
            $hash
        );
        $collectionInfo = $collectionData->getData();
        foreach ($collectionInfo as $data) {
            $orderId = $data['increment_id'];
            if ($orderId) {
                $order = $this->orderFactory->create()->loadByIncrementId($orderId);
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