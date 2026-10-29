# UI kit — emilydiazslp.com (marketing website)

Click-through recreation of the live WordPress/Elementor site at https://emilydiazslp.com, rebuilt on this design system's components.

## Screens
| File | Source page |
| --- | --- |
| `HomeScreen.jsx` | / |
| `AboutScreen.jsx` | /about |
| `ServicesScreen.jsx` | /aac-services |
| `CoursesScreen.jsx` | /courses-2 |
| `ContactScreen.jsx` | /contact (form recreated; live form is an Elementor form) |
| `ResourcesScreen.jsx` | /free-resources opt-in (lead-magnet pattern) |
| `Chrome.jsx` | Header (with services dropdown), footer, `Section` wrapper |

`index.html` is interactive: the nav swaps screens, the services dropdown opens on hover, and both forms submit to a thank-you state.

## Caveats
- **Images are hot-linked from emilydiazslp.com** (photography, flat icons, partner logos). The source files were only available over the network, so they could not be copied into `assets/`. They will not render offline — replace with local files when Emily can supply them.
- The member area (learn.emilydiazslp.com) is behind a login, so no course-platform kit was built. Nothing was invented for it.
