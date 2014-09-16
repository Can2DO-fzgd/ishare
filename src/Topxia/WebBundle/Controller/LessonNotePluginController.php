<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class LessonNotePluginController extends BaseController
{

    public function initAction (Request $request)
    {
        $currentUser = $this->getCurrentUser();
        
        $product = $this->getProductService()->getProduct($request->query->get('productId'));
        $lesson = array('id' => $request->query->get('lessonId'),'productId' => $product['id']);
        $note = $this->getProductNoteService()->getUserLessonNote($currentUser['id'], $lesson['id']);
        $formInfo = array(
            'productId' => $product['id'], 
            'lessonId' => $lesson['id'],
            'content'=>$note['content'],
            'id'=>$note['id'],
        );
        $form = $this->createNoteForm($formInfo);
        return $this->render('TopxiaWebBundle:LessonNotePlugin:index.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function saveAction(Request $request)
    {
        $form = $this->createNoteForm();
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $note = $form->getData();
                $this->getProductNoteService()->saveNote($note);
                return $this->createJsonResponse(true);
            } else {
                return $this->createJsonResponse(false);
            }
        }
        return $this->createJsonResponse(false);
    }

    private function createNoteForm($data = array())
    {
        return $this->createNamedFormBuilder('note', $data)
            ->add('id', 'hidden', array('required' => false))
            ->add('content', 'textarea',array('required' => false))
            ->add('productId', 'hidden', array('required' => false))
            ->add('lessonId', 'hidden', array('required' => false))
            ->getForm();
    }

    protected function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    protected function getProductNoteService()
    {
        return $this->getServiceKernel()->createService('Product.NoteService');
    }
}