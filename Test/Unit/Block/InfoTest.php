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

namespace SDM\Valitor\Test\Unit\Block;

use Magento\Sales\Model\Order;
use SDM\Valitor\Block\Info as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\Info;
use SDM\Valitor\Test\Unit\MainTestCase;
use SDM\Valitor\Test\Unit\ConstantTestConfig;

/**
 * Class InfoTest
 * @package SDM\Valitor\Test\Unit\Block
 */
class InfoTest extends MainTestCase
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
     * @var Info|\PHPUnit_Framework_MockObject_MockObject
     */
    private $info;

    /**
     *
     */
    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCcTransId',
                'getPaymentId'
            ])
            ->getMock();
        $order->method('getCcTransId')->willReturn(ConstantTestConfig::CC_TRANSACTION_ID);
        $order->method('getPaymentId')->willReturn(ConstantTestConfig::CC_PAYMENT_ID);

        $this->info = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLastTransId', 'getOrder'])
            ->getMock();
        $this->info->method('getOrder')->willReturn($order);

        $this->classToTest->setInfo($this->info);
    }

    /**
     *
     */
    public function testPrepareSpecificInformation()
    {
        $this->info->method('getLastTransId')->willReturn(ConstantTestConfig::CC_TRANSACTION_ID);
        $result = $this->classToTest->getSpecificInformation();
        $this->assertNotEmpty($result);
    }

    /**
     *
     */
    public function testPrepareSpecificInformationNoLastTransId()
    {
        $this->info->method('getLastTransId')->willReturn('');

        $result = $this->classToTest->getSpecificInformation();
        $this->assertArrayHasKey('Payment has not been processed yet.', $result);
    }
}
