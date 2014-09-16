<?php
namespace Topxia\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class ProductQuestionController extends BaseController
{

    public function indexAction (Request $request, $postStatus)
    {

		$conditions = $request->query->all();        
        $conditions['type'] = 'question';
        if($postStatus == 'unPosted'){
            $conditions['postNum'] = 0;
        }

        $paginator = new Paginator(
            $request,
            $this->getThreadService()->searchThreadCount($conditions),
            20
        );

        $questions = $this->getThreadService()->searchThreads(
            $conditions,
            'createdNotStick',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($questions, 'userId'));
        $products = $this->getProductService()->findProductsByIds(ArrayToolkit::column($questions, 'productId'));
        $lessons = $this->getProductService()->findLessonsByIds(ArrayToolkit::column($questions, 'lessonId'));

    	return $this->render('TopxiaAdminBundle:ProductQuestion:index.html.twig', array(
    		'paginator' => $paginator,
            'questions' => $questions,
            'users'=> $users,
            'products' => $products,
            'lessons' => $lessons,
            'type' => $postStatus
    	));
    }

    public function deleteAction(Request $request, $id)
    {
        $this->getThreadService()->deleteThread($id);
        return $this->createJsonResponse(true);
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $request->request->get('ids');
        foreach ($ids ? : array() as $id) {
            $this->getThreadService()->deleteThread($id);
        }
        return $this->createJsonResponse(true);
    }

    protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Product.ThreadService');
    }

    protected function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

}