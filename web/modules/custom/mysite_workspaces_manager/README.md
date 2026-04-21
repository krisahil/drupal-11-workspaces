# MySite Workspaces Manager

Provides a filterable workspace changes list and workspace management
enhancements for Drupal's core Workspaces module.

## Overview

This module replaces the default workspace view builder with a custom one that
adds:

- **Filter form** -- Filter the workspace changes list by entity title and
  entity type/bundle.
- **Publish status column** -- A new column showing each entity's publish or
  moderation state (e.g., "Draft", "Published").

## Requirements

- Drupal 11+
- Core Workspaces UI module (`workspaces_ui`)

## Installation

Enable the module:

```bash
ddev drush en mysite_workspaces_manager
```

No additional configuration is required. The enhanced workspace view is active
immediately.
