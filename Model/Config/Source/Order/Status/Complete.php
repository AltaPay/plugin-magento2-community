<?php
/**
 * Altapay Module version 3.0.1 for Magento 2.x.
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
 * Class Complete
 * @package SDM\Altapay\Model\Config\Source\Order\Status
 */
class Complete extends Status
{

    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        Order::STATE_COMPLETE,
        Order::STATE_CLOSED,
    ];
}
