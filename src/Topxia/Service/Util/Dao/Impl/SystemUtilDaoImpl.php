<?php

namespace Topxia\Service\Util\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Util\Dao\SystemUtilDao;

class SystemUtilDaoImpl extends BaseDao implements SystemUtilDao
{
   public function getProductIdsWhereProductHasDeleted()
   {
        $sql = "SELECT DISTINCT  targetId FROM upload_files WHERE "; 
        $sql .= " targetType='productlesson' and targetId NOT IN (SELECT id FROM product)";
        return $this->getConnection()->fetchAll($sql);    
   }


}