<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Test\Unit\Observer;

use SDM\Valitor\Model\Handler\RestoreQuote;
use SDM\Valitor\Observer\CheckoutCartIndex as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use SDM\Valitor\Test\Unit\MainTestCase;

class CheckoutCartIndexTest extends MainTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * setup class for restore quote observer
     */
    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $restoreQuote = $this->getMockBuilder(RestoreQuote::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'restoreQuote' => $restoreQuote
        ]);
    }

    /**
     * excute checkout cart index obeserver to restore quote
     */
    public function testExecute()
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }
}
