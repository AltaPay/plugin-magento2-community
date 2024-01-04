<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Cron;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class UpdateOrderStatus 
{
    const CRON_ENABLED = 'payment/sdm_altapay_config/cron_scheduled/enabled';
    const EXCLUDE_ADMIN_ORDER = 'payment/sdm_altapay_config/cron_scheduled/exclude_orders';
    const CRON_CANCELLATION_HOURS = 'payment/sdm_altapay_config/cron_scheduled/cancellation_timeframe';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CollectionFactory
     */
    protected $orderCollection;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * UpdateOrderStatus constructor.
     *
     * @param LoggerInterface          $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder    $searchCriteriaBuilder
     * @param ScopeConfigInterface     $scopeConfig
     * @param CollectionFactory        $orderCollection
     */

    public function __construct(
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $orderCollection
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
        $storeScope = ScopeInterface::SCOPE_STORE;
        $cronEnabled = $this->scopeConfig->getValue(self::CRON_ENABLED, $storeScope);

        $cancellationTimeframe = $this->scopeConfig->getValue(self::CRON_CANCELLATION_HOURS);
        if(!$cancellationTimeframe) {
            $cancellationTimeframe = 24;
        }
        $cutoffTime = strtotime("-$cancellationTimeframe hours");

        try
        {
            if (!$cronEnabled){
                $this->logger->info('Cron is not enabled');
                return;
            }
            $orderCollection = $this->orderCollection->create();
            $orderCollection->addFieldToSelect('entity_id')
                            ->addFieldToFilter('created_at', ['lt' => date('Y-m-d H:i:s', $cutoffTime)])
                            ->addAttributeToFilter('status','pending')
                            ->addAttributeToFilter('state','new')
                            ->addAttributeToFilter('altapay_payment_form_url', ['neq' => 'NULL']);
            
            if ($this->scopeConfig->getValue(self::EXCLUDE_ADMIN_ORDER, $storeScope)) {
                $orderCollection->addFieldToFilter('remote_ip', ['neq' => null]);
            }
            
            if (array_filter($orderCollection->getData())) {
                foreach ($orderCollection as $order) {

                    $order = $this->orderRepository->get($order->getEntityId());
                    $order->setStatus($completeStatus)->setState($completeStatus);

                    $this->orderRepository->save($order); 
                }

                $this->logger->info('Order status has been changed from pending to canceled');
            } else {

                $this->logger->info('No order exist with pending status');
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception(__('Something went wrong , '.$e->getMessage()));
        }
    }

}