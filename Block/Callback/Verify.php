<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Block\Callback;

use Magento\Framework\View\Element\Template;

class Verify extends Template
{
    protected function _prepareLayout()
    {
        $message = __('OKAY');
        $this->setMessage($message);
        
        return $this;
    }
}
