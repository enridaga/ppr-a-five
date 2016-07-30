<?php

namespace Propagation\Service;

use Propagation\Model\PropagationRules;
use Propagation\Model\PropagationRulesMatrix;
use Propagation\Model\Lattice as Lattice;
use \Propagation\Module;

class FCA {
	public function __construct() {
	}
	public function lattice(array $context){
		return new Lattice(lattice($context));
	}
}

function sort_matrix_by_attribute(array $c1, array $c2){
	$o1 = $c1[1];
	$o2 = $c2[1];
	if(strcmp($o1, $o2) === 0) return 0;
	return $o1 < $o2 ? 1 : -1;
}

function lattice(array $matrix){
	// 1. write the attribute extents to a list

	// sort the matrix by object
	usort($matrix, 'Propagation\Service\sort_matrix_by_attribute');

	//foreach($matrix as $cell) print $cell[0]."\n";die;
	$_extents = array ();
	$_lastAttribute = NULL;
	$_objects = array ();
	$_first = TRUE;
	foreach ( $matrix as $cell ) {
		if ( $cell [2] == "0") {
			continue;
		}
		$_o = $cell [0];
		$_a = $cell [1];
		// 		print "$_o $_a\n";
		if ($_a != $_lastAttribute) {
			sort ( $_objects );
			if (! in_array ( $_objects, $_extents )) {
				if ($_first) {
					$_first = FALSE;
				} else {
					// 					print " = " . count($_objects) . "\n";
					array_push ( $_extents, $_objects );
				}
			}else{
				// 				print count($_objects) . "\n";
			}
			// 			print $_a . "\n";
			$_objects = array ();
		}
		if(!in_array($_o, $_objects)){
			array_push ( $_objects, $_o );
			sort ( $_objects );
		}
		$_lastAttribute = $_a;
	}
	// Add last item in the resultset
	if (! in_array ( $_objects, $_extents ) && $_first != TRUE) {
		array_push ( $_extents, $_objects );
	}
	$objects = $_extents;
	// 	var_dump($objects); die;
	//  print '<pre>';
	//  print "\nExtents: " . print_r($objects, TRUE);
	// 2. Compute all pairwise intersections

	$cycle = TRUE;
	while($cycle){
		$cycle = FALSE;
		foreach($objects as $obj){
			foreach($objects as $obj2){
				$intersect = array_intersect($obj, $obj2);
				sort($intersect);
				if( !in_array($intersect, $objects) ){
					array_push($objects, $intersect);
					$cycle = TRUE;
				}
			}
		}
	}
	// Add G
	$G = array();
	foreach($objects as $arr){
		$G = array_merge($arr, $G);
	}
	$G = array_unique($G);
	sort($G);
	if(!in_array($G, $objects)){
		array_push($objects, $G);
	}
	// empty extent
	//array_push($objects, array());
	$extents = $objects;
	sort($extents);
	$concepts = array();

	$allobjects = array();
	$allattributes = array();
	foreach($matrix as $cell){
		if(!in_array($cell[0], $allobjects)){
			array_push($allobjects, $cell[0]);
		}
		if(!in_array($cell[1], $allattributes)){
			array_push($allattributes, $cell[1]);
		}
	}

	sort($allattributes);
	sort($allobjects);

	//  		print "\nObjects: " . join(',',$allobjects);
	//  		print "\nAttributes: " . join(',',$allattributes);
	// 3. Compute the intents
	foreach($extents as $extent){
		$intent = array();
		//print implode(',',$extent) . "\n";
			
		// COMPUTE THE INTENT OF THE CONCEPT
		$_objects = $extent;
		$_intent = array ();
		$_o = NULL;
		$_a = NULL;
		$_allAttributes = array ();
		foreach ( $matrix as $cell ) {
			if ($cell [2] == "0") {
				continue;
			}else if(!in_array($cell[0], $_objects)){
				continue;
			}
			$_o = $cell [0];
			$_a = $cell [1];
			if (! in_array ( $_a, $_allAttributes )) {
				array_push ( $_allAttributes, $_a );
			}
			if (! isset ( $_intent [$_o] )) {
				$_intent [$_o] = array ();
			}
			array_push ( $_intent [$_o], $_a );
		}
		$_result = NULL;
		if (empty ( $_objects )) {
			sort ( $_allAttributes );
			$_result = $_allAttributes;
		} else {
				
			if (count ( array_values ( $_intent ) ) > 1) {
				$_result = call_user_func_array ( 'array_intersect', array_values ( $_intent ) );
			} else if (count ( array_values ( $_intent ) ) == 1) {
				$ival = array_values ( $_intent );
				$_result = array_pop ( $ival );
				unset($ival);
			} else {
				$_result = array ();
			}
			sort ( $_result );
		}
		$intent = $_result;


		if(! (empty($extent) && empty($intent))){
			if($intent == $allattributes || $extent == array()){
				$topconceptfound = TRUE;
			}
			if($extent == $allobjects || $intent == array()){
				$bottomconceptfound = TRUE;
			}
			array_push($concepts, array($extent, $intent));
		}
	}
	// 	print "\nConcepts: " . print_r($concepts, TRUE);
	if(!isset($topconceptfound)){
		// Add empty extent (only if needed!)
		//  			print "\nAdd empty extent. ";
		array_push($concepts, array(array(), $allattributes));
	}
	if(!isset($bottomconceptfound)){
		// Add empty intent (only if needed!)
		//  			print "\nAdd empty intent";
		array_push($concepts, array($allobjects, array()));
	}

	// Compute the relation "is subconcept of"
	$subrelations = array();
	foreach($concepts as $c1key => $concept1){
		foreach($concepts as $c2key => $concept2){
			if($concept1 == $concept2){
				// Do nothing
			}else // We compute the intersection of extents
				if ( array_intersect($concept1[0], $concept2[0]) == $concept1[0] ){
				// concept1 is sub concept of concept2
				if(!isset($subrelations[$c1key])){
					$subrelations[$c1key] = array();
				}
				$skip = FALSE;
				foreach($subrelations[$c1key] as &$superc){
					// If c2 is subconcept of an already visited superconcept, replace the old one with c2
					if(array_intersect($concept2[0], $concepts[$superc][0]) == $concept2[0]){
						$superc = $c2key;
						//print "\n " . print_r($concept2[0], true) . " is subconcept of " . print_r($concepts[$superc][0], true);
					}

					// If c2 is superconcept of a visited concept, ignore it
					if(array_intersect($concepts[$superc][0], $concept2[0]) == $concepts[$superc][0]){
						$skip = TRUE;
						//print "\n " . print_r($concept2[0], true) . " is superconcept of " . print_r($concepts[$superc][0], true);
					}

				}
				// If it is not there, add it
				if(!$skip && !in_array($c2key, $subrelations[$c1key])){
					array_push($subrelations[$c1key], $c2key);
				}
			}
		}
	}

	// XXX If there is only 1 concept we link it to itself
	if(empty($subrelations) && count($concepts) == 1){
		$subrelations[0] = array(0);
	}
	// 		print "\nConcepts:";
	//  		print_r($concepts);
	// 		print "\nSubrelations:";
	//  		print_r($subrelations);
	//  		print '</pre>';
	//  		die;
	// Build graph from subrelations
	// 	$taxonomy = array();
	// 	foreach($subrelations as $ck => $sckeys){
	// 		foreach($sckeys as $sck){
	// 			$rel = array($concepts[$ck], $concepts[$sck]);
	// 			if (! in_array ( $rel, $taxonomy )) {
	// 				array_push ( $taxonomy, $rel );
	// 			}
	// 		}
	// 	}

	return array($concepts, $subrelations);
}

function printLattice($lattice){
	$concepts = $lattice[0];
	$subrelations = $lattice [1];
	foreach($concepts as $ix => $concept){
		print "$ix";
		print " (" . count($concept[0]) ." " . count($concept[1]) .")";
		print "\t";
		print @implode(',', $subrelations[$ix] );
		print "\n";
	}
}
