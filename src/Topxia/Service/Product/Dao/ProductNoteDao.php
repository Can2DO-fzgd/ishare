<?php
namespace Topxia\Service\Product\Dao;

interface ProductNoteDao
{
	public function getNote($id);

	public function addNote($noteInfo);

    public function updateNote($id,$noteInfo);
    
    public function deleteNote($id);

    public function getNoteByUserIdAndLessonId($userId,$lessonId);

	public function searchNotes($conditions, $orderBy, $start, $limit);

	public function searchNoteCount($conditions);

    public function findNotesByUserIdAndStatus($userId, $status);

    public function findNotesByUserIdAndProductId($userId, $productId);

    public function getNoteCountByUserIdAndProductId($userId, $productId);
}