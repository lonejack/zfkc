<?php
/**
 * 
 * Admin Image Controller
 * This files regards then image management at administration level. The appliable methods
 * are typical CRUD.
 * 
 * @author Claudio Eterno
 * @package admin
 * @version 1.0.0
 */

/**
 * 
 * Enter description here ...
 * @author Claudio Eterno
 *
 */

class Admin_ImageController extends Zend_Controller_Action {
	/**
	 * 
	 * The role of the user is getting into: superadmin, admin, member, guest
	 * @var enum
	 */
	protected $role;
	
	/**
	 * 
	 * The instantiated acl action helper
	 * @var Zend_Controller_Action_Helper_Acl
	 */
	protected $aclHelper;
	
	/**
	 * 
	 * The auth instance
	 * @var My_Auth
	 */
	protected $auth;
	
	/**
	 * 
	 * The default content user language
	 * @var string
	 */
	protected $default_content;
	
	/**
	 * 
	 * The flash messager helper 
	 * @var Zend_Controller_Action_Helper_FlashMessenger
	 */
	protected $_flashmessager;
	
	/**
	 * the controller configuration and action for user 
	 */
	protected $_config;
	
	/**
	 * (non-PHPdoc)
	 * @see library/Zend/Controller/Zend_Controller_Action::init()
	 */
	
	public function init() {
		/* Initialize action controller here */
		
		$this->aclHelper = $this->_helper->getHelper ( 'Acl' )->generateExOnDenied(true);
		$acl = $this->aclHelper->getInvokeAclResource( 'Acl' );
		
		$this->auth = My_Auth::getInstance();
		$this->default_content = $this->auth->get('default_content');
		$this->role = $this->auth->getRole ();
		
		
		
		//$action = $this->getRequest()->getActionName();
		$role = $this->role;
		//$this->_config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/Admin_ImageController.ini', $role .'_'. $action );
		
		//$acl->addResource ( new Zend_Acl_Resource ( 'admin:image' ) );
		
		//My_Acl::ConfigResource($acl,'admin:image',$this->role, $this->_config->acl );
		$this->_flashMessenger =  $this->_helper->getHelper('FlashMessenger');
	}
	
	/**
	 * 
	 * Index action
	 * This function shows the number of images loaded depending on the user's privileges  
	 */
	
	public function indexAction() {
		// action body
		
		$images = new Admin_Model_ImageMapper (array('auth'=>$this->auth));
		
		$view = $this->view;
		$view->numImages = $images->count ();
	}
	
	public function listallAction() {

		$request = $this->getRequest ();
		$images = new Admin_Model_ImageMapper (array('auth'=>$this->auth));
		
		$paginator = new Zend_Paginator ( $images );
		$page = $request->getParam ( 'page', null );
		$paginator->setCurrentPageNumber ( $page );
		
		$this->view->entries = $paginator->getCurrentItems ();
		Zend_Paginator::setDefaultScrollingStyle ( 'Sliding' );
		Zend_View_Helper_PaginationControl::setDefaultViewPartial ( '_pagination_control.phtml' );
		
		$helper = $this->getHelper ( 'ListHelper' );
		$privileges = Admin_Model_Image::getPrivileges ( $this->role );
		$helper->setPrivileges ( $this->view->entries, $privileges );
		
		$paginator->setView ( $this->view );
		$this->view->paginator = $paginator;
		$this->view->messages = $this->_flashMessenger->getMessages();
	}
	
	public function insertAction() {
		// action body

		$request = $this->getRequest ();
		$absolute_path = PUBLIC_PATH.$this->auth->get('download_area');
		$relative_path = $this->auth->get('download_area');
		//$default_content = $this->auth->get('default_content');
		$form = new Admin_Form_ImageInsert ($absolute_path);
		if(!is_writable($absolute_path))
		   throw new Exception('directory not writable');
		
		
		$helper = $this->getHelper ( 'FormHandler' );
		
		if ($request->isPost ()) {
			// Does the POST contain the form namespace? If not, just render the form
			try {
				
				if (! $form->ifValidRemoveHash ( $request->getPost () ))
					throw new Exception ( 'Parametri non corretti' );
			    if (!$form->image->receive()) {
                	print "Error receiving the file";
                }
				$images = new Admin_Model_ImageMapper (array('auth'=>$this->auth));
				$values = $form->getValues(true);
				$values['user_id']= $this->auth->getId();
				echo "<!--";
				print_r($values);
				echo "-->";
				$values[$this->default_content]=$values['description'];
				unset($values['description']);
				$values['path']=$relative_path.$values['image'];
				unset($values['image']);
				$ret = $images->create ( $values );
				$this->_redirect ( '/admin/image/listall' );
			
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->error = $e->getMessage ();
			}
		}
		$form->addElement ( 'submit', 'create', array ('label' => 'insert', 'ignore' => 'true', 'required' => 'false' ) );
		$form->setAction ( '/admin/image/insert#' )->setMethod ( 'post' );
		$this->view->form = $form;
	}
	
	/**
	 * 
	 */
	public function viewAction() {

		$request = $this->getRequest ();
		$params = $request->getParams();
		$id = $request->getParam ( 'id', null );
		
		$images = new Admin_Model_ImageMapper (array('auth'=>$this->auth));
		$data = $images->findData ( $id )->makeArray();
		
		
		$this->view->data = $data;
	}

	public function thumbnailAction() {
		$request = $this->getRequest ();
		$id = $request->getParam ( 'id', null );
		
		$images = new Admin_Model_ImageMapper (array('auth'=>$this->auth));
		$data = $images->find ( $id )->makeArray();

		$imageHelper = $this->_helper->getHelper ( 'Images' );
		$folder = $imageHelper->getFolder($data['path']);
		$thumb_dir = PUBLIC_PATH. $folder. DIRECTORY_SEPARATOR . '.thumb';
		
		$imageHelper->mk_dir(PUBLIC_PATH.$folder,'.thumb');
		
		$imageAbsPath = PUBLIC_PATH.$data['path'];
		$thumbAbsPath = $thumb_dir.DIRECTORY_SEPARATOR .$data['id'].'.png';
		$thumbRelPath = $folder.DIRECTORY_SEPARATOR .'.thumb'.DIRECTORY_SEPARATOR .$data['id'].'.png';
		
		$imageHelper->createthumb($imageAbsPath, $thumbAbsPath, 120, 80);
		
		$this->_flashMessenger->addMessage("Creato miniatura dell'immagine: ".$data['name'].'!');
		$data['thumbnail'] = $thumbRelPath;
		$images->update($data);
        $this->_redirect ( '/admin/image/listall' );
 	}
	
	/*
	 * 
	 */
	public function editAction() {
		// action body
		
		$request = $this->getRequest ();
		$form = new Admin_Form_ImageDetail ();
		$helper = $this->getHelper ( 'FormHandler' );
		
		if (!$request->isPost ()) {
			
			$id = $request->getParam ( 'id', null );
			$images = new Admin_Model_ImageMapper (array('auth'=>$this->auth));
			$data = $images->find ( $id )->makeArray();
			$data['description']= $data[$this->default_content];
			$helper->populate ( $form, $data );
			$this->view->data = $data;
			
		} else {
			// Does the POST contain the form namespace? If not, just render the form
			try {
				
				if (! $form->ifValidRemoveHash ( $request->getPost () ))
					throw new Exception ( 'Parametri non corretti' );
				
				$values = $form->getValues(true);
				$values['user_id']= $this->auth->getId();
				$values[$this->default_content]=$values['description'];
				unset($values['description']);
				
				$images = new Admin_Model_ImageMapper (array('auth'=>$this->auth));
				$images->update($values);
				
				$this->_redirect ( '/admin/image/listall' );
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->placeholder('error')->set($e->getMessage ());
			}
		}
		
		$form->setAction ( '/admin/image/edit#' )->setMethod ( 'post' );
		
		$helper->setReadonly ( $form, 'id' );
		$form->addElement ( 'submit', 'update', array ('label' => 'update', 'ignore' => 'true', 'required' => 'false' ) );
		
		$this->view->form = $form;
	}
	
	public function deleteAction() {
		// action body
		$request = $this->getRequest ();
		$form = new Admin_Form_Deleteimage();
		
		if (!$request->isPost ()) {
			$id = $request->getParam ( 'id', null );
			$images = new Admin_Model_ImageMapper (array('auth'=>$this->auth));
			$image = $images->find($id);
			$form->setDefaults( array('id' => $id,'imagename' => $image->imagename));
			
		} else {
			// Does the POST contain the form namespace? If not, just render the form
			try {
				
				if (! $form->isValid ( $request->getPost () ))
					throw new Exception ( 'Parametri non corretti' );
				$images = new Admin_Model_ImageMapper (array('auth'=>$this->auth));
				$id = $form->getElement ( 'id' )->getValue ();
				$images->delete($id);
			
				$this->_redirect ( '/admin/image/listall' );
			
			} catch ( Exception $e ) {
				/* rethrow it */
				$this->view->placeholder('error')->set($e->getMessage ());
				
			}
		
		}
		$this->view->form = $form;
		
	}
	
	public function browseAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/KcConfig.ini', 'browser' );
		
		$this->_helper->layout->disableLayout();
		$request = $this->getRequest();
		$params = $request->getParams();
		$browser = new Admin_Model_KCFinderBrowser($config, array('session_started' => true));
		$ret = $browser->action();
		$this->view->answers = $browser->getBody();
		$this->view->headers = $browser->getHeader();
		switch( $ret )
		{
			case Admin_Model_KCFinderBrowser::KC_DIE:
				$this->view->render('image/kc_die.phtml');
				break;
			case Admin_Model_KCFinderBrowser::KC_BROWSER_OK:
				$this->view->opener = $browser->getOpener();
				$this->view->session = $browser->getSession();
				$this->view->config = $browser->getConfig();
				$this->view->type = $browser->getType();
				$this->view->lang = $browser->getLang();
				$this->view->get = $browser->getGet();
				$this->view->cms = $browser->getCms();
				$this->view->label = $browser->getLabels();
				$this->view->version = $browser->getVersion();
				$content = $this->view->render('image/kc_browser.phtml');
				$this->getResponse()->appendBody($content);
				break;
				
			default:
				$this->view->render('image/kc_browse.phtml');
				break;
		}
	}
}















