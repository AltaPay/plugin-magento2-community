<?php
namespace SDM\Altapay\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
class Button extends Field
{
    protected $_template = 'SDM_Altapay::system/config/button.phtml';

    public function __construct(
        Context $context, 
        \Magento\Framework\UrlInterface $urlInterface,  
        \Magento\Framework\App\RequestInterface $request,  
        array $data = []
        )
    {
        parent::__construct($context, $data);
        $this->_urlInterface = $urlInterface;
        $this->request = $request;
        
    }
 
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getCustomUrl()
    {
        return $this->getUrl('sdmaltapay/system_config/button');
    }

        /**
     * @throws LocalizedException
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'btn_id',
                'label' => __('Synchronize Terminals')
            ]
        );

        return $button->toHtml();
    }

    /**
     * Prining URLs using URLInterface
     */
    public function getUrlInterfaceData()
    {
        $request = $this->_request;

        return (int) $request->getParam('store', 0);
    }


}