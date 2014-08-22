<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;

class TestController extends BaseController
{
    public function indexAction(Request $request)
    {
        var_dump(filter_var('a', FILTER_VALIDATE_INT));exit();

        return $this->render('TopxiaWebBundle:Test:test.html.twig');
    }

    private function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }
    
}