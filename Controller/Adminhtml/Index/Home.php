<?php
namespace Bunting\Core\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Result\PageFactory;

class Home extends \Bunting\Core\Controller\AbstractAction
{

    public function __construct(
        Context $context,
        CollectionFactory $buntingCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        PageFactory $pageFactory
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_pageFactory = $pageFactory;
        parent::__construct($context, $buntingCollectionFactory);
    }
    public function execute(){
        if (!$this->buntingInstalled()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('bunting/index/index');
            return $resultRedirect;
        }
        
        return $this->_pageFactory->create();
    }
}