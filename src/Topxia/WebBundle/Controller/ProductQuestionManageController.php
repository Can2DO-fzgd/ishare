<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;
use Topxia\Service\Question\QuestionService;

class ProductQuestionManageController extends BaseController
{

    public function indexAction(Request $request, $productId)
    {
        $product = $this->getProductService()->tryManageProduct($productId);
        
        $conditions = $request->query->all();

        if (empty($conditions['target'])) {
            $conditions['targetPrefix'] = "product-{$product['id']}";
        }

        if (!empty($conditions['keyword'])) {
            $conditions['stem'] = $conditions['keyword'];
        }

        if (!empty($conditions['parentId'])) {

            $parentQuestion = $this->getQuestionService()->getQuestion($conditions['parentId']);
            if (empty($parentQuestion)){
                return $this->redirect($this->generateUrl('product_manage_question',array('productId' => $productId)));
            }

            $orderBy = array('createdTime' ,'ASC');
        } else {
            $conditions['parentId'] = 0;
            $parentQuestion = null;
            $orderBy = array('createdTime' ,'DESC');
        }

        $paginator = new Paginator(
            $this->get('request'),
            $this->getQuestionService()->searchQuestionsCount($conditions),
            10
        );

        $questions = $this->getQuestionService()->searchQuestions(
            $conditions,
            $orderBy,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($questions, 'userId'));

        $targets = $this->get('topxia.target_helper')->getTargets(ArrayToolkit::column($questions, 'target'));

        return $this->render('TopxiaWebBundle:ProductQuestionManage:index.html.twig', array(
            'product' => $product,
            'questions' => $questions,
            'users' => $users,
            'targets' => $targets,
            'paginator' => $paginator,
            'parentQuestion' => $parentQuestion,
            'conditions' => $conditions,
            'targetChoices' => $this->getQuestionTargetChoices($product),
        ));
    }

    public function createAction(Request $request, $productId, $type)
    {
        $product = $this->getProductService()->tryManageProduct($productId);

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();

            $question = $this->getQuestionService()->createQuestion($data);

            if ($data['submission'] == 'continue') {
                $urlParams = ArrayToolkit::parts($question, array('target', 'difficulty', 'parentId'));
                $urlParams['type'] = $type;
                $urlParams['productId'] = $productId;
                $urlParams['goto'] = $request->query->get('goto', null);
                $this->setFlashMessage('success', '问卷题目添加成功，请继续添加。');
                return $this->redirect($this->generateUrl('product_manage_question_create', $urlParams));
            } elseif ($data['submission'] == 'continue_sub') {
                $this->setFlashMessage('success', '问卷题目添加成功，请继续添加子题。');
                return $this->redirect($request->query->get('goto', $this->generateUrl('product_manage_question', array('productId' => $productId, 'parentId' => $question['id']))));
            } else {
                $this->setFlashMessage('success', '问卷题目添加成功。');
                return $this->redirect($request->query->get('goto', $this->generateUrl('product_manage_question', array('productId' => $productId))));
            }
        }

        $question = array(
            'id' => 0,
            'type' => $type,
            'target' => $request->query->get('target'),
            'difficulty' => $request->query->get('difficulty', 'normal'),
            'parentId' => $request->query->get('parentId', 0),
        );

        if ($question['parentId'] > 0) {
            $parentQuestion = $this->getQuestionService()->getQuestion($question['parentId']);
            if (empty($parentQuestion)){
                return $this->createMessageResponse('error', '问卷 父题不存在，不能创建问卷子题！');
            }
        } else {
            $parentQuestion = null;
        }

        if ($this->container->hasParameter('enabled_features')) {
            $features = $this->container->getParameter('enabled_features');
        } else {
            $features = array();
        }

        $enabledAudioQuestion = in_array('audio_question', $features);

        return $this->render("TopxiaWebBundle:ProductQuestionManage:question-form-{$type}.html.twig", array(
            'product' => $product,
            'question' => $question,
            'parentQuestion' => $parentQuestion,
            'targetsChoices' => $this->getQuestionTargetChoices($product),
            'categoryChoices' => $this->getQuestionCategoryChoices($product),
            'enabledAudioQuestion' => $enabledAudioQuestion
        ));
    }

    public function updateAction(Request $request, $productId, $id)
    {
        $product = $this->getProductService()->tryManageProduct($productId);

        if ($request->getMethod() == 'POST') {
            $question = $request->request->all();

            $question = $this->getQuestionService()->updateQuestion($id, $question);

            $this->setFlashMessage('success', '问卷题目修改成功！');

            return $this->redirect($request->query->get('goto', $this->generateUrl('product_manage_question',array('productId' => $productId,'parentId' => $question['parentId']))));
        }

        $question = $this->getQuestionService()->getQuestion($id);
        if ($question['parentId'] > 0) {
            $parentQuestion = $this->getQuestionService()->getQuestion($question['parentId']);
        } else {
            $parentQuestion = null;
        }

        return $this->render("TopxiaWebBundle:ProductQuestionManage:question-form-{$question['type']}.html.twig", array(
            'product' => $product,
            'question' => $question,
            'parentQuestion' => $parentQuestion,
            'targetsChoices' => $this->getQuestionTargetChoices($product),
            'categoryChoices' => $this->getQuestionCategoryChoices($product),
        ));

    }

    public function deleteAction(Request $request, $productId, $id)
    {
        $product = $this->getProductService()->tryManageProduct($productId);
        $question = $this->getQuestionService()->getQuestion($id);
        $this->getQuestionService()->deleteQuestion($id);
        
        return $this->createJsonResponse(true);
    }

    public function deletesAction(Request $request, $productId)
    {   
        $product = $this->getProductService()->tryManageProduct($productId);

        $ids = $request->request->get('ids');
        foreach ($ids ? : array() as $id) {
            $this->getQuestionService()->deleteQuestion($id);
        }

        return $this->createJsonResponse(true);
    }

    public function uploadFileAction (Request $request, $productId, $type)
    {
        $product = $this->getProductService()->getProduct($productId);

        if ($request->getMethod() == 'POST') {
            $originalFile = $this->get('request')->files->get('file');
            $file = $this->getUploadFileService()->addFile('quizquestion', 0, array('isPublic' => 1), 'local', $originalFile);
            return new Response(json_encode($file));
        }
    }


    /**
     * @todo refact it, to xxvholic.
     */
    public function previewAction (Request $request, $productId, $id)
    {
        $isNewWindow = $request->query->get('isNew');

        $product = $this->getProductService()->tryManageProduct($productId);

        $question = $this->getQuestionService()->getQuestion($id);

        if (empty($question)) {
            throw $this->createNotFoundException('问卷题目不存在！');
        }

        $item = array(
            'questionId' => $question['id'],
            'questionType' => $question['type'],
            'question' => $question
        );

        if ($question['subCount'] > 0) {
            $questions = $this->getQuestionService()->findQuestionsByParentId($id);

            foreach ($questions as $value) {
                $items[] = array(
                    'questionId' => $value['id'],
                    'questionType' => $value['type'],
                    'question' => $value
                );
            }

            $item['items'] = $items;
        }

        $type = in_array($question['type'], array('single_choice', 'uncertain_choice')) ? 'choice' : $question['type'];
        $questionPreview = true;

        if($isNewWindow){
            return $this->render('TopxiaWebBundle:QuizQuestionTest:question-preview.html.twig', array(
                'item' => $item,
                'type' => $type,
                'questionPreview' => $questionPreview
            ));
        }

        return $this->render('TopxiaWebBundle:QuizQuestionTest:question-preview-modal.html.twig', array(
            'item' => $item,
            'type' => $type,
            'questionPreview' => $questionPreview
        ));
    }

    private function getQuestionTargetChoices($product)
    {
        $lessons = $this->getProductService()->getProductLessons($product['id']);
        $choices = array();
        $choices["product-{$product['id']}"] = '本产品';
        foreach ($lessons as $lesson) {
            if ($lesson['type'] == 'testpaper') {
                continue;
            }
            $choices["product-{$product['id']}/lesson-{$lesson['id']}"] = "产品介绍{$lesson['number']}：{$lesson['title']}";
        }
        return $choices;
    }

    private function getQuestionCategoryChoices($product)
    {
        $categories = $this->getQuestionService()->findCategoriesByTarget("product-{$product['id']}", 0, QuestionService::MAX_CATEGORY_QUERY_COUNT);
        $choices = array();
        foreach ($categories as $category) {
            $choices[$category['id']] = $category['name'];
        }
        return $choices;
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getQuestionService()
    {
        return $this->getServiceKernel()->createService('Question.QuestionService');
    }

    private function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }

}