<?php

namespace Topxia\Service\Product\Dao;

interface LessonViewedDao
{

    public function deleteViewedsByProductId($productId);
}