# MySite Workspaces Preview Indicator

Provides a block that displays the active workspace context on node pages and
alerts users when a node's latest changes live in a different workspace.

## Overview

When placed on node pages, this block shows:

- The name of the currently active workspace.
- Whether the current workspace contains the node's latest revision.
- A warning if another workspace has more recent changes to the node.

The block has two visual states:

- **Info (green)** -- The active workspace has the latest changes, or the node
  has no workspace-specific changes.
- **Warning (yellow)** -- Another workspace contains more recent changes to the
  node, displayed as: _"This page is being edited in workspace
  {workspace-name}."_

## Requirements

- Drupal 10 or 11
- Core Workspaces module (`workspaces`)

## Installation

1. Enable the module: `drush pm:install mysite_workspaces_preview_indicator`
2. Place the **"MySite Workspace preview indicator"** block in a region on your
node pages (via Block Layout or Layout Builder).

The block requires a node context, so it should be placed on pages where a node
entity is available.
