<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;

class LessonQuestionPluginController extends BaseController
{

    public function initAction (Request $request)
    {

        $product = $this->getProductService()->getProduct($request->query->get('productId'));
        $lesson = array(
            'id' => $request->query->get('lessonId'),
            'productId' => $product['id'],
        );

        $threads = $this->getThreadService()->searchThreads(
            array(
                'lessonId' => $lesson['id'],
                'type' => 'question',
            ),
            'createdNotStick',
            0, 20
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($threads, 'userId'));

        $form = $this->createQuestionForm(array(
            'productId' => $product['id'],
            'lessonId' => $lesson['id'],
        ));


    	return $this->render('TopxiaWebBundle:LessonQuestionPlugin:index.html.twig', array(
    		'threads' => $threads,
            'lesson' => $lesson,
            'form' => $form->createView(),
            'users' => $users,
		));
    }

    public function listAction(Request $request)
    {
        $product = $this->getProductService()->getProduct($request->query->get('productId'));
        $lesson = array(
            'id' => $request->query->get('lessonId'),
            'productId' => $product['id'],
        );

        $threads = $this->getThreadService()->searchThreads(
            array(
                'lessonId' => $lesson['id'],
                'type' => 'question',
            ),
            'createdNotStick',
            0, 20
        );

        return $this->render('TopxiaWebBundle:LessonQuestionPlugin:list.html.twig', array(
            'threads' => $threads,
            'lesson' => $lesson,
        ));
    }

    public function showAction(Request $request)
    {

        $product = $this->getProductService()->getProduct($request->query->get('productId'));

        $thread = $this->getThreadService()->getThread(
            $product['id'],
            $request->query->get('id')
        );

        $paginator = new Paginator(
            $request,
            $this->getThreadService()->getThreadPostCount($product['id'], $thread['id']),
            100
        );

        $posts = $this->getThreadService()->findThreadPosts(
            $thread['productId'],
            $thread['id'],
            'default',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $threader = $this->getUserService()->getUser($thread['userId']);
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($posts, 'userId'));

        $form = $this->createPostForm(array(
            'productId' => $product['id'],
            'threadId' => $thread['id'],
        ));

        return $this->render('TopxiaWebBundle:LessonQuestionPlugin:show.html.twig', array(
            'product' => $product,
            'thread' => $thread,
            'threader' => $threader,
            'posts' => $posts,
            'users' => $users,
            'form' => $form->createView(),
        ));

    }

    public function createAction(Request $request)
    {
        $form = $this->createQuestionForm();
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $question = $form->getData();
                $question['type'] = 'question';

                $thread = $this->getThreadService()->createThread($question);
                return $this->render("TopxiaWebBundle:LessonQuestionPlugin:item.html.twig", array(
                    'thread' => $thread,
                    'user' => $this->getCurrentUser(),
                ));
            } else {
                return $this->createJsonResponse(false);
            }
        }

        return $this->render("TopxiaWebBundle:LessonQuestionPlugin:form.html.twig", array(
            'product' => $product,
            'form' => $form->createView(),
        ));
    }

    public function answerAction(Request $request)
    {
        $form = $this->createPostForm();
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $post = $form->getData();
                $post = $this->getThreadService()->createPost($post);

                return $this->render('TopxiaWebBundle:LessonQuestionPlugin:post-item.html.twig', array(
                    'post' => $post,
                    'user' => $this->getUserService()->getUser($post['userId']),
                    'product' => $this->getProductService()->getProduct($post['productId']),
                ));
            } else {
                return $this->createJsonResponse(false);
            }
        }

        return $this->render("TopxiaWebBundle:LessonQuestionPlugin:form.html.twig", array(
            'product' => $product,
            'form' => $form->createView(),
        ));
    }

    private function createQuestionForm($data = array())
    {
        return $this->createNamedFormBuilder('question', $data)
            ->add('title', 'text')
            ->add('content', 'textarea')
            ->add('productId', 'hidden')
            ->add('lessonId', 'hidden')
            ->getForm();
    }

    private function createPostForm($data = array())
    {
        return $this->createNamedFormBuilder('post', $data)
            ->add('content', 'textarea')
            ->add('productId', 'hidden')
            ->add('threadId', 'hidden')
            ->getForm();
    }

    private function getThreadService()
    {
        return $this->getServiceKernel()->createService('Product.ThreadService');
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }


}