<?php

namespace SDM\Valitor\Ui\Component\Listing\Column;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Framework\App\Config\ScopeConfigInterface;

class PaymentMethod extends Column
{
    protected $_orderRepository;
    protected $_searchCriteria;
    /**
     * @var ScopeConfigInterface
     */
    protected $_appConfigScopeConfigInterface;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $criteria,
        ScopeConfigInterface $appConfigScopeConfigInterface,
        array $components = [],
        array $data = []
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria  = $criteria;
        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    
    /**
     * {@inheritdoc}
     *
     * @param  array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as $key => &$items) {
                $order_id = $items["entity_id"];
                if (isset($items["order_id"])) {
                    $order_id = $items["order_id"];
                }
                $order  = $this->_orderRepository->get($order_id);
                $storeCode = $order->getStore()->getCode();
                $storeId = $order->getStore()->getId();
                $payment = $order->getPayment();
                $method = $payment->getMethodInstance();
                $title = $method->getConfigData('title', $storeId);;
                $terminalID = $payment->getMethod();
                    if($title == null){
                        $terminalTitle = $this->_appConfigScopeConfigInterface
                        ->getValue('payment/'.$terminalID.'/terminalname',$storeScope,$storeCode); 
                    } else{
                        $terminalTitle = $title; 
                    }
                $dataSource['data']['items'][$key]['payment_method_title'] = $terminalTitle;  
            }
        }
        return $dataSource;
    }
}
