import { computed } from 'vue'
import {CUSTOM_MARKER_ELEMENTS} from "@/googleMapsConfig"

export const getIconForOrder = (site, list) => {
    const riskFactor = site.calculated_risk_factor ?? 0

    const buildingType = computed( () =>
        list == 'listMotrice' ? CUSTOM_MARKER_ELEMENTS.TruckSvgPath :
        list == 'listRimorchio' ? CUSTOM_MARKER_ELEMENTS.TrailerSvgPath :
        list == 'listRiempimento' ? CUSTOM_MARKER_ELEMENTS.RouteSvgPath :
        CUSTOM_MARKER_ELEMENTS.OrderSvgPath
    );

    const buildingFaIcon = computed( () =>
        list == 'listMotrice' ? CUSTOM_MARKER_ELEMENTS.TruckFaIcon :
        list == 'listRimorchio' ? CUSTOM_MARKER_ELEMENTS.TrailerFaIcon :
        list == 'listRiempimento' ? CUSTOM_MARKER_ELEMENTS.RouteFaIcon :
        CUSTOM_MARKER_ELEMENTS.OrderFaIcon
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



  

