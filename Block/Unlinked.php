<?php
namespace Bunting\Core\Block;

use Magento\Framework\View\Element\Template;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;
use Magento\Backend\Model\Session;

class Unlinked extends Template
{
    public function __construct(
        Template\Context $context,
        array $data = [])
    {
        parent::__construct($context, $data);
        $this->pageConfig->getTitle()->set(__('Bunting Personalization'));
    }
}