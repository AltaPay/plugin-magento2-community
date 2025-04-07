<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SDM\Altapay\Plugin\Block\Adminhtml\Order;

use SDM\Altapay\Api\Data\TransactionInterface;
use Magento\Framework\App\CacheInterface;

class View
{
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(
        CacheInterface $cache
    ) {
        $this->cache = $cache;
    }

    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $view)
    {
        $order = $view->getOrder();

        // Check if order is already canceled
        if ($order->getState() === \Magento\Sales\Model\Order::STATE_CANCELED) {
            return;
        }

        $key           = 'altapay_release_failed_' . $order->getId();
        $releaseFailed = $this->cache->load($key);

        if ($releaseFailed) {
            $message = __('This will cancel the order in Magento only. No notification will be sent to AltaPay. Are you sure you want to continue?');
            $url = $view->getUrl('sdmaltapay/order/cancel', ['order_id' => $order->getId()]);

            $view->addButton(
                'forcefully_cancel_order',
                [
                    'label' => __('Cancel Forcefully'),
                    'onclick' => "confirmSetLocation('{$message}', '{$url}')"
                ],
                -1
            );
        }
    }
}