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
namespace SDM\Valitor\Controller\Index;

use Magento\Framework\App\Action\Action;
use SDM\Valitor\Controller\Index\Request as ClassToTest;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use SDM\Valitor\Test\Unit\MainTestCase;

/**
 * Class RequestTest
 * @package SDM\Valitor\Controller\Index
 */
class RequestTest extends MainTestCase
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
