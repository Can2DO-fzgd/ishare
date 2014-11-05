<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\WebBundle\Form\ReviewType;
use Topxia\Service\Product\ProductService;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;
use Topxia\Common\FileToolkit;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface;

class ProductManageController extends BaseController
{
	//产品基本信息管理
	public function indexAction(Request $request, $id)
	{
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        return $this->forward('TopxiaWebBundle:ProductManage:base',  array(
			'categories' => $categories,
			'id' => $id));
	}

	public function baseAction(Request $request, $id)
	{	
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
		$product = $this->getProductService()->tryManageProduct($id);

	    if($request->getMethod() == 'POST'){
            $data = $request->request->all();
			
			$pid = $request->request->get('categoryId');
			$categoriedpid1 =  $this->getCategoryService()->getCategory($pid);
			$categoriedpid2 =  $this->getCategoryService()->getCategory($categoriedpid1['pid']);
			
			if ($categoriedpid2['pid'] >= '2' && $categoriedpid1['pid'] > '2') {
				$data['tagido'] = $categoriedpid2['pid'];
				$data['tagidt'] = $categoriedpid1['pid'];
				$data['tagids'] = $pid;
			} elseif ($categoriedpid2['pid'] == '1' && $categoriedpid1['pid'] >= '2') {
			 	$data['tagido'] = $categoriedpid1['pid'];
				$data['tagidt'] = $pid;
				$data['tagids'] = '-1';
			} elseif ($categoriedpid2['pid'] == '0' && $categoriedpid1['pid'] == '1') {
				$data['tagido'] = $pid;
				$data['tagidt'] = '-1';
				$data['tagids'] = '0';
			}
			
			/*if ($categoriedpid2 >= 2 && $categoriedpid1 > 2) {
				$data['tagido'] = $categoriedpid2['pid'];
				$data['tagidt'] = $categoriedpid1['pid'];
				$data['tagids'] = $pid;
			} elseif ($categoriedpid2 == 1 && $categoriedpid1 >= 2) {
			 	$data['tagido'] = $categoriedpid1['pid'];
				$data['tagidt'] = $pid;
				$data['tagids'] = '0';
			} elseif ($categoriedpid2 == 0 && $categoriedpid1 == 1) {
				$data['tagido'] = $pid;
				$data['tagidt'] = '0';
				$data['tagids'] = '0';
			}*/
			
            $this->getProductService()->updateProduct($id, $data);
            $this->setFlashMessage('success', '产品基本信息已保存！');
            return $this->redirect($this->generateUrl('product_manage_base',array('id' => $id))); 
        }

        $tags = $this->getTagService()->findTagsByIds($product['tags']);

		return $this->render('TopxiaWebBundle:ProductManage:base.html.twig', array(
			'product' => $product,
			'categories' => $categories,
            'tags' => ArrayToolkit::column($tags, 'name')
		));
	}

    public function userNameCheckAction(Request $request, $productId)
    {
        $userName = $request->query->get('value');
        $result = $this->getUserService()->isUserNameAvaliable($userName);
        if ($result) {
            $response = array('success' => false, 'message' => '该用户还不存在！');
        } else {
            $user = $this->getUserService()->getUserByUserName($userName);
            $isProductStudent = $this->getProductService()->isProductStudent($productId, $user['id']);
            if($isProductStudent){
                $response = array('success' => false, 'message' => '该用户已是本产品的买家了！');
            } else {
                $response = array('success' => true, 'message' => '');
            }
            
            $isProductTeacher = $this->getProductService()->isProductTeacher($productId, $user['id']);
            if($isProductTeacher){
                $response = array('success' => false, 'message' => '该用户是本产品的享客，不能添加!');
            }
        }
        return $this->createJsonResponse($response);
    }

	//产品详细信息管理
	public function detailAction(Request $request, $id)
	{
        $categories = $this->getCategoryService()->findGroupRootCategories('product');
		
		//判断是否有权操作
		$product = $this->getProductService()->tryManageProduct($id);

	    if($request->getMethod() == 'POST'){
            $detail = $request->request->all();
            $detail['efficacyinfo'] = (empty($detail['efficacyinfo']) or !is_array($detail['efficacyinfo'])) ? array() : $detail['efficacyinfo'];
            $detail['yaodian'] = (empty($detail['yaodian']) or !is_array($detail['yaodian'])) ? array() : $detail['yaodian'];

			//更新产品
            $this->getProductService()->updateProduct($id, $detail);
            $this->setFlashMessage('success', '产品详细信息已保存！');

            return $this->redirect($this->generateUrl('product_manage_detail',array('id' => $id))); 
        }

		return $this->render('TopxiaWebBundle:ProductManage:detail.html.twig', array(
			'categories' => $categories,
			'product' => $product
		));
	}

	//产品图片上传
	public function pictureAction(Request $request, $id)
	{
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
		//判断是否有权操作
		$product = $this->getProductService()->tryManageProduct($id);

        if($request->getMethod() == 'POST'){
            $file = $request->files->get('picture');
            if (!FileToolkit::isImageFile($file)) {
                return $this->createMessageResponse('error', '上传图片格式错误，请上传jpg, gif, png格式的文件。');
            }

			//拼接文件名
            $filenamePrefix = "productpic_{$product['id']}_";
            $hash = substr(md5($filenamePrefix . time()), -8);
            //$hash = substr(guid(),-8);
			$ext = $file->getClientOriginalExtension();
            $filename = $filenamePrefix . $hash . '.' . $ext;

            $directory = $this->container->getParameter('topxia.upload.public_directory') . '/tmp';
            $file = $file->move($directory, $filename);

            $fileName = str_replace('.', '!', $file->getFilename());

            return $this->redirect($this->generateUrl('product_manage_picture_crop', array(
                'id' => $product['id'],
                'file' => $fileName)
            ));
        }

		return $this->render('TopxiaWebBundle:ProductManage:picture.html.twig', array(
			'product' => $product,
			'categories' => $categories,
		));
	}
	
	//图片裁剪
    public function pictureCropAction(Request $request, $id)
    {
        $categories = $this->getCategoryService()->findGroupRootCategories('product');
		
		$product = $this->getProductService()->tryManageProduct($id);

        //@todo 文件名的过滤
        $filename = $request->query->get('file');
        $filename = str_replace('!', '.', $filename);
        $filename = str_replace(array('..' , '/', '\\'), '', $filename);

        $pictureFilePath = $this->container->getParameter('topxia.upload.public_directory') . '/tmp/' . $filename;

        if($request->getMethod() == 'POST') {
            $c = $request->request->all();
            $this->getProductService()->changeProductPicture($product['id'], $pictureFilePath, $c);
            return $this->redirect($this->generateUrl('product_manage_picture', array('id' => $product['id'])));
        }

        try {
        $imagine = new Imagine();
            $image = $imagine->open($pictureFilePath);
        } catch (\Exception $e) {
            @unlink($pictureFilePath);
            return $this->createMessageResponse('error', '该文件为非图片格式文件，请重新上传。');
        }

        $naturalSize = $image->getSize();
        $scaledSize = $naturalSize->widen(304)->heighten(171);//heighten171
		//$scaledSize = $naturalSize->widen(480)->heighten(270);//图片裁剪预选框大小默认为480*270

        // @todo fix it.
        $assets = $this->container->get('templating.helper.assets');
        $pictureUrl = $this->container->getParameter('topxia.upload.public_url_path') . '/tmp/' . $filename;
        $pictureUrl = ltrim($pictureUrl, ' /');
        $pictureUrl = $assets->getUrl($pictureUrl);

        return $this->render('TopxiaWebBundle:ProductManage:picture-crop.html.twig', array(
            'product' => $product,
            'pictureUrl' => $pictureUrl,
			'categories' => $categories,
            'naturalSize' => $naturalSize,
            'scaledSize' => $scaledSize,
        ));
    }

    public function priceAction(Request $request, $id)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        $product = $this->getProductService()->tryManageProduct($id);

        $canModifyPrice = true;
        $teacherModifyPrice = $this->setting('product.teacher_modify_price', true);
        if (empty($teacherModifyPrice)) {
            if (!$this->getCurrentUser()->isAdmin()) {
                $canModifyPrice = false;
                goto response;
            }
        }

        if ($request->getMethod() == 'POST') {
            $product = $this->getProductService()->updateProduct($id, $request->request->all());
            $this->setFlashMessage('success', '产品价格已经修改成功!');
        }

        if ($this->setting('vip.enabled')) {
            $levels = $this->getLevelService()->findEnabledLevels();
        } else {
            $levels = array();
        }


        response:
        return $this->render('TopxiaWebBundle:ProductManage:price.html.twig', array(
            'product' => $product,
            'canModifyPrice' => $canModifyPrice,
			'categories' => $categories,
            'levels' => $this->makeLevelChoices($levels),
        ));
    }

    private function makeLevelChoices($levels)
    {
        $choices = array();
        foreach ($levels as $level) {
            $choices[$level['id']] = $level['name'];
        }
        return $choices;
    }

    public function teachersAction(Request $request, $id)
    {
        $categories = $this->getCategoryService()->findGroupRootCategories('product');
		
		$product = $this->getProductService()->tryManageProduct($id);

        if($request->getMethod() == 'POST'){
        	
            $data = $request->request->all();
            $data['ids'] = empty($data['ids']) ? array() : array_values($data['ids']);

            $teachers = array();
            foreach ($data['ids'] as $teacherId) {
            	$teachers[] = array(
            		'id' => $teacherId,
            		'isVisible' => empty($data['visible_' . $teacherId]) ? 0 : 1
        		);
            }
            $this->getProductService()->setProductTeachers($id, $teachers);
            $this->setFlashMessage('success', '享客设置成功！');

            return $this->redirect($this->generateUrl('product_manage_teachers',array('id' => $id))); 
        }

        $teacherMembers = $this->getProductService()->findProductTeachers($id);
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($teacherMembers, 'userId'));

        $teachers = array();
        foreach ($teacherMembers as $member) {
        	if (empty($users[$member['userId']])) {
        		continue;
        	}
        	$teachers[] = array(
                'id' => $member['userId'],
        		'userName' => $users[$member['userId']]['userName'],
                'avatar'  => $this->getWebExtension()->getFilePath($users[$member['userId']]['smallAvatar'], 'avatar.png'),
        		'isVisible' => $member['isVisible'] ? true : false,
    		);
        }
        
        return $this->render('TopxiaWebBundle:ProductManage:teachers.html.twig', array(
            'product' => $product,
			'categories' => $categories,
            'teachers' => $teachers
        ));
    }

    public function publishAction(Request $request, $id)
    {
    	$this->getProductService()->publishProduct($id);
    	return $this->createJsonResponse(true);
    }

    public function teachersMatchAction(Request $request)
    {
        $likeString = $request->query->get('q');
        $users = $this->getUserService()->searchUsers(array('userName'=>$likeString, 'roles'=> 'ROLE_ISHARE'), array('createdTime', 'DESC'), 0, 10);

        $teachers = array();
        foreach ($users as $user) {
            $teachers[] = array(
                'id' => $user['id'],
                'userName' => $user['userName'],
                'avatar' => $this->getWebExtension()->getFilePath($user['smallAvatar'], 'avatar.png'),
                'isVisible' => 1
            );
        }

        return $this->createJsonResponse($teachers);
    }

	private function createProductBaseForm($product)
	{
		$builder = $this->createNamedFormBuilder('product', $product)
			->add('name', 'text')
			->add('jianjie', 'textarea')
			->add('tags', 'tags')
            ->add('expiryDay', 'text')
			->add('categoryId', 'default_category', array(
				'empty_value' => '请选择分类'
			));

	    return $builder->getForm();
	}

    private function calculateUserLearnProgress($product, $member)
    {
		$categories = $this->getCategoryService()->findGroupRootCategories('product');
		
        if ($product['lessonNum'] == 0) {
            return array('percent' => '0%', 'number' => 0, 'total' => 0);
        }

        $percent = intval($member['learnedNum'] / $product['lessonNum'] * 100) . '%';

        return array (
            'percent' => $percent,
            'categories' => $categories,
			'number' => $member['learnedNum'],
            'total' => $product['lessonNum']
        );
    }

	private function guid(){
    if (function_exists('com_create_guid')){
	
        return com_create_guid();
    	
		}else{
        	mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        	$charid = strtoupper(md5(uniqid(rand(), true)));
        	$hyphen = chr(45);// "-"
        	$uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
        	return $uuid;
    	}
	}
	
    private function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

    private function getProductService()
    {
        return $this->getServiceKernel()->createService('Product.ProductService');
    }

    private function getLevelService()
    {
        return $this->getServiceKernel()->createService('Vip:Vip.LevelService');
    }

    private function getFileService()
    {
        return $this->getServiceKernel()->createService('Content.FileService');
    }

    private function getWebExtension()
    {
        return $this->container->get('topxia.twig.web_extension');
    }

    private function getNotificationService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }

    private function getTagService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.TagService');
    }
}