<?php
namespace Topxia\Service\Content\Impl;

use Topxia\Service\Common\BaseService;
use Topxia\Service\Content\ContentService;
use Topxia\Service\Content\Type\ContentTypeFactory;
use Topxia\Common\ArrayToolkit;

class ContentServiceImpl extends BaseService implements ContentService
{
	public function getContent($id)
	{
		return ContentSerialize::unserialize($this->getContentDao()->getContent($id));
	}

	public function getContentByAlias($alias)
	{
		return ContentSerialize::unserialize($this->getContentDao()->getContentByAlias($alias));
	}

	public function searchContents($conditions, $sort, $start, $limit)
	{
		switch ($sort) {
			default:
				$orderBy = array('createdTime', 'DESC');
				break;
		}
		$conditions = $this->prepareSearchConditions($conditions);
		return $this->getContentDao()->searchContents($conditions, $orderBy, $start, $limit);
	}

	public function searchContentCount($conditions)
	{
		$conditions = $this->prepareSearchConditions($conditions);
		return $this->getContentDao()->searchContentCount($conditions);
	}

	private function prepareSearchConditions($conditions)
	{
		$conditions = array_filter($conditions);
		if (isset($conditions['categoryId'])) {
			$childrenIds = $this->getCategoryService()->findCategoryChildrenIds($conditions['categoryId']);
			$conditions['categoryIds'] = array_merge(array($conditions['categoryId']), $childrenIds);
			unset($conditions['categoryId']);
		}
		return $conditions;
	}

	public function createContent($content)
	{
		if (empty($content['type'])) {
			throw $this->createServiceException('参数缺失，创建内容失败！');
		}

		$type = ContentTypeFactory::create($content['type']);
		$content = $type->convert($content);
		$content = ArrayToolkit::parts($content, $type->getFields());
		$content['type'] = $type->getAlias();

		if (empty($content['title'])) {
			throw $this->createServiceException('内容标题不能为空，创建内容失败！');
		}

		$content['userId'] = $this->getCurrentUser()->id;
		$content['createdTime'] = time();
		
        if (empty($content['publishedTime'])) {
			$content['publishedTime'] = $content['createdTime'];
		}

		// if(isset($content['body'])){
  //           $content['body'] = $this->purifyHtml($content['body']);
  //       }


		$id = $this->getContentDao()->addContent(ContentSerialize::serialize($content));

		$content = $this->getContent($id);

        $this->getLogService()->info('content', 'create', "创建内容《({$content['title']})》({$content['id']})", $content);

		return $content;
	}

	public function updateContent($id, $fields)
	{
		$content = $this->getContent($id);
		if (empty($content)) {
			throw $this->createServiceException('内容不存在，更新失败！');
		}

		$type = ContentTypeFactory::create($content['type']);
		$fields = $type->convert($fields);
		$fields = ArrayToolkit::parts($fields, $type->getFields());

        // if(isset($fields['body'])){
        //     $fields['body'] = $this->purifyHtml($fields['body']);
        // }

		$this->getContentDao()->updateContent($id, ContentSerialize::serialize($fields));

		$content = $this->getContent($id);

		$this->getLogService()->info('content', 'update', "内容《({$content['title']})》({$content['id']})更新", $content);

		return $content;
	}

	public function trashContent($id)
	{
		$this->getContentDao()->updateContent($id, $fields = array('status' => 'trash'));
		$this->getLogService()->info('content', 'trash', "内容#{$id}移动到回收站");
	}

	public function deleteContent($id)
	{
		$this->getContentDao()->deleteContent($id);
		$this->getLogService()->info('content', 'delete', "内容#{$id}永久删除");
	}

	public function publishContent($id)
	{
		$this->getContentDao()->updateContent($id, $fields = array('status' => 'published'));
		$this->getLogService()->info('content', 'publish', "内容#{$id}发布");
	}

	public function isAliasAvaliable($alias)
	{
		if (empty($alias)) {
			return true;
		}
		$content = $this->getContentDao()->getContentByAlias($alias);
		return $content ? false : true;
	}

	private function getContentDao()
	{
		return $this->createDao('Content.ContentDao');
	}

    private function getCategoryService()
    {
        return $this->createService('Taxonomy.CategoryService');
    }

    private function getLogService()
    {
        return $this->createService('System.LogService');
    }

}



class ContentSerialize
{
    public static function serialize(array &$product)
    {
    	if (isset($product['tagIds'])) {
    		if (is_array($product['tagIds']) and !empty($product['tagIds'])) {
    			$product['tagIds'] = '|' . implode('|', $product['tagIds']) . '|';
    		} else {
    			$product['tagIds'] = '';
    		}
    	}
        return $product;
    }

    public static function unserialize(array $product = null)
    {
    	if (empty($product)) {
    		return $product;
    	}

		$product['tagIds'] = empty($product['tagIds']) ? array() : explode('|', trim($product['tagIds'], '|'));

		return $product;
    }

    public static function unserializes(array $products)
    {
    	return array_map(function($product) {
    		return ContentSerialize::unserialize($product);
    	}, $products);
    }
}