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
use Magento\Framework\App\CacheInterface;

class Cancel extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        CacheInterface $cache
    ) {
        $this->orderRepository = $orderRepository;
        $this->cache           = $cache;
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $key = 'altapay_cancel_forcefully_' . $orderId;
        $this->cache->save(true, $key, ['cancel_forcefully'], 60 * 60);

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
