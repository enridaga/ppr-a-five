<?php

namespace Propagation\Model;

class Analysis {
	// private $_branches;
	// private $_extents;
	private $_intersections;
	private $_scores;
	private $_matches;
	// private $_extents;
	// private $_branches;
	private $_lattice;
	private $_datanode;
	public function __construct(Datanode $datanode, Lattice $lattice) {
		$this->_lattice = $lattice;
		$this->_datanode = $datanode;
		$this->_perform ();
	}
	/**
	 *
	 * @return array - indexed by cluster id
	 */
	public function intersections() {
		return $this->_intersections;
	}
	public function scores() {
		return $this->_scores;
	}
	public function matches($cid = NULL) {
		if ($cid) {
			return array (
					$cid => $this->_matches [$cid] 
			);
		}
		return $this->_matches;
	}
	/**
	 * Conflicts between clusters (fca) and the ontology hierarchy.
	 * It checks that subsumption of the lattice is coherent with the one of the ontology.
	 * returns an array:
	 * <pre>
	 * array(
	 * 'c' => number,
	 * 'branch' => uri,
	 * 'relation' => uri
	 * )
	 * </pre>
	 * Where <tt>branch</tt> is an item in the extent of <tt>c</tt> <tt>and relation</tt> a
	 * member of the extent of a superconcept of <tt>c</tt> being also a subconcept of <tt>branch</tt>.
	 *
	 * @param int $cid
	 *        	- Cluster Id
	 * @param string $d
	 *        	- Display Details
	 * @return array -
	 */
	public function conflicts($cid, $d = FALSE) {
		$conflicts = array ();
		$ancestors = $this->_lattice->ancestors ( $cid );
		if ($d) {
			print "Ancestors: " . implode ( ',', $ancestors ) . "\n";
		}
		$superRelations = array ();
		foreach ( $ancestors as $ancId ) {
			$superRelations = array_merge ( $superRelations, $this->_lattice->extent ( $ancId ) );
		}
		if (count ( $superRelations ) == 0) {
			return array ();
		}
		
		$extent = $this->_lattice->extent ( $cid );
		// only check extent items that are not in subconcepts
		$tocheck = array_diff ( $superRelations, $extent );
		
		if (count ( $tocheck ) == 0)
			return array ();
		
		if ($d) {
			print "\nC $cid (" . count ( $tocheck ) . " to check)";
			print "\nChecking:";
		}
		foreach ( $tocheck as $relation ) {
			if ($d) {
				// //////
				print "\n $relation ";
				foreach ( $ancestors as $ancId ) {
					if (in_array ( $relation, $this->_lattice->extent ( $ancId ) )) {
						print "[$ancId]";
					}
				}
				// //////
			}
			
			foreach ( $extent as $item ) {
				if ($d) {
					print "\n is in the " . $item . " branch?";
				}
				if (in_array ( $relation, $this->_datanode->branches ()[$item] )) {
					if ($d)
						print " Yes (Conflict)";
					if (! isset ( $conflicts [$item . '|' . $relation] )) {
						$conflicts [$item . '|' . $relation] = array (
								'c' => array (
										$cid 
								),
								'branch' => $item,
								'relation' => $relation 
						);
					} else {
						if (! in_array ( $cid, $conflicts [$item . '|' . $relation] ['c'] )) {
							array_push ( $conflicts [$item . '|' . $relation] ['c'], $cid );
						}
					}
				} else {
					if ($d)
						print ' NO (ok)';
				}
			}
		}
		return $conflicts;
	}
	
	/**
	 * Number of conflicts (branch/child conflicts)
	 */
	public function conflictsGrouped() {
		$conflicts = array ();
		foreach ( $this->_lattice->conceptIds () as $cid ) {
			$cnfls = $this->conflicts ( $cid );
			foreach ( $cnfls as $i => $c ) {
				if (! isset ( $conflicts [$i] )) {
					$conflicts [$i] = $c;
				} else {
					array_push ( $conflicts [$i] ['c'], $c ['c'] );
				}
			}
		}
		return $conflicts;
	}
	
	/**
	 * Returns two lists of policies.
	 * The first list contains all the policies from $left
	 * that are not propagated by $right. The second list contains all the policies from $right
	 * thta are not propagated by $left.
	 *
	 * @param string $left        	
	 * @param string $right        	
	 * @return array -
	 */
	public function relationsDiff($left, $right) {
		$lpo = array ();
		$rpo = array ();
		foreach ( $this->_lattice->conceptsOfExtent ( array (
				$left 
		) ) as $c ) {
			$lpo = array_merge ( $lpo, $this->_lattice->intent ( $c ) );
		}
		$lpo = array_unique ( $lpo );
		foreach ( $this->_lattice->conceptsOfExtent ( array (
				$right 
		) ) as $c ) {
			$rpo = array_merge ( $rpo, $this->_lattice->intent ( $c ) );
		}
		$rpo = array_unique ( $rpo );
		$lpo1 = array_diff ( $lpo, $rpo );
		$rpo1 = array_diff ( $rpo, $lpo );
		return array (
				array_values ( $lpo1 ),
				array_values ( $rpo1 ) 
		);
	}
	public function query(array $options = array(), $details = FALSE) {
		$return = array ();
		$conceptIds = array ();
		if (array_key_exists ( 'c', $options )) {
			$conceptIdsOptions = explode ( ',', $options ['c'] );
			foreach ( $conceptIdsOptions as $opt ) {
				if (strpos ( $opt, '-' )) {
					// range
					$range = explode ( '-', $opt );
					$from = intval ( $range [0] );
					$to = intval ( $range [1] );
					for($x = $from; $x <= $to; $x ++) {
						array_push ( $conceptIds, $x );
					}
				} else {
					array_push ( $conceptIds, $opt );
				}
			}
		} else {
			$conceptIds = array_keys ( $this->_intersections );
		}
		foreach ( $conceptIds as $cID ) {
			$return = array_merge ( $return, $this->queryConcept ( $cID, $options, $details ) );
		}
		if (@$options ['by']) {
			$sorter = new Sorter ( $options ['by'] );
			usort ( $return, array (
					$sorter,
					'compare' 
			) );
		}
		return $return;
	}
	public function queryConcept($cID, array $options = array(), $details = FALSE) {
		$return = array ();
		foreach ( $this->_intersections [$cID] as $vvk => $vvvv ) {
			$intersect = $vvk;
			$isco = $this->_scores [$cID] [$vvk];
			// check filter on branch
			if (isset ( $options ['branch'] )) {
				if ($vvk != $options ['branch']) {
					continue;
				}
			}
			if ($this->_skip_intersection ( $isco, $options )) {
				continue;
			}
			$item = array (
					'c' => $cID,
					'es' => count ( $this->_lattice->extents ( $cID ) ) 
			);
			$item = array_merge ( $item, $isco );
			$item ['branch'] = $intersect;
			if ($details) {
				$missing = array ();
				$item ['details'] = array ();
				foreach ( $this->_datanode->branches ()[$vvk] as $child ) {
					if (! in_array ( $child, $this->_lattice->extent ( $cID ) )) {
						array_push ( $item ['details'], array (
								'!',
								$child 
						) );
					} else {
						array_push ( $item ['details'], array (
								'+',
								$child 
						) );
					}
				}
			}
			array_push ( $return, $item );
		}
		return $return;
	}
	private function _skip_intersection($scores, $options) {
		$res = ! ($this->_match_the_score ( $scores ['pre'], @$options ['pre'] ) && $this->_match_the_score ( $scores ['rec'], @$options ['rec'] ) && $this->_match_the_score ( $scores ['f1'], @$options ['f1'] ) && $this->_match_the_score ( $scores ['bs'], @$options ['bs'] ) && $this->_match_the_score ( $scores ['is'], @$options ['is'] ));
		return $res;
	}
	private function _match_the_score($score, $opts) {
		if ($opts == NULL) {
			return TRUE;
		}
		$opts = explode ( ',', $opts );
		foreach ( $opts as $opt ) {
			$operator = '=';
			$val = $opt;
			if (strpos ( $opt, 'eq' ) === 0) {
				$operator = 'eq';
				$val = substr ( $val, 2 );
			} elseif (strpos ( $opt, 'gt' ) === 0) {
				$operator = 'gt';
				$val = substr ( $val, 2 );
			} elseif (strpos ( $opt, 'lt' ) === 0) {
				$operator = 'lt';
				$val = substr ( $val, 2 );
			} elseif (strpos ( $opt, ':' ) !== FALSE) {
				$operator = ':';
				$val = explode ( ':', $opt );
			}
			$match = TRUE;
			if (is_array ( $val )) {
				foreach ( $val as &$v )
					$v = $v + 0;
			} else {
				$val = ($val + 0);
			}
			$score = ($score + 0);
			// print "$score $operator $val\n";
			switch ($operator) {
				case 'eq' :
					$match = $score == $val;
					break;
				case 'gt' :
					$match = $score >= $val;
					break;
				case 'lt' :
					$match = $score <= $val;
					break;
				case ':' :
					$match = ($score >= $val [0] && $score <= $val [1]);
					break;
				default :
					$match = $score == $val;
			}
			// var_dump($match);
			if (! $match) {
				return FALSE;
			}
		}
		return true;
	}
	private function _perform() {
		$branches = $this->_datanode->branches ();
		$extents = $this->_lattice->extents ();
		$intersections = array (); // overlapping datanode branches
		$scores = array (); // different scores
		$matches = array (); // branches with precision 1.0
		foreach ( $extents as $c => $e ) {
			sort ( $e );
			foreach ( $branches as $dp => $b ) {
				$b_s = count ( $b );
				$e_s = count ( $e );
				sort ( $b );
				$i = array_intersect ( $b, $e );
				$i_s = count ( $i );
				
				if ($i_s === 0) {
					continue;
				}
				
				if (! array_key_exists ( $c, $intersections )) {
					$intersections [$c] = array ();
					$scores [$c] = array ();
					$matches [$c] = array ();
				}
				
				// b/e score: How much the branch is present in the extent
				$b_e_s = (1 / $b_s) * $i_s;
				// e/b score: How much the extent is covered by the branch
				$e_b_s = $i_s / $e_s;
				$b_e_s = round ( $b_e_s, 2, PHP_ROUND_HALF_DOWN );
				$e_b_s = round ( $e_b_s, 2, PHP_ROUND_HALF_DOWN );
				
				$skip = FALSE;
				if ($b_e_s === 1.0) {
					// FULL MATCH!
					// Add to quasi only if
					foreach ( $intersections [$c] as $dq => $qv ) {
						$cvg = array_intersect ( $b, $qv );
						if (count ( $cvg ) === $b_s && $scores [$c] [$dq] ['pre'] === 1.0) {
							// All have been found in a previously matched branch (this is a sub-branch of a score 1 branch)
							$skip = TRUE;
							break;
						}
					}
				}
				// else if (($b_e_s + $e_b_s)/2 < 0.7){
				// $skip = TRUE;
				// }
				
				if (! $skip) {
					$f1 = 2 * (($b_e_s * $e_b_s) / ($b_e_s + $e_b_s));
					$f1 = round ( $f1, 2, PHP_ROUND_HALF_DOWN );
					$intersections [$c] [$dp] = $b;
					if ($b_e_s === 1.0) {
						$matches [$c] [$dp] = $b;
					}
					$scores [$c] [$dp] = array (
							'bs' => $b_s,
							'is' => $i_s,
							'pre' => $b_e_s,
							'rec' => $e_b_s,
							'f1' => $f1 
					);
				}
			}
		}
		
		$this->_intersections = $intersections;
		$this->_scores = $scores;
		$this->_matches = $matches;
	}
}
class Sorter {
	private $_options = array ();
	public function __construct($options) {
		$this->_options = explode ( ',', $options );
		foreach ( $this->_options as &$opt ) {
			$opt = explode ( '/', $opt );
			if (count ( $opt ) == 1) {
				$opt [1] = 'd';
			}
		}
	}
	public function compare(array $a, array $b) {
		$r = 0;
		foreach ( $this->_options as $opt ) {
			$what = $opt [0];
			if ($a [$what] == $b [$what]) {
				continue;
			}
			switch ($opt [1]) {
				case 'a' :
					$r = $a [$what] > $b [$what] ? 1 : - 1;
					break;
				default :
					$r = $a [$what] < $b [$what] ? 1 : - 1;
			}
			return $r;
		}
		return 0;
	}
}