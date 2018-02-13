<?php
namespace Bunting\Core\Block;

use Magento\Framework\View\Element\Template;

class Install extends Template
{
    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->pageConfig->getTitle()->set(__('Install Bunting'));

        $store_id = $this->_storeManager->getStore()->getId();

        $shopName = $this->_scopeConfig->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id);

        $this->assign('shop_owner_email',$this->_scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id));
        $this->assign('shop_name',$shopName);
        $this->assign('potential_subdomain',strtolower(preg_replace("/[^A-Za-z0-9]/", '', $shopName)));
    }
}