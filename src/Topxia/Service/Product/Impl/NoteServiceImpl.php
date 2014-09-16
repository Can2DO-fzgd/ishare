<?php
namespace Topxia\Service\Product\Impl;

use Topxia\Service\Common\BaseService;
use Topxia\Service\Product\NoteService;
use Topxia\Common\ArrayToolkit;

class NoteServiceImpl extends BaseService implements NoteService
{
	public function getNote($id)
	{
		return $this->getNoteDao()->getNote($id);
    }

    public function getUserLessonNote($userId, $lessonId)
    {
        return $this->getNoteDao()->getNoteByUserIdAndLessonId($userId, $lessonId);
    }

    public function findUserProductNotes($userId, $productId)
    {   
        return $this->getNoteDao()->findNotesByUserIdAndProductId($userId, $productId);
    }

    public function searchNotes($conditions, $sort, $start, $limit)
    {
        switch ($sort) {
            case 'created':
                $orderBy = array('createdTime', 'DESC');
                break;
            case 'updated':
                $orderBy =  array('updatedTime', 'DESC');
                break;
            default:
                throw $this->createServiceException('参数sort不正确。');
        }

        $conditions = $this->prepareSearchNoteConditions($conditions);
        return $this->getNoteDao()->searchNotes($conditions, $orderBy, $start, $limit);
    }

    public function searchNoteCount($conditions)
    {
        $conditions = $this->prepareSearchNoteConditions($conditions);
        return $this->getNoteDao()->searchNoteCount($conditions);
    }

    private function prepareSearchNoteConditions($conditions)
    {
        $conditions = array_filter($conditions);

        if (isset($conditions['keywordType']) && isset($conditions['keyword'])) {
            if (!in_array($conditions['keywordType'], array('content', 'productId'))) {
                throw $this->createServiceException('keywordType参数不正确');
            }
            $conditions[$conditions['keywordType']] = $conditions['keyword'];
        }
        unset($conditions['keywordType']);
        unset($conditions['keyword']);

        if (isset($conditions['author'])) {
            $author = $this->getUserService()->getUserByUserName($conditions['author']);
            $conditions['userId'] = $author ? $author['id'] : -1;
            unset($conditions['author']);
        }

        return $conditions;
    }

    /**
     *类似这样的，提交数据保存到数据的流程是：
     *
     *  1. 检查参数是否正确，不正确就抛出异常
     *  2. 过滤数据
     *  3. 插入到数据库
     *  4. 更新其他相关的缓存字段
     */
	public function saveNote(array $note)
	{
        if (!ArrayToolkit::requireds($note, array('lessonId', 'productId', 'content'))) {
            throw $this->createServiceException('缺少必要的字段，保存备忘失败');
        }

        list($product, $member) = $this->getProductService()->tryTakeProduct($note['productId']);
        $user = $this->getCurrentUser();

        if(!$this->getProductService()->getProductLesson($note['productId'], $note['lessonId'])) {
            throw $this->createServiceException('不存在，保存备忘失败');
        }

        $note = ArrayToolkit::filter($note, array(
            'productId' => 0,
            'lessonId' => 0,
            'content' => '',
        ));

        $note['content'] = $this->purifyHtml($note['content']) ? : '';
        $note['length'] = $this->calculateContnentLength($note['content']);

        $existNote = $this->getUserLessonNote($user['id'], $note['lessonId']);
        if (!$existNote) {
            $note['userId'] = $user['id'];
            $note['createdTime'] = time();
            $note = $this->getNoteDao()->addNote($note);
        } else {
            $note['updatedTime'] = time();
            $note = $this->getNoteDao()->updateNote($existNote['id'], $note);
        }

        $this->getProductService()->setMemberNoteNumber(
            $note['productId'],
            $note['userId'], 
            $this->getNoteDao()->getNoteCountByUserIdAndProductId($note['userId'], $note['productId'])
        );

        return $note;
	}

	public function deleteNote($id)
	{
        $note = $this->getNote($id);
        if (empty($note)) {
            throw $this->createServiceException("备忘(#{$id})不存在，删除失败");
        }

        $currentUser = $this->getCurrentUser();
        if (($note['userId'] != $currentUser['id']) && !$this->getProductService()->canManageProduct($note['productId'])) {
            throw $this->createServiceException("你没有权限删除备忘(#{$id})");
        }

        $this->getNoteDao()->deleteNote($id);

        $this->getProductService()->setMemberNoteNumber(
            $note['productId'],
            $note['userId'], 
            $this->getNoteDao()->getNoteCountByUserIdAndProductId($note['userId'], $note['productId'])
        );

        if ($note['userId'] != $currentUser['id']) {
            $this->getLogService()->info('note', 'delete', "删除备忘#{$id}");
        }
	}

    public function deleteNotes(array $ids)
    {
        foreach ($ids as $id) {
            $this->deleteNote($id);
        }
    }

    // @todo HTML Purifier
    private function calculateContnentLength($content)
    {
        $content = strip_tags(trim(str_replace(array("\\t", "\\r\\n", "\\r", "\\n"), '',$content)));
        return mb_strlen($content, 'utf-8');
    }

    private function getNoteDao()
    {
    	return $this->createDao('Product.ProductNoteDao');
    }

   private function getProductService()
    {
        return $this->createService('Product.ProductService');
    }

    private function getUserService()
    {
        return $this->createService('User.UserService');
    }

    private function getLogService()
    {
        return $this->createService('System.LogService');
    }
}
