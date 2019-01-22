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
namespace SDM\Altapay\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

/**
 * Class Pending
 * @package SDM\Altapay\Model\Config\Source\Order\Status
 */
class Pending extends Status
{

    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        Order::STATE_NEW,
        Order::STATE_PENDING_PAYMENT,
    ];
}
