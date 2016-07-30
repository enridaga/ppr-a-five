<?php

namespace Propagation\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class OperationController extends AbstractController {
	
	public function fillAction(){
		$cID = $this->getRequest()->getParam('cid');
		$dn = $this->getRequest()->getParam('branch');
		if(strpos($dn, 'http://') !== 0){
			$dn = "http://purl.org/datanode/ns/" . $dn;
		}
		$intersections = $this->_analysis()->intersections();
		$branches = $this->_branches();
		$extents = $this->_lattice()->extents();
		$intents = $this->_lattice()->intents();
		if (array_key_exists ( $dn, $intersections [$cID] )) {
			// take the missing datanode properties and set them to 1 in the context
			$missing = array_diff ( $branches [$dn], $extents [$cID] );
			if (empty ( $missing )) {
				print "Nothing to do.\n";
			} else {
				$type = "change context";
				$comment = "Extend coverage of branch $dn";
				print "Stack change: $type\n";
				print "Reason: $comment\n";
				$data = array ();
				foreach ( $missing as $m ) {
					foreach ( $intents [$cID] as $attr ) {
						array_push ( $data, array (
								$m,
								$attr,
								'1'
						) );
						print "$m,$attr,1\n";
					}
				}
				if ($this->_consoleConfirm ( "Are you sure you want to stack this change?" )) {
					// perform changes
					$this->_database()->addChange ( $type, serialize ( $data ), $comment );
				} else {
					print "Nothing to do.\n";
				}
			}
		} else {
			print "Not in the set of intersecting branches...\n";
		}
	}
	public function groupwithAction(){
		$cID = $this->getRequest()->getParam('cid');
		$with = $this->getRequest()->getParam('with');
		$under = $this->getRequest()->getParam('under');
		$type = "change ontology";
		$comment = "Group the extent with property $with under $under";
		print "Stack change: $type\n";
		print "Reason: $comment\n";
		
		$extents = $this->_lattice()->extents();
		$data = array ();
		$p = 'http://www.w3.org/2000/01/rdf-schema#subPropertyOf';
		array_push ( $data, array (
				'+',
				$with,
				$p,
				$under
		) );
		foreach ( $extents [$cID] as $e ) {
			array_push ( $data, array (
					'+',
					$e,
					$p,
					$with
			) );
		}
		foreach ( $data as $d ) {
			print implode ( ' ', $d ) . "\n";
		}
		if (confirm ( "Are you sure you want to stack this change?" )) {
			// perform changes
			$this->_database()->addChange ( $type, serialize ( $data ), $comment );
		} else {
			print print "Nothing to do.\n";
		}
	}
	public function addrelationAction(){
		$branch = $this->getRequest()->getParam('branch');
		$what = $this->getRequest()->getParam('relation');
		if(strpos($branch, 'http://') !==0){
			$branch = "http://purl.org/datanode/ns/" . $branch;
		}
		if(strpos($what, 'http://') !==0){
			$what = "http://purl.org/datanode/ns/" . $what;
		}
		$what = explode(',',$what);
		$under = $branch;
		$type = "change ontology";
		$comment = "Add to the sub properties of $under:\n ";
		$comment .= implode ( "\n ", $what ) . "\n";
		print "Stack change: $type\n";
		print "Reason: $comment\n";
		$data = array ();
		$p = 'http://www.w3.org/2000/01/rdf-schema#subPropertyOf';
		foreach ( $what as $w ) {
			array_push ( $data, array (
					'+',
					$w,
					$p,
					$under
			) );
		}
		foreach ( $data as $d ) {
			print implode ( ' ', $d ) . "\n";
		}
		if ($this->_consoleConfirm( "Are you sure you want to stack this change?" )) {
			// perform changes
			$this->_database()->addChange ( $type, serialize ( $data ), $comment );
		} else {
			print print "Nothing to do.\n";
		}
	}
	public function wedgeAction(){
		$branch = $this->getRequest()->getParam('branch');
		$what = $this->getRequest()->getParam('relation');
		$under = $branch;
		$type = "change ontology";
		$comment = "Wedge $what between $under and its direct subroperties";
		print "Stack change: $type\n";
		print "Reason: $comment\n";
		$data = array ();
		$p = 'http://www.w3.org/2000/01/rdf-schema#subPropertyOf';
		array_push ( $data, array (
				'+',
				$what,
				$p,
				$under
		) );
		
		// for each direct child of $dn
		$directChild = array ();
		foreach ( $triples as $t ) {
			if ($t ['o'] == $dn && $t ['p'] == $p) {
				array_push ( $data, array (
						'+',
						$t ['s'],
						$p,
						$what
				) );
				array_push ( $data, array (
						'-',
						$t ['s'],
						$p,
						$t ['o']
				) );
			}
		}
		foreach ( $data as $d ) {
			print implode ( ' ', $d ) . "\n";
		}
		if (confirm ( "Are you sure you want to stack this change?" )) {
			// perform changes
			$this->_database()->addChange ( $type, serialize ( $data ), $comment );
		} else {
			print print "Nothing to do.\n";
		}
	}
	public function removerelationAction(){
		$branch = $this->getRequest()->getParam('branch');
		$what = $this->getRequest()->getParam('relation');
		$under = $branch;
		$type = "change ontology";
		$comment = "Remove $what from the sub properties of $under";
		print "Stack change: $type\n";
		print "Reason: $comment\n";
		$data = array ();
		$p = 'http://www.w3.org/2000/01/rdf-schema#subPropertyOf';
		array_push ( $data, array (
				'-',
				$what,
				$p,
				$under
		) );
		foreach ( $data as $d ) {
			print implode ( ' ', $d ) . "\n";
		}
		if (confirm ( "Are you sure you want to stack this change?" )) {
			// perform changes
			$this->_database()->addChange ( $type, serialize ( $data ), $comment );
		} else {
			print print "Nothing to do.\n";
		}
	}
	
	
	public function changesAction() {
		$request = $this->getRequest ();
		$showData = $request->getParam ( 'v', FALSE );
		$change = $request->getParam ( 'change', FALSE );
		$database = $this->getServiceLocator ()->get ( 'database' );
		$changes = $database->changes ();
		ob_start ();
		$cc = 0;
		foreach ( $changes as $ch ) {
			$cc += 1;
			// change can be the change id or the index of the change.
			if ((! $change) || $ch [0] === $change || (intval ( $change ) >= $cc)) {
				print $this->_print_change ( $ch, $showData );
			}
		}
		return ob_get_clean ();
	}
	function _print_change($change, $showData = FALSE) {
		print "Id: " . $change [0] . "\n";
		print "Time: " . date ( DATE_ATOM, $change [1] ) . "\n";
		print "Type: " . $change [2] . "\n";
		print "Note: " . $change [4] . "\n";
		if ($showData) {
			print "Data: \n";
			$dt = unserialize ( $change [3] );
			// if type is change context
			if ($change [2] == "change context") {
				foreach ( $dt as $di ) {
					print implode ( ",", $di ) . "\n";
				}
			} elseif ($change [2] == "change ontology") {
				foreach ( $dt as $di ) {
					print implode ( ",", $di ) . "\n";
				}
			}
		}
		print "\n";
	}
}