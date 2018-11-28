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
namespace SDM\Altapay\Model;

use \Magento\Framework\Webapi\Rest\Request;
use SDM\Altapay\Api\GatewayInterface;

/**
 * Class Gateway
 * @package SDM\Altapay\Model
 */
class Gateway implements GatewayInterface
{
    protected $request;
    protected $jsonHelper;

    public function __construct(
        Request $request,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
    }

   /**
    * createRequest to altapay
    * @return void
    */
    public function createRequest()
    {
    }
}
