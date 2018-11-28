<?php
/**
 * Altapay Module version 3.0.1 for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */

namespace SDM\Altapay\Test\Unit\Observer;

use SDM\Altapay\Observer\BeforePaymentObserver as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use SDM\Altapay\Test\Unit\MainTestCase;
use SDM\Altapay\Model\SystemConfig;

/**
 * Class BeforePaymentObserverTest
 * @package SDM\Altapay\Test\Unit\Observer
 */
class BeforePaymentObserverTest extends MainTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $beforePaymentObserver = $this->getMockBuilder(BeforePaymentObserver::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
        'beforePaymentObserver' => $beforePaymentObserver
        ]);
    }

    /**
     * Compare both expected vs actual results new/new
     */
    public function testExecute()
    {
        $expectedStatus = 'new';

        $paymentMethod = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $paymentMethod->method('getConfigData')->willReturn($expectedStatus);

        $order = $this->objectManager->getObject(Order::class);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getMethodInstance')->willReturn($paymentMethod);
        $payment->method('getOrder')->willReturn($order);

        $event = $this->getMockBuilder(Event::class)->disableOriginalConstructor()->setMethods(['getPayment'])->getMock();
        $event->method('getPayment')->willReturn($payment);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $observer->method('getEvent')->willReturn($event);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);

        $this->assertEquals(Order::STATE_NEW, $order->getState());
        $this->assertEquals($expectedStatus, $order->getStatus());
    }
}
