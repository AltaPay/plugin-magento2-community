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

namespace SDM\Altapay\Test\Unit\Setup;

use SDM\Altapay\Setup\UpgradeSchema as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use SDM\Altapay\Test\Unit\MainTestCase;

/**
 * Class UpgradeSchemaTest
 * @package SDM\Altapay\Test\Unit\Setup
 */
class UpgradeSchemaTest extends MainTestCase
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
     *
     */
    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class);
    }

    /**
     *
     */
    public function testUpgrade()
    {
        $table = $this->getMockBuilder(Table::class)->disableOriginalConstructor()->getMock();

        $connection = $this->getMockBuilder(AdapterInterface::class)->disableOriginalConstructor()->getMock();
        $connection->method('isTableExists')->willReturn(false);
        $connection->method('newTable')->willReturn($table);

        $setup = $this->getMockBuilder(SchemaSetupInterface::class)->disableOriginalConstructor()->getMock();
        $setup->method('getConnection')->willReturn($connection);
        $setup->method('getTable')->willReturn('table');

        $context = $this->getMockBuilder(ModuleContextInterface::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->upgrade($setup, $context);
        $this->assertNull($result);
    }
}
