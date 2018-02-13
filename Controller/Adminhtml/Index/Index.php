<?php
namespace Bunting\Core\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Bunting\Core\Controller\AbstractAction
{

    public function __construct(
        Context $context,
        CollectionFactory $buntingCollectionFactory,
        PageFactory $pageFactory
    )
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context, $buntingCollectionFactory);
    }

    public function execute(){
        if ($this->buntingInstalled()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('bunting/index/home');
            return $resultRedirect;
        }
        
        return $this->_pageFactory->create();
    }
}