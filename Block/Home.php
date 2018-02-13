<?php
namespace Bunting\Core\Block;

use Magento\Framework\View\Element\Template;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;
use Magento\Backend\Model\Session;

class Home extends Template
{
    public function __construct(
        Template\Context $context,
        CollectionFactory $buntingCollectionFactory,
        Session $backendSession,
        array $data = [])
    {
        parent::__construct($context, $data);
        $this->pageConfig->getTitle()->set(__('Bunting Personalization'));

        $bunting = $buntingCollectionFactory->create()->getFirstItem();

        $timestamp = time();
        $bunting_subdomain = $bunting->getBuntingSubdomain();
        $bunting_email = $bunting->getBuntingEmail();
        $password_api = $bunting->getPasswordApi();
        $account_key = $bunting_subdomain.$bunting_email.$timestamp;

        $message = $backendSession->getBuntingMessage();
        $backendSession->unsBuntingMessage();

        $this->assign('bunting_subdomain',$bunting_subdomain);
        $this->assign('timestamp',$timestamp);
        $this->assign('hash',hash_hmac('sha256', $account_key, '5dKc763_f}E5%s-'));
        $this->assign('password_api',$password_api);
        $this->assign('email_address',$bunting_email);
        $this->assign('message',$message);
    }
}