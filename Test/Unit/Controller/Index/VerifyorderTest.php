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
namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\Action\Action;
use SDM\Altapay\Controller\Index\Verifyorder as ClassToTest;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use SDM\Altapay\Test\Unit\MainTestCase;

/**
 * Class VerifyorderTest
 * @package SDM\Altapay\Controller\Index
 */
class VerifyorderTest extends MainTestCase
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
