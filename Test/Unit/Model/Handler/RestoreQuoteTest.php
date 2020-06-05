<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Test\Unit\Model\Handler;

use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use SDM\Valitor\Model\Handler\RestoreQuote as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use SDM\Valitor\Test\Unit\MainTestCase;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Exception\LocalizedException;
use SDM\Valitor\Test\Unit\ConstantTestConfig;

class RestoreQuoteTest extends MainTestCase
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
     * @var Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

    /**
     * setup class for restore quote called before test execution
     */
    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getId')->willReturn(ConstantTestConfig::ID);

        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getLastOrderId',
                'unsLastRealOrderId',
            ])
            ->getMock();
        $checkoutSession->method('getLastOrderId')->willReturn(ConstantTestConfig::LAST_ORDER_ID);
        $checkoutSession->method('unsLastRealOrderId')->willReturn($checkoutSession);

        $this->order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $this->order->method('load')->willReturn($this->order);

        $orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $orderFactory->method('create')->willReturn($this->order);

        $quoteRepository = $this->getMockBuilder(QuoteRepository::class)->disableOriginalConstructor()->getMock();
        $quoteRepository->method('get')->willReturn($quote);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'checkoutSession' => $checkoutSession,
            'orderFactory' => $orderFactory,
            'quoteFactory' => $quoteFactory
        ]);
    }
   
    /**
     * handle quote method test
     */
    public function testHandleQuote()
    {
        $result = $this->classToTest->handleQuote();
        $this->assertNull($result);
    }

    public function testExecuteException()
    {
        $exception = new \Exception();
        $this->order->method('cancel')->willThrowException($exception);

        $result = $this->classToTest->handleQuote();
        $this->assertNull($result);
    }

    public function testExecuteLocalizedException()
    {
        $exception = new LocalizedException(__('An error occured'));
        $this->order->method('cancel')->willThrowException($exception);

        $result = $this->classToTest->handleQuote();
        $this->assertNull($result);
    }
}
