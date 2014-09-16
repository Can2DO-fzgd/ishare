<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;

class ProductChapterManageController extends BaseController
{

	public function createAction(Request $request, $id)
	{
		$product = $this->getProductService()->tryManageProduct($id);
        $type = $request->query->get('type');
        $type = in_array($type, array('chapter', 'unit')) ? $type : 'chapter';

	    if($request->getMethod() == 'POST'){
        	$chapter = $request->request->all();
        	$chapter['productId'] = $product['id'];
        	$chapter = $this->getProductService()->createChapter($chapter);
			return $this->render('TopxiaWebBundle:ProductChapterManage:list-item.html.twig', array(
				'product' => $product,
				'chapter' => $chapter,
			));
        }

		return $this->render('TopxiaWebBundle:ProductChapterManage:chapter-modal.html.twig', array(
			'product' => $product,
            'type' => $type,
		));
	}

	public function editAction(Request $request, $productId, $chapterId)
	{
		$product = $this->getProductService()->tryManageProduct($productId);
		$chapter = $this->getProductService()->getChapter($productId, $chapterId);
		if (empty($chapter)) {
			throw $this->createNotFoundException("产品介绍内容(#{$chapterId})不存在！");
		}

	    if($request->getMethod() == 'POST'){
        	$fields = $request->request->all();
        	$fields['productId'] = $product['id'];
        	$chapter = $this->getProductService()->updateChapter($productId, $chapterId, $fields);
			return $this->render('TopxiaWebBundle:ProductChapterManage:list-item.html.twig', array(
				'product' => $product,
				'chapter' => $chapter,
			));
        }

		return $this->render('TopxiaWebBundle:ProductChapterManage:chapter-modal.html.twig', array(
			'product' => $product,
			'chapter' => $chapter,
            'type' => $chapter['type'],
		));
		
	}

	public function deleteAction(Request $request, $productId, $chapterId)
	{
		$product = $this->getProductService()->tryManageProduct($productId);
		$this->getProductService()->deleteChapter($product['id'], $chapterId);

		return $this->createJsonResponse(true);
	}

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }
}