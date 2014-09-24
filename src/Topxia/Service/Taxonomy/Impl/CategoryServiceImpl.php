<?php
namespace Topxia\Service\Taxonomy\Impl;

use Topxia\Service\Taxonomy\CategoryService;
use Topxia\Service\Common\BaseService;
use Topxia\Common\ArrayToolkit;

class CategoryServiceImpl extends BaseService implements CategoryService
{
    public function getCategory($id)
    {
        if (empty($id)) {
            return null;
        }
        return $this->getCategoryDao()->getCategory($id);
    }

    public function getCategoryByCode($sn)
    {
        return $this->getCategoryDao()->findCategoryByCode($sn);
    }

    public function getCategoryTree($groupId)
    {
        $group = $this->getGroup($groupId);
        if (empty($group)) {
            throw $this->createServiceException("分类Group #{$groupId}，不存在");
        }
        $prepare = function($categories) {
            $prepared = array();
            foreach ($categories as $category) {
                if (!isset($prepared[$category['pid']])) {
                    $prepared[$category['pid']] = array();
                }
                $prepared[$category['pid']][] = $category;
            }
            return $prepared;
        };

        $categories = $prepare($this->findCategories($groupId));

        $tree = array();
        $this->makeCategoryTree($tree, $categories, 1);

        return $tree;
    }

    public function findCategories($groupId)
    {
        $group = $this->getGroup($groupId);
        if (empty($group)) {
            throw $this->createServiceException("分类Group #{$groupId}，不存在");
        }
        return $this->getCategoryDao()->findCategoriesByGroupId($group['id']);
    }

    public function findGroupRootCategories($groupCode)
    {
        $group = $this->getGroupByCode($groupCode);
        if (empty($group)) {
            throw $this->createServiceException("分类Group #{$groupCode}，不存在");
        }        
        return $this->getCategoryDao()->findCategoriesByGroupIdAndParentId($group['id'], 1);
    }

    public function findCategoryChildrenIds($id)
    {
        $category = $this->getCategory($id);
        if (empty($category)) {
            return array();
        }
        $tree = $this->getCategoryTree($category['groupId']);

        $childrenIds = array();
        $depth = 0;
        foreach ($tree as $node) {
            if ($node['id'] == $category['id']) {
                $depth = $node['depth'];
                continue;
            }
            if ($depth > 0 && $depth < $node['depth']) {
                $childrenIds[] = $node['id'];
            }

            if ($depth > 0 && $depth >= $node['depth']) {
                break;
            }

        }

        return $childrenIds;
    }

    public function findCategoriesByIds(array $ids)
    {
        return ArrayToolkit::index( $this->getCategoryDao()->findCategoriesByIds($ids), 'id');
    }

    public function findAllCategories()
    {
        return $this->getCategoryDao()->findAllCategories();
    }

    public function isCategoryCodeAvaliable($sn, $exclude = null)
    {
        if (empty($sn)) {
            return false;
        }

        if ($sn == $exclude) {
            return true;
        }

        $category = $this->getCategoryDao()->findCategoryByCode($sn);

        return $category ? false : true;
    }

    public function createCategory(array $category)
    {
        $category = ArrayToolkit::parts($category, array('name', 'sn', 'orderNo', 'groupId', 'pid'));

        if (!ArrayToolkit::requireds($category, array('name', 'sn', 'orderNo', 'groupId', 'pid'))) {
            throw $this->createServiceException("缺少必要参数，，添加分类失败");
        }

        $this->filterCategoryFields($category);

        $category = $this->getCategoryDao()->addCategory($category);

        $this->getLogService()->info('category', 'create', "添加分类 {$category['name']}(#{$category['id']})", $category);

        return $category;
    }

    public function updateCategory($id, array $fields)
    {
        $category = $this->getCategory($id);
        if (empty($category)) {
            throw $this->createNoteFoundException("分类(#{$id})不存在，更新分类失败！");
        }

        $fields = ArrayToolkit::parts($fields, array('name', 'sn', 'orderNo', 'pid'));
        if (empty($fields)) {
            throw $this->createServiceException('参数不正确，更新分类失败！');
        }

        // filterCategoryFields里有个判断，需要用到这个$fields['groupId']
        $fields['groupId'] = $category['groupId'];

        $this->filterCategoryFields($fields, $category);

        $this->getLogService()->info('category', 'update', "编辑分类 {$fields['name']}(#{$id})", $fields);

        return $this->getCategoryDao()->updateCategory($id, $fields);
    }

    public function deleteCategory($id)
    {
        $category = $this->getCategory($id);
        if (empty($category)) {
            throw $this->createNotFoundException();
        }

        $ids = $this->findCategoryChildrenIds($id);
        $ids[] = $id;
        foreach ($ids as $id) {
            $this->getCategoryDao()->deleteCategory($id);
        }

        $this->getLogService()->info('category', 'delete', "删除分类{$category['name']}(#{$id})");
    }

    /**
     * group
     */
    public function getGroup($id)
    {   
        return $this->getGroupDao()->getGroup($id);
    }

    public function getGroupByCode($code)
    {
        return $this->getGroupDao()->findGroupByCode($code);
    }

    public function getGroups($start, $limit)
    {
        return $this->getGroupDao()->findGroups($start, $limit);
    }

    public function findAllGroups()
    {
        return $this->getGroupDao()->findAllGroups();
    }

    public function addGroup(array $group)
    {
        return $this->getGroupDao()->addGroup($group);
    }

    public function deleteGroup($id)
    {
        return $this->getGroupDao()->deleteGroup($id);
    }

    private function makeCategoryTree(&$tree, &$categories, $pid)
    {
        static $depth = 0;
        static $leaf = false;
        if (isset($categories[$pid]) && is_array($categories[$pid])) {
            foreach ($categories[$pid] as $category) {
                $depth++;
                $category['depth'] = $depth;
                $tree[] = $category;
                $this->makeCategoryTree($tree, $categories, $category['id']);
                $depth--;
            }
        }
        return $tree;
    }

    private function filterCategoryFields(&$category, $releatedCategory = null)
    {
        foreach (array_keys($category) as $key) {
            switch ($key) {
                case 'name':
                    $category['name'] = (string) $category['name'];
                    if (empty($category['name'])) {
                        throw $this->createServiceException("名称不能为空，保存分类失败");
                    }
                    break;
                case 'sn':
                    if (empty($category['sn'])) {
                        throw $this->createServiceException("编码不能为空，保存分类失败");
                    } else {
                        if (!preg_match("/^[a-zA-Z0-9_]+$/i", $category['sn'])) {
                            throw $this->createServiceException("编码({$category['sn']})含有非法字符，保存分类失败");
                        }
                        if (ctype_digit($category['sn'])) {
                            throw $this->createServiceException("编码({$category['sn']})不能全为数字，保存分类失败");
                        }
                        $exclude = empty($releatedCategory['sn']) ? null : $releatedCategory['sn'];
                        if (!$this->isCategoryCodeAvaliable($category['sn'], $exclude)) {
                            throw $this->createServiceException("编码({$category['sn']})不可用，保存分类失败");
                        }
                    }
                    break;
                case 'groupId':
                    $category['groupId'] = (int) $category['groupId'];
                    $group = $this->getGroup($category['groupId']);
                    if (empty($group)) {
                        throw $this->createServiceException("分类分组ID({$category['groupId']})不存在，保存分类失败");
                    }
                    break;
                case 'pid':
                    $category['pid'] = (int) $category['pid'];
                    if ($category['pid'] > 0) {
                        $parentCategory = $this->getCategory($category['pid']);
                        if (empty($parentCategory) or $parentCategory['groupId'] != $category['groupId']) {
                            throw $this->createServiceException("父分类(ID:{$category['groupId']})不存在，保存分类失败");
                        }
                    }
                    break;
            }
        }

        return $category;
    }

    private function getCategoryDao ()
    {
        return $this->createDao('Taxonomy.CategoryDao');
    }

    private function getGroupDao()
    {
        return $this->createDao('Taxonomy.CategoryGroupDao');
    }

    private function getLogService()
    {
        return $this->createService('System.LogService');
    }

}