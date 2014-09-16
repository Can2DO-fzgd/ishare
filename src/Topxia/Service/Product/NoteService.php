<?php
namespace Topxia\Service\Product;


interface NoteService
{
    CONST NOTE_TYPE_PRIVATE = 0;
    CONST NOTE_TYPE_PUBLIC = 1;

	public function getNote($id);

    public function getUserLessonNote($userId, $lessonId);

    public function findUserProductNotes($userId, $productId);

    public function searchNotes($conditions, $sort, $start, $limit);

    public function searchNoteCount($conditions);

    public function saveNote(array $note);

	public function deleteNote($id);

	public function deleteNotes(array $ids);
}