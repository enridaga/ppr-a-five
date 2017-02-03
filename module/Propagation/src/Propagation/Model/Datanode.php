<?php

namespace Propagation\Model;

class Datanode {
	private $_triples;
	private $_root;
	private $_arc;
	private $_branches;
	public function __construct(array $triples) {
		$this->_triples = $triples;
		$config = \Propagation\Module::getConfig ();
		$this->_root = $config ['constants'] ['ontology-root'];
		$this->_arc = $config ['constants'] ['ontology-arc'];
	}
	private function _treefy($focus, $triples, $rel) {
		$arr = array ();
		foreach ( $triples as $t ) {
			if ($t ['p'] == $rel && $t ['o'] == $focus) {
				$arr [$t ['s']] = $this->_treefy ( $t ['s'], $triples, $rel );
			}
		}
		return $arr;
	}
	public function triples(){
		return $this->_triples;
	}
	function tree($root = NULL) {
		if($root == NULL){
			$root = $this->_root;
		}
		$tree = array ();
		$tree [$root] = $this->_treefy ( $root, $this->_triples, $this->_arc );
		return $tree;
	}
	public function relations() {
		$triples = $this->_triples;
		$relations = array ();
		
		foreach ( $triples as $t ) {
			if ($t ['p'] == $this->_arc) {
				array_push ( $relations, $t ['s'] );
				array_push ( $relations, $t ['o'] );
			}
		}
		
		$relations = array_unique ( $relations );
		return $relations;
	}
	public function branch($topRelation){
		return $this->_reverse_transitive_closure ( $topRelation, $this->_triples, $this->_arc );
	}
	public function branches() {
		if (! $this->_branches) {
			// 'sort_by_size_desc'
			$sortBy = function (array $a1, array $a2) {
				$c1 = count ( $a1 );
				$c2 = count ( $a2 );
				if ($c1 == $c2)
					return 0;
				return $c1 > $c2 ? - 1 : 1;
			};
			
			$branches = $this->_cluster_datanode_branches ();
			uasort ( $branches, $sortBy );
			$this->_branches = $branches;
		}
		return $this->_branches;
	}
	
	private function _cluster_datanode_branches() {
		$branches = array ();
		$all = $this->_reverse_transitive_closure ( $this->_root, $this->_triples, $this->_arc );
		$branches [$this->_root] = $all;
		foreach ( $all as $node ) {
			if (! isset ( $branches [$node] )) {
				$branches [$node] = $this->_reverse_transitive_closure ( $node, $this->_triples, $this->_arc );
			}
		}
		return $branches;
	}
	
	/**
	 * Collects navigates the reverse transitive closure.
	 *
	 * @param string $focus        	
	 * @param array $triples        	
	 * @param string $rel        	
	 * @return array
	 */
	private function _reverse_transitive_closure($focus, $triples, $rel) {
		$arr = array (
				$focus 
		);
		foreach ( $triples as $t ) {
			if ($t ['p'] == $rel && $t ['o'] == $focus) {
				array_push ( $arr, $t ['s'] );
				$arr = array_merge ( $arr, $this->_reverse_transitive_closure ( $t ['s'], $triples, $rel ) );
			}
		}
		$arr = array_unique ( $arr );
		return $arr;
	}
}