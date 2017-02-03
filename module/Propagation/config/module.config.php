<?php

namespace Propagation;

return array (
		'router' => array (
				'routes' => array () 
		),
		// HTTP routes are defined here
		
		'service_manager' => array (
				'invokables' => array (
						'Database' => 'Propagation\Service\Database',
						'Logic' => 'Propagation\Service\Logic',
						'FCA' => 'Propagation\Service\FCA' 
				) 
		),
		'controllers' => array (
				'invokables' => array (
						'Propagation\Controller\Operation' => Controller\OperationController::class,
						'Propagation\Controller\Rules' => Controller\RulesController::class,
						'Propagation\Controller\Configuration' => Controller\ConfigurationController::class,
						'Propagation\Controller\Datanode' => Controller\DatanodeController::class,
						'Propagation\Controller\Analyse' => Controller\AnalyseController::class,
						'Propagation\Controller\Lattice' => Controller\LatticeController::class,
						'Propagation\Controller\Export' => Controller\ExportController::class 
				) 
		),
		'console' => array (
				'router' => array (
						'routes' => array (
								// Console routes go here
								'configuration' => array (
										'options' => array (
												'description' => 'See configuration details.',
												'route' => 'configuration',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Configuration',
														'action' => 'show' 
												) 
										) 
								),
								'rules' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Show propagation rules.',
												'route' => 'rules (flat|compressed|abstracted):action [--format=] [--cid=] [-v] [--changes=]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Rules',
														'action' => 'flat',
														'changes' => "all",
														'format' => 'matrix' 
												) 
										) 
								),
								'matrix' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Show the matrix of relations/policies (the input formal context for the FCA).',
												'route' => 'rules matrix [--format=] [-v] [--changes=]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Rules',
														'action' => 'matrix',
														'changes' => "all",
														'format' => 'matrix' 
												) 
										) 
								),
								'branches' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Show branches.',
												'route' => 'branches [-v] [--changes=]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Datanode',
														'action' => 'branches',
														'changes' => "all" 
												) 
										) 
								),
								'branch' => array (
									'type' => 'simple',
										'options' => array(
											'description' => 'Show a list of relations within a given branch.',
											'route' => 'branch --relation= [-v] [--changes=]',
											'defaults' => array (
											'controller' => 'Propagation\Controller\Datanode',
											'action' => 'branch',
											'changes' => 'all'
										)
									)
								),
								'datanode-tree' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Show Datanode tree.',
												'route' => 'tree [-v] [--branch=] [--depth=] [--changes=]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Datanode',
														'action' => 'tree',
														'changes' => "all" 
												) 
										) 
								),
								'relations' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Show relations.',
												'route' => 'relations [-v] [--changes=]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Datanode',
														'action' => 'relations',
														'changes' => "all" 
												) 
										) 
								),
								'analyse' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Analyse cluster(s) with branches. Obtain a report showing all intersections, full matches or partial ones.',
												'route' => 'analyse [-d] [--pre=] [--rec=] [--es=] [--is=] [--bs=] [--cid=] [--branch=] [--by=] [-v] [--changes=]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Analyse',
														'action' => 'query',
														'changes' => "all",
														'v' => FALSE,
														'd' => FALSE,
														'cid' => '*' 
												) 
										) 
								),
								'conflict' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Analyse conflicts.',
												'route' => 'conflict [--cid=] [-d] [-v] [--changes=]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Analyse',
														'action' => 'conflict',
														'changes' => "all",
														'v' => FALSE,
														'd' => FALSE,
														'cid' => '*' 
												) 
										) 
								),
								'rel-diff' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Analyse conflicts.',
												'route' => 'relations diff --left= --right= [--changes=]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Analyse',
														'action' => 'reldiff',
														'changes' => "all",
														'v' => FALSE,
														'd' => FALSE 
												) 
										) 
								),
								'lattice-info' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Cluster rules and show the whole cluster(s) or relations/policies of cluster(s).',
												'route' => 'lattice (info|relations|policies|browse):action [--max=] [--cid=] [-v] [--changes=]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Lattice',
														// 'action' => 'clusters',
														'changes' => "all",
														'v' => FALSE,
														'cid' => '*' 
												) 
										) 
								),
								
								'stats' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Show changes.',
												'route' => 'stats [--row] [--changes=] [-v]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Analyse',
														'action' => 'stats',
														'changes' => 'all' 
												) 
										) 
								),
								'changes' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Show changes.',
												'route' => 'changes [--change=] [-v]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Operation',
														'action' => 'changes' 
												) 
										) 
								),
								'operation-remove' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Perform operation: remove',
												'route' => 'operation remove --relation= --branch=',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Operation',
														'action' => 'removerelation' 
												) 
										) 
								),
								'operation-group' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Perform operation: group',
												'route' => 'operation group --cid= --with= --under=',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Operation',
														'action' => 'groupwith' 
												) 
										) 
								),
								'operation-add' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Perform operation: add',
												'route' => 'operation add --relation= --branch=',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Operation',
														'action' => 'addrelation' 
												) 
										) 
								),
								'operation-wedge' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Perform operation: wedge',
												'route' => 'operation wedge --relation= --branch=',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Operation',
														'action' => 'addrelation' 
												) 
										) 
								),
								'operation-fill' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Perform operation: fill',
												'route' => 'operation fill --branch= --cid=',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Operation',
														'action' => 'fill' 
												) 
										) 
								),
								'export-ontology' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Export the ontology',
												'route' => 'export (ontology|relations):action (prolog|rdf|java):format [--compact] [--nohierarchy] [--changes=]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Export',
														'changes' => 'all'
												) 
										) 
								),
								'export-rules' => array (
										'type' => 'simple',
										'options' => array (
												'description' => 'Export rules.',
												'route' => 'export rules (prolog|rdf):format [--compress] [--changes=]',
												'defaults' => array (
														'controller' => 'Propagation\Controller\Export',
														'action' => 'rules',
														'changes' => 'all',
														'compress' => False 
												) 
										) 
								) 
						) 
				),
				'parameters' => array (
						array (
								'--changes',
								'Number of changes to apply. Eg: --changes=1.' 
						),
						array (
								'-v',
								'Display details.' 
						),
						array (
								'--cid',
								'A CSV list of cluster ids. Eg: --cid=77 or --cid=76,54,12 --cid=*' 
						),
						array (
								'--pre',
								'(Precision) Filter items with precision <expression>. Expressions can be: "eq0.7" (same as "0.7"), "lt1" (lower than), "gt0.5" (greater than), "0.8:1" (between 0.8 and 1)' 
						),
						array (
								'--rec',
								'(Recall) Filter items with recall <expression>. ' 
						),
						array (
								'--bs',
								'(Branch size) Filter items with branch size <expression>. ' 
						),
						array (
								'--is',
								'(Intersection size) Filter items with intersection size <expression>. ' 
						),
						array (
								'--es',
								'(Extent size) Filter items with extent <expression>. ' 
						),
						array (
								'--f1',
								'(F1 measure) Filter items with F1 <expression>. ' 
						),
						array (
								'--by',
								'Order items by <expression>. Expression is [c|pre|rec|bs|is|es|f1]/[a|d]. Examples: "pre/d" (precision descending), "c/a" (cluster id ascending), "es/a,pre/d" (extent size ascending first, then pre descending).' 
						),
						array (
								'--compress',
								'Flag. Default is False.' 
						) 
				) 
		)
		,
		'view_manager' => array (
				// The TemplateMapResolver allows you to directly map template names
				// to specific templates. The following map would provide locations
				// for a home page template ("Propagation/index/index"), as well as for
				// the layout ("layout/layout"), error pages ("error/index"), and
				// 404 page ("error/404"), resolving them to view scripts.
				'template_map' => array (),
				// 'Propagation/propagation/rules' => __DIR__ . '/../view/Propagation/propagation/rules.php'
				// //'Propagation/index/index' => __DIR__ . '/../view/Propagation/index/index.phtml',
				// //'site/layout' => __DIR__ . '/../view/layout/layout.phtml',
				// //'error/index' => __DIR__ . '/../view/error/index.phtml',
				// //'error/404' => __DIR__ . '/../view/error/404.phtml'
				
				// The TemplatePathStack takes an array of directories. Directories
				// are then searched in LIFO order (it's a stack) for the requested
				// view script. This is a nice solution for rapid Propagation
				// development, but potentially introduces performance expense in
				// production due to the number of static calls necessary.
				//
				// The following adds an entry pointing to the view directory
				// of the current module. Make sure your keys differ between modules
				// to ensure that they are not overwritten -- or simply omit the key!
				'template_path_stack' => array (
						'Propagation' => __DIR__ . '/../view' 
				),
				
				// This will be used as the default suffix for template scripts resolving, it defaults to 'phtml'.
				'default_template_suffix' => 'php',
				
				// Set the template name for the site's layout.
				//
				// By default, the MVC's default Rendering Strategy uses the
				// template name "layout/layout" for the site's layout.
				// Here, we tell it to use the "site/layout" template,
				// which we mapped via the TemplateMapResolver above.
				'layout' => 'site/layout',
				
				// By default, the MVC registers an "exception strategy", which is
				// triggered when a requested action raises an exception; it creates
				// a custom view model that wraps the exception, and selects a
				// template. We'll set it to "error/index".
				//
				// Additionally, we'll tell it that we want to display an exception
				// stack trace; you'll likely want to disable this by default.
				'display_exceptions' => true,
				'exception_template' => 'error/index',
				
				// Another strategy the MVC registers by default is a "route not
				// found" strategy. Basically, this gets triggered if (a) no route
				// matches the current request, (b) the controller specified in the
				// route match cannot be found in the service locator, (c) the controller
				// specified in the route match does not implement the DispatchableInterface
				// interface, or (d) if a response from a controller sets the
				// response status to 404.
				//
				// The default template used in such situations is "error", just
				// like the exception strategy. Here, we tell it to use the "error/404"
				// template (which we mapped via the TemplateMapResolver, above).
				//
				// You can opt in to inject the reason for a 404 situation; see the
				// various `Propagation\:\:ERROR_*`_ constants for a list of values.
				// Additionally, a number of 404 situations derive from exceptions
				// raised during routing or dispatching. You can opt-in to display
				// these.
				'display_not_found_reason' => true,
				'not_found_template' => 'error/404' 
		),
		'resources' => array (
				'changes.db' => 'resources/changes.db-dev',
				'context.csv' => 'resources/context.csv-dev' 
		),
		'constants' => array (
				'arc2' => 'arc2-2.2.4/ARC2.php',
				'datanode' => 'resources/datanode.nt',
				'ontology-root' => 'http://purl.org/datanode/ns/relatedWith',
				'ontology-arc' => 'http://www.w3.org/2000/01/rdf-schema#subPropertyOf' 
		) 
);