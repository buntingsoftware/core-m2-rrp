<?php
namespace Bunting\Core\Controller\Adminhtml\Index;
class Unlink extends \Bunting\Core\Controller\AbstractAction
{
    public function execute(){
        $this->getBunting()->delete();
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('bunting/index/unlinked');
        return $resultRedirect;
    }
}