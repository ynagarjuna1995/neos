.. _TYPO3 Neos Command Reference:

TYPO3 Neos Command Reference
============================

.. note:

  This reference uses ``./flow`` as the command to invoke. If you are on
  Windows, this will probably not work, there you need to use ``flow.bat``
  instead.

The commands in this reference are shown with their full command identifiers.
On your system you can use shorter identifiers, whose availability depends
on the commands available in total (to avoid overlap the shortest possible
identifier is determined during runtime).

To see the shortest possible identifiers on your system as well as further
commands that may be available, use::

  ./flow help

The following reference was automatically generated from code on 2014-03-03


Package *TYPO3.NEOS*
--------------------


``typo3.neos:domain:add``
*************************

**Add a domain record**



Arguments
^^^^^^^^^

``--site-node-name``
  The nodeName of the site rootNode, e.g. "neostypo3org
``--host-pattern``
  The host pattern to match on, e.g. "neos.typo3.org







``typo3.neos:domain:delete``
****************************

**Delete a domain record**



Arguments
^^^^^^^^^

``--host-pattern``
  The host pattern of the domain to remove







``typo3.neos:domain:list``
**************************

**Display a list of available domain records**





Options
^^^^^^^

``--host-pattern``
  An optional host pattern to search for





``typo3.neos:node:autocreatechildnodes``
****************************************

**Create missing childNodes for a node type**

This command automatically creates missing child nodes for a node type
based on the structure defined in the NodeTypes configuration.

Example for creating child nodes for the TYPO3.Neos.NodeTypes:Page node type in the
live workspace:

./flow node:autocreatechildnodes --node-type TYPO3.Neos.NodeTypes:Page

Arguments
^^^^^^^^^

``--node-type``
  Node type name



Options
^^^^^^^

``--workspace``
  Workspace name, default is 'live'





``typo3.neos:site:export``
**************************

**Export sites content**

This command exports all or one specific site with all its content into an XML
format.

If the filename option is given, any resources will be exported
to files in a folder named "Resources" alongside the XML file.

If not given, the XML will be printed to standard output and assets will be embedded
into the XML in base64 encoded form.



Options
^^^^^^^

``--site-node``
  the node name of the site to be exported; if none given will export all sites
``--tidy``
  Whether to export formatted XML
``--filename``
  relative path and filename to the XML file to create. Any resource will be stored in a sub folder "Resources". If omitted the export will be printed to standard output





``typo3.neos:site:import``
**************************

**Import sites content**

This command allows for importing one or more sites or partial content from an XML source. The format must
be identical to that produced by the export command.

If a filename is specified, this command expects the corresponding file to contain the XML structure

If a package key is specified, this command expects a Sites.xml file to be located in the private resources
directory of the given package:
.../Resources/Private/Content/Sites.xml



Options
^^^^^^^

``--package-key``
  Package key specifying the package containing the sites content
``--filename``
  relative path and filename to the XML file containing the sites content





``typo3.neos:site:list``
************************

**Display a list of available sites**









``typo3.neos:site:prune``
*************************

**Remove all content and related data - for now. In the future we need some more sophisticated cleanup.**





Options
^^^^^^^

``--confirmation``
  





``typo3.neos:user:addrole``
***************************

**Add a role to a user**

This command allows for adding a specific role to an existing user.
Currently supported roles: "TYPO3.Neos:Editor", "TYPO3.Neos:Administrator"

Arguments
^^^^^^^^^

``--username``
  The username of the user
``--role``
  Role ot be added to the user







``typo3.neos:user:create``
**************************

**Create a new user**

This command creates a new user which has access to the backend user interface.
It is recommended to user the email address as a username.

Arguments
^^^^^^^^^

``--username``
  The username of the user to be created.
``--password``
  Password of the user to be created
``--first-name``
  First name of the user to be created
``--last-name``
  Last name of the user to be created



Options
^^^^^^^

``--roles``
  A comma separated list of roles to assign





``typo3.neos:user:remove``
**************************

**Remove a user which has access to the backend user interface.**



Arguments
^^^^^^^^^

``--username``
  The username of the user to be removed.



Options
^^^^^^^

``--confirmation``
  





``typo3.neos:user:removerole``
******************************

**Remove a role from a user**



Arguments
^^^^^^^^^

``--username``
  The username of the user
``--role``
  Role ot be removed from the user







``typo3.neos:user:setpassword``
*******************************

**Set a new password for the given user**

This allows for setting a new password for an existing user account.

Arguments
^^^^^^^^^

``--username``
  Username of the account to modify
``--password``
  The new password







``typo3.neos:workspace:discardall``
***********************************

**Discard everything in the workspace with the given workspace name.**



Arguments
^^^^^^^^^

``--workspace-name``
  



Options
^^^^^^^

``--verbose``
  





``typo3.neos:workspace:publishall``
***********************************

**Publish everything in the workspace with the given workspace name.**



Arguments
^^^^^^^^^

``--workspace-name``
  



Options
^^^^^^^

``--verbose``
  





