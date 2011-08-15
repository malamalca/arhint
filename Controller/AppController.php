<?php
/**
 * Arhim: The Architectural practice
 *
 * @copyright     Copyright 2010, MalaMalca (http://malamalca.com)
 * @license       http://www.malamalca.com/licenses/lil_intranet.php
 */
$GLOBALS['email_types'] = array('P' => __('personal', true), 'W' => __('work', true)); 
$GLOBALS['phone_types'] = array('P' => __('personal', true), 'M' => __('mobile', true), 'W' => __('work', true), 'F' => __('fax', true), 'H' => __('home', true));
$GLOBALS['address_types'] = array('H' => __('home', true), 'W' => __('work', true));
/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package       arhim 
 * @subpackage    cake.app
 */
class AppController extends Controller {
/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('Session', 'Auth', 'RequestHandler');
/**
 * helpers property
 *
 * @var array
 * @access public
 */
	var $helpers = array('Session', 'Html', 'Lil.LilDate', 'Lil.LilFloat', 'Lil.LilForm');
/**
 * beforeFilter method
 *
 * @access public
 * @return void
 */
	function beforeFilter() {
		parent::beforeFilter();
		
		$this->Auth->authenticate = array(
			'Form' => array(
				'userModel' => 'Lil.User',
				'fields'    => array('username' => 'username', 'password' => 'passwd'),
			),
		);
		$this->Auth->loginRedirect	= '/admin';
		$this->Auth->loginAction	= '/lil/login';
		$this->Auth->logoutRedirect	= '/';
		$this->Auth->sessionKey		= 'Auth.User';
		
		/*$this->Auth->loginError		= __('Wrong username or password. Please try again.', true);
		$this->Auth->authError		= __('User must be logged in.', true);*/
		
		//$this->set('Auth', $this->Auth->user());	
	}
}