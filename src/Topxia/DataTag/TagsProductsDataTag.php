<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class TagsProductsDataTag extends ProductBaseDataTag implements DataTag  
{

    /**
     * 获取热词产品列表
     *
     * 可传入的参数：
     *   TagIds 可选 热词ID
     *   count    必需 产品数量，取值不超过10
     * 
     * @param  array $arguments 参数
     * @return array 产品列表
     */
    public function getData(array $arguments)
    {	
  
        $tags = $this->getTagService()->findTagsByNames($arguments['tags']);

        $tagIds = array();

        foreach ($tags as $tagId) {
             array_push($tagIds, $tagId['id']);
        }

        if (empty($arguments['state'])) {
            $state = '1';
        } else {
            $state = $arguments['state'];
        }

        $products = $this->getProductService()->findProductsByTagIdsAndStatus($tagIds, $state, 0, $arguments['count']);

        return $this->getProductTeachersAndCategories($products);
    }

    protected function getTagService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.TagService');
    }

}
