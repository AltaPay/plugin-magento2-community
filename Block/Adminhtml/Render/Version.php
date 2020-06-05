<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;
use SDM\Valitor\Response\TerminalsResponse;
use SDM\Valitor\Model\SystemConfig;

class Version extends Field
{
    const MODULE_CODE = 'SDM_Valitor';
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Version constructor.
     *
     * @param Context             $context
     * @param ModuleListInterface $moduleList
     * @param SystemConfig        $systemConfig
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        SystemConfig $systemConfig
    ) {
        $this->moduleList = $moduleList;
        parent::__construct($context);
        $this->systemConfig = $systemConfig;
    }


    /**
     * Render module version
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html       = '';
        $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);
        try {
            $call = new \SDM\Valitor\Api\Others\Terminals($this->systemConfig->getAuth());
            /** @var TerminalsResponse $response */
            $response  = $call->call();
            $terminals = [];

            foreach ($response->Terminals as $terminal) {
                $creditCard = false;
                foreach ($terminal->Natures as $nature) {
                    if ($nature->Nature == "CreditCard") {
                        $creditCard = true;
                    }
                }
                $terminals[] = [
                    'title'      => $terminal->Title,
                    'creditCard' => $creditCard
                ];
            }

            $html .= "<tr id='row_terminals_data'>";
            $html .= "<td class='label'><input type='hidden' id='terminal_data_obj' value='" . json_encode($terminals)
                     . "'></td>";
            $html .= " <td></td>";
            $html .= " <td></td>";
            $html .= "</tr>";

        } catch (\Exception $e) {
        }

        $html .= '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="label">' . $element->getData('label') . '</td>';
        $html .= '  <td class="value">' . $moduleInfo['setup_version'] . '</td>';
        $html .= '  <td></td>';
        $html .= '</tr>';

        return $html;
    }
}
