<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Valitor
 * @category  payment
 * @package   valitor
 */
namespace SDM\Valitor\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

/**
 * Class Processing
 * @package SDM\Valitor\Model\Config\Source\Order\Status
 */
class Processing extends Status
{

    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        Order::STATE_PROCESSING,
    ];
}
