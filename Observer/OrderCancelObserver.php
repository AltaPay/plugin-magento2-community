<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */
namespace SDM\Altapay\Observer;

use SDM\Altapay\Api\Payments\ReleaseReservation;
use SDM\Altapay\Exceptions\ResponseHeaderException;
use SDM\Altapay\Response\ReleaseReservationResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Altapay\Model\SystemConfig;

/**
 * Class OrderCancelObserver
 * @package SDM\Altapay\Observer
 */
class OrderCancelObserver implements ObserverInterface
{

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * OrderCancelObserver constructor.
     * @param SystemConfig $systemConfig
     */
    public function __construct(SystemConfig $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws ResponseHeaderException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer['order'];

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
            $api = new ReleaseReservation($this->systemConfig->getAuth($order->getStore()->getCode()));
            $api->setTransaction($payment->getLastTransId());
            /** @var ReleaseReservationResponse $response */
            try {
                $response = $api->call();
                if ($response->Result != 'Success') {
                    throw new \InvalidArgumentException('Could not release reservation');
                }
            } catch (ResponseHeaderException $e) {
                throw $e;
            }
        }
    }
}
