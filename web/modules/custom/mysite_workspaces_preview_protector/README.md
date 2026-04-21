# MySite Workspaces Preview Protector

Prevents workspace preview content from being served on public/CDN domains,
protecting against cache poisoning.

## How It Works

When workspace previews are accessed on a CDN-fronted domain, the CDN may cache
preview content and serve it to all visitors. This module blocks workspace
preview access on configured hostnames to prevent that.

It enforces protection at multiple levels:

1. **Route access** -- Adds a custom access check to the
   `wse_preview.workspace_preview` route that returns "forbidden" on CDN
   hostnames.
1. **Cookie negotiator decorator** -- Prevents the WSE Preview cookie-based
   workspace negotiator from activating on CDN hostnames, so preview cookies are
   ignored even if present.

A `CdnStatus` service reads the request hostname and compares it against the
configured `cdn_hostnames` list. The protection mechanisms listed above all
consult this service. On non-CDN hostnames, workspace previews work normally for
users with the appropriate permission.

## Requirements

- Drupal 11+
- WSE Preview module (`wse_preview`)

## Installation

Enable the module:

```bash
drush pm:install mysite_workspaces_preview_protector
```

## Configuration

The module ships with a default configuration that must be updated to match your
environment. Edit the `cdn_hostnames` setting to list the hostnames where
workspace previews should be blocked.

You can update this via Drush:

```bash
drush config:set mysite_workspaces_preview_protector.settings cdn_hostnames.0 "www.example.com" -y
```

Or export, edit, and re-import configuration:

```bash
drush config:export # Edit
config/sync/mysite_workspaces_preview_protector.settings.yml drush config:import
```

Or set it directly in your `settings.php`:

```php
$config['mysite_workspaces_preview_protector.settings']['cdn_hostnames'] = [ 'www.example.com', 'cdn.example.com', ];
```

### Example configuration

```yaml
# mysite_workspaces_preview_protector.settings.yml
cdn_hostnames:
  - www.example.com
  - cdn.example.com
```

Any hostname in this list will have workspace preview access fully blocked. All
other hostnames will allow previews as normal.
