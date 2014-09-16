<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class MyNotebookController extends BaseController
{

    public function indexAction(Request $request)
    {
        $user = $this->getCurrentUser();

        $conditions = array(
            'userId' => $user['id'],
            'noteNumGreaterThan' => 0
        );

        $paginator = new Paginator(
            $request,
            $this->getProductService()->searchMemberCount($conditions),
            10
        );

        $productMembers = $this->getProductService()->searchMember($conditions, $paginator->getOffsetCount(), $paginator->getPerPageCount());

        $products = $this->getProductService()->findProductsByIds(ArrayToolkit::column($productMembers, 'productId'));
        
        return $this->render('TopxiaWebBundle:MyNotebook:index.html.twig', array(
            'productMembers'=>$productMembers,
            'paginator' => $paginator,
            'products'=>$products
        ));
    }

    public function showAction(Request $request, $productId)
    {   
        $user = $this->getCurrentUser();

        $product = $this->getProductService()->getProduct($productId);
        $lessons = ArrayToolkit::index($this->getProductService()->getProductLessons($productId), 'id');
        $notes = $this->getNoteService()->findUserProductNotes($user['id'], $product['id']);

        foreach ($notes as &$note) {
            $note['lessonNumber'] = empty($lessons[$note['lessonId']]) ? 0 : $lessons[$note['lessonId']]['number'];
            unset($note);
        }

        usort($notes, function($note1, $note2) {
            if ($note1['lessonNumber'] == 0) {
                return true;
            }

            if ($note2['lessonNumber'] == 0) {
                return false;
            }

            return $note1['lessonNumber'] > $note2['lessonNumber'];
        });

        return $this->render('TopxiaWebBundle:MyNotebook:show.html.twig', array(
            'product' => $product,
            'lessons' => $lessons,
            'notes' => $notes,
        ));
    }

    public function noteDeleteAction(Request $request, $id)
    {
        $this->getNoteService()->deleteNote($id);
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