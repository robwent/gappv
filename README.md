# GAPPV — Google Analytics Post Page Views

A WordPress plugin that adds a **GA Views** column to admin list tables, showing the real Google Analytics 4 pageview count for each post, page, custom post type item, and taxonomy term archive.

No tracking code, no front-end output, no data duplication — it queries the [GA4 Data API](https://developers.google.com/analytics/devguides/reporting/data/v1) for the exact page path of each item and caches the result.

## What it looks like

A sortable "GA Views" column on any post type or taxonomy list screen you enable:

- `edit.php` (posts, pages, custom post types) — views per post
- `edit-tags.php` (categories, tags, custom taxonomies) — views per term archive page

Counts are fetched one row at a time via AJAX the first time you view a list, then served from cache. A WP-CLI command can pre-fill counts in the background instead (recommended for large sites — see below).

## Requirements

- WordPress with a GA4 property collecting data for the site
- PHP 8.1+
- The **bcmath** PHP extension (or the protobuf extension) — required by Google's protobuf library; without it every API call is a fatal `Call to undefined function bccomp()` error
- A Google Cloud **service account** with access to your GA4 property

## Installation

1. Download or clone into `wp-content/plugins/gappv` (the Google client library is bundled — no build step needed)
2. Activate the plugin

## Connecting to Google Analytics

The plugin authenticates with a service account JSON key:

1. In [Google Cloud Console](https://console.cloud.google.com/), create (or pick) a project and enable the **Google Analytics Data API**.
2. Create a **service account** (IAM & Admin → Service Accounts), then create a **JSON key** for it and download the file.
3. In Google Analytics, add the service account's email address (`something@project.iam.gserviceaccount.com`) to your GA4 property with **Viewer** access (Admin → Property Access Management).
4. In WordPress, go to **Settings → GAPPV Settings**:
   - **JSON Key** — paste the full contents of the downloaded key file
   - **Analytics Property ID** — the numeric GA4 property ID (Admin → Property Settings)
   - **Start Date** — earliest date to count views from (posts also use their own publish date if later)
   - **Cache Time (H)** — hours to cache each count before refreshing (e.g. `24`)

## Choosing where the column appears

Also under **Settings → GAPPV Settings**:

- **Post Types** — tick the post types that should get the column. Leave all unchecked to show it on every public post type.
- **Taxonomies** — tick the taxonomies whose term list screens should get the column. Taxonomies are opt-in (none by default).

Term counts are matched against the term's archive URL (e.g. `/category/news/`), post counts against the post's permalink. Matching is exact-path, relative to the site root.

## Sorting

The column is sortable for both posts and terms. Items with no stored count yet sort as zero/no value — they fill in as counts are fetched.

## Pre-filling counts with WP-CLI

Fetching counts via admin AJAX is fine for small sites, but with thousands of items it's better to fill them in the background:

```bash
wp gappv populate               # process 10 posts + 10 terms missing a count
wp gappv populate --number=50   # process 50 of each
```

The command only processes items that don't have a stored count yet, so it's safe (and cheap) to run on a schedule. Once the backlog is filled, each run just picks up newly published content. A typical crontab entry:

```cron
# every 15 minutes, offset from the top of the hour
4-59/15 * * * * cd /path/to/site && wp gappv populate --number=25 >/dev/null 2>&1
```

### API quota

A standard GA4 property allows roughly 1,250 quota tokens per hour / 25,000 per day, and each count lookup costs about 1–2 tokens. Each `populate` run makes at most `2 × --number` API calls. The crontab example above peaks at ~200 calls/hour, well within quota.

## How counts are stored

| What | Where |
|------|-------|
| Post counts | `_gappv_views` post meta + `_gappv-{post_id}` transient |
| Term counts | `_gappv_views` term meta + `_gappv-term-{term_id}` transient |

The transient (per **Cache Time**) throttles API refreshes; the meta is the last known value, shown immediately and used for sorting. Uninstalling the plugin removes the settings, all cached transients, and all stored meta.

## Notes

- Posts that genuinely have zero recorded views are stored as `0` and not re-queried by the CLI command (avoids endlessly retrying dead pages).
- Counts refresh when a list screen is viewed after the cache expires; the CLI command only backfills missing counts.
- If page paths change (permalink structure, slug renames), old views under the previous path won't be counted — matching is exact.

## License

GPL-2.0+
