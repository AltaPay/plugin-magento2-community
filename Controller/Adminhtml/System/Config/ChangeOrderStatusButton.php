<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use \Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class ChangeOrderStatusButton extends Action
{
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
        Context $context,
        LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
    ) {
        $this->logger = $logger;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->orderCollection = $orderCollection;
        parent::__construct($context);
    }
    /**
     * @return Json
     */
    public function execute()
    {
        $completeStatus = 'canceled';
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        try
        {
            $orderCollection = $this->orderCollection->create();
            $orderCollection->addAttributeToFilter('status','pending')
                            ->addAttributeToFilter('altapay_payment_form_url', ['neq' => 'NULL']);
            
            if (array_filter($orderCollection->getData())) {
                foreach ($orderCollection as $order) {

                    $order = $this->orderRepository->get($order->getEntityId());
                    $order->setStatus($completeStatus)->setState($completeStatus);

                    $this->orderRepository->save($order); 
                }

                $message = __('Order status has beem changed from pending to canceled');

            } else {

                $message = __('No order exist with pendng orders');
            }
        } catch (\Exception $e)
        {
            $message = __("Error:" . $e->getMessage());
        }

        /** @var Json $result */
        $result = $this->resultJsonFactory->create();

        return $result->setData(['message' => $message]);
    }
}