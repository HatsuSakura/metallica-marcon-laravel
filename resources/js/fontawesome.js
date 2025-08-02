/* import the fontawesome core */
import { library } from '@fortawesome/fontawesome-svg-core'
/* import font awesome icon component */
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
/* import icons */
import * as solidIcons from '@fortawesome/free-solid-svg-icons'
import * as regularIcons from '@fortawesome/free-regular-svg-icons'
import * as brandsIcons from '@fortawesome/free-brands-svg-icons'
// Add all icons
library.add(
  ...Object.values(solidIcons).filter(icon => icon.iconName),
  ...Object.values(regularIcons).filter(icon => icon.iconName),
  ...Object.values(brandsIcons).filter(icon => icon.iconName),
)


// Register the component globally
export default FontAwesomeIcon;

