# Bundled fonts

Latin `woff2` files (weights 400 and 700) for GLPI hosts that are offline or
block the Google Fonts CDN (CSP). Served at `/plugins/brandkit/fonts/…`.

| Family | Licence | Files |
| --- | --- | --- |
| Inter | SIL OFL 1.1 (`inter/OFL.txt`) | `inter-latin-400.woff2`, `inter-latin-700.woff2` |
| Poppins | SIL OFL 1.1 (`poppins/OFL.txt`) | `poppins-latin-400.woff2`, `poppins-latin-700.woff2` |
| Roboto | Apache 2.0 (`roboto/LICENSE.txt`) | `roboto-latin-400.woff2`, `roboto-latin-700.woff2` |
| Open Sans | SIL OFL 1.1 (`open_sans/OFL.txt`) | `open_sans-latin-400.woff2`, `open_sans-latin-700.woff2` |
| Montserrat | SIL OFL 1.1 (`montserrat/OFL.txt`) | `montserrat-latin-400.woff2`, `montserrat-latin-700.woff2` |
| Lato | SIL OFL 1.1 (`lato/OFL.txt`) | `lato-latin-400.woff2`, `lato-latin-700.woff2` |
| Nunito | SIL OFL 1.1 (`nunito/OFL.txt`) | `nunito-latin-400.woff2`, `nunito-latin-700.woff2` |
| Raleway | SIL OFL 1.1 (`raleway/OFL.txt`) | `raleway-latin-400.woff2`, `raleway-latin-700.woff2` |
| IBM Plex Sans | SIL OFL 1.1 (`ibm_plex/OFL.txt`) | `ibm_plex-latin-400.woff2`, `ibm_plex-latin-700.woff2` |
| Source Sans 3 | SIL OFL 1.1 (`source_sans/OFL.txt`) | `source_sans-latin-400.woff2`, `source_sans-latin-700.woff2` |

Files were taken from the [Fontsource](https://fontsource.org/) latin subsets
(`@fontsource/*` 5.3.x). Licence texts are the upstream OFL / Apache copies
from the [google/fonts](https://github.com/google/fonts) (and Roboto) repos.

Google Fonts CSS remains an **opt-in** (`style[font_cdn]`) for hosts that
allow `fonts.googleapis.com` / `fonts.gstatic.com`.
