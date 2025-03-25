<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SDM\Altapay\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Sales\Model\Order;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

class Cancel extends Action
{
    protected $orderRepository;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderRepository->get($orderId);
            if ($order->canCancel()) {
                $order->cancel();
                $this->orderRepository->save($order);
                $this->messageManager->addSuccessMessage(__('Order canceled successfully.'));
            } else {
                $order->setState(Order::STATE_CANCELED)
                      ->setStatus(Order::STATE_CANCELED);
                $this->orderRepository->save($order);
                $this->messageManager->addSuccessMessage(__('Order forcefully canceled.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error canceling order: ' . $e->getMessage()));
        }

        return $this->_redirect('sales/order/view', ['order_id' => $orderId]);
    }
}
