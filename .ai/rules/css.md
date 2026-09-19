---
paths:
  - resources/css/app.css
---

# Css

## Every select carries nk-select and its own pr-*
A native <select> is drawn by the operating system, which ignores most of the border, radius and padding set on it and gives it different box metrics from the <input> beside it — on a phone that reads as unstyled HTML and as fields that do not line up. It shipped to neekah.my that way.

Every select under resources therefore carries the `nk-select` utility (resources/css/app.css): appearance:none plus the 24px line chevron the OS arrow is replaced by. The utility deliberately sets NO padding; each select adds its own pr-9 / pr-10 so its longest option clears the arrow. That works because Tailwind emits every pr-* after every px-*, so padding-right wins — verified in the built CSS, not assumed.

SelectStylingTest scans resources and fails on a select without nk-select, or with nk-select and no pr-*. Keep the class literal on the tag; a class hidden in a PHP variable passes nothing on to the next reader and the test cannot see it either.

Still open: selects and inputs use text-sm (14px), and iOS Safari zooms the page when a control under 16px takes focus. Not fixed, because raising it to 16px changes the mobile design everywhere.
