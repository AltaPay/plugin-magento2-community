<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Config\Source;

use Altapay\Response\TerminalsResponse;
use Magento\Framework\Option\ArrayInterface;
use SDM\Altapay\Model\SystemConfig;
use SDM\Altapay\Logger\Logger;
use SDM\Altapay\Model\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfiguredTerminals implements ArrayInterface
{
    /**
     * @var ConfigProvider
     */
    private $dataPayment;
    /**
     * @var SystemConfig
     */
    private $systemConfig;
    /**
     * @var Logger
     */
    private $altapayLogger;

    /**
     * Terminals constructor.
     *
     * @param SystemConfig $systemConfig
     * @param Logger       $altapayLogger
     */
    public function __construct(
        SystemConfig $systemConfig,
        ScopeConfigInterface $scopeConfig,
        ConfigProvider $dataPayment,
        Logger $altapayLogger
    ) {
        $this->dataPayment = $dataPayment;
        $this->systemConfig  = $systemConfig;
        $this->scopeConfig = $scopeConfig;
        $this->altapayLogger = $altapayLogger;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $terminals = [];
        $storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode         = $this->systemConfig->resolveCurrentStoreCode();

        try {
            $paymentMethod = $this->dataPayment->getActivePaymentMethod();
            $terminals[] = ['value' => ' ', 'label' => '-- Please Select --'];
            foreach ($paymentMethod as $key => $paymentModel) {
                foreach (SystemConfig::getTerminalCodes() as $terminalName) {
                    if($terminalName == $key) {
                        $terminalStatus = $this->scopeConfig->getValue('payment/' . $key . '/active', $storeScope, $storeCode);
                        $label          = $this->scopeConfig->getValue('payment/' . $key .  '/title', $storeScope, $storeCode);
                        if($terminalStatus){ 
                            $terminals[] = ['value' => $key, 'label' => ucfirst($key).' - '.$label];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->altapayLogger->addCriticalLog('Exception', $e->getMessage());
        }
        // Sort the terminals alphabetically
        array_multisort(array_column($terminals, 'label'), SORT_ASC, SORT_NUMERIC, $terminals);

        return $terminals;
    }
}
