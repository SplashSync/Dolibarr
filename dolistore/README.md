# Dolistore Product Page

Content of the Splash module page on [Dolistore](https://www.dolistore.com/product.php?id=1648), one folder per language.

| File                        | Dolistore field              | Format     |
|-----------------------------|------------------------------|------------|
| `<lang>/resume.<lang>.txt`  | Short description (summary)  | Plain text |
| `<lang>/description.<lang>.html` | Description             | HTML       |

## Publishing Rules

- The description goes through Dolistore's CKEditor filter, which keeps a short list of styles per element:
  the layout is built with `<td>` (blocks: background, borders, radius, alignment) and inner `<div>`
  (padding, text color). `p`, `h2`, `h3`, `a` and `span` keep no style; `class`, `<style>`, `colspan` and
  `<h4>` are dropped. Stick to these rules and the page survives any save, in source or visual mode.
- CKEditor sorts CSS properties alphabetically: write borders side by side (`border-top`, `border-left`...)
  rather than `border` + `border-top`, or the shorthand wins.
- Edit these files first, then paste them again: never fix the text directly on Dolistore.
- The Dolistore no longer replaces `module_version_desc` / `dolibarr_min_desc` placeholders: write
  versions as plain text.
- The summary is plain text, one line, no HTML.
