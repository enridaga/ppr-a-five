<?php

namespace Propagation\Service;

use Propagation\Model\PropagationRules;
use Propagation\Model\PropagationRulesMatrix;
use Propagation\Model\Analysis;
use \Propagation\Module;

class Logic {
	public function __construct() {
	}
	public function makeAbstracted(array $matches, array $intents, $renderer = NULL){
		$rules = array ();
		foreach ( $matches as $c => $branch ) {
			foreach ( $branch as $bid => $subprops ) {
				foreach ( $subprops as $subprop ) {
					if ($bid == $subprop)
						continue; // we don't abstract with himself!
					if (array_key_exists ( $c, $intents )) {
						foreach ( $intents [$c] as $int ) {
						if ($renderer != NULL) {
							if (is_array ( $renderer )) {
								$object = $renderer [0];
								$method = $renderer [1];
								$rule = $object->$method ( $subprop, $int );
							} else if (is_callable ( $renderer )) {
								$rule = $renderer ( $subprop, $int );
							}else{
								throw new \RuntimeException();
							}
						} else {
							$rule = array (
									$subprop,
									$int 
							);
						}
						if(!in_array($rule, $rules)){
							array_push ( $rules, $rule );
						}
						}
					}
				}
			}
		}
		sort ( $rules );
		return $rules;
	}
	public function makeRules(array $extents, array $intents, $renderer = NULL) {
		$rules = array ();
		// Let's apply the rules when there is a full match
		foreach ( $extents as $c => $ext ) {
			foreach ( $ext as $e ) {
				if (array_key_exists ( $c, $intents )) {
					foreach ( $intents [$c] as $int ) {
						if ($renderer != NULL) {
							if (is_array ( $renderer )) {
								$object = $renderer [0];
								$method = $renderer [1];
								$rule = $object->$method ( $e, $int );
							} else if (is_callable ( $renderer )) {
								$rule = $renderer ( $e, $int );
							}else{
								throw new \RuntimeException();
							}
						} else {
							$rule = array (
									$e,
									$int 
							);
						}
						if(!in_array($rule, $rules)){
							array_push ( $rules, $rule );
						}
					}
				}
			}
		}
		sort ( $rules );
		return $rules;
	}
	
}