<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SDM\Altapay\Model\Handler;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\RefundOrder;
use Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory;
use SDM\Altapay\Logger\Logger;

class CreateCreditMemo
{
    /**
     * @var ItemCreationFactory
     */
    protected $itemCreationFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var RefundOrder
     */
    protected $refundOrder;

    /**
     * @var Logger
     */
    protected $altapayLogger;

    /**
     * @param ItemCreationFactory $itemCreationFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param RefundOrder $refundOrder
     * @param Logger $altapayLogger
     */
    public function __construct(
        ItemCreationFactory      $itemCreationFactory,
        OrderRepositoryInterface $orderRepository,
        RefundOrder              $refundOrder,
        Logger                   $altapayLogger
    )
    {
        $this->itemCreationFactory = $itemCreationFactory;
        $this->orderRepository = $orderRepository;
        $this->refundOrder = $refundOrder;
        $this->altapayLogger = $altapayLogger;
    }

    /**
     * @param int $orderId
     * @return void
     */
    public function createCreditMemo(int $orderId)
    {
        try {

            $order = $this->orderRepository->get($orderId);

            if (!$order->canCreditmemo()) {
                $this->altapayLogger->addInfoLog('Error', 'Cannot create credit memo for order : ' . $orderId);
            }

            $itemIdsToRefund = [];

            foreach ($order->getAllItems() as $orderItem) {
                $creditMemoItem = $this->itemCreationFactory->create();

                $creditMemoItem->setQty($orderItem->getQtyOrdered())->setOrderItemId($orderItem->getId());
                $itemIdsToRefund[] = $creditMemoItem;
            }

            $this->refundOrder->execute($orderId, $itemIdsToRefund);

        } catch (NoSuchEntityException $e) {
            $this->altapayLogger->addCriticalLog('Undefined orderId:', $orderId);
        } catch (Exception $e) {
            $this->altapayLogger->addCriticalLog('Cannot create credit memo', $e->getMessage());
        }
    }
}