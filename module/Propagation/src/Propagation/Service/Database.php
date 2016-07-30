<?php

namespace Propagation\Service;

use Propagation\Model\Datanode;
use Propagation\Model\PropagationRules;
use Propagation\Model\PropagationRulesMatrix;
use Propagation\Service\Logic;

class Database {
	public function __construct() {
	}
	
	public function datanode($changes = TRUE, $verbose = FALSE){
		return new \Propagation\Model\Datanode($this->_load_datanode ( $changes, $verbose ));
	}
	
	public function changes(){
		return $this->_list_changes();
	}
	
	public function addChange($type, $data, $comment){
		$config = \Propagation\Module::getConfig ();
		$changesFile = $config ['resources'] ['changes.db'];
		$id = md5 ( $type . $data . $comment, FALSE );
		$handle = fopen ( $changesFile, "a" );
		if ($handle) {
			fputcsv ( $handle, array (
					$id,
					time (),
					$type,
					$data,
					$comment
			) );
			fclose ( $handle );
		}
		return true;
	}
	
	/**
	 *
	 * @param integer $changes        	
	 * @return PropagationRules
	 */
	public function propagationRules($changes = TRUE, $verbose = FALSE) {
		$matrix = $this->_load_context ( $changes, $verbose );
		return new PropagationRulesMatrix ( $matrix );
	}
	
	public function propagationMatrix($changes = TRUE, $verbose = FALSE){
		return $this->_load_context ( $changes, $verbose );
	}
	
	private function _list_changes() {
		$config = \Propagation\Module::getConfig ();
		$changesFile = $config ['resources'] ['changes.db'];
		$handle = fopen ( $changesFile, "r" );
		if ($handle) {
			$lines = array ();
			while ( $line = fgetcsv ( $handle ) ) {
				$lines [$line [0]] = $line;
			}
			fclose ( $handle );
			if ($lines) {
				return $lines;
			}
		}
		return array ();
	}
	private function _load_context($applyChanges = true, $verbose = FALSE) {
		$config = \Propagation\Module::getConfig ();
		$contextFile = $config ['resources'] ['context.csv'];
		
//		$verbose = TRUE; // / TODO get this value somewhere
		
		$handle = fopen ( $contextFile, "r" );
		if ($handle) {
			$matrix = array ();
			while ( ($line = fgets ( $handle )) !== false ) {
				$cell = explode ( ",", trim ( $line ) );
				array_push ( $matrix, $cell );
			}
			fclose ( $handle );
		} else {
			print "Cannot open the context file";
			exit ( 1 );
		}
		
		if ($applyChanges) {
			// Collect changes
			$changes = $this->_list_changes ();
			// print_r($changes);die;
			$matrixChanges = array ();
			$visited = 0;
			foreach ( $changes as $change ) {
				if ($change [2] == "change context") {
					$data = unserialize ( $change [3] );
					foreach ( $data as $cell ) {
						$matrixChanges [$cell [0] . ',' . $cell [1]] = $cell [2];
					}
				}
				$visited ++;
				if ($applyChanges !== TRUE) {
					// echo "shit"; var_dump($applyChanges); die;
					if ($visited == $applyChanges) {
						break;
					}
				}
			}
			
			if (! empty ( $matrixChanges )) {
				if ($verbose)
					print "Applying changes to context\n";
					// Apply changes to the matrix
				$cellsFound = array ();
				foreach ( $matrix as $idx => &$cell ) {
					$kkk = $cell [0] . ',' . $cell [1];
					if (array_key_exists ( $kkk, $matrixChanges )) {
						if ($verbose)
							print 'Apply change to ' . $kkk . ' from ' . $cell [2] . ' to ' . $matrixChanges [$kkk] . "\n";
						$cell [2] = $matrixChanges [$kkk];
						array_push ( $cellsFound, $kkk );
					}
				}
				foreach ( $matrixChanges as $kkk => $val ) {
					if (in_array ( $kkk, $cellsFound )) {
						continue;
					}
					if ($verbose)
						print "Add cell $kkk,$val to matrix\n";
					$sp = explode ( ',', $kkk );
					$cell = array (
							$sp [0],
							$sp [1],
							$val 
					);
					array_push ( $cellsFound, $kkk );
					array_push ( $matrix, $cell );
				}
				if ($verbose)
					print "Done.\n\n";
			}
		}
		return $matrix;
	}
	
	private function _load_datanode($applyChanges = TRUE, $verbose = FALSE) {

		$config = \Propagation\Module::getConfig ();
		require_once $config ['constants']['arc2'];
		$datanode = $config ['constants']['datanode'];
		// BRANCHES
		$parser = \ARC2::getRDFParser ();
		$parser->parse ( $datanode );
		$triples = $parser->getTriples ();
//var_dump($applyChanges);die;
		if ($applyChanges) {
			// APPLY CHANGES TO DATANODE
			$changes = $this->_list_changes ();
			$addTriples = array ();
			$removeTriples = array ();
			$visited = 0;
			foreach ( $changes as $change ) {
				if ($change [2] == "change ontology") {
					if ($verbose)
						print "[load change] " . $change [0] . ".\n";
					$data = unserialize ( $change [3] );
					foreach ( $data as $trch ) {
						$t = array (
								'type' => 'triple',
								's' => $trch [1],
								'p' => $trch [2],
								'o' => $trch [3],
								's_type' => 'uri',
								'p_type' => 'uri',
								'o_type' => 'uri',
								'o_datatype' => NULL,
								'o_lang' => NULL 
						);
						if ($trch [0] == '+') {
							array_push ( $addTriples, $t );
						} else if ($trch [0] == '-') {
							array_push ( $removeTriples, $t );
						}
					}
				}else{
					if ($verbose)
						print "[load change] " . $change [0] . " (inconsequential).\n";
				}
				$visited ++;
				if ($applyChanges !== TRUE) {
					if ($visited == $applyChanges) {
						break;
					}
				}
			}
			
			// Perform changes on triples
			foreach ( $addTriples as $t ) {
				if ($verbose)
					print "[apply] Adding " . implode ( ' ', $t ) . ".\n";
				array_push ( $triples, $t );
			}
			foreach ( $removeTriples as $t ) {
				if ($verbose)
					print "[apply] Removing " . implode ( ' ', $t ) . " .\n";
				if (in_array ( $t, $triples )) {
					$keys = array_keys ( $triples, $t );
					foreach ( $keys as $k ) {
						unset ( $triples [$k] );
					}
				}
			}
			$triples = array_values ( $triples );
			//
		}
		return $triples;
	}
}