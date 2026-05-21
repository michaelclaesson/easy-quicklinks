# Easy Quicklinks

A WordPress plugin that lets editors assign short, top-level redirect slugs to pages directly from the post quick edit panel.

## How it works

A **quick link** is a short, memorable path (e.g. `/testskolan`) that redirects visitors to an existing page regardless of where that page lives in the page hierarchy. The redirect is a `301 Permanent Redirect`.

- A new **Quick Link** column appears in the Pages list.
- Editors set or clear the slug via the built-in **Quick Edit** panel.
- On the frontend, any matching top-level request that would otherwise produce a 404 is silently redirected to the target page.
- WordPress's slug uniqueness logic is extended so that no page can claim a permalink slug already in use as a quick link.

## Requirements

| Requirement | Version      |
| ----------- | ------------ |
| WordPress   | 6.4 or later |
| PHP         | 8.3 or later |

## Installation

1. Upload the `easy-quicklinks` folder to `wp-content/plugins/`.
2. Activate the plugin via **Plugins → Installed Plugins**.
3. No further configuration is required.

## Usage

1. Go to **Pages** in the WordPress admin.
2. Hover over a page and click **Quick Edit**.
3. Enter a slug in the **Quick Link** field (lowercase letters and dashes only, e.g. `testskolan`).
4. Click **Update**.

The quick link is immediately live. Visiting `https://example.com/testskolan` will redirect to the target page.

To remove a quick link, open **Quick Edit** and clear the field, then click **Update**.

## Slug format

Quick link slugs must match the pattern `[a-z][a-z-]*` — they start with a lowercase letter and contain only lowercase letters and dashes. No numbers, no uppercase, no special characters.

## Conflict prevention

The plugin registers a filter on `wp_unique_post_slug` so that WordPress will never assign a page a permalink slug that is already claimed by a quick link belonging to another page. If a conflict is detected, WordPress appends a numeric suffix to the new page's slug instead.

## Integrations

- **Nested Pages** — the quick link value is preserved when pages are reordered or updated via the Nested Pages plugin.

## Translations

The plugin ships with a Swedish (`sv_SE`) translation. Additional translations can be placed in `languages/` following the standard WordPress naming convention (`easy-quicklinks-{locale}.po/.mo`).

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)
