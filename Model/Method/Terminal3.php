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
namespace SDM\Altapay\Model\Method;

/**
 * Pay In Store payment method model
 */
class Terminal3 extends TerminalModel
{

    /**
     *
     */
    const METHOD_CODE = 'terminal3';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;
}
