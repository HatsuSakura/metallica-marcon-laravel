import { computed } from 'vue'
import {CUSTOM_MARKER_ELEMENTS} from "@/googleMapsConfig"

export const getIconForSite = (site) => {
    const siteType = site.site_type
    const riskFactor = site.calculated_risk_factor ?? 0

    const buildingType = computed( () =>
        siteType == 'fully_operative' ? CUSTOM_MARKER_ELEMENTS.HouseSvgPath :
        siteType == 'only_legal' ? CUSTOM_MARKER_ELEMENTS.OfficeSvgPath :
        siteType == 'only_stock' ? CUSTOM_MARKER_ELEMENTS.GarageSvgPath :
        CUSTOM_MARKER_ELEMENTS.HouseSvgPath
    );

    const buildingFaIcon = computed( () =>
        siteType == 'fully_operative' ? CUSTOM_MARKER_ELEMENTS.HouseFaIcon :
        siteType == 'only_legal' ? CUSTOM_MARKER_ELEMENTS.OfficeFaIcon :
        siteType == 'only_stock' ? CUSTOM_MARKER_ELEMENTS.GarageFaIcon :
        CUSTOM_MARKER_ELEMENTS.HouseFaIcon
    );


    const backgroundColor = computed(() => 
        riskFactor >= 0.85 ? CUSTOM_MARKER_ELEMENTS.fillColorGT85 :
        riskFactor >= 0.75 ? CUSTOM_MARKER_ELEMENTS.fillColor7585 :
        riskFactor >= 0.50 ? CUSTOM_MARKER_ELEMENTS.fillColor5075 :
        CUSTOM_MARKER_ELEMENTS.fillColorLT50
      );
      
    const borderColor = computed( () =>
        riskFactor >= 0.85 ? CUSTOM_MARKER_ELEMENTS.strokeColorGT85 :
        riskFactor >= 0.75 ? CUSTOM_MARKER_ELEMENTS.strokeColor7585 :
        riskFactor >= 0.50 ? CUSTOM_MARKER_ELEMENTS.strokeColor5075 :
        CUSTOM_MARKER_ELEMENTS.strokeColorLT50
    );


  
    return { buildingType, buildingFaIcon, backgroundColor, borderColor }
  }



  

