<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Cron;

use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Api\OrderManagementInterface;

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
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * UpdateOrderStatus constructor.
     *
     * @param LoggerInterface          $logger
     * @param SearchCriteriaBuilder    $searchCriteriaBuilder
     * @param ScopeConfigInterface     $scopeConfig
     * @param CollectionFactory        $orderCollection
     * @param OrderManagementInterface $orderManagement
     */

    public function __construct(
        LoggerInterface $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $orderCollection,
        OrderManagementInterface $orderManagement
    ) {
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->orderCollection = $orderCollection;
        $this->orderManagement = $orderManagement;
    }

    public function execute()
    {
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
                    $orderId = $order->getEntityId();

                    if (!is_numeric($orderId) || (int)$orderId <= 0) {
                        throw new \InvalidArgumentException('Invalid order ID');
                    }

                    $this->orderManagement->cancel($orderId);
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