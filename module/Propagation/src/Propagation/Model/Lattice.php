<?php

namespace Propagation\Model;

class Lattice {
	private $_extents = array();
	private $_intents = array();
	private $_lattice;
	private $_concepts = NULL;
	private $_conceptIds = NULL;
	private $_subrelations = NULL;
	private $_superrelations = NULL;
	public function __construct(array $lattice) {
		$this->_lattice = $lattice;
		$this->_concepts = $lattice [0];
		$this->_superrelations = $lattice[1];
		$this->_subrelations = array();
		foreach($this->_superrelations as $k => $vv){
			foreach($vv as $v){
				if(!array_key_exists($v, $this->_subrelations)){
					$this->_subrelations[$v] = array();
				}
				array_push($this->_subrelations[$v], $k);
			}
		}
		foreach ( $lattice [0] as $cID => $cV ) {
			$this->_extents [$cID] = $cV [0];
			$this->_intents [$cID] = $cV [1];
		}
		
		$sort_extents = function ($a, $b) {
			return count ( $b ) - count ( $a );
		};
		uasort ( $this->_extents, $sort_extents );
	}
	
	public function toArray(){
		return $this->_lattice;
	}
	
	public function extents(){
		return $this->_extents;
	}

	public function extent($cid){
		return $this->_extents[$cid];
	}
	public function intent($cid){
		return $this->_intents[$cid];
	}
	public function hierarchy(){
		return $this->_subrelations;
	}
	
	public function intents(){
		return $this->_intents;
	}
	
	public function conceptsOfExtent(array $item = array()){
		if(count($item) == 0){
			return $this->conceptIds();
		}
		$cids = array();
		foreach($this->_extents as $cid => $extent){
			if(count(array_diff($item, $extent)) == 0){
				array_push($cids, $cid);
			}
		}
		return $cids;
	}
	
	public function conceptsOfIntent(array $item = array()){
		if(count($item) == 0){
			return $this->conceptIds();
		}
		$cids = array();
		foreach($this->_intents as $cid => $intent){
			if(count(array_diff($item, $intent)) == 0){
				array_push($cids, $cid);
			}
		}
		return $cids;
	}
	public function conceptsOf(array $extent = array(), array $intent = array()){
		return array_intersect($this->conceptsOfExtent($extent), $this->conceptsOfExtent($intent));
		return $ids;
	}
	
	public function ancestors($cid){
		if(!@$this->_superrelations[$cid]) return array();
		return $this->_superrelations[$cid];
	}

	public function descendants($cid){
		if(!@$this->_subrelations[$cid]) return array();
		return $this->_subrelations[$cid];
	}
	
	public function concepts(){
		return $this->_concepts;
	}
	
	public function conceptIds(){
		if($this->_conceptIds == NULL){
			$this->_conceptIds = array_unique(array_merge ( array_keys ( $this->_extents ), array_keys ( $this->_intents ) ));
		}
		return $this->_conceptIds;
	}
	
	public function concept($id){
		return array('id' => $id, 'extent' => @$this->_extents[$id], 'intent' => @$this->_intents[$id], 'ancestors' => @$this->_superrelations[$id], 'descendants' => @$this->_subrelations[$id]);
	}
	
}