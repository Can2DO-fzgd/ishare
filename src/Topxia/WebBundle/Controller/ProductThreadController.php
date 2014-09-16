<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;

class ProductThreadController extends BaseController
{
    public function indexAction(Request $request, $id)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            return $this->createMessageResponse('info', '亲！你好像忘了登录哦？', null, 3000, $this->generateUrl('login'));
        }

        $product = $this->getProductService()->getProduct($id);
        if (empty($product)) {
            throw $this->createNotFoundException("对不起!产品不存在，或已删除。");
        }

        if (!$this->getProductService()->canTakeProduct($product)) {
            return $this->createMessageResponse('info', "您还不是产品《{$product['name']}》的关注或购买用户，请先关注或购买。", null, 3000, $this->generateUrl('product_show', array('id' => $id)));
        }

        list($product, $member) = $this->getProductService()->tryTakeProduct($id);

        $filters = $this->getThreadSearchFilters($request);
        $conditions = $this->convertFiltersToConditions($product, $filters);

        $paginator = new Paginator(
            $request,
            $this->getThreadService()->searchThreadCount($conditions),
            20
        );

        $threads = $this->getThreadService()->searchThreads(
            $conditions,
            $filters['sort'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $lessons = $this->getProductService()->findLessonsByIds(ArrayToolkit::column($threads, 'lessonId'));

        $userIds = array_merge(
            ArrayToolkit::column($threads, 'userId'),
            ArrayToolkit::column($threads, 'latestPostUserId')
        );
        $users = $this->getUserService()->findUsersByIds($userIds);

        $template = $request->isXmlHttpRequest() ? 'index-main' : 'index';
        return $this->render("TopxiaWebBundle:ProductThread:{$template}.html.twig", array(
            'product' => $product,
            'threads' => $threads,
            'users' => $users,
            'paginator' => $paginator,
            'filters' => $filters,
            'lessons'=>$lessons
        ));
    }

    public function showAction(Request $request, $productId, $id)
    {

        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            return $this->createMessageResponse('info', '亲！你好像忘了登录哦？', null, 3000, $this->generateUrl('login'));
        }

        $product = $this->getProductService()->getProduct($productId);
        if (empty($product)) {
            throw $this->createNotFoundException("对不起！产品不存在，或已删除。");
        }

        if (!$this->getProductService()->canTakeProduct($product)) {
            return $this->createMessageResponse('info', "您还不是产品《{$product['name']}》的关注或购买会员，请先关注或购买。", null, 3000, $this->generateUrl('product_show', array('id' => $productId)));
        }

        list($product, $member) = $this->getProductService()->tryTakeProduct($productId);

        if ($member && !$this->getProductService()->isMemberNonExpired($product, $member)) {
            // return $this->redirect($this->generateUrl('product_threads',array('id' => $productId)));
            $isMemberNonExpired = false;
        } else {
            $isMemberNonExpired = true;
        }
        
        $thread = $this->getThreadService()->getThread($product['id'], $id);
        if (empty($thread)) {
            throw $this->createNotFoundException();
        }

        $paginator = new Paginator(
            $request,
            $this->getThreadService()->getThreadPostCount($product['id'], $thread['id']),
            30
        );

        $posts = $this->getThreadService()->findThreadPosts(
            $thread['productId'],
            $thread['id'],
            'default',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        if ($thread['type'] == 'question' and $paginator->getCurrentPage() == 1) {
            $elitePosts = $this->getThreadService()->findThreadElitePosts($thread['productId'], $thread['id'], 0, 10);
        } else {
            $elitePosts = array();
        }

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($posts, 'userId'));

        $this->getThreadService()->hitThread($productId, $id);

        $isManager = $this->getProductService()->canManageProduct($product['id']);

        $lesson = $this->getProductService()->getProductLesson($product['id'], $thread['lessonId']);
        return $this->render("TopxiaWebBundle:ProductThread:show.html.twig", array(
            'product' => $product,
            'lesson' => $lesson,
            'thread' => $thread,
            'author' => $this->getUserService()->getUser($thread['userId']),
            'posts' => $posts,
            'elitePosts' => $elitePosts,
            'users' => $users,
            'isManager' => $isManager,
            'isMemberNonExpired' => $isMemberNonExpired,
            'paginator' => $paginator,
        ));
    }

    public function createAction(Request $request, $id)
    {
        list($product, $member) = $this->getProductService()->tryTakeProduct($id);

        if ($member && !$this->getProductService()->isMemberNonExpired($product, $member)) {
            return $this->redirect($this->generateUrl('product_threads',array('id' => $id)));
        }

        if ($member && $member['levelId'] > 0) {
            if ($this->getVipService()->checkUserInMemberLevel($member['userId'], $product['vipLevelId']) != 'ok') {
                return $this->redirect($this->generateUrl('product_show',array('id' => $id)));
            }
        }


        $type = $request->query->get('type') ? : 'discussion';
        $form = $this->createThreadForm(array(
        	'type' => $type,
        	'productId' => $product['id'],
    	));

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $thread = $this->getThreadService()->createThread($form->getData());
                return $this->redirect($this->generateUrl('product_thread_show', array(
                   'productId' => $thread['productId'],
                   'id' => $thread['id'], 
                )));
            }
        }

        return $this->render("TopxiaWebBundle:ProductThread:form.html.twig", array(
            'product' => $product,
            'form' => $form->createView(),
            'type' => $type,
        ));
    }

    public function editAction(Request $request, $productId, $id)
    {
        $thread = $this->getThreadService()->getThread($productId, $id);
        if (empty($thread)) {
            throw $this->createNotFoundException();
        }

        $user = $this->getCurrentUser();
        if ($user->isLogin() and $user->id == $thread['userId']) {
            $product = $this->getProductService()->getProduct($productId);
        } else {
            $product = $this->getProductService()->tryManageProduct($productId);
        }

        $form = $this->createThreadForm($thread);
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $thread = $this->getThreadService()->updateThread($thread['productId'], $thread['id'], $form->getData());
                return $this->redirect($this->generateUrl('product_thread_show', array(
                   'productId' => $thread['productId'],
                   'id' => $thread['id'], 
                )));
            }
        }

        return $this->render("TopxiaWebBundle:ProductThread:form.html.twig", array(
            'form' => $form->createView(),
            'product' => $product,
            'thread' => $thread,
            'type' => $thread['type'],
        ));

    }

    private function createThreadForm($data = array())
    {
        return $this->createNamedFormBuilder('thread', $data)
            ->add('title', 'text')
            ->add('content', 'textarea')
            ->add('type', 'hidden')
            ->add('productId', 'hidden')
            ->getForm();
    }

    public function latestBlockAction($product)
    {
    	$threads = $this->getThreadService()->searchThreads(array('productId' => $product['id']), 'createdNotStick', 0, 10);

    	return $this->render('TopxiaWebBundle:ProductThread:latest-block.html.twig', array(
    		'product' => $product,
            'threads' => $threads,
		));
    }

    public function deleteAction(Request $request, $productId, $id)
    {
        $this->getThreadService()->deleteThread($id);
        return $this->createJsonResponse(true);
    }

    public function stickAction(Request $request, $productId, $id)
    {
        $this->getThreadService()->stickThread($productId, $id);
        return $this->createJsonResponse(true);
    }

    public function unstickAction(Request $request, $productId, $id)
    {
        $this->getThreadService()->unstickThread($productId, $id);
        return $this->createJsonResponse(true);
    }

    public function eliteAction(Request $request, $productId, $id)
    {
        $this->getThreadService()->eliteThread($productId, $id);
        return $this->createJsonResponse(true);
    }

    public function uneliteAction(Request $request, $productId, $id)
    {
        $this->getThreadService()->uneliteThread($productId, $id);
        return $this->createJsonResponse(true);
    }

    public function postAction(Request $request, $productId, $id)
    {
        list($product, $member) = $this->getProductService()->tryTakeProduct($productId);
        $thread = $this->getThreadService()->getThread($product['id'], $id);
        $form = $this->createPostForm(array(
            'productId' => $thread['productId'],
            'threadId' => $thread['id']
        ));

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $post = $this->getThreadService()->createPost($form->getData());

                return $this->render('TopxiaWebBundle:ProductThread:post-list-item.html.twig', array(
                    'product' => $product,
                    'thread' => $thread,
                    'post' => $post,
                    'author' => $this->getUserService()->getUser($post['userId']),
                    'isManager' => $this->getProductService()->canManageProduct($product['id'])
                ));
            } else {
                return $this->createJsonResponse(false);
            }
        }

        return $this->render('TopxiaWebBundle:ProductThread:post.html.twig', array(
            'product' => $product,
            'thread' => $thread,
            'form' => $form->createView()
        ));
    }

    public function editPostAction(Request $request, $productId, $threadId, $id)
    {
        $post = $this->getThreadService()->getPost($productId, $id);
        if (empty($post)) {
            throw $this->createNotFoundException();
        }

        $user = $this->getCurrentUser();
        if ($user->isLogin() and $user->id == $post['userId']) {
            $product = $this->getProductService()->getProduct($productId);
        } else {
            $product = $this->getProductService()->tryManageProduct($productId);
        }

        $thread = $this->getThreadService()->getThread($productId, $threadId);

        $form = $this->createPostForm($post);

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $post = $this->getThreadService()->updatePost($post['productId'], $post['id'], $form->getData());
                return $this->redirect($this->generateUrl('product_thread_show', array(
                    'productId' => $post['productId'],
                    'id' => $post['threadId']
                )));
            }
        }

        return $this->render('TopxiaWebBundle:ProductThread:post-form.html.twig', array(
            'product' => $product,
            'form' => $form->createView(),
            'post' => $post,
            'thread' => $thread,
        ));

    }

    public function deletePostAction(Request $request, $productId, $threadId, $id)
    {
        $this->getThreadService()->deletePost($productId, $id);
        return $this->createJsonResponse(true);
    }

    public function questionBlockAction(Request $request, $product)
    {
        $threads = $this->getThreadService()->searchThreads(
            array('productId' => $product['id'], 'type'=> 'question'),
            'createdNotStick',
            0,
            8
        );

        return $this->render('TopxiaWebBundle:ProductThread:question-block.html.twig', array(
            'product' => $product,
            'threads' => $threads,
        ));
    }

    private function createPostForm($data = array())
    {
        return $this->createNamedFormBuilder('post', $data)
            ->add('content', 'textarea')
            ->add('productId', 'hidden')
            ->add('threadId', 'hidden')
            ->getForm();
    }

    protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Product.ThreadService');
    }

    protected function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getThreadSearchFilters($request)
    {
        $filters = array();
        $filters['type'] = $request->query->get('type');
        if (!in_array($filters['type'], array('all', 'question', 'elite'))) {
            $filters['type'] = 'all';
        }
        $filters['sort'] = $request->query->get('sort');

        if (!in_array($filters['sort'], array('created', 'posted', 'createdNotStick', 'postedNotStick'))) {
            $filters['sort'] = 'posted';
        }
        return $filters;
    }

    private function convertFiltersToConditions($product, $filters)
    {
        $conditions = array('productId' => $product['id']);
        switch ($filters['type']) {
            case 'question':
                $conditions['type'] = 'question';
                break;
            case 'elite':
                $conditions['isElite'] = 1;
                break;
            default:
                break;
        }
        return $conditions;
    }
}