<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class LessonLessonPluginController extends BaseController
{

    public function listAction (Request $request)
    {
        $user = $this->getCurrentUser();
        list($product, $member) = $this->getProductService()->tryTakeProduct($request->query->get('productId'));

        $items = $this->getProductService()->getProductItems($product['id']);
        $learnStatuses = $this->getProductService()->getUserLearnLessonStatuses($user['id'], $product['id']);

        return $this->render('TopxiaWebBundle:LessonLessonPlugin:list.html.twig', array(
            'product' => $product,
            'items' => $items,
            'learnStatuses' => $learnStatuses,
        ));
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

}