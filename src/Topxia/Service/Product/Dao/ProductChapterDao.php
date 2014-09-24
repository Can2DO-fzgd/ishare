<?php

namespace Topxia\Service\Product\Dao;

interface ProductChapterDao
{

    public function getChapter($id);

    public function findChaptersByProductId($productId);

    public function getChapterCountByProductIdAndType($productId, $type);

    public function getChapterCountByProductIdAndTypeAndParentId($productId, $type, $pid);

    public function getLastChapterByProductIdAndType($productId, $type);

    public function getLastChapterByProductId($productId);

    public function getChapterMaxSeqByProductId($productId);

    public function addChapter(array $chapter);

    public function updateChapter($id, array $chapter);

    public function deleteChapter($id);

    public function deleteChaptersByProductId($productId);

}