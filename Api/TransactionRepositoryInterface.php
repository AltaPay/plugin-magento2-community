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

namespace SDM\Altapay\Api;

/**
 * Interface TransactionRepositoryInterface
 *
 * @package SDM\Altapay\Api
 */
interface TransactionRepositoryInterface
{
    /**
     * It creates the entity and saves the XML request.
     *
     * @param string $orderid
     * @param string $transactionid
     * @param string $paymentid
     * @param string $transactiondata
     * @param string $parametersdata
     */
    public function addTransactionData($orderid, $transactionid, $paymentid, $transactiondata, $parametersdata);
}
