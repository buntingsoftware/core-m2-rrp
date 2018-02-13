<?php
namespace Bunting\Core\Block;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;

class Category extends \Magento\Framework\View\Element\Template
{

    protected $_registry;
    
    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $buntingCollectionFactory,
        array $data = [])
    {
        $bunting = $buntingCollectionFactory->create()->getFirstItem();
        $this->assign('bunting',$bunting);
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    public function getCurrentCategory()
    {
        $category = $this->_registry->registry('current_category');
        return $this->getCategoryBreadcrumb($category);
    }

    private function getCategoryBreadcrumb($category, $category_string = '') {
        if ($category) {
            if ($category_string != '') {
                $category_string = '>'.$category_string;
            }
            $category_string = $category->getName().$category_string;
            try {
                $parent_category = $category->getParentCategory();
                if (!is_null($parent_category->getName())) {
                    $category_string = $this->getCategoryBreadcrumb($parent_category, $category_string);
                }
            }
            catch(\Magento\Framework\Exception\NoSuchEntityException $e) {}
        }
        else {
            $category_string = false;
        }
        return $category_string;
    }
}