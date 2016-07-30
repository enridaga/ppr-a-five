<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Propagation;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module {
	public function onBootstrap(MvcEvent $e) {
		$eventManager = $e->getApplication ()->getEventManager ();
		$moduleRouteListener = new ModuleRouteListener ();
		$moduleRouteListener->attach ( $eventManager );
	}
	public static function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}
	public function getAutoloaderConfig() {
		return array (
				'Zend\Loader\StandardAutoloader' => array (
						'namespaces' => array (
								__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__ 
						) 
				) 
		);
	}
	
	public function getConsoleUsage($console) {
		$man = array();
		$c = self::getConfig();
		foreach($c['console']['router']['routes'] as $routeId => $route){
			$description = "\n\t".@$route['options']['description'];
			$first=true;
			foreach(@$route['options']['defaults'] as $defaultKey => $defaultValue){
				// Skip Zend Specific
				if($defaultKey == 'controller' || $defaultKey == 'action'){
					continue;
				}
				if($first){
					$description .= "\n\tDefaults:";
					$first = false;
				}
				$description .= "\n\t - ". $defaultKey . ': "' . $defaultValue . '".';
			}
			$man[$route['options']['route']] = $description; 
		}
		$man = array_merge($man, $c['console']['parameters']);
		return $man;
// 		array (
// 				// Describe available commands
// 				'user resetpassword [--verbose|-v] EMAIL' => 'Reset password for a user',
// 				'user resetpassword [--verbose|-v] EMAIL' => 'Reset password for a user',
				
// 				// Describe expected parameters
// 				array (
// 						'EMAIL',
// 						'Email of the user for a password reset' 
// 				),
// 				array (
// 						'--verbose|-v',
// 						'(optional) turn on verbose mode' 
// 				) 
// 		);
	}
}