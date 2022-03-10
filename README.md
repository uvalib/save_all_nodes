## Save All Nodes

This module allows for saving of all nodes for a specific content
type or for all types.

### Features

* Admin form which uses Batch API to save all nodes.
* Drush command which also triggers the Batch API process.
* Setting for saving any translations attached to node.
* Setting for regenerating alias on nodes saved.
* Setting for creating new revision on nodes saved.
* Configure chunk size for the number of nodes to process
per batch process.

### Requirements

No major requirements, but leverages code nodes and pathauto.

### Install/Usage

* Install and enable module like any other contrib module.
* Configure settings for module:
  **/admin/config/development/save-all-nodes/settings**
* Once configured, run batch process from here:
  **/admin/config/development/save-all-nodes**
* Can also run batch process via Drush command as well. See below
for more information.

### Drush Command

Replace {content_type} with machine name for the node content type.
Like 'page' for Basic page content type.

```bash
drush save-all-nodes {content_type}
```

Eg.

```bash
drush save-all-nodes page
```

Defaults to page if no content type is provided.

### Maintainers

George Anderson (geoanders)
https://www.drupal.org/u/geoanders
