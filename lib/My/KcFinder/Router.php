<?php
class My_KcFinder_Router extends Zend_Controller_Router_Route_Static
{
	protected $_defaults = array();
	public function match($path, $partial = false)
	{
		$path = trim($path, '/');
		if( strncmp ( $path, $this->_route, strlen($this->_route)) != 0 )
		{
			return false;
		}
		$path = trim(substr($path, strlen($this->_route)), '/');
		$pathex = explode('/', $path);
		$front = Zend_Controller_Front::getInstance();
		$request = $front->getRequest();
		$defaults = $this->getDefaults();
		$params = $request->getParams();
		switch($pathex[0])
		{
			case 'style.css':
				switch($params['type'] )
				{
					default:
						$defaults['action'] = 'getcssimage';
						return $defaults;
				}
				break;
			case 'js':
				break;
				
			case 'gettranslation.php':
				$defaults['action'] = 'localize';
				return $defaults;
			case 'browse.php':
				if(!isset($params['act']) )
				{
					$defaults['action'] = 'browse';
				}
				else
				{
					$jump = array(
						'chDir'		=> 'chdir',
						'init'		=> 'browseinit',
						'cp_cbd'	=> 'copycbd',
						'mv_cbd'	=> 'movecbd',
						'rm_cbd'	=> 'removecbd'
					);
					
					if( array_key_exists($params['act'], $jump) )
					{
						$defaults['action'] = $jump[$params['act']];
					}
					else
					{
						$defaults['action'] = $params['act'];
					}
					
				}
				break;
				
			case 'getjoiner.js':
				$defaults['action'] = 'getjoiner';
				break;
				
			default:
				throw new Zend_Exception('invalid argument in router');
		}
		return $defaults;
	}
	
}