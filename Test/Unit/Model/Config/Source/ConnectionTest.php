<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Valitor
 * @category  payment
 * @package   valitor
 */

namespace SDM\Valitor\Test\Unit\Model\Config\Source;

use SDM\Valitor\Model\Config\Source\Connection as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use SDM\Valitor\Test\Unit\MainTestCase;

class ConnectionTest extends MainTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    protected function setUp()
    {
        $objectManager = $this->getObjectManager();
        $this->classToTest = $objectManager->getObject(ClassToTest::class);
    }

    public function testToOptionArray()
    {
        $result = $this->classToTest->toOptionArray();
        $this->assertCount(1, $result);
    }
}
