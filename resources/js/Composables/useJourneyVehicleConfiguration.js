import {computed} from 'vue'
import {journeyVehicleConfiguration} from '@/utils/journeyVehicleConfiguration'
export function useJourneyVehicleConfiguration(form, props) {
    const configuration = computed(() => journeyVehicleConfiguration(form, props.vehicles, props.cargos, props.trailers))
    return {configuration, trailerEnabled:computed(() => configuration.value.trailerAllowed)}
}
