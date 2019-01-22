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

namespace SDM\Altapay\Test\Unit\Observer;

use SDM\Altapay\Observer\CaptureObserver as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use SDM\Altapay\Test\Unit\MainTestCase;

/**
 * Class CaptureObserverTest
 * @package SDM\Altapay\Test\Unit\Observer
 */
class CaptureObserverTest extends MainTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager
     */
    private $objectManager;
}
