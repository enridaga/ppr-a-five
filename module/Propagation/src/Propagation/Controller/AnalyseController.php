<?php

namespace Propagation\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AnalyseController extends AbstractController {
	public function reldiffAction() {
		$left = $this->getRequest ()->getParam ( 'left' );
		$right = $this->getRequest ()->getParam ( 'right' );
		if (strpos ( $left, 'http://' ) !== 0) {
			$left = 'http://purl.org/datanode/ns/' . $left;
		}
		if (strpos ( $right, 'http://' ) !== 0) {
			$right = 'http://purl.org/datanode/ns/' . $right;
		}
		$columns = $this->_analysis ()->relationsDiff ( $left, $right );
		$rows = array (
				array (
						$left,
						$right 
				) 
		);
		$clno = 2;
		$rowmax = 0;
		foreach ( $columns as $column ) {
			$csize = count ( $column );
			if ($csize > $rowmax) {
				$rowmax = $csize;
			}
		}
		// print $clno . "\n"; die;
		$clix = 0;
		foreach ( $columns as $column ) {
			for($x = 0; $x < $rowmax; $x ++) {
				if (! isset ( $rows [$x + 1] )) {
					$rows [$x + 1] = array ();
					for($y = 0; $y < $clno; $y ++) {
						$rows [$x + 1] [$y] = '-';
					}
				}
				if (isset ( $column [$x] )) {
					$rows [$x + 1] [$clix] = $column [$x];
				}
			}
			$clix ++;
		}
		return $this->_render_table ( $rows );
	}
	public function conflictAction() {
		$ids = $this->getRequest ()->getParam ( 'cid', "*" );
		$diff = $this->getRequest ()->getParam ( 'diff', FALSE );
		if ($ids == '*') {
			$ids = $this->_lattice ()->conceptIds ();
		} else {
			$ids = explode ( ',', $ids );
		}
		$d = $this->getRequest ()->getParam ( 'd', FALSE );
		$v = $this->_verbose ();
		$conflicts = array (
				array (
						'Cluster',
						'Branch',
						'Relation' 
				) 
		);
		foreach ( $ids as $cid ) {
			foreach ( $this->_analysis ()->conflicts ( $cid, $d ) as $confix => $conflict ) {
				if(isset($conflicts[$confix])){
					$conflicts[$confix]['c'] = array_merge ( $conflicts[$confix]['c'], $conflict['c'] );
				}else{
					$conflicts[$confix] = $conflict;
				}
			}
		}
		print "\nFound " . (count ( $conflicts ) - 1) . " conflicts.";
		if (count ( $conflicts ) - 1) {
			print "\n";
			$this->_render_table ( $conflicts );
		}
	}
	public function queryAction() {
		$analysis = $this->_analysis ();
		
		$request = $this->getRequest ();
		$details = $request->getParam ( 'd', FALSE );
		$options = array ();
		$ids = $request->getParam ( 'cid', "*" );
		if ($ids != "*") {
			$options ['c'] = $ids;
		}
		
		$pre = $request->getParam ( 'pre', FALSE );
		$rec = $request->getParam ( 'rec', FALSE );
		$es = $request->getParam ( 'es', FALSE );
		$bs = $request->getParam ( 'bs', FALSE );
		$is = $request->getParam ( 'is', FALSE );
		$branch = $request->getParam ( 'branch', FALSE );
		$sort = $request->getParam ( 'by', FALSE );
		if ($pre)
			$options ['pre'] = $pre;
		if ($rec)
			$options ['rec'] = $rec;
		if ($es)
			$options ['es'] = $es;
		if ($bs)
			$options ['bs'] = $bs;
		if ($is)
			$options ['is'] = $is;
		if ($branch) {
			if (strpos ( $branch, 'http://' ) !== 0) {
				$branch = "http://purl.org/datanode/ns/" . $branch;
			}
			$options ['branch'] = $branch;
		}
		if ($sort)
			$options ['by'] = $sort;
		
		$intersections = $analysis->query ( $options, $details );
		// Sort
		// var_dump($intersections);die;
		return $this->_render_intersections ( $intersections, $details, TRUE );
	}
	
	public function rollstatsAction(){
		
	}
	public function statsAction() {
		$logic = $this->getServiceLocator ()->get ( 'logic' );
		$lattice = $this->_lattice ();
		$flat = $logic->makeRules ( $lattice->extents (), $lattice->intents () );
		$abstracted = $logic->makeAbstracted ( $this->_analysis ()->matches (), $lattice->intents () );
		$row = $this->getRequest ()->getParam ( 'row', FALSE );
		$changes = $this->_changes () === TRUE ? count ( $this->_database ()->changes () ) : $this->_changes ();
		$nconflicts = count($this->_analysis()->conflictsGrouped());
		$nconcepts = count ( $this->_lattice ()->concepts () );
		$rules = count ( $flat );
		$abstract = count ( $abstracted );
		$co = ($rules - $abstract);
		$cf = (round ( $abstract / $rules, 3 ));
		if ($row) {
			print "$changes\t";
			print "$nconcepts\t";
			print "$nconflicts\t";
			print "$rules\t";
			print "$abstract\t";
			print "$co\t";
			print "$cf\t\n";
		} else {
			print "$changes changes.\n";
			print "$nconcepts concepts.\n";
			print "$nconflicts conflicts.\n";
			print "$rules rules total.\n";
			print "$abstract rules abstracted.\n";
			print $co . " rules remaining.\n";
			print "Compression factor: " . $cf . "\n";
		}
	}
	function _render_intersections($intersections, $verbose = FALSE, $headers = FALSE) {
		ob_start ();
		if ($headers) {
			print str_pad ( 'c', 5 );
			print str_pad ( 'es', 5 );
			print str_pad ( 'is', 5 );
			print str_pad ( 'bs', 5 );
			print str_pad ( 'pre', 5 );
			print str_pad ( 'rec', 5 );
			print str_pad ( 'f1', 5 );
			print 'branch';
			print "\n";
		}
		foreach ( $intersections as $item ) {
			print str_pad ( $item ['c'], 5 );
			print str_pad ( $item ['es'], 5 );
			print str_pad ( $item ['is'], 5 );
			print str_pad ( $item ['bs'], 5 );
			print str_pad ( $item ['pre'], 5 );
			print str_pad ( $item ['rec'], 5 );
			print str_pad ( $item ['f1'], 5 );
			print $item ['branch'];
			print "\n";
			if ($verbose) {
				foreach ( $item ['details'] as $det ) {
					print str_pad ( ' ', 35 );
					print $det [0];
					print ' ';
					print $det [1];
					print "\n";
				}
			}
		}
		return ob_get_clean ();
	}
}