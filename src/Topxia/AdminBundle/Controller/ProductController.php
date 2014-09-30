<?php
namespace Topxia\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class ProductController extends BaseController
{

    public function indexAction (Request $request)
    {
        $conditions = $request->query->all();

        $count = $this->getProductService()->searchProductCount($conditions);

        $paginator = new Paginator($this->get('request'), $count, 20);

        $products = $this->getProductService()->searchProducts($conditions, null, $paginator->getOffsetCount(),  $paginator->getPerPageCount());

        $categories = $this->getCategoryService()->findCategoriesByIds(ArrayToolkit::column($products, 'categoryId'));
  
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($products, 'userId'));

        return $this->render('TopxiaAdminBundle:Product:index.html.twig', array(
            'conditions' => $conditions,
            'products' => $products ,
            'users' => $users,
            'categories' => $categories,
            'paginator' => $paginator
        ));
    }

    public function deleteAction(Request $request, $id)
    {
        $result = $this->getProductService()->deleteProduct($id);
        return $this->createJsonResponse(true);
    }

    public function publishAction(Request $request, $id)
    {
        $this->getProductService()->publishProduct($id);
        return $this->renderProductTr($id);
    }

    public function closeAction(Request $request, $id)
    {
        $this->getProductService()->closeProduct($id);
        return $this->renderProductTr($id);
    }

    public function recommendAction(Request $request, $id)
    {
        $product = $this->getProductService()->getProduct($id);

        $ref = $request->query->get('ref');

        if ($request->getMethod() == 'POST') {
            $number = $request->request->get('number');

            $product = $this->getProductService()->recommendProduct($id, $number);

            $user = $this->getUserService()->getUser($product['userId']);

            if ($ref == 'recommendList') {
                return $this->render('TopxiaAdminBundle:Product:product-recommend-tr.html.twig', array(
                    'product' => $product,
                    'user' => $user
                ));
            }


            return $this->renderProductTr($id);
        }


        return $this->render('TopxiaAdminBundle:Product:product-recommend-modal.html.twig', array(
            'product' => $product,
            'ref' => $ref
        ));
    }

    public function cancelRecommendAction(Request $request, $id)
    {
        $product = $this->getProductService()->cancelRecommendProduct($id);
        return $this->renderProductTr($id);
    }

    public function recommendListAction(Request $request)
    {
        $conditions = array(
            'status' => 'published',
            'tuijian'=> 1
        );

        $paginator = new Paginator(
            $this->get('request'),
            $this->getProductService()->searchProductCount($conditions),
            20
        );

        $products = $this->getProductService()->searchProducts(
            $conditions,
            'recommendedSeq',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($products, 'userId'));

        return $this->render('TopxiaAdminBundle:Product:product-recommend-list.html.twig', array(
            'products' => $products,
            'users' => $users,
            'paginator' => $paginator
        ));
    }


    public function categoryAction(Request $request)
    {
        return $this->forward('TopxiaAdminBundle:Category:embed', array(
            'group' => 'product',
            'layout' => 'TopxiaAdminBundle:Product:layout.html.twig',
        ));
    }

    public function chooserAction (Request $request)
    {   
        $conditions = $request->query->all();

        $count = $this->getProductService()->searchProductCount($conditions);

        $paginator = new Paginator($this->get('request'), $count, 20);

        $products = $this->getProductService()->searchProducts($conditions, null, $paginator->getOffsetCount(),  $paginator->getPerPageCount());

        $categories = $this->getCategoryService()->findCategoriesByIds(ArrayToolkit::column($products, 'categoryId'));

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($products, 'userId'));

        return $this->render('TopxiaAdminBundle:Product:product-chooser.html.twig', array(
            'conditions' => $conditions,
            'products' => $products ,
            'users' => $users,
            'categories' => $categories,
            'paginator' => $paginator
        ));
    }

    private function renderProductTr($productId)
    {
        $product = $this->getProductService()->getProduct($productId);

        return $this->render('TopxiaAdminBundle:Product:tr.html.twig', array(
            'user' => $this->getUserService()->getUser($product['userId']),
            'category' => $this->getCategoryService()->getCategory($product['categoryId']),
            'product' => $product ,
        ));
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

    private function getNotificationService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }
}