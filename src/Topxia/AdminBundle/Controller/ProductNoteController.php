<?php
namespace Topxia\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class ProductNoteController extends BaseController
{
	public function indexAction(Request $request)
	{
		$conditions = $request->query->all();

        $paginator = new Paginator(
            $request,
            $this->getNoteService()->searchNoteCount($conditions),
            20
        );
        $notes = $this->getNoteService()->searchNotes(
            $conditions,
            'created',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($notes, 'userId'));
        $products = $this->getProductService()->findProductsByIds(ArrayToolkit::column($notes, 'productId'));
        $lessons = $this->getProductService()->findLessonsByIds(ArrayToolkit::column($notes, 'lessonId'));
		return $this->render('TopxiaAdminBundle:ProductNote:index.html.twig',array(
            'notes' => $notes,
            'paginator' => $paginator,
            'users'=>$users,
            'lessons'=>$lessons,
            'products'=>$products
		));
	}

    public function deleteAction(Request $request, $id)
    {
        $note = $this->getNoteService()->deleteNote($id);

        return $this->createJsonResponse(true);
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $request->request->get('ids', array());
        $this->getNoteService()->deleteNotes($ids);

        return $this->createJsonResponse(true);
    }

    protected function getNoteService()
    {
        return $this->getServiceKernel()->createService('Product.NoteService');
    }

    protected function getProductService()
    {
    	return $this->getServiceKernel()->createService('Product.ProductService');
    }
}