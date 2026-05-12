# AMAANAT MEDICAL — Full Website Project

Generated from Google Stitch export. Organized into standard file structure.

## Folder Structure

```
amaanat-project/
│
├── index.html                  ← Home page
│
├── pages/
│   ├── about.html              ← About Us
│   ├── services.html           ← Our Services
│   ├── equipment.html          ← Equipment Catalog
│   ├── projects.html           ← Our Projects
│   ├── partners.html           ← OEM Partners
│   └── contact.html            ← Contact Us
│
├── css/
│   └── styles.css              ← Global custom styles + font imports
│
├── js/
│   ├── tailwind.config.js      ← Shared Tailwind theme (all colors, fonts, spacing)
│   └── layout.js               ← Shared nav + footer renderer (renderNav / renderFooter)
│
└── images/
    ├── screen-home.png          ← Stitch preview: Home
    ├── screen-about.png         ← Stitch preview: About Us
    ├── screen-services.png      ← Stitch preview: Services
    ├── screen-equipment.png     ← Stitch preview: Equipment
    ├── screen-projects.png      ← Stitch preview: Projects
    ├── screen-partners.png      ← Stitch preview: Partners
    └── screen-contact.png       ← Stitch preview: Contact
```

## How Shared Layout Works

Every page loads `layout.js` and calls two functions at the bottom of `<body>`:

```html
<script src="../js/layout.js"></script>
<script>
  renderNav("about", "../"); // first arg = active page key, second = base path
  renderFooter("../"); // base path to root
</script>
```

- **`renderNav(page, basePath)`** — injects the full nav into `<header id="site-nav">`.
  The matching nav link gets the active (cyan underline) style automatically.
- **`renderFooter(basePath)`** — injects the full footer into `<footer id="site-footer">`.

For `index.html` (root level), call with empty base path:

```js
renderNav("home");
renderFooter();
```

## Design System

Design tokens from Stitch DESIGN.md: _"Clinical Engineering Precision"_

| Token         | Value            |
| ------------- | ---------------- |
| Primary       | `#00263f` (Navy) |
| Secondary     | `#006783` (Teal) |
| Background    | `#f8f9ff`        |
| Font H1/H2/H3 | Inter            |
| Font Body     | Open Sans        |

## Tech Stack

- HTML5 (7 pages)
- Tailwind CSS via CDN + custom config
- Google Material Symbols (icon font)
- Google Fonts: Inter + Open Sans
- Vanilla JS for shared nav/footer
