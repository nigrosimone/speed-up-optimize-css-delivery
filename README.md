# Speed Up - Optimize CSS Delivery

[![CI](https://github.com/nigrosimone/speed-up-optimize-css-delivery/actions/workflows/ci.yml/badge.svg)](https://github.com/nigrosimone/speed-up-optimize-css-delivery/actions/workflows/ci.yml)
[![WordPress plugin](https://img.shields.io/wordpress/plugin/v/speed-up-optimize-css-delivery.svg)](https://wordpress.org/plugins/speed-up-optimize-css-delivery/)
[![Active installs](https://img.shields.io/wordpress/plugin/installs/speed-up-optimize-css-delivery.svg)](https://wordpress.org/plugins/speed-up-optimize-css-delivery/)
[![Downloads](https://img.shields.io/wordpress/plugin/dt/speed-up-optimize-css-delivery.svg)](https://wordpress.org/plugins/speed-up-optimize-css-delivery/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A WordPress plugin that loads stylesheets asynchronously, so the browser can paint the
page instead of waiting for CSS it may not need yet.

📦 [Get it on WordPress.org](https://wordpress.org/plugins/speed-up-optimize-css-delivery/)

## What it does

A `<link rel="stylesheet">` in the `<head>` is render-blocking: the browser refuses to
show anything until that file has downloaded and parsed. For fonts, icon sets and
below-the-fold styling, that's a delay users pay for nothing.

This plugin rewrites every stylesheet tag into a `preload` that promotes itself to a real
stylesheet once loaded — the pattern from
[loadCSS](https://github.com/filamentgroup/loadCSS) by Filament Group:

```html
<!-- before -->
<link rel="stylesheet" href="/fonts.css" media="all">

<!-- after -->
<link rel="preload" href="/fonts.css" as="style" media="all"
      onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="/fonts.css" media="all"></noscript>
```

The `<noscript>` fallback means visitors without JavaScript still get the stylesheet, and
a small inline polyfill covers browsers that don't support `rel="preload"`.

The `media` attribute is preserved, so `media="print"` stays `media="print"`.

## Choosing what stays synchronous

**The recommended setup is to load your vital CSS normally and defer the rest.** Loading
*everything* asynchronously causes a flash of unstyled content: the page paints before its
own layout arrives.

Exclude your main stylesheets with a filter in your theme's `functions.php`:

```php
// Load main and child stylesheets synchronously; defer everything else.
add_filter( 'speed-up-optimize-css-delivery', function ( $handle ) {
    return in_array( $handle, array( 'main-stylesheet', 'child-stylesheet' ), true );
} );
```

Return `true` for any handle that must keep blocking. Good candidates to leave deferred:
fonts, icon sets, and any CSS for content below the fold.

## Things to check before you rely on it

- **Expect a flash of unstyled content until you configure the exclusion above.** That's
  not a bug, it's what deferring your layout CSS looks like.
- The plugin does nothing in the WordPress admin: it checks `is_admin()` and registers no
  hooks there.
- It runs at priority `PHP_INT_MAX`, so it sees the tag after every other filter.

## Requirements

WordPress 3.5 or newer. No server-side requirements.

## Installation

From your dashboard: **Plugins → Add New**, search for *Speed Up - Optimize CSS Delivery*,
install and activate.

Manually: upload the `speed-up-optimize-css-delivery` folder to `/wp-content/plugins/` and
activate it from the **Plugins** menu.

## Development

```bash
composer install        # also activates the git pre-commit hook

composer test           # PHPUnit
composer phpcs          # WordPress Coding Standards
composer phpcbf         # auto-fix coding style
composer lint           # php -l across every shipped file
composer compat         # PHP 7.0+ compatibility
composer check-version  # plugin header, Stable tag and changelog agree
```

Every check above also runs in CI on each pull request, across PHP 7.2 → 8.4, and all of
them block a merge. The one exception is WordPress Plugin Check: it needs Docker and npm,
so an infrastructure hiccup there must not fail a release.

The CI workflows and the helper commands come from
[`nigrosimone/wp-plugin-ci`](https://github.com/nigrosimone/wp-plugin-ci), shared by every
`speed-up-*` plugin so there's one copy to maintain instead of eight.

`js/loadCSS.js` and `js/loadCSS.min.js` ship with CRLF line endings, as they have since
2018. `.gitattributes` marks them `-text` so git never normalises them: what users receive
must not change just because the project moved to GitHub.

### Releases

**GitHub is the source of truth. The WordPress.org SVN repository is a publishing target,
written only by CI — never edit it by hand.**

**Actions → Prepare release → Run workflow**, filling in the version, `Tested up to` and
the changelog. The workflow opens a pull request with the version bump; merging it tags
the release, publishes to WordPress.org and creates a GitHub Release.

A weekly job compares the published SVN trunk against `main` and opens an issue if they
diverge.

## Credits

The async loading technique and the bundled polyfill come from
[loadCSS](https://github.com/filamentgroup/loadCSS) by Filament Group, MIT licensed.

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html) — © Simone Nigro
