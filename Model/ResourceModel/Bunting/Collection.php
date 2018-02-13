<?php namespace Bunting\Core\Model\ResourceModel\Bunting;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'bunting_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Bunting\Core\Model\Bunting', 'Bunting\Core\Model\ResourceModel\Bunting');
    }

}