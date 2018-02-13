<?php
namespace Bunting\Core\Controller\Adminhtml\Index;
class Login extends \Bunting\Core\Controller\AbstractSubmitAction
{
    public function execute(){
        $bunting_data = $this->submitToBunting('verify',[
            'email_address' => $this->getRequest()->getParam('verify_email_address'),
            'password' => $this->getRequest()->getParam('verify_password'),
            'subdomain' => $this->getRequest()->getParam('verify_bunting_subdomain')
        ]);
        $this->buntingResponse($bunting_data);
    }
}