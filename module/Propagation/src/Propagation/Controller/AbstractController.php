<?php

namespace Propagation\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Propagation\Service\Logic;

class AbstractController extends AbstractActionController {
	private $_lattice;
	private $_matrix;
	private $_changes;
	private $_verbose;
	private $_database;
	private $_datanode;
	private $_branches;
	private $_analysis;
	private $_logic;
	protected function _verbose() {
		if (! $this->_verbose) {
			$request = $this->getRequest ();
			$verbose = $request->getParam ( 'v', FALSE );
			$this->_verbose = $verbose;
		}
		return $this->_verbose;
	}
	/**
	 * 
	 * @return Logic
	 */
	protected function _logic() {
		if (! $this->_logic) {
			$this->_logic = $this->getServiceLocator ()->get ( 'logic' );
		}
		return $this->_logic;
	}
	
	/**
	 * Id is generated using a CRC32 hash function with two concatenated string,
	 * being the full uris of the deontic property and the action.
	 * Eg: "http://www.w3.org/ns/odrl/2/permission http://www.w3.org/ns/odrl/2/use"
	 *
	 * @return number
	 */
	public static function build_policy_id($deontic_uri, $action_uri) {
		return crc32 ( $deontic_uri . $action_uri );
	}
	
	protected function _makeRules($cid = NULL, $compress = false) {
		$logic = $this->_logic ();
		$lattice = $this->_lattice ();
		$flat = array ();
		$abstracted = array ();
		if ($cid) {
			$concept = $lattice->concept ( $cid );
			$flat = $logic->makeRules ( array (
					$cid => $concept ['extent'] 
			), array (
					$cid => $concept ['intent'] 
			) );
			if ($compress) {
				$abstracted = $logic->makeAbstracted ( $this->_analysis ()->matches ( $cid ), $lattice->intents () );
			}
		} else {
			$flat = $logic->makeRules ( $lattice->extents (), $lattice->intents () );
			if ($compress) {
				$abstracted = $logic->makeAbstracted ( $this->_analysis ()->matches (), $lattice->intents () );
			}
		}
		
		if ($compress) {
			$rules = array ();
			foreach ( $flat as $fr ) {
				if (! in_array ( $fr, $abstracted )) {
					array_push ( $rules, $fr );
				}
			}
			return $rules;
		} else {
			return $flat;
		}
	}
	protected function _database() {
		if (! $this->_database) {
			$this->_database = $this->getServiceLocator ()->get ( 'database' );
		}
		return $this->_database;
	}
	protected function _datanode() {
		if (! $this->_datanode) {
			$this->_datanode = $this->_database ()->datanode ();
		}
		return $this->_datanode;
	}
	protected function _changes() {
		if (! $this->_changes) {
			$request = $this->getRequest ();
			$changes = $request->getParam ( 'changes', "all" );
			if ($changes == 'all') {
				$changes = TRUE;
			}
			$this->_changes = $changes;
		}
		return $this->_changes;
	}
	protected function _matrix() {
		if (! $this->_matrix) {
			$this->_matrix = $this->_database ()->propagationMatrix ( $this->_changes (), $this->_verbose () );
		}
		return $this->_matrix;
	}
	
	/**
	 * return \Propagation\Model\Lattice
	 */
	protected function _lattice() {
		if (! $this->_lattice) {
			$fca = $this->getServiceLocator ()->get ( 'fca' );
			$this->_lattice = $fca->lattice ( $this->_matrix () );
		}
		return $this->_lattice;
	}
	
	/**
	 *
	 * @return \Propagation\Model\Analysis
	 */
	protected function _analysis() {
		if (! $this->_analysis) {
			$this->_analysis = new \Propagation\Model\Analysis ( $this->_datanode (), $this->_lattice () );
		}
		return $this->_analysis;
	}
	/**
	 */
	protected function _branches() {
		if (! $this->_branches) {
			$this->_branches = $this->_datanode ()->branches ();
		}
		return $this->_branches;
	}
	protected function _consoleConfirm($msg) {
		$confirmation = $this->_consoleInput ( $msg );
		if ($confirmation !== 'y') {
			// The user did not say 'y'.
			return FALSE;
		}
		return TRUE;
	}
	protected function _consoleInput($msg) {
		print $msg . "\n";
		flush ();
		@ob_flush ();
		return trim ( fgets ( STDIN ) );
	}
	protected function _render_table(array $rows, $delimiter = '  ') {
		$maxes = array ();
		foreach ( $rows as $row ) {
			$i = 0;
			foreach ( $row as $key => $value ) {
				if (is_array ( $value ))
					$value = implode ( ',', $value );
				$l = strlen ( $value );
				if (! isset ( $maxes [$i] )) {
					$maxes [$i] = 0;
				}
				if ($l > $maxes [$i]) {
					$maxes [$i] = $l;
				}
				$i ++;
			}
		}
		
		foreach ( $rows as $row ) {
			$i = 0;
			foreach ( $row as $key => $value ) {
				if (is_array ( $value ))
					$value = implode ( ',', $value );
				print str_pad ( $value, $maxes [$i] );
				$i ++;
				print $delimiter;
			}
			print "\n";
		}
	}
}