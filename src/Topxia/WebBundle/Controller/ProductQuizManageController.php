<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;

class ProductQuizManageController extends BaseController
{

	public function indexAction(Request $request, $productId, $lessonId)
	{
		$product = $this->getProductService()->tryManageProduct($productId);
		$lesson = $this->getProductService()->getProductLesson($product['id'], $lessonId);
		if (empty($lesson)) {
			throw $this->createNotFoundException("产品介绍(#{$lessonId})不存在！");
		}

		$items = $this->getQuizService()->findLessonQuizItems($product['id'], $lesson['id']);

        return $this->render('TopxiaWebBundle:ProductQuizManage:quiz-modal.html.twig', array(
			'product' => $product,
			'lesson' => $lesson,
			'quizItems' => $items,
		));
	}

    public function saveItemAction(Request $request, $productId, $lessonId)
    {
        $item = $request->request->all();
        $item['answers'] = explode('|', $item['answers']);
        
        if (empty($item['id'])) {
            $item['productId'] = $productId;
            $item['lessonId'] = $lessonId;
            $item = $this->getQuizService()->createItem($item);
        } else {
            $item = $this->getQuizService()->updateItem($item['id'], $item);
        }

        return $this->createJsonResponse($item);
    }

    public function deleteItemAction(Request $request, $productId, $itemId)
    {
        $this->getQuizService()->deleteItem($itemId);
        return $this->createJsonResponse(true);
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getQuizService()
    {
        return $this->getServiceKernel()->createService('Product.QuizService');
    }

}