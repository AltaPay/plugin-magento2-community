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
namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\Action\Action;
use SDM\Altapay\Controller\Index\Ok as ClassToTest;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use SDM\Altapay\Test\Unit\MainTestCase;

/**
 * Class OkTest
 * @package SDM\Altapay\Controller\Index
 */
class OkTest extends MainTestCase
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
