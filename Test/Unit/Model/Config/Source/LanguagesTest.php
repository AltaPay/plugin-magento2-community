<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Test\Unit\Model\Config\Source;

use SDM\Valitor\Model\Config\Source\Languages as ClassToTest;
use Magento\Config\Model\Config\Source\Locale;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use SDM\Valitor\Test\Unit\MainTestCase;

class LanguagesTest extends MainTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    protected function setUp()
    {
        $objectManager     = $this->getObjectManager();
        $this->classToTest = $objectManager->getObject(ClassToTest::class);
    }

    public function testToOptionArray()
    {
        $result = $this->classToTest->toOptionArray();
        $this->assertCount(count($result), $result);
    }
}
