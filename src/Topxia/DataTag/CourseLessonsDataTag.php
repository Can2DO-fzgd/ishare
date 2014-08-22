<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class CourseLessonsDataTag extends CourseBaseDataTag implements DataTag  
{
    /**
     * 获取一个产品的课时列表
     *
     * 可传入的参数：
     * 
     *   courseId 必需 产品ID
     *   count    必需 产品数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品演示视频列表
     */

    public function getData(array $arguments)
    {
        $this->checkCourseId($arguments);
        $this->checkCount($arguments);
    	$lessons = $this->getCourseService()->getCourseLessons($arguments['courseId']);

        return $this->getCoursesAndUsers($lessons);
    }
}
