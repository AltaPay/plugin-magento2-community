<?php
namespace SDM\Altapay\Model\Cron;

use \Psr\Log\LoggerInterface;

class UpdateOrderStatus {

    const CRON_ENABLED = 'payment/sdm_altapay_config/cronScheduled/enabled';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollection;

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
     */
    public function __construct(
        LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->orderCollection = $orderCollection;
    }

    public function execute()
    {
        $completeStatus = 'canceled';
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $cronEnabled = $this->scopeConfig->getValue(self::CRON_ENABLED, $storeScope);
        try
        {
            if (!$cronEnabled){
                $this->logger->info('Cron is not enabled');
                return;
            }
            $orderCollection = $this->orderCollection->create();
            $orderCollection->addAttributeToFilter('status','pending')
                            ->addAttributeToFilter('altapay_payment_form_url', ['neq' => 'NULL']);

            if (array_filter($orderCollection->getData())) {
                foreach ($orderCollection as $order) {

                    $order = $this->orderRepository->get($order->getEntityId());
                    $order->setStatus($completeStatus)->setState($completeStatus);

                    $this->orderRepository->save($order); 
                }

                $this->logger->info('Order status has beem changed from pending to canceled');
            } else {

                $this->logger->info('No order exist with pendng orders');
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception(__('Some Thing Want Wrong , '.$e->getMessage()));
        }
    }

}