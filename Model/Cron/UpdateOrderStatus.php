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
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class UpdateOrderStatus {

    const CRON_ENABLED = 'payment/sdm_altapay_config/cronScheduled/enabled';

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