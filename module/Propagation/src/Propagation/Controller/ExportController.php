<?php

namespace Propagation\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Filter\Word\CamelCaseToUnderscore as CC2U;
use \RuntimeException as RuntimeException;
use \ARC2 as ARC2;

class ExportController extends AbstractController {
	private $_cc2u = NULL;
	protected function _format() {
		$format = $this->getRequest ()->getParam ( 'format' );
		if (! $format) {
			throw new RuntimeException ( '"format" is mandatory!' );
		}
		return $format;
	}
	protected function _compress() {
		return $this->getRequest ()->getParam ( 'compress', FALSE );
	}
	public function ontologyAction() {
		$format = $this->_format ();
		$compact = $this->getRequest ()->getParam ( 'compact', FALSE );
		$nohierarchy = $this->getRequest ()->getParam ( 'nohierarchy', FALSE ); 
		if ($compact) {
			$method_name = '_render_compact_' . $format;
		} else {
			$method_name = '_render_ontology_' . $format;
		}
		if (method_exists ( $this, $method_name )) {
			return $this->$method_name ($nohierarchy);
		} else {
			throw new RuntimeException ( 'Unsupported format: ' . $format );
		}
	}
	public function rulesAction() {
		$format = $this->_format ();
		$rules = $this->_makeRules ( NULL, $this->_compress () );
		$method_name = '_render_rules_' . $format;
		if (method_exists ( $this, $method_name )) {
			return $this->$method_name ( $rules );
		} else {
			throw new RuntimeException ( 'Unsupported format: ' . $format );
		}
	}
	public function relationsAction() {
		$format = $this->_format ();
		$method_name = '_render_relations_' . $format;
		if (method_exists ( $this, $method_name )) {
			return $this->$method_name ();
		} else {
			throw new RuntimeException ( 'Unsupported format: ' . $format );
		}
	}
	public function _render_compact_prolog($nohierarchy = FALSE) {
		$include = array (
				'http://www.w3.org/2002/07/owl#inverseOf' 
		);
		if (! $nohierarchy) {
			array_push ( $include, 'http://www.w3.org/2000/01/rdf-schema#subPropertyOf' );
		}
		ob_start ();
		foreach ( $include as $inc )
			foreach ( $this->_datanode ()->triples () as $triple ) {
				$p = $triple ['p'];
				if ($p == $inc) {
					print $this->_render_triple_prolog ( $triple ) . "\n";
				}
			}
		return ob_get_clean ();
	}
	private function _uri2predicate($uri) {
		$p = $uri;
		$prefix = '';
		if (strpos ( $p, 'http://www.w3.org/2000/01/rdf-schema#' ) === 0) {
			$prefix = 'rdfs';
		} elseif (strpos ( $p, 'http://www.w3.org/2002/07/owl#' ) === 0) {
			$prefix = 'owl';
		} elseif (strpos ( $p, 'http://purl.org/datanode/ns/' ) === 0) {
			$prefix = 'dn';
		} elseif (strpos ( $p, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' ) === 0) {
			$prefix = 'rdf';
		} elseif (strpos ( $p, 'http://www.w3.org/ns/odrl/2/' ) === 0) {
			$prefix = 'odrl';
		}
		$prefix .= '_';
		$rstrpos = function ($haystack, $needle, $offset = NULL) {
			$size = strlen ( $haystack );
			if ($offset == NULL) {
				$offset = $size;
			}
			$pos = strpos ( strrev ( $haystack ), $needle, $size - $offset );
			$q = strrev ( $haystack );
			if ($pos === false) {
				return false;
			}
			return $size - $pos;
		};
		$hash = $rstrpos ( $p, '#' );
		if (! $hash) {
			$hash = $rstrpos ( $p, '/' );
		}
		$localName = substr ( $p, $hash );
		if (! $this->_cc2u) {
			$this->_cc2u = new CC2U ();
		}
		$predicate = $prefix . strtolower ( $this->_cc2u->filter ( $localName ) );
		return $predicate;
	}
	public function _render_triple_prolog($triple) {
		$s = $triple ['s'];
		$p = $triple ['p'];
		$o = $triple ['o'];
		$predicate = $this->_uri2predicate ( $p );
		$o = str_replace ( "'", "`", $o );
		return "$predicate('$s','$o').";
	}
	public function _render_ontology_prolog() {
		ob_start ();
		$cc2u = new CC2U ();
		$sorted = array ();
		foreach ( $this->_datanode ()->triples () as $triple ) {
			$predicate = $this->_render_triple_prolog ( $triple );
			array_push ( $sorted, "$predicate\n" );
		}
		sort ( $sorted );
		foreach ( $sorted as $p ) {
			print $p;
		}
		return ob_get_clean ();
	}
	function _render_ontology_java() {
		$branches = $this->_branches ();
		ob_start ();
		print "package enridaga.datanode.dsl;\npublic abstract class DNGraph implements Walkable, Measurable {";
		print "public abstract DNGraph arc(String id);\n";
		print "public abstract DNGraph node(String id);\n";
		$res = array_keys ( $branches );
		$res = array_unique ( $res );
		foreach ( $res as $b ) {
			$sn = substr ( $b, strrpos ( $b, '/' ) + 1 );
			print "\npublic final static String $sn = \"$sn\";";
			print "\npublic final DNGraph ";
			print $sn;
			print '(){return arc(' . $sn . ');}';
		}
		print "\n}";
		return ob_get_clean ();
	}
	public function _render_ontology_rdf() {
		ob_start ();
		$ser = ARC2::getNtriplesSerializer ();
		print $ser->getSerializedTriples ( $this->_datanode ()->triples () );
		return ob_get_clean ();
	}
	public function _render_relations_prolog() {
		$include = array (
				'http://www.w3.org/2000/01/rdf-schema#subPropertyOf',
				'http://www.w3.org/2002/07/owl#inverseOf' 
		);
		foreach ( $include as $inc ) {
			foreach ( $this->_datanode ()->triples () as $triple ) {
				$p = $triple ['p'];
				if ($p == $inc) {
					print $this->_render_triple_prolog ( $triple ) . "\n";
				}
			}
		}
	}
	public function _render_relations_rdf() {
		ob_start ();
		$ser = ARC2::getNtriplesSerializer ();
		$flatRelations = array ();
		foreach ( $this->_datanode ()->triples () as $triple ) {
			if ($triple ['p'] == 'http://www.w3.org/2000/01/rdf-schema#subPropertyOf') {
				// Generates domain and range for the property
				$domain = $range = $triple;
				$domain ['p'] = 'http://www.w3.org/2000/01/rdf-schema#domain';
				$domain ['o'] = 'http://purl.org/datanode/ns/Datanode';
				$range ['p'] = 'http://www.w3.org/2000/01/rdf-schema#range';
				$range ['o'] = 'http://purl.org/datanode/ns/Datanode';
				array_push ( $flatRelations, $domain );
				array_push ( $flatRelations, $range );
			} else if ($triple ['p'] == 'http://www.w3.org/2000/01/rdf-schema#domain') {
				array_push ( $flatRelations, $triple );
			} else if ($triple ['p'] == 'http://www.w3.org/2000/01/rdf-schema#range') {
				array_push ( $flatRelations, $triple );
			}
		}
		print $ser->getSerializedTriples ( $flatRelations );
		return ob_get_clean ();
	}
	public function _render_rules_prolog(array $rules) {
		ob_start ();
		foreach ( $rules as $rule ) {
			$dn_property = $rule [0];
			$policy = $rule [1];
			print "propagates('$dn_property','http://www.w3.org/ns/odrl/2/$policy').\n";
		}
		return ob_get_clean ();
	}
	public function _render_rules_rdf(array $rules) {
		ob_start ();
		foreach ( $rules as $rule ) {
			$dn_property = $rule [0];
			$policy = $rule [1];
			print $this->_make_rule_as_nt ( $dn_property, $policy );
		}
		return ob_get_clean ();
	}
	public function _render_compact_rdf() {
		$ser = ARC2::getNtriplesSerializer ();
		$subpropertyOf = array ();
		foreach ( $this->_datanode ()->triples () as $triple ) {
			if ($triple ['p'] == 'http://www.w3.org/2000/01/rdf-schema#subPropertyOf') {
				array_push ( $subpropertyOf, $triple );
			} else if ($triple ['p'] == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type') {
				array_push ( $subpropertyOf, $triple );
			} else if ($triple ['p'] == 'http://www.w3.org/2000/01/rdf-schema#domain') {
				array_push ( $subpropertyOf, $triple );
			} else if ($triple ['p'] == 'http://www.w3.org/2000/01/rdf-schema#range') {
				array_push ( $subpropertyOf, $triple );
			} else if ($triple ['p'] == 'http://www.w3.org/2002/07/owl#inverseOf') {
				array_push ( $subpropertyOf, $triple );
			}
		}
		print $ser->getSerializedTriples ( $subpropertyOf );
	}
	private function _make_rule_as_nt($dn_property, $policy) {
		$dn = 'http://purl.org/datanode/ns/';
		$ppr = 'http://purl.org/datanode/ppr/ns/';
		$odrl = "http://www.w3.org/ns/odrl/2/";
		$prop = $ppr . 'propagates';
		$pp = explode ( ' ', $policy );
		$deontic = $odrl . $pp [0];
		$action = $pp [1];
		$policyId = self::build_policy_id ( $deontic, $action );
		return "<$dn_property> <$prop> <{$ppr}$policyId> .\n<{$ppr}$policyId> <$deontic> <$action> .\n";
	}
}