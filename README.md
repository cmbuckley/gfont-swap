# gfont-swap

This little script adds `font-display: swap;` to declaration blocks returned by [Google Fonts](https://developers.google.com/fonts/).

The [`font-display` property](https://developer.mozilla.org/en-US/docs/Web/CSS/@font-face/font-display) controls how a font should be displayed while waiting for assets to load. Typically, browsers will not display the text until the asset has loaded, which prevents a double paint where users see a flash of unstyled text (FOUT), but does result in a period where the text is invisible (FOIT).

For certain fonts, particularly icon fonts, FOIT may be preferable over FOUT, but for most body content, it is generally suggested that the content is more important than the appearance.

This script adds the `font-display: swap` declaration, which causes text in that font to be displayed in fallback font until the Google font is loaded. This is optional, by adding the `?display=swap` parameter; without this, the script just returns the exact same output as Google Fonts.

The reason for this script was to avoid relying on service workers required by an [alternative workaround](https://medium.com/@pierluc/supercharge-google-fonts-with-cloudflare-and-service-workers-25c37462fb6a).

## Usage

Install the script on a server with gzip and ideally [brotli](https://github.com/google/brotli/) support (see [php-ext-brotli](https://github.com/kjdev/php-ext-brotli)).

Wherever you would request an import of a Google font:

```css
@import url('https://fonts.googleapis.com/css?family=Roboto:300,300i,400');
```

Instead, use your own script path, passing the appropriate parameters:

```css
@import url('https://example.com/gfont-swap/?family=Roboto:300,300i,400&display=swap');
```

## Further reading

* [`font-display` for the Masses](https://css-tricks.com/font-display-masses/)
* [`font-display` descriptor](https://www.w3.org/TR/css-fonts-4/#font-display-desc) at W3C
* [google/fonts#358](https://github.com/google/fonts/issues/358) — outstanding issue to support this on Google Fonts
* [Supercharge Google Fonts with Cloudflare and Service Workers](https://medium.com/@pierluc/supercharge-google-fonts-with-cloudflare-and-service-workers-25c37462fb6a) — great workaround using service workers, [discussed in the issue comments](https://github.com/google/fonts/issues/358#issuecomment-423833532)
