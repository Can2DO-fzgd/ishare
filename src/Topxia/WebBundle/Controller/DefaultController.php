<?php

namespace Topxia\WebBundle\Controller;
use Topxia\Common\ArrayToolkit;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Topxia\System;

class DefaultController extends BaseController
{
	//首页产品
    public function indexAction ()
    {
        $conditions = array('status' => 'published');
        $products = $this->getProductService()->searchProducts($conditions, 'latest', 0, 12);

        $categories = $this->getCategoryService()->findGroupRootCategories('product');

        $blocks = $this->getBlockService()->getContentsByCodes(array('home_top_banner'));
        return $this->render('TopxiaWebBundle:Default:index.html.twig', array(
            'products' => $products,
            'categories' => $categories,
            'blocks' => $blocks
        ));
    }
	
	//推荐产品
    public function index1Action ()
    {
        $conditions = array('status' => 'published');
        $products = $this->getProductService()->searchProducts($conditions, 'latest', 0, 12);

        $categories = $this->getCategoryService()->findGroupRootCategories('product');

        $blocks = $this->getBlockService()->getContentsByCodes(array('home_top_banner'));
        return $this->render('TopxiaWebBundle:Default:index1.html.twig', array(
            'products' => $products,
            'categories' => $categories,
            'blocks' => $blocks
        ));
    }
	
	//最新产品
    public function index2Action ()
    {
        $conditions = array('status' => 'published');
        $products = $this->getProductService()->searchProducts($conditions, 'latest', 0, 12);

        $categories = $this->getCategoryService()->findGroupRootCategories('product');

        $blocks = $this->getBlockService()->getContentsByCodes(array('home_top_banner'));
        return $this->render('TopxiaWebBundle:Default:index2.html.twig', array(
            'products' => $products,
            'categories' => $categories,
            'blocks' => $blocks
        ));
    }

	//产品排行
    public function index3Action ()
    {
        $conditions = array('status' => 'published');
        $products = $this->getProductService()->searchProducts($conditions, 'latest', 0, 12);

        $categories = $this->getCategoryService()->findGroupRootCategories('product');

        $blocks = $this->getBlockService()->getContentsByCodes(array('home_top_banner'));
        return $this->render('TopxiaWebBundle:Default:index3.html.twig', array(
            'products' => $products,
            'categories' => $categories,
            'blocks' => $blocks
        ));
    }
	
    public function promotedTeacherBlockAction()
    {
        $teacher = $this->getUserService()->findLatestPromotedTeacher(0, 1);
        if ($teacher) {
            $teacher = $teacher[0];
            $teacher = array_merge(
                $teacher,
                $this->getUserService()->getUserProfile($teacher['id'])
            );
        }

        return $this->render('TopxiaWebBundle:Default:promoted-teacher-block.html.twig', array(
            'teacher' => $teacher,
        ));
    }

    public function latestReviewsBlockAction($number)
    {
        $reviews = $this->getReviewService()->searchReviews(array(), 'latest', 0, $number);
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($reviews, 'userId'));
        $products = $this->getProductService()->findProductsByIds(ArrayToolkit::column($reviews, 'productId'));
        return $this->render('TopxiaWebBundle:Default:latest-reviews-block.html.twig', array(
            'reviews' => $reviews,
            'users' => $users,
            'products' => $products,
        ));
    }

    public function topNavigationAction($siteNav = null)
    {
    	$navigations = $this->getNavigationService()->findNavigationsByType('top', 0, 100);

    	return $this->render('TopxiaWebBundle:Default:top-navigation.html.twig', array(
    		'navigations' => $navigations,
            'siteNav' => $siteNav
		));
    }


    public function footNavigationAction()
    {
        $navigations = $this->getNavigationService()->findNavigationsByType('foot', 0, 100);

        return $this->render('TopxiaWebBundle:Default:foot-navigation.html.twig', array(
            'navigations' => $navigations,
        ));
    }

    public function customerServiceAction()
    {
        $customerServiceSetting = $this->getSettingService()->get('customerService', array());

        return $this->render('TopxiaWebBundle:Default:customer-service-online.html.twig', array(
            'customerServiceSetting' => $customerServiceSetting,
        ));

    }

    public function systemInfoAction()
    {
        $info = array(
            'version' => System::VERSION,
        );

        return $this->createJsonResponse($info);
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    }

    protected function getNavigationService()
    {
        return $this->getServiceKernel()->createService('Content.NavigationService');
    }

    protected function getBlockService()
    {
        return $this->getServiceKernel()->createService('Content.BlockService');
    }

    protected function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    protected function getReviewService()
    {
        return $this->getServiceKernel()->createService('Product.ReviewService');
    }

    protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

}
