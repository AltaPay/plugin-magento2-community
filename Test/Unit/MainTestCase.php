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

namespace SDM\Altapay\Test\Unit;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Class MainTestCase
 * @package SDM\Altapay\Test\Unit
 */
class MainTestCase extends TestCase
{
    /**
     * Return ObjectManager
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return new ObjectManager($this);
    }
}
