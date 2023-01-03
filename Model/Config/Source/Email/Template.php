<?php
namespace SDM\Altapay\Model\Config\Source\Email;

use Magento\Config\Model\Config\Source\Email\Template as EmailTemplate;
use Magento\Framework\Option\ArrayInterface;

class Template implements ArrayInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Email\Template
     */
    private $templateSource;

    /**
     * @param \Magento\Config\Model\Config\Source\Email\Template $templateSource
     */
    public function __construct(
        EmailTemplate $templateSource
    ) {
        $this->templateSource = $templateSource;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {        
        return $this->templateSource->setPath('payment_sdm_altapay_config_general_payment_template')->toOptionArray();
    }
}
