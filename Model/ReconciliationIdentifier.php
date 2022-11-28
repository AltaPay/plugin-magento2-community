<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use Magento\Framework\Model\AbstractModel;

class ReconciliationIdentifier extends AbstractModel
{
    public function _construct()
    {
        $this->_init(\SDM\Altapay\Model\ResourceModel\ReconciliationIdentifier::class);
    }
}
