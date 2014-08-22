<?php
namespace Topxia\Service\Testpaper;

interface TestpaperService
{
    public function getTestpaper($id);

    public function getTestpaperResult($id);

    public function findTestpapersByIds($ids);

    public function searchTestpapers($conditions, $sort, $start, $limit);

    public function searchTestpapersCount($conditions);

    public function createTestpaper($fields);

    public function updateTestpaper($id, $fields);

    public function publishTestpaper($id);

    public function closeTestpaper($id);

    public function deleteTestpaper($id);

    public function deleteTestpaperByIds(array $ids);

    public function buildTestpaper($id, $options);

    public function canBuildTestpaper($builder, $options);

    public function findTestpaperResultsByUserId ($id, $start, $limit);

    public function findTestpaperResultsCountByUserId ($id);

    public function findTestpaperResultByTestpaperIdAndUserIdAndActive($testpaperId, $userId);

    public function findTestpaperResultsByTestIdAndStatusAndUserId($testpaperId, $userId, array $status);

    public function findTestpaperResultsByStatusAndTestIds ($ids, $status, $start, $limit);

    public function findTestpaperResultCountByStatusAndTestIds ($ids, $status);

    public function findTestpaperResultsByStatusAndTeacherIds ($ids, $status, $start, $limit);

    public function findTestpaperResultCountByStatusAndTeacherIds ($ids, $status);

    public function findAllTestpapersByTarget ($id);

    /**
     * 开始做试问卷
     * 
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function startTestpaper($id, $target);

    public function finishTestpaper($resultId);

    public function previewTestpaper($testpaperId);

    public function showTestpaper($testpaperResultId, $isAccuracy = null);

    /**
     * [submitTestpaperAnswer description]
     * @param  [type] $testpaperId [description]
     * @param  [type] $answers     [description]
     * @return [type]              [description]
     */
    public function submitTestpaperAnswer($resultId, $answers);

    public function reviewTestpaper($resultId, $items, $remark = null);

    public function makeTestpaperResultFinish ($id);

    public function finishTest($id, $userId, $usedTime);

    public function makeTeacherFinishTest ($id, $paperId, $teacherId, $field);

    public function updateTestpaperResult($id, $usedTime);

    public function findTeacherTestpapersByTeacherId ($teacherId);

    /**
     * 获取问卷的所有题目
     * 
     * @param  integer $id 问卷ID
     * @return array     问卷所有题目，包含item对应的question的信息
     */
    public function getTestpaperItems($testpaperId);

    public function updateTestpaperItems($testpaperId, $items);

}