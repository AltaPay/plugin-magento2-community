<?php
namespace SDM\Altapay\Plugin\Checkout\Controller\Cart;

use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\QuoteFactory;

class Index
{
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

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * Index constructor.
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param CollectionFactory $salesOrderCollection
     * @param QuoteFactory $quoteFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Session $checkoutSession,
        OrderFactory $orderFactory,
        CollectionFactory $salesOrderCollection,
        QuoteFactory $quoteFactory,
        ManagerInterface $messageManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->salesOrderCollection = $salesOrderCollection;
        $this->quoteFactory = $quoteFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * @param \Magento\Checkout\Controller\Cart\Index $subject
     */
    public function beforeExecute(\Magento\Checkout\Controller\Cart\Index $subject)
    {
        $hash = $subject->getRequest()->getParam('restore_token', false);
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
                    $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
                    if ($quote->getId()) {
                        $quote->setIsActive(1)->setReservedOrderId(null)->save();
                        $this->checkoutSession->replaceQuote($quote);
                    }
                    $msg = $subject->getRequest()->getParam('msg');
                    if (!empty($msg)) {
                        $this->messageManager->addErrorMessage(__($msg));
                    }
                    $order->setAltapayOrderHash(null);
                    $order->getResource()->save($order);
                }

            }
        }
    }
}
