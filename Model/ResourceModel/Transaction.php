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
namespace SDM\Altapay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use SDM\Altapay\Api\Data\TransactionInterface;

/**
 * Class Transaction
 * @package SDM\Altapay\Model\ResourceModel
 */
class Transaction extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            TransactionInterface::TABLE_NAME,
            TransactionInterface::ENTITY_ID
        );
    }
}
