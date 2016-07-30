<?php
namespace Propagation\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class LatticeController extends AbstractController {
	
	public function latticeAction(){
		$concepts = $this->_lattice()->concepts();
		$subrelations = $this->_lattice()->hierarchy();
		foreach($concepts as $cid => $concept){
			print $cid . " [" . count($concept[0]) . "/" . count($concept[1]) . "]";
			print ": " .implode(",", $subrelations[$cid]);
			print "\n";
		}
	}
	
	public function browseAction() {
		$ids = $this->getRequest ()->getParam ( 'cid', "*" );
		$this->_browseAction ( $ids );
	}
	public function _browseAction($ids) {
		$do = $this->_infoAction ( $ids );
		print $do;
		$input = $this->_consoleInput ( "Next concept id:" );
		if ($input !== "") {
			$this->_browseAction ( $input );
		}
	}
	public function _infoAction($ids) {
		$max = $this->getRequest ()->getParam ( 'max', - 1 );
	
		if ($ids == "*") {
			$ids = $this->_lattice ()->conceptIds ();
		} else {
			$ids = explode ( ',', $ids );
		}
	
		// console only
		ob_start ();
		foreach ( $ids as $id ) {
			// print $id . "\n";
			print $this->_render_concept ( $this->_lattice ()->concept ( $id ), $max );
			$cflcs = $this->_analysis()->conflicts($id);
			$cflsno = count($cflcs);
			print "\n Conflicts: $cflsno";
			print "\n";
			$this->_render_table($cflcs );
		}
	
		return ob_get_clean ();
	}
	function _render_list($list) {
		foreach ( $list as $d ) {
			print " - $d\n";
		}
	}
	public function infoAction() {
		$ids = $this->getRequest ()->getParam ( 'cid', "*" );
		return $this->_infoAction ( $ids );
	}
	public function relationsAction() {
		$lattice = $this->_lattice ();
		$ids = $this->getRequest ()->getParam ( 'cid', "*" );
		if ($ids == "*") {
			$ids = $lattice->conceptIds ();
		} else {
			$ids = explode ( ',', $ids );
		}
		// console only
		ob_start ();
		foreach ( $ids as $id ) {
			print "$id ". join("\n$id ", $lattice->concept ( $id )['extent']) . "\n";
		}
	
		return ob_get_clean ();
	}
	public function policiesAction() {
		$ids = $this->getRequest ()->getParam ( 'cid', "*" );
		if ($ids == "*") {
			$ids = $this->_lattice ()->conceptIds ();
		} else {
			$ids = explode ( ',', $ids );
		}
		// console only
		ob_start ();
		foreach ( $ids as $id ) {
			print "$id ". join("\n$id ", $this->_lattice()->concept ( $id )['intent']) . "\n";
		}
		
		return ob_get_clean ();
	}

	function _render_concept($concept, $max = -1) {
		$cID = $concept ['id'];
	
		$extent = $concept ['extent'];
		$intent = $concept ['intent'];
		$ancestors = @implode ( ',', @$concept ['ancestors'] );
		$descendants = @implode ( ',', @$concept ['descendants'] );
		ob_start ();
		print "Concept $cID\n";
		print "Ancestors: $ancestors\n";
		print "Descendants: $descendants\n";
		$ne = count ( $extent );
		$ni = count ( $intent );
		$nr = $ne > $ni ? $ne : $ni;
		$eml = 0;
		$iml = 0;
		$rows = array (
				array (
						"Extent [$ne]",
						"Intent [$ni]"
				)
		);
		for($r = 0; $r < $nr; $r ++) {
			$e = '';
			if (array_key_exists ( $r, $extent )) {
				$e = $extent [$r];
				$eml = (strlen ( $e ) > $eml) ? strlen ( $e ) : $eml;
			}
			$i = '';
			if (array_key_exists ( $r, $intent )) {
				$i = $intent [$r];
				$iml = (strlen ( $i ) > $iml) ? strlen ( $i ) : $iml;
			}
			array_push ( $rows, array (
					$e,
					$i
			) );
		}
		$printed = 0;
		$head = true;
		foreach ( $rows as $row ) {
			if ($printed == $max){
				break;
			}
			$pe = str_pad ( $row [0], $eml + 2 );
			$pi = $row [1];
			print "$pe $pi\n";
			if ($head) {
				$head = false;
			} else {
				$printed += 1;
			}
		}
		print "\n";
		return ob_get_clean ();
	}
}