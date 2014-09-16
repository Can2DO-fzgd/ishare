<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class LatestProductThreadsByTypeDataTag extends ProductBaseDataTag implements DataTag  
{
    
    /**
     * 获取最新发表的产品话题列表
     *
     * 可传入的参数：
     *   type 选填 话题类型
     *   count 必需 产品话题数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品话题
     */

    public function getData(array $arguments)
    {
        $this->checkCount($arguments);

        if (empty($arguments['type'])){
            $type = array();
        } else {
            $type = $arguments['type'];
        }
    	$threads = $this->getThreadService()->findLatestThreadsByType($type, 0, $arguments['count']);

        return $threads;
    }


}
