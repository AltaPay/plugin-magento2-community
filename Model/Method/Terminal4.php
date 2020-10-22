<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Method;

/**
 * Pay In Store payment method model
 */
class Terminal4 extends TerminalModel
{
    const METHOD_CODE = 'terminal4';

    /**
     * Payment code
     *
     * @var string
     */

    protected $_code = self::METHOD_CODE;
}
