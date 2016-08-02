# Tools supporting the (A)AAAA Methodology

ppr-a-five includes a set of tools to support the application of the (A)AAAA methodology described in [1,2].
Follows basic usage instructions and examples.


## Usage
This project requires PHP and composer.

Preparation:

```
$ php composer.phar install
```

Then inspect available commands:

```
$ ./ppm-cli.sh -h
```


## (A)AAAA support
Examples with relation to the methodology.

### Acquisition
This phase consists in setting up a matrix of relations propagating a given policy.
The current file: `resources/context.csv` has been developed with the support of the Contento tool and is also available for inspection at this address: [http://data.open.ac.uk/contento/?controller=contexts&action=context&context=73]

See the matrix in the original form:
```
$ ./ppm-cli.sh rules matrix --changes=0
```


See the matrix in the current form (including changes performed in the Adjustment phase):
```
$ ./ppm-cli.sh rules matrix
```

### Analysis
Apply Formal Concent Analysis (FCA).

List all concepts:
```
$ ./ppm-cli.sh lattice info
```
Browse lattice:
```
$ ./ppm-cli.sh lattice browse --cid=0
```

### Abstraction
See abstracted branches:
```
$ ./ppm-cli.sh analyse --pre=1
```

### Assessment
Browse the result of the analysis and inspect measures.

List of conflicts:

```
$ ./ppm-cli.sh conflict --changes=0

Found 15 conflicts.
Cluster                                                          Branch                                      Relation                                      
78,75,71,70                                                      http://purl.org/datanode/ns/hasDerivation   http://purl.org/datanode/ns/hasSummarization  
76,77,75,72,70                                                   http://purl.org/datanode/ns/hasComputation  http://purl.org/datanode/ns/hasStatistic      
76,77,75,72,70                                                   http://purl.org/datanode/ns/processedInto   http://purl.org/datanode/ns/hasStatistic      
77,75,74,73,71,65,64,60,58,56,48,46,47,45,28,26,27,23,25,22,9,4  http://purl.org/datanode/ns/hasVocabulary   http://purl.org/datanode/ns/hasDatatypes      
77,75,74,73,71,65,64,60,58,56,48,46,47,45,28,26,27,23,25,22,9,4  http://purl.org/datanode/ns/hasVocabulary   http://purl.org/datanode/ns/hasDescriptors    
77,75,74,73,71,65,64,60,58,56,48,46,47,45,28,26,27,23,25,22,9,4  http://purl.org/datanode/ns/hasVocabulary   http://purl.org/datanode/ns/hasRelations      
77,75,72,70                                                      http://purl.org/datanode/ns/hasDerivation   http://purl.org/datanode/ns/hasStatistic      
73,72,71,70,67,55,56,52,11,8,10,6                                http://purl.org/datanode/ns/isVocabularyOf  http://purl.org/datanode/ns/attributesOf      
73,72,71,70,67,55,56,52,11,8,10,6                                http://purl.org/datanode/ns/isVocabularyOf  http://purl.org/datanode/ns/datatypesOf       
73,72,71,70,67,55,56,52,11,8,10,6                                http://purl.org/datanode/ns/isVocabularyOf  http://purl.org/datanode/ns/descriptorsOf     
73,72,71,70,67,55,56,52,11,8,10,6                                http://purl.org/datanode/ns/isVocabularyOf  http://purl.org/datanode/ns/relationsOf       
73,72,71,70,67,55,56,52,11,8,10,6                                http://purl.org/datanode/ns/isVocabularyOf  http://purl.org/datanode/ns/typesOf           
68,65,55,54,47,43,34,36,37,27,24,16,18,10,5                      http://purl.org/datanode/ns/hasStandIn      http://purl.org/datanode/ns/hasAnonymized     
37,32,29,24,25,21,22,17,9,4,1,0                                  http://purl.org/datanode/ns/hasSnapshot     http://purl.org/datanode/ns/hasCache          
37,32,24,25,21,9,1                                               http://purl.org/datanode/ns/hasStandIn      http://purl.org/datanode/ns/hasCache        
```

### Adjustment
Examples of operations that can be performed.
In the examples below, `remove` detach a relation from a branch (removes the rdfs:subPropertyOf axiom from the ontology), and `fill` adds all the rules in order to make all sub properties of hasCache propagate all the policies in the intent of concept 99.

```
$ ./ppm-cli.sh operation remove --relation=http://purl.org/datanode/ns/hasCache --branch=http://purl.org/datanode/ns/hasStandIn             
$ ./ppm-cli.sh operation fill --branch=http://purl.org/datanode/ns/hasCache --cid=99
```

Changes are stacked and can be inspected (see below).
Moreover, each of the commands in the tool (exept the `operation` command) can be executed with respect to a given stage using the `--changes=X` parameter.

## Other actions

### Change management
List changes:
```
$ ./ppm-cli.sh changes
```

### Export data
Data can be exported in rdf,prolog or java code snippets.
```
$ ./ppm-cli.sh export ontology rdf
$ ./ppm-cli.sh export rules prolog
$ ./ppm-cli.sh export ontology java --changes=5
```

## References
[1] E. Daga, M. d’Aquin, A. Gangemi, and E. Motta. Propagation of policies in rich data flows. In Proceedings of the 8th In- ternational Conference on Knowledge Capture, K-CAP 2015, pages 5:1–5:8, New York, NY, USA, 2015. ACM.

[2] E. Daga, A. Gangemi, and E. Motta. Reasoning with Data Flows and Policy Propagation Rules [SUBMITTED]
http://www.semantic-web-journal.net/content/reasoning-data-flows-and-policy-propagation-rules