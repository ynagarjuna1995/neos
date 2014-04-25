.. _creating-a-plugin:

============================
Creating a TYPO3 Neos plugin
============================

Any TYPO3 Flow package can be used as a plugin with a little effort. This section
will guide you through a simple example. First, we will create a really basic
TYPO3 Flow package. Second, we'll expose this TYPO3 Flow package as a Neos plugin.

Creating a TYPO3 Flow package
=============================

First we will create a very simple Flow package to use for integrating it as a plugin.

.. note::
  When developing sites the need for simple plugins will often arise. And those small
  plugins will be very site-specific most of the time. In these cases it makes sense
  to create the needed code inside the site package, instead of in a separate package.

  For the sake of simplicity we will create a seperate package now.

If you do not have the Kickstart package installed, you must do this now:

.. code-block:: bash

  cd /your/htdocs/TYPO3-Neos
  php /path/to/composer.phar require typo3/kickstart \*

Now create a package with a model, so we have something to show in the plugin:

.. code-block:: bash

  ./flow kickstart:package Sarkosh.CdCollection
  ./flow kickstart:model Sarkosh.CdCollection Album title:string year:integer description:string rating:integer
  ./flow kickstart:repository Sarkosh.CdCollection Album

Then generate a migration to create the needed DB schema:

.. code-block:: bash

  ./flow doctrine:migrationgenerate
  mkdir -p Packages/Application/Sarkosh.CdCollection/Migrations/Mysql
  mv Data/DoctrineMigrations/Version<timestamp>.php Packages/Application/Sarkosh.CdCollection/Migrations/Mysql/
  # check the generated migration
  ./flow doctrine:migrate

You should now have a package with a default controller and templates created.

In order to view them you can call the frontend like ``http://neos.demo/sarkosh.cdcollection``,
but you need to include the TYPO3 Flow default routes first (add them before the Neos routes):

*Configuration/Routes.yaml*

.. code-block:: yaml

  -
    name: 'Flow'
    uriPattern: '<FlowSubroutes>'
    defaults:
      '@format': 'html'
    subRoutes:
      FlowSubroutes:
        package: TYPO3.Flow

Now you can add some entries for your CD collection in the database::

  INSERT INTO sarkosh_cdcollection_domain_model_album (
    persistence_object_identifier, title, year, description, rating
  ) VALUES (
    uuid(), 'Jesus Christ Superstar', '1970',
    'Jesus Christ Superstar is a rock opera by Andrew Lloyd Webber, with lyrics by Tim Rice.',
    '5'
  );

(or using your database tool of choice) and adjust the templates so a list of
CDs is shown. When you are done with that, you can make a plugin out of that.

As an optional step you can move the generated package from it's default location
*Packages/Application/* to *Packages/Plugins*. This is purely a convention and at
times it might be hard to tell an "application package" from a "plugin", but it helps
to keep things organized. Technically it has no relevance.

.. code-block:: bash

  mkdir Packages/Plugins
  mv Packages/Application/Sarkosh.CdCollection Packages/Plugins/Sarkosh.CdCollection

Converting a TYPO3 Flow Package Into a Neos Plugin
==================================================

To activate a TYPO3 Flow package as a Neos plugin, you only need to provide two
configuration blocks. First, you need to add a new *node type* for the plugin,
such that the user can choose the plugin from the list of content elements:

Add the following to *Configuration/NodeTypes.yaml* of your package:

.. code-block:: yaml

  'Sarkosh.CdCollection:Plugin':
    superTypes: ['TYPO3.Neos:Plugin']
    ui:
      label: 'CD Collection'
      group: 'plugins'

This will add a new entry labeled "CD Collection" to the "Plugins" group in the content
element selector (existing groups are *General*, *Structure* and *Plugins*).

Second, the rendering of the plugin needs to be specified using TypoScript, so the following
TypoScript needs to be added to your package.

*Resources/Private/TypoScripts/Library/Plugin.ts2*::

  prototype(Sarkosh.CdCollection:Plugin) < prototype(TYPO3.Neos:Plugin)
  prototype(Sarkosh.CdCollection:Plugin) {
       package = 'Sarkosh.CdCollection'
       controller = 'Standard'
       action = 'index'
  }

Finally tweak your site package's *Root.ts2* and include the newly created TypoScript file::

  include: resource://Sarkosh.CdCollection/Private/TypoScripts/Library/Plugin.ts2

Now log in to your Neos backend (you must remove the TYPO3 Flow routes again), and you
will be able to add your plugin just like any other content element.

Configuring a plugin to show specific actions on different pages
================================================================

With the simple plugin you created above all of the actions of that plugin are
executed on one specific page node. But sometimes you might want to break that
up onto different pages. For this use case there is a node type called
``Plugin View``. A plugin view is basically a view of a specific set of actions
configured in your Settings.yaml.

You can update your *Configuration/NodeTypes.yaml* like this to configure which actions
will be available for the ``Plugin View``:

.. code-block:: yaml

  'Sarkosh.CdCollection:Plugin':
    superTypes: ['TYPO3.Neos:Plugin']
    ui:
      label: 'CD Collection'
      group: 'plugins'
    options:
      pluginViews:
        'CollectionShow':
          label: 'Show Collection'
          controllerActions:
            'Sarkosh\CdCollection\Controller\CollectionController': ['show']
        'CollectionOverview':
          label: 'Collection Overview'
          controllerActions:
            'Sarkosh\CdCollection\Controller\CollectionController': ['overview']

When you insert a plugin view for a node the links in both of this nodes get rewritten
automatically to link to the view or plugin, depending on the action the link points
to. Insert a "Plugin View" node in your page, and then, in the inspector, configure
the "Master View" (the master plugin instance) and the "Plugin View".

Fixing Plugin Output
--------------------

If you check the HTML of a page that includes your plugin, you will clearly see that things
are not as they should be. The plugin is included using it's complete HTML, including head
and body tags. This of course results in an invalid document.

.. warning:: The documentation is incomplete at this point. Please ask on irc.freenode.net in #typo3-neos for further details.

.. Neos-Aware Plugin Development
.. =============================

.. TBD

.. Using TYPO3 CR Nodes in a Plugin
.. ================================

.. TBD
