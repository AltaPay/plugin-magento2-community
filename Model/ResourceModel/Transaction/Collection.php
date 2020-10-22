<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\ResourceModel\Transaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use SDM\Altapay\Api\Data\TransactionInterface;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $idFieldName = TransactionInterface::ENTITY_ID;

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\SDM\Altapay\Model\Transaction::class, \SDM\Altapay\Model\ResourceModel\Transaction::class);
    }
}
