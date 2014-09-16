<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;
use Topxia\Service\Question\QuestionService;

class ProductQuestionCategoryManageController extends BaseController
{

    public function indexAction(Request $request, $productId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);
        $categories = $this->getQuestionService()->findCategoriesByTarget("product-{$product['id']}", 0, QuestionService::MAX_CATEGORY_QUERY_COUNT);

        return $this->render('TopxiaWebBundle:ProductQuestionCategoryManage:index.html.twig', array(
            'product' => $product,
            'categories' => $categories,
        ));
    }

    public function createAction(Request $request, $productId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);
        if ($request->getMethod() == 'POST') {

            $data =$request->request->all();
            $data['target'] = "product-{$product['id']}";
            $category = $this->getQuestionService()->createCategory($data);

            return $this->render('TopxiaWebBundle:ProductQuestionCategoryManage:tr.html.twig', array(
                'category' => $category,
                'product' => $product
            ));
        }
        return $this->render('TopxiaWebBundle:ProductQuestionCategoryManage:modal.html.twig', array(
            'product' => $product,
        ));
    }

    public function updateAction(Request $request, $productId, $id)
    {
        $product = $this->getProductService()->tryManageProduct($productId);
        $category = $this->getQuestionService()->getCategory($id);

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $category = $this->getQuestionService()->updateCategory($id, $data);

            return $this->render('TopxiaWebBundle:ProductQuestionCategoryManage:tr.html.twig', array(
                'category' => $category,
                'product' => $product,
            ));
        }

        return $this->render('TopxiaWebBundle:ProductQuestionCategoryManage:modal.html.twig', array(
            'category' => $category,
            'product' => $product,
        ));
    }

    public function deleteAction(Request $request, $productId, $id)
    {
        $product = $this->getProductService()->tryManageProduct($productId);
        $category = $this->getQuestionService()->getCategory($id);
        $this->getQuestionService()->deleteCategory($id);
        return $this->createJsonResponse(true);
    }

    public function sortAction(Request $request, $productId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);
        
        $this->getQuestionService()->sortCategories("product-".$product['id'], $request->request->get('ids'));
        return $this->createJsonResponse(true);
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getQuestionService()
    {
        return $this->getServiceKernel()->createService('Question.QuestionService');
    }
}