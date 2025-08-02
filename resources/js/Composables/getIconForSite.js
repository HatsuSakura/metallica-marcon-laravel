import { computed } from 'vue'
import {CUSTOM_MARKER_ELEMENTS} from "@/googleMapsConfig"

export const getIconForSite = (site) => {

    const buildingType = computed( () =>
        site.tipologia == 'fully_operative' ? CUSTOM_MARKER_ELEMENTS.HouseSvgPath :
        site.tipologia == 'only_legal' ? CUSTOM_MARKER_ELEMENTS.OfficeSvgPath :
        site.tipologia == 'only_stock' ? CUSTOM_MARKER_ELEMENTS.GarageSvgPath :
        CUSTOM_MARKER_ELEMENTS.HouseSvgPath
    );

    const buildingFaIcon = computed( () =>
        site.tipologia == 'fully_operative' ? CUSTOM_MARKER_ELEMENTS.HouseFaIcon :
        site.tipologia == 'only_legal' ? CUSTOM_MARKER_ELEMENTS.OfficeFaIcon :
        site.tipologia == 'only_stock' ? CUSTOM_MARKER_ELEMENTS.GarageFaIcon :
        CUSTOM_MARKER_ELEMENTS.HouseFaIcon
    );


    const backgroundColor = computed(() => 
        site.fattore_rischio_calcolato >= 0.85 ? CUSTOM_MARKER_ELEMENTS.fillColorGT85 :
        site.fattore_rischio_calcolato >= 0.75 ? CUSTOM_MARKER_ELEMENTS.fillColor7585 :
        site.fattore_rischio_calcolato >= 0.50 ? CUSTOM_MARKER_ELEMENTS.fillColor5075 :
        CUSTOM_MARKER_ELEMENTS.fillColorLT50
      );
      
    const borderColor = computed( () =>
        site.fattore_rischio_calcolato >= 0.85 ? CUSTOM_MARKER_ELEMENTS.strokeColorGT85 :
        site.fattore_rischio_calcolato >= 0.75 ? CUSTOM_MARKER_ELEMENTS.strokeColor7585 :
        site.fattore_rischio_calcolato >= 0.50 ? CUSTOM_MARKER_ELEMENTS.strokeColor5075 :
        CUSTOM_MARKER_ELEMENTS.strokeColorLT50
    );


  
    return { buildingType, buildingFaIcon, backgroundColor, borderColor }
  }



  