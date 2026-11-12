# Save All Nodes

This module allows for saving of all nodes for a specific content
type or for all types.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/save_all_nodes).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/save_all_nodes).


### Requirements

No major requirements, but leverages code nodes and pathauto.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Configure settings for module:
   **/admin/config/development/save-all-nodes/settings**
2. Once configured, run batch process from here:
   **/admin/config/development/save-all-nodes**
3. Can also run batch process via Drush command as well. See below
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


### Features

- Admin form which uses Batch API to save all nodes.
- Drush command which also triggers the Batch API process.
- Setting for saving any translations attached to node.
- Setting for regenerating alias on nodes saved.
- Setting for creating new revision on nodes saved.
- Configure chunk size for the number of nodes to process
  per batch process.


## Maintainers

- George Anderson - [geoanders](https://www.drupal.org/u/geoanders)
- Michael O'Hara - [mikeohara](https://www.drupal.org/u/mikeohara)

## UVA Library fork

This is a fork of the drupal.org project `save_all_nodes` (branch `2.x`, including the
`accessCheck(true)` fix from issue #3400676) with one change: `core_version_requirement` and the
`drupal/core` requirement are widened to `^10 || ^11` so it installs on Drupal 11. No code changes.

It exists only because upstream has no Drupal 11 release. **Review after the Drupal 11 upgrade
whether a better approach exists** (upstream release, a replacement, or removing the module).
