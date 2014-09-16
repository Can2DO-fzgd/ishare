<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;
use Topxia\Service\Question\Type\QuestionTypeFactory;

class ProductTestpaperManageController extends BaseController
{
    public function indexAction(Request $request, $productId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);

        $conditions = array();
        $conditions['target'] = "product-{$product['id']}";
        $paginator = new Paginator(
            $this->get('request'),
            $this->getTestpaperService()->searchTestpapersCount($conditions),
            10
        );

        $testpapers = $this->getTestpaperService()->searchTestpapers(
            $conditions,
            array('createdTime' ,'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($testpapers, 'updatedUserId')); 
        
        return $this->render('TopxiaWebBundle:ProductTestpaperManage:index.html.twig', array(
            'product' => $product,
            'testpapers' => $testpapers,
            'users' => $users,
            'paginator' => $paginator,

        ));
    }

    public function createAction(Request $request, $productId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);

        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();
            $fields['ranges'] = empty($fields['ranges']) ? array() : explode(',', $fields['ranges']);
            $fields['target'] = "product-{$product['id']}";
            $fields['pattern'] = 'QuestionType';
            list($testpaper, $items) = $this->getTestpaperService()->createTestpaper($fields);
            return $this->redirect($this->generateUrl('product_manage_testpaper_items',array('productId' => $product['id'], 'testpaperId' => $testpaper['id'])));
        }

        $typeNames = $this->get('topxia.twig.web_extension')->getDict('questionType');
        $types = array();
        foreach ($typeNames as $type => $name) {
            $typeObj = QuestionTypeFactory::create($type);
            $types[] = array(
                'key' => $type,
                'name' => $name,
                'hasMissScore' => $typeObj->hasMissScore(),
            );
        }

        return $this->render('TopxiaWebBundle:ProductTestpaperManage:create.html.twig', array(
            'product'    => $product,
            'ranges' => $this->getQuestionRanges($product),
            'types' => $types,
        ));
    }

    public function buildCheckAction(Request $request, $productId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);

        $data = $request->request->all();
        $data['target'] = "product-{$product['id']}";
        $data['ranges'] = empty($data['ranges']) ? array() : explode(',', $data['ranges']);
        $result = $this->getTestpaperService()->canBuildTestpaper('QuestionType', $data);
        return $this->createJsonResponse($result);
    }

    public function updateAction(Request $request, $productId, $id)
    {
        $product = $this->getProductService()->tryManageProduct($productId);

        $testpaper = $this->getTestpaperService()->getTestpaper($id);
        if (empty($testpaper)) {
            throw $this->createNotFoundException('问卷不存在');
        }

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $testpaper = $this->getTestpaperService()->updateTestpaper($id, $data);
            $this->setFlashMessage('success', '问卷信息保存成功！');
            return $this->redirect($this->generateUrl('product_manage_testpaper', array('productId' => $product['id'])));
        }

        return $this->render('TopxiaWebBundle:ProductTestpaperManage:update.html.twig', array(
            'product'    => $product,
            'testpaper' => $testpaper,
        ));
    }

    public function deleteAction(Request $request, $productId, $testpaperId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);
        $testpaper = $this->getTestpaperWithException($product, $testpaperId);
        $this->getTestpaperService()->deleteTestpaper($testpaper['id']);

        return $this->createJsonResponse(true);
    }

    public function deletesAction(Request $request, $productId)
    {   
        $product = $this->getProductService()->tryManageProduct($productId);

        $ids = $request->request->get('ids');

        foreach (is_array($ids) ? $ids : array() as $id) {
            $testpaper = $this->getTestpaperWithException($product, $id);
            $this->getTestpaperService()->deleteTestpaper($id);
        }

        return $this->createJsonResponse(true);
    }

    public function publishAction (Request $request, $productId, $id)
    {
        $product = $this->getProductService()->tryManageProduct($productId);

        $testpaper = $this->getTestpaperWithException($product, $id);

        $testpaper = $this->getTestpaperService()->publishTestpaper($id);

        $user = $this->getUserService()->getUser($testpaper['updatedUserId']);

        return $this->render('TopxiaWebBundle:ProductTestpaperManage:tr.html.twig', array(
            'testpaper' => $testpaper,
            'user' => $user,
            'product' => $product,
        ));
    }

    public function closeAction (Request $request, $productId, $id)
    {
        $product = $this->getProductService()->tryManageProduct($productId);

        $testpaper = $this->getTestpaperWithException($product, $id);

        $testpaper = $this->getTestpaperService()->closeTestpaper($id);

        $user = $this->getUserService()->getUser($testpaper['updatedUserId']);

        return $this->render('TopxiaWebBundle:ProductTestpaperManage:tr.html.twig', array(
            'testpaper' => $testpaper,
            'user' => $user,
            'product' => $product,
        ));
    }

    private function getTestpaperWithException($product, $testpaperId)
    {
        $testpaper = $this->getTestpaperService()->getTestpaper($testpaperId);
        if (empty($testpaper)) {
            throw $this->createNotFoundException();
        }

        if ($testpaper['target'] != "product-{$product['id']}") {
            throw $this->createAccessDeniedException();
        }
        return $testpaper;
    }

    public function itemsAction(Request $request, $productId, $testpaperId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);

        $testpaper = $this->getTestpaperService()->getTestpaper($testpaperId);
        if(empty($testpaper)){
            throw $this->createNotFoundException('问卷不存在');
        }

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            
            if (empty($data['questionId']) or empty($data['scores'])) {
                return $this->createMessageResponse('error', '问卷题目不能为空！');
            }
            if (count($data['questionId']) != count($data['scores'])) {
                return $this->createMessageResponse('error', '问卷题目数据不正确');
            }

            $data['questionId'] = array_values($data['questionId']);
            $data['scores'] = array_values($data['scores']);

            $items = array();
            foreach ($data['questionId'] as $index => $questionId) {
                $items[] = array('questionId' => $questionId, 'score' => $data['scores'][$index]);
            }

            $this->getTestpaperService()->updateTestpaperItems($testpaper['id'], $items);

            $this->setFlashMessage('success', '问卷题目保存成功！');
            return $this->redirect($this->generateUrl('product_manage_testpaper',array( 'productId' => $productId)));
        }

        $items = $this->getTestpaperService()->getTestpaperItems($testpaper['id']);

        $questions = $this->getQuestionService()->findQuestionsByIds(ArrayToolkit::column($items, 'questionId'));

        $targets = $this->get('topxia.target_helper')->getTargets(ArrayToolkit::column($questions, 'target'));

        $subItems = array();
        foreach ($items as $key => $item) {
            if ($item['parentId'] > 0) {
                $subItems[$item['parentId']][] = $item;
                unset($items[$key]);
            }
        }


        return $this->render('TopxiaWebBundle:ProductTestpaperManage:items.html.twig', array(
            'product' => $product,
            'testpaper' => $testpaper,
            'items' => ArrayToolkit::group($items, 'questionType'),
            'subItems' => $subItems,
            'questions' => $questions,
            'targets' => $targets,
        ));
    }

    public function itemsResetAction(Request $request, $productId, $testpaperId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);

        $testpaper = $this->getTestpaperService()->getTestpaper($testpaperId);
        if(empty($testpaper)){
            throw $this->createNotFoundException('问卷不存在');
        }

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $data['target'] = "product-{$product['id']}";
            $data['ranges'] = explode(',', $data['ranges']);
            $this->getTestpaperService()->buildTestpaper($testpaper['id'], $data);
            return $this->redirect($this->generateUrl('product_manage_testpaper_items', array('productId' => $productId, 'testpaperId' => $testpaperId)));
        }

        $typeNames = $this->get('topxia.twig.web_extension')->getDict('questionType');
        $types = array();
        foreach ($typeNames as $type => $name) {
            $typeObj = QuestionTypeFactory::create($type);
            $types[] = array(
                'key' => $type,
                'name' => $name,
                'hasMissScore' => $typeObj->hasMissScore(),
            );
        }


        return $this->render('TopxiaWebBundle:ProductTestpaperManage:items-reset.html.twig', array(
            'product'    => $product,
            'testpaper' => $testpaper,
            'ranges' => $this->getQuestionRanges($product),
            'types' => $types,
        ));
    }

    public function itemPickerAction(Request $request, $productId, $testpaperId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);
        $testpaper = $this->getTestpaperService()->getTestpaper($testpaperId);
        if (empty($testpaper)) {
            throw $this->createNotFoundException();
        }

        $conditions = $request->query->all();

        if (empty($conditions['target'])) {
            $conditions['targetPrefix'] = "product-{$product['id']}";
        }

        $conditions['parentId'] = 0;
        $conditions['excludeIds'] = empty($conditions['excludeIds']) ? array() : explode(',', $conditions['excludeIds']);

        if (!empty($conditions['keyword'])) {
            $conditions['stem'] = $conditions['keyword'];
        }


        $replace = empty($conditions['replace']) ? '' : $conditions['replace'];

        $paginator = new Paginator(
            $request,
            $this->getQuestionService()->searchQuestionsCount($conditions),
            7
        );

        $questions = $this->getQuestionService()->searchQuestions(
                $conditions, 
                array('createdTime' ,'DESC'), 
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
        );

        $targets = $this->get('topxia.target_helper')->getTargets(ArrayToolkit::column($questions, 'target'));

        return $this->render('TopxiaWebBundle:ProductTestpaperManage:item-picker-modal.html.twig', array(
            'product' => $product,
            'testpaper' => $testpaper,
            'questions' => $questions,
            'replace' => $replace,
            'paginator' => $paginator,
            'targetChoices' => $this->getQuestionRanges($product, true),
            'targets' => $targets,
            'conditions' => $conditions,
        ));
        
    }

    public function itemPickedAction(Request $request, $productId, $testpaperId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);
        $testpaper = $this->getTestpaperService()->getTestpaper($testpaperId);
        if (empty($testpaper)) {
            throw $this->createNotFoundException();
        }

        $question = $this->getQuestionService()->getQuestion($request->query->get('questionId'));
        if (empty($question)) {
            throw $this->createNotFoundException();
        }

        if ($question['subCount'] > 0) {
            $subQuestions = $this->getQuestionService()->findQuestionsByParentId($question['id']);
        } else {
            $subQuestions = array();
        }

        $targets = $this->get('topxia.target_helper')->getTargets(array($question['target']));

        return $this->render('TopxiaWebBundle:ProductTestpaperManage:item-picked.html.twig', array(
            'product'    => $product,
            'testpaper' => $testpaper,
            'question' => $question,
            'subQuestions' => $subQuestions,
            'targets' => $targets,
            'type' => $question['type']
        ));

    }



    private function getQuestionRanges($product, $includeProduct = false)
    {
        $lessons = $this->getProductService()->getProductLessons($product['id']);
        $ranges = array();

        if ($includeProduct == true) {
            $ranges["product-{$product['id']}"] = '本产品';
        }

        foreach ($lessons as  $lesson) {
            if ($lesson['type'] == 'testpaper') {
                continue;
            }
            $ranges["product-{$lesson['productId']}/lesson-{$lesson['id']}"] = "产品介绍{$lesson['number']}： {$lesson['title']}";
        }

        return $ranges;
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getTestpaperService()
    {
        return $this->getServiceKernel()->createService('Testpaper.TestpaperService');
    }

    private function getQuestionService()
    {
        return $this->getServiceKernel()->createService('Question.QuestionService');
    }
}