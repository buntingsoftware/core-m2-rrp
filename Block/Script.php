<?php
namespace Bunting\Core\Block;

use Magento\Framework\View\Element\Template;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;

class Script extends Template
{
    public function __construct(
        Template\Context $context,
        CollectionFactory $buntingCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Session $checkoutSession,
        array $data = [])
    {
        $bunting = $buntingCollectionFactory->create()->getFirstItem();
        $this->customerSession = $customerSession;
        $this->assign('order', $checkoutSession->getLastRealOrderId());
        $this->assign('bunting',$bunting);
        parent::__construct($context, $data);
    }

    public function isLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getCustomer() {
        return $this->customerSession->getCustomer();
    }

    public function getGender() {
        return $this->getCustomer()->getResource()
        ->getAttribute('gender')
        ->getSource()
        ->getOptionText($this->getCustomer()->getData('gender'));
    }

}