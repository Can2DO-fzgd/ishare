<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;
use Topxia\WebBundle\Form\ReviewType;

class ProductReviewController extends BaseController
{

    public function listAction(Request $request, $id)
    {
        $product = $this->getProductService()->getProduct($id);

        $previewAs = $request->query->get('previewAs');
        $isModal = $request->query->get('isModal');

        $paginator = new Paginator(
            $this->get('request'),
            $this->getReviewService()->getProductReviewCount($id)
            , 10
        );

        $reviews = $this->getReviewService()->findProductReviews(
            $id,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($reviews, 'userId'));

        return $this->render('TopxiaWebBundle:ProductReview:list.html.twig', array(
            'product' => $product,
            'reviews' => $reviews,
            'users' => $users,
            'isModal' => $isModal,
            'paginator' => $paginator
        ));
    }

    public function createAction(Request $request, $id)
    {
        $currentUser = $this->getCurrentUser();
        $product = $this->getProductService()->getProduct($id);
        $review = $this->getReviewService()->getUserProductReview($currentUser['id'], $product['id']);
        $form = $this->createForm(new ReviewType(), $review ? : array());

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $fields = $form->getData();
                $fields['rating'] = $fields['rating'];
                $fields['userId']= $currentUser['id'];
                $fields['productId']= $id;
                $this->getReviewService()->saveReview($fields);
                return $this->createJsonResponse(true);
            }
        }

        return $this->render('TopxiaWebBundle:ProductReview:write-modal.html.twig', array(
            'form' => $form->createView(),
            'product' => $product,
            'review' => $review,
        ));
    }

	public function latestBlockAction($product)
	{
        $reviews = $this->getReviewService()->findProductReviews($product['id'], 0, 10);
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($reviews, 'userId'));
    	return $this->render('TopxiaWebBundle:ProductReview:latest-block.html.twig', array(
    		'product' => $product,
            'reviews' => $reviews,
            'users' => $users,
		));

	}

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getReviewService()
    {
        return $this->getServiceKernel()->createService('Product.ReviewService');
    }

}