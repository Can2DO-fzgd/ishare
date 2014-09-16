<?php

namespace Topxia\Service\Product\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Product\Dao\LessonViewedDao;

class LessonViewedDaoImpl extends BaseDao implements LessonViewedDao
{
    protected $table = 'product_lesson_viewed';

    public function deleteViewedsByProductId($productId)
    {
    	$sql = "SELECT * FROM {$this->table} WHERE productId = ? ORDER BY createdTime DESC";
        return $this->getConnection()->fetchAll($sql, array($productId));
    }

}
