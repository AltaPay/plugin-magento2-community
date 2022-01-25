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
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class ChangeOrderStatusButton extends Action
{
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
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * ChangeOrderStatusButton constructor.
     *
     * @param Context                  $context
     * @param LoggerInterface          $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder    $searchCriteriaBuilder
     * @param ScopeConfigInterface     $scopeConfig
     * @param JsonFactory              $resultJsonFactory
     * @param CollectionFactory        $orderCollection
     */

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        JsonFactory $resultJsonFactory,
        CollectionFactory $orderCollection
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
                
                $message = __('Order status has been changed from pending to canceled');

            } else {
                $message = __('No order exist with pendng status');
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