<?php

namespace Propagation\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Console\Request as ConsoleRequest;
use Zend\Http\Request as HttpRequest;
use RuntimeException;
use Propagation\Model\PropagationRulesMatrix;

class RulesController extends AbstractController {
	public function matrixAction() {
		$format = $this->getRequest ()->getParam ( 'format', 'matrix' );
		$matrix = $this->_matrix ();
		$pprm = new PropagationRulesMatrix ( $matrix );
		return $this->renderMatrix ( $pprm->query (), $format );
	}
	public function flatAction() {
		$format = $this->getRequest ()->getParam ( 'format', 'matrix' );
		$cid = $this->getRequest ()->getParam ( 'cid', NULL );
// 		$logic = $this->getServiceLocator ()->get ( 'logic' );
// 		$lattice = $this->_lattice ();
// 		if ($cid) {
// 			$concept = $lattice->concept ( $cid );
// 			$rules = $logic->makeRules ( array (
// 					$cid => $concept ['extent'] 
// 			), array (
// 					$cid => $concept ['intent'] 
// 			) );
// 		} else {
// 			$rules = $logic->makeRules ( $lattice->extents (), $lattice->intents () );
// 		}
		return $this->renderRules ( $this->_makeRules($cid, FALSE), $format );
	}
	public function abstractedAction() {
		// Compute abstracted rules
		$format = $this->getRequest ()->getParam ( 'format', 'matrix' );
		$cid = $this->getRequest ()->getParam ( 'cid', NULL );
		$logic = $this->getServiceLocator ()->get ( 'logic' );
		$lattice = $this->_lattice ();
		if ($cid) {
			$rules = $logic->makeAbstracted ( $this->_analysis ()->matches ( $cid ), $lattice->intents () );
		} else {
			$rules = $logic->makeAbstracted ( $this->_analysis ()->matches (), $lattice->intents () );
		}
		return $this->renderRules ( $rules, $format );
	}
	public function compressedAction() {
		// Compute compressed rule base
		// Compute abstracted rules
		$format = $this->getRequest ()->getParam ( 'format', 'matrix' );
		$cid = $this->getRequest ()->getParam ( 'cid', NULL );
// 		$logic = $this->getServiceLocator ()->get ( 'logic' );
// 		$lattice = $this->_lattice ();
// 		if ($cid) {
// 			$concept = $lattice->concept ( $cid );
// 			$flat = $logic->makeRules ( array (
// 					$cid => $concept ['extent'] 
// 			), array (
// 					$cid => $concept ['intent'] 
// 			) );
// 			$abstracted = $logic->makeAbstracted ( $this->_analysis ()->matches ( $cid ), $lattice->intents () );
// 		} else {
// 			$flat = $logic->makeRules ( $lattice->extents (), $lattice->intents () );
// 			$abstracted = $logic->makeAbstracted ( $this->_analysis ()->matches (), $lattice->intents () );
// 		}
// 		$rules = array ();
// 		foreach ( $flat as $fr ) {
// 			if (! in_array ( $fr, $abstracted )) {
// 				array_push ( $rules, $fr );
// 			}
// 		}
		return $this->renderRules ( $this->_makeRules($cid, TRUE), $format );
	}
	
	private function renderRules(array $rules, $format = 'matrix') {
		ob_start ();
		switch ($format) {
			case 'matrix' :
				foreach ( $rules as $rule ) {
					print $rule [0];
					print ' ';
					print $rule [1];
					print "\n";
				}
				break;
		}
		return ob_get_clean ();
	}
	private function renderMatrix($results, $format) {
		if ($this->getRequest () instanceof ConsoleRequest) {
			switch ($format) {
				case 'csv' :
					return $this->_render_matrix_csv ( $results, "," );
				case 'matrix' :
				default :
					return $this->_render_matrix_csv ( $results, "\t" );
			}
		} else
			throw new RuntimeException ();
	}
	private function _render_matrix_csv($rules, $del = " ") {
		$return = '';
		foreach ( $rules as $rule ) {
			$return .= $rule [0] . $del . $rule [1] . $del . $rule [2] . "\n";
		}
		return $return;
	}
}