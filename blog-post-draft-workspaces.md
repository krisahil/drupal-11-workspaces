# Introduction

Have you ever wanted to preview a set of content changes across your entire
Drupal site, before pushing them live? Not just one article, but a whole batch
of changes? Drupal core's Workspaces module can help you, but you need more than
what Drupal core provides.

In this post, I'll show you the modules and customizations you need to build a
production-ready Workspaces workflow.

# Why Workspaces?

Consider these scenarios:

- A marketing team needs to update 20 pages for a product launch, and all the
  changes must go live at the same time.
- A content editor wants stakeholder review of a set of changes across multiple
  content types before publishing.
- Your organization requires a staging workflow for content, not just code.

Without Workspaces, Drupal's content moderation operates on individual nodes.
You can draft a single node, but there's no built-in way to group a batch of
changes and publish them together. The Workspaces module solves this problem by
introducing an isolated environment where all content changes are tracked,
previewed, and published as a unit.

# Features

## Core's Workspaces: Start the experience

Drupal core provides base functionality for workspaces in modules *Workspaces*
and *Workspaces UI*. This includes:

- **Isolated editing environments.** Create a workspace, switch into it, and
  every content change you make is tracked in that workspace. Other users
  visiting the live site see no changes until you publish the workspace.
- **Full-site preview.** While active in a workspace, the entire site reflects
  your in-progress changes. This is a full-site preview, not a single-node
  preview — you can navigate between pages and see how your changes look in
  context.
  - *Note:* This requires the user to log into Drupal and have access to the
    admin toolbar. See contrib module recommendations below for a better
    experience.
- **Publish as a batch.** When your changes are ready, publish the workspace.
  All tracked revisions are pushed to the live site at once.
- **Revision tracking.** Workspaces hooks into Drupal's entity revision system.
  Every change you make in a workspace creates a new revision associated with
  that workspace.

### How does it work?

If you’re curious about how Workspaces actually works, check out [Appendix
1](#appendix-1:-how-does-workspaces-work?).

## Contrib add-ons: Extend the experience

I would highly recommend these contributed modules to add essential features to
the Workspaces experience.

### Workspaces Extra

The [Workspaces Extra](https://www.drupal.org/project/wse) module adds helpful
features like workspace status tracking (open/closed), rollback capabilities,
content movement between workspaces, and revision squashing on publish.

This module also provides a suite of helpful add-ons. See that module's page for
its full list of add-ons. I'll highlight a few below.

#### Workspaces Preview (wse\_preview)

This is one of WSE's standout features. It generates shareable preview links
that allow anyone — including unauthenticated users — to view workspace content
without logging in. This is invaluable for stakeholder review.

The flow works like this:

1. Create a workspace and make your content changes.
2. Generate a preview link for the workspace.
3. Share the link with reviewers. When they visit it, a cookie-based session
activates and they see the workspace content as they browse the site.

To configure this, enable module *Workspaces Preview*. Then grant the "Access
workspace previews" permission to the appropriate roles (including Anonymous, if
external reviewers need access).

#### Workspaces Menu (wse\_menu)

Allows you to stage menu hierarchy changes.

*Note:* In my usage, after enabling the module, I needed to run database updates
and flush caches before it worked as expected. This behavior may have been fixed
since I last installed this module (TODO test).

Example to enable this module and perform these additional steps:

``` drush pm:install wse_menu drush updatedb drush cache:rebuild ```

### Workspaces Parallel

The [Workspaces Parallel](https://www.drupal.org/project/workspaces_parallel)
module (`drupal/workspaces_parallel`) addresses a significant limitation of core
Workspaces: by default, a piece of content can only be edited in one workspace
at a time. With Workspaces Parallel, the same content can exist and be edited in
multiple workspaces simultaneously.

A word of caution: This module does not provide conflict resolution. That is, if
you edit a node from two different workspaces, the workspace published second
will overwrite the first.

Or, consider this more likely scenario. You create a workspace "Tuesday" on
Tuesday. On Wednesday, you make changes to a node in that workspace. On
Thursday, you switch back to the live workspace in order to fix a critical typo
on that same node. Then, on Friday, you publish the "Tuesday" workspace. If you
did not also fix the typo in this workspace, then your typo fix (from the live
workspace) will be overwritten when you publish the "Tuesday" workspace.

## Custom modules: Complete the experience

When we implemented Workspaces on an enterprise Drupal application, we developed
a few modules that further helped our site (these may make good contrib modules,
hint hint).

### Workspace preview indicator

This block alerts a user when they are in a workspace preview. This indicator
helps stakeholders easily know when they are in a workspace preview, including
whether the current page has been changed in this workspace.

See [the module's
code](https://github.com/krisahil/drupal-11-workspaces/tree/main/web/modules/custom/mysite_workspaces_preview_indicator).

Example:

### Searchable change list

Drupal core's workspace manager lists the changes in a workspace. If you have
many changes in this workspace, the changes are split into pages, and you cannot
search for changes. This behavior is a problem if you have a lot of changes and
need to quickly find a node change in this list.

To address this problem, we wrote an alternative workspace manager that allows
you to filter the changed items in a workspace, by title and entity type+bundle.

See [the module's
code](https://github.com/krisahil/drupal-11-workspaces/tree/main/web/modules/custom/mysite_workspaces_manager).

Example:

**Performance note**: This custom list is very expensive for performance,
because 1\) it doesn't use a pager and 2\) it post-processes the rows to exclude
ones that don't match filter criteria. In my experience, even when there are
just a few hundred changes, the page can take 10-20 seconds to load. If I
re-implemented this, I would try to find a more performant method (e.g.,
integrate the search filters into the SQL queries as `WHERE` clauses, instead of
loading all of a workspace’s entities and then pruning items that don’t match
the search filters).

### CDN-safe previews

If your website uses a CDN or other external page cache (e.g., Cloudflare,
Fastly, Varnish, Akamai, etc.), you *might* risk corrupting your page cache when
you view a Workspace preview.

This will only affect you if all of the following are true:

1. You use an external CDN for caching pages.
2. Your CDN domain name is different from your origin's domain name. For
example, your public domain is `www.acme.com` and your origin/internal domain is
`drupal.acme.com`.
3. Your internal users (e.g., editorial staff and Drupal admins) use the origin
domain, but all public traffic is routed to the CDN domain.
4. Your CDN is configured to ignore cookies. This is not default behavior for
most CDNs; unless you have specifically configured your page cache to ignore
cookies, this condition probably does not apply to you.

If your site meets all of these conditions, you should consider using this
custom module, to prevent someone from opening a workspace preview on the CDN’s
public domain name. If someone opens a workspace preview on the CDN domain, the
CDN might cache that page and serve it to other users, thus accidentally
exposing non-published changes.

See [the module's
code](https://github.com/krisahil/drupal-11-workspaces/tree/main/web/modules/custom/mysite_workspaces_preview_protector).

**Technical explanation**: When you enter a workspace preview, Drupal sets a
cookie to restrict you to this specific workspace. When this cookie is set,
Drupal disables page caches, and allows the workspace preview to determine which
versions of pages to serve you. If your page cache does not respect cookies when
it considers whether to store a page in the cache, and if a user is in a
workspace preview and visits a previously-uncached page, the CDN may cache that
page. Later, when someone else requests the page at that URL, the CDN would
serve that cached page to them, regardless of whether that user is in a
workspace preview.

### Publish workspace in a code deployment

To publish a workspace, you can use the workspace form in the UI. However, this
form is problematic for large change sets, because it does not use batching
(e.g., you run the risk of PHP memory limits or server timeouts). To work around
this, you can publish a workspace with a deploy hook (triggered by `drush
deploy`). While this method requires planning, it gives you a more testable
approach.

Here is [an example deploy
hook](https://github.com/krisahil/drupal-11-workspaces/blob/main/web/modules/custom/mysite/mysite.deploy.php),
in a sample custom module named `mysite`.

# Patches

Working with Workspaces in production, I encountered a few core and contrib
issues that required patches. These are worth tracking:

- Drupal core (tested against release 11.3.7)
  - [\#3511204](https://www.drupal.org/project/drupal/issues/3511204):
    Workspaces shouldn't disable the `latest-version` link template ([patch
    direct
    link](https://github.com/krisahil/drupal-11-workspaces/blob/main/patches/3511204-patch__MR-13382__commit-e2e8edf2.patch)).
  - [\#3541380](https://www.drupal.org/project/drupal/issues/3541380):
    Workspaces loads the wrong revision on the node edit form when Content
    Moderation is also enabled ([patch direct
    link](https://www.drupal.org/files/issues/2025-12-17/node-edit-form-using-wrong-revision-with-workspaces-and-content-moderation-3541380-3.patch)).
- Workspaces Extra (tested against 3.0.0-alpha2)
  - [\#3563768](https://www.drupal.org/project/wse/issues/3563768): Adds context
    and warnings to the WSE discard changes form ([patch direct
    link](https://github.com/krisahil/drupal-11-workspaces/blob/main/patches/3563768-patch__MR-126-49259c14.patch)).
- Redirect (tested against 1.12.0)
  - No issue: Use [this
    patch](https://github.com/krisahil/drupal-11-workspaces/blob/main/patches/redirect__do-not-auto-create-redirect-in-a-workspace.patch)
    to prevent the Redirect module from automatically creating redirects for URL
    alias changes inside workspaces (redirects are not revisionable entities, so
    they do not play well with Workspaces). But don't worry; when you publish a
    workspace, Redirect will then hook in to automatically create redirects for
    changed URL aliases.

# Wrapping up

Drupal's Workspaces is a powerful tool for managing sets of content changes.
Combined with contrib and custom add-ons, you can use Workspaces to enable your
team to safely and efficiently create, stage, review, and publish large sets of
changes.

# Additional resources

- [DrupalCon presentation (Sep 2024\) about Workspaces and helpful contrib
  modules](https://www.youtube.com/watch?v=bgJ-oubGjbc)
- [Workspaces module
  documentation](https://www.drupal.org/docs/core-modules-and-themes/core-modules/workspaces-module)
- [WSE (Workspaces Extra) project page](https://www.drupal.org/project/wse)
- [Workspaces Parallel project
  page](https://www.drupal.org/project/workspaces_parallel)

# Appendices

## Appendix 1: How does Workspaces work?
{#appendix-1:-how-does-workspaces-work?}

In short, Workspaces is a way to group entity revisions. The Workspace module
tracks these revisions, and displays them only when a user is inside this
workspace. If someone publishes the workspace, then those revisions get promoted
to the live site (i.e., the default workspace).

In the Drupal UI, an administrator (or whoever has appropriate permissions) can
create a workspace. They can manage and publish it in the UI. At this time,
there are no Drush integrations for command-line Workspaces control.

Here’s a technical illustration of how workspaces works:

* In the default workspace, node \#1 has 3 revisions. Revision \#3 is published,
  so the site always uses that revision when node \#1 is requested.
* Someone creates a workspace named “Wednesday changes”.
* Inside this workspace, they update node \#1, which results in revision \#4.
* When this workspace is active, if node \#1 is requested, then the Workspaces
  module hooks in to provide revision \#4.
* Otherwise, when someone in the default workspace requests this node,
  Workspaces module ignores that request, which means the user gets revision
  \#3.

Workspaces accomplishes this in [an entity pre-load
hook](https://git.drupalcode.org/project/drupal/-/blob/11.3.7/core/modules/workspaces/src/Provider/WorkspaceProviderBase.php#L91).

## Appendix 2: FAQs

Q: *Is a workspace preview environment on a different domain name?*  A: No. The
workspace preview is on the same domain name. Drupal knows which workspace
preview (if any) to display based on your session data (if logged into Drupal)
or a cookie (if logged out).
