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

namespace SDM\Altapay\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Class Version
 * @package SDM\Altapay\Block\Adminhtml\Render
 */
class Version extends Field
{
    const MODULE_CODE = 'SDM_Altapay';
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * Version constructor.
     *
     * @param Context                  $context
     * @param ModuleListInterface      $moduleList
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList
    ) {
        $this->moduleList = $moduleList;
        parent::__construct($context);
    }

    /**
     * Render module version
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);
        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="label">' . $element->getData('label') . '</td>';
        $html .= '  <td class="value">' . $moduleInfo['setup_version'] . '</td>';
        $html .= '  <td></td>';
        $html .= '</tr>';

        return $html;
    }
}
