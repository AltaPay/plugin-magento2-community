<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Test\Unit\Observer;

use SDM\Valitor\Observer\CaptureObserver as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use SDM\Valitor\Test\Unit\MainTestCase;

/**
 * Class CaptureObserverTest
 * Handle the invoice capture functionality.
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
