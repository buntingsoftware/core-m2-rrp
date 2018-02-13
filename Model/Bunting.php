<?php

namespace Bunting\Core\Model;

class Bunting extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Bunting\Core\Model\ResourceModel\Bunting');
    }
}