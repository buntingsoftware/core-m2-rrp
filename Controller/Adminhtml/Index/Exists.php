<?php
namespace Bunting\Core\Controller\Adminhtml\Index;
class Exists extends \Bunting\Core\Controller\AbstractAction
{
    /**
     * Checks if the subdomain passed as a parameter exists within bunting
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute(){
        $subdomain = $this->getRequest()->getParam('subdomain');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $subdomain . '.1.bunting.com/login?a=lost_password');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_exec($ch);

        $this->getResponse()->setBody((int) (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200));
    }
}