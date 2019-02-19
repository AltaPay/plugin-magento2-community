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
 
namespace SDM\Valitor\Block\Callback;

use Magento\Framework\View\Element\Template;

/**
 * Class Verify
 * @package SDM\Valitor\Block\Callback
 */
class Verify extends Template
{

    /**
     *
     */
    protected function _prepareLayout()
    {
        $message =  __('OKAY');
        $this->setMessage($message);
    }
}
