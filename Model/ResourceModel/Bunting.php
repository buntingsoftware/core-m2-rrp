<?php
namespace Bunting\Core\Model\ResourceModel;

class Bunting extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('bunting_core_bunting', 'bunting_id');
    }
}