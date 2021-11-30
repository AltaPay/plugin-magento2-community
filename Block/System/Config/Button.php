<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SDM\Altapay\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\App\RequestInterface;

class Button extends Field
{
    protected $_template = 'SDM_Altapay::system/config/button.phtml';
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        Context $context,
        RequestInterface $request,
        array $data = []
        )
    {
        parent::__construct($context, $data);
        $this->request = $request;
    }

    /**
     * @param AbstractElement $element
     *
     * @return mixed
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getCustomUrl()
    {
        return $this->getUrl('sdmaltapay/system_config/button');
    }

    /**
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'sync_button',
                'label' => __('Synchronize Terminals')
            ]
        );

        return $button->toHtml();
    }

    /**
     * @return int
     */
    public function getUrlInterfaceData()
    {
        $request = $this->_request;

        return (int) $request->getParam('store', 0);
    }
}