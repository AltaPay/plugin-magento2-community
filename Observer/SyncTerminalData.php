<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use SDM\Altapay\Logger\Logger;
use SDM\Altapay\Model\TerminalData;

/**
 * Reads the terminal details from the gateway when the payment configuration is saved.
 */
class SyncTerminalData implements ObserverInterface
{
    /**
     * @var TerminalData
     */
    private $terminalData;

    /**
     * @var Logger
     */
    private $altapayLogger;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param TerminalData         $terminalData
     * @param Logger               $altapayLogger
     * @param ManagerInterface     $messageManager
     */
    public function __construct(
        TerminalData $terminalData,
        Logger $altapayLogger,
        ManagerInterface $messageManager
    ) {
        $this->terminalData   = $terminalData;
        $this->altapayLogger  = $altapayLogger;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $scope   = $observer->getEvent()->getData('scope') ?: 'default';
        $scopeId = (int)$observer->getEvent()->getData('scope_id');

        try {
            $this->terminalData->sync($scope, $scopeId);
        } catch (\Exception $e) {
            $this->altapayLogger->addCriticalLog('Terminal data exception', $e->getMessage());
            $this->messageManager->addErrorMessage(
                __('The terminals could not be read from AltaPay: %1', $e->getMessage())
            );
        }
    }
}
