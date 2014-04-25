.. _FlowQuery Operation Reference:

FlowQuery Operation Reference
=============================

This reference was automatically generated from code on 2014-03-03


add
---

Add another $flowQuery object to the current one.

:Implementation: TYPO3\\Eel\\FlowQuery\\Operations\\AddOperation
:Priority: 1
:Final: No
:Returns: void





children
--------

"children" operation working on generic objects. It iterates over all
context elements and returns the values of the properties given in the
filter expression that has to be specified as argument or in a following
filter operation.

:Implementation: TYPO3\\Eel\\FlowQuery\\Operations\\Object\\ChildrenOperation
:Priority: 1
:Final: No
:Returns: void





children
--------

"children" operation working on TYPO3CR nodes. It iterates over all
context elements and returns all child nodes or only those matching
the filter expression specified as optional argument.

:Implementation: TYPO3\\Neos\\TypoScript\\FlowQueryOperations\\ChildrenOperation
:Priority: 100
:Final: No
:Returns: void





closest
-------

"closest" operation working on TYPO3CR nodes. For each node in the context,
get the first node that matches the selector by testing the node itself and
traversing up through its ancestors.

:Implementation: TYPO3\\Neos\\TypoScript\\FlowQueryOperations\\ClosestOperation
:Priority: 100
:Final: No
:Returns: void





count
-----

Count the number of elements in the context.

If arguments are given, these are used to filter the elements before counting.

:Implementation: TYPO3\\Eel\\FlowQuery\\Operations\\CountOperation
:Priority: 1
:Final: Yes
:Returns: integer with the number of elements





filter
------

This filter implementation contains specific behavior for use on TYPO3CR
nodes. It will not evaluate any elements that are not instances of the
`NodeInterface`.

The implementation changes the behavior of the `instanceof` operator to
work on node types instead of PHP object types, so that::

	[instanceof TYPO3.Neos.NodeTypes:Page]

will in fact use `isOfType()` on the `NodeType` of context elements to
filter. This filter allow also to filter the current context by a given
node. Anything else remains unchanged.

:Implementation: TYPO3\\Neos\\TypoScript\\FlowQueryOperations\\FilterOperation
:Priority: 100
:Final: No
:Returns: void





filter
------

Filter operation, limiting the set of objects. The filter expression is
expected as string argument and used to reduce the context to matching
elements by checking each value against the filter.

A filter expression is written in Fizzle, a grammar inspired by CSS selectors.
It has the form `"[" [<value>] <operator> <operand> "]"` and supports the
following operators:

=
  Strict equality of value and operand
$=
  Value ends with operand (string-based)
^=
  Value starts with operand (string-based)
\*=
  Value contains operand (string-based)
instanceof
  Checks if the value is an instance of the operand

For the latter the behavior is as follows: if the operand is one of the strings
object, array, int(eger), float, double, bool(ean) or string the value is checked
for being of the specified type. For any other strings the value is used as
classname with the PHP instanceof operation to check if the value matches.

:Implementation: TYPO3\\Eel\\FlowQuery\\Operations\\Object\\FilterOperation
:Priority: 1
:Final: No
:Returns: void





find
----

"find" operation working on TYPO3CR nodes. This operation allows for retrieval
of nodes specified by a path. The current context node is also used as a context
for evaluating relative paths.

:Implementation: TYPO3\\Neos\\TypoScript\\FlowQueryOperations\\FindOperation
:Priority: 100
:Final: No
:Returns: void





first
-----

Get the first element inside the context.

:Implementation: TYPO3\\Eel\\FlowQuery\\Operations\\FirstOperation
:Priority: 1
:Final: No
:Returns: void





get
---

Get a (non-wrapped) element from the context.

If FlowQuery is used, the result is always another FlowQuery. In case you
need to pass a FlowQuery result (and lazy evaluation does not work out) you
can use get() to unwrap the result from the "FlowQuery envelope".

If no arguments are given, the full context is returned. Otherwise the
value contained in the context at the index given as argument is
returned. If no such index exists, NULL is returned.

:Implementation: TYPO3\\Eel\\FlowQuery\\Operations\\GetOperation
:Priority: 1
:Final: Yes
:Returns: mixed





is
--

Check whether the at least one of the context elements match the given filter.

Without arguments is evaluates to TRUE if the context is not empty. If arguments
are given, they are used to filter the context before evaluation.

:Implementation: TYPO3\\Eel\\FlowQuery\\Operations\\IsOperation
:Priority: 1
:Final: Yes
:Returns: boolean





last
----

Get the last element inside the context.

:Implementation: TYPO3\\Eel\\FlowQuery\\Operations\\LastOperation
:Priority: 1
:Final: No
:Returns: void





next
----

"next" operation working on TYPO3CR nodes. It iterates over all
context elements and returns each following sibling or only those matching
the filter expression specified as optional argument.

:Implementation: TYPO3\\Neos\\TypoScript\\FlowQueryOperations\\NextOperation
:Priority: 100
:Final: No
:Returns: void





parent
------

"parent" operation working on TYPO3CR nodes. It iterates over all
context elements and returns each direct parent nodes or only those matching
the filter expression specified as optional argument.

:Implementation: TYPO3\\Neos\\TypoScript\\FlowQueryOperations\\ParentOperation
:Priority: 100
:Final: No
:Returns: void





parents
-------

"parents" operation working on TYPO3CR nodes. It iterates over all
context elements and returns the parent nodes or only those matching
the filter expression specified as optional argument.

:Implementation: TYPO3\\Neos\\TypoScript\\FlowQueryOperations\\ParentsOperation
:Priority: 100
:Final: No
:Returns: void





prev
----

"prev" operation working on TYPO3CR nodes. It iterates over all
context elements and returns each preceding sibling or only those matching
the filter expression specified as optional argument

:Implementation: TYPO3\\Neos\\TypoScript\\FlowQueryOperations\\PrevOperation
:Priority: 100
:Final: No
:Returns: void





property
--------

Used to access properties of a TYPO3CR Node. If the property mame is
prefixed with _, internal node properties like start time, end time,
hidden are accessed.

:Implementation: TYPO3\\Neos\\TypoScript\\FlowQueryOperations\\PropertyOperation
:Priority: 100
:Final: Yes
:Returns: mixed





property
--------

Access properties of an object using ObjectAccess.

Expects the name of a property as argument. If the context is empty, NULL
is returned. Otherwise the value of the property on the first context
element is returned.

:Implementation: TYPO3\\Eel\\FlowQuery\\Operations\\Object\\PropertyOperation
:Priority: 1
:Final: Yes
:Returns: mixed





siblings
--------

"siblings" operation working on TYPO3CR nodes. It iterates over all
context elements and returns all sibling nodes or only those matching
the filter expression specified as optional argument.

:Implementation: TYPO3\\Neos\\TypoScript\\FlowQueryOperations\\SiblingsOperation
:Priority: 100
:Final: No
:Returns: void





slice
-----

Slice the current context

If no arguments are given, the full context is returned. Otherwise the
value contained in the context are sliced with offset and length.

:Implementation: TYPO3\\Eel\\FlowQuery\\Operations\\SliceOperation
:Priority: 1
:Final: No
:Returns: void




