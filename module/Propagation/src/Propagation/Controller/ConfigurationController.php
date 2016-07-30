<?php

namespace Propagation\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ConfigurationController extends AbstractActionController {
	public function showAction() {
		$config = $this->getServiceLocator()->get('config');
		return "Resources:\n- " . implode($config ['resources'],"\n- ") . "\n";
	}
}