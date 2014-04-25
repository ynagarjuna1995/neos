.. _interaction-with-the-neos-backend:

=================================
Interaction with the Neos backend
=================================

JavaScript events
=================

Some sites will rely on JavaScript initialization when the page is rendered,
typically on DocumentReady, and typically via jQuery or similar framework.
The Neos backend will however often reload the page via Ajax whenever a node
property is changed, and this might break functionality on sites relying on
custom JavaScript being executed on DocumentReady.

To fix this, the Neos backend will dispatch an event when the page is reloaded
via ajax, and site specific JavaScript can listen on this event to trigger
whatever code is needed to render the content correctly.

.. code-block:: javascript

  if (typeof document.addEventListener === 'function') {
  	document.addEventListener('Neos.PageLoaded', function(event) {
  		// Do your stuff!
  	}, false);
  }

The event object given, will always have the message and time set on
event.detail. Some events might have more attributes set.

Note that this only works in IE9+, Safari, Firefox and Chrome. For earlier IE
versions the events are not triggered.

The Neos backend will dispatch events that can be listened on when the following
events occur:

* **Neos.PageLoaded** Whenever the page reloads by Ajax
* **Neos.EnablePreview** When the backend switches from edit to preview mode
* **Neos.DisablePreview** When the backend switches from preview to edit mode
* **Neos.NodeSelected** When a new node is selected.
* **Neos.NodeUnselected** When a new node is unselected.
* **Neos.ContentModuleLoaded** When the content module is loaded (i.e. when a user is logged in).

The NodeSelected and NodeUnselected events will have additional information about
the selected/unselected node in the event.detail.node attribute.

Example of setting the background-color on elements when they are selected.

.. code-block:: javascript

  document.addEventListener('Neos.NodeSelected', function(event) {
  	event.detail.node.$element.css({backgroundColor: 'blue'});
  }, false);

  document.addEventListener('Neos.NodeUnselected', function(event) {
  	event.detail.node.$element.css({backgroundColor: 'green'});
  }, false);