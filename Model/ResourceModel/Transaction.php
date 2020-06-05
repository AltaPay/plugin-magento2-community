<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use SDM\Valitor\Api\Data\TransactionInterface;

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
