<?php

namespace Propagation\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Propagation\Service\Logic as Logic;

class DatanodeController extends AbstractController {
	public function branchesAction() {
		return $this->_render_branches ( $this->_datanode ()->branches () );
	}
	public function treeAction() {
		$rel = $this->getRequest ()->getParam ( 'branch', NULL );
		if ($rel != NULL && strpos ( $rel, 'http://' ) !== 0) {
			$rel = "http://purl.org/datanode/ns/" . $rel;
		}
		$max = $this->getRequest ()->getParam ( 'depth', NULL );
		return $this->_render_tree ( $this->_datanode ()->tree (), $max, $rel );
	}
	public function relationsAction() {
		$r = $this->_datanode ()->relations ();
		sort ( $r );
		return $this->_render_relations ( $r );
	}
	private $_wasFound = FALSE;
	private function _render_tree($tree, $maximumDepth = FALSE, $focus = NULL, $currentDepth = 0) {
		
		if ($this->_wasFound) {
			return;
		}
		if ($focus == NULL) {
			$focus = TRUE; // First call, display everything
		}
		ob_start ();
		if ($maximumDepth) {
			if ($currentDepth >= $maximumDepth) {
				return;
			}
		}
		foreach ( $tree as $item => $children ) {
			//print ('Test: '. $item . " -$focus- -". $this->_wasFound. "-\n" );
			if ($item == $focus) {
				$nextFocus = TRUE;
			} else if ($focus !== TRUE) {
				$nextFocus = $focus;
			} else {
				$nextFocus = $focus;
			}
			if ($nextFocus === TRUE) {
				print str_repeat ( ' ', $currentDepth ) . $item . "\n";
				$newDepth = $currentDepth + 1;
			} else {
				$newDepth = $currentDepth;
			}
			print $this->_render_tree ( $children, $maximumDepth, $nextFocus, $newDepth );
			if ($item === $focus) {
				$this->_wasFound = TRUE;
			}
		}
		
		return ob_get_clean ();
	}
	private function _render_branches($branches) {
		$return = '';
		foreach ( $branches as $dn => $br ) {
			$return .= "$dn\n";
			foreach ( $br as $b ) {
				$return .= " - $b\n";
			}
		}
		return $return;
	}
	private function _render_relations($relations) {
		$return = '';
		foreach ( $relations as $b ) {
			$return .= $b . "\n";
		}
		return $return;
	}
}