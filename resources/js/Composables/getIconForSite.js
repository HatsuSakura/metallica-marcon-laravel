import { computed } from 'vue'
import {CUSTOM_MARKER_ELEMENTS} from "@/googleMapsConfig"

export const getIconForSite = (site) => {
    const getSite = () => {
        if (typeof site === 'function') {
            return site()
        }
        if (site && typeof site === 'object' && 'value' in site) {
            return site.value
        }
        return site
    }

    const buildingType = computed( () =>
        getSite()?.site_type == 'fully_operative' ? CUSTOM_MARKER_ELEMENTS.HouseSvgPath :
        getSite()?.site_type == 'only_legal' ? CUSTOM_MARKER_ELEMENTS.OfficeSvgPath :
        getSite()?.site_type == 'only_stock' ? CUSTOM_MARKER_ELEMENTS.GarageSvgPath :
        CUSTOM_MARKER_ELEMENTS.HouseSvgPath
    );

    const buildingFaIcon = computed( () =>
        getSite()?.site_type == 'fully_operative' ? CUSTOM_MARKER_ELEMENTS.HouseFaIcon :
        getSite()?.site_type == 'only_legal' ? CUSTOM_MARKER_ELEMENTS.OfficeFaIcon :
        getSite()?.site_type == 'only_stock' ? CUSTOM_MARKER_ELEMENTS.GarageFaIcon :
        CUSTOM_MARKER_ELEMENTS.HouseFaIcon
    );


    const backgroundColor = computed(() => 
        Number(getSite()?.calculated_risk_factor ?? 0) >= 0.85 ? CUSTOM_MARKER_ELEMENTS.fillColorGT85 :
        Number(getSite()?.calculated_risk_factor ?? 0) >= 0.75 ? CUSTOM_MARKER_ELEMENTS.fillColor7585 :
        Number(getSite()?.calculated_risk_factor ?? 0) >= 0.50 ? CUSTOM_MARKER_ELEMENTS.fillColor5075 :
        CUSTOM_MARKER_ELEMENTS.fillColorLT50
      );
      
    const borderColor = computed( () =>
        Number(getSite()?.calculated_risk_factor ?? 0) >= 0.85 ? CUSTOM_MARKER_ELEMENTS.strokeColorGT85 :
        Number(getSite()?.calculated_risk_factor ?? 0) >= 0.75 ? CUSTOM_MARKER_ELEMENTS.strokeColor7585 :
        Number(getSite()?.calculated_risk_factor ?? 0) >= 0.50 ? CUSTOM_MARKER_ELEMENTS.strokeColor5075 :
        CUSTOM_MARKER_ELEMENTS.strokeColorLT50
    );


  
    return { buildingType, buildingFaIcon, backgroundColor, borderColor }
  }



  

