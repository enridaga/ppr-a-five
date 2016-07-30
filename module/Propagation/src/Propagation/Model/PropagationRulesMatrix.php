<?php

namespace Propagation\Model;

class PropagationRulesMatrix implements PropagationRules {
	private $_matrix = array ();
	public function __construct(array $matrix) {
		$this->_matrix = $matrix;
	}
	public function query($relation = NULL, $policy = NULL, $holds = NULL) {
		$results = array ();
		foreach ( $this->_matrix as $cell ) {
			$include = TRUE;
			if ($include && isset ( $holds )) {
				$include = $cell [2] == $holds;
			}
			if ($include && isset ( $relation )) {
				$include = preg_match ( '/' . $relation . '/i', $cell [0] ) ? TRUE : FALSE;
			}
			if ($include && isset ( $policy )) {
				$include = preg_match ( '/' . $policy . '/i', $cell [1] ) ? TRUE : FALSE;
			}
			if ($include) {
				array_push ( $results, $cell );
			}
		}
		return $results;
	}
}