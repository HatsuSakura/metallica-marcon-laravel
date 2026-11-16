const truthy = value => [true, 1, '1'].includes(value)
export function journeyVehicleConfiguration(form, vehicles, cargos, trailers) {
    const find = (rows, id) => id ? rows.find(row => Number(row.id) === Number(id)) : null
    const vehicle = find(vehicles, form.vehicle_id)
    const fixedName = ({sponda:'Sponda', furgone:'Furgone'})[vehicle?.type]
    const trailerAllowed = !!vehicle && !fixedName && truthy(vehicle.has_trailer)
    const cargo = find(cargos, form.vehicle_cargo_id)
    const trailer = find(trailers, form.trailer_id)
    const trailerCargo = find(cargos, form.trailer_cargo_id)
    const issues = {}
    if (!vehicle) issues.vehicle_id = 'Seleziona la motrice.'
    if (!cargo) issues.vehicle_cargo_id = fixedName ? `Manca il cassone predefinito ${fixedName} in anagrafica.` : 'Seleziona il cassone della motrice.'
    else if (fixedName ? cargo.name !== fixedName : ['Sponda','Furgone'].includes(cargo.name)) issues.vehicle_cargo_id = 'Il cassone non è compatibile con la motrice.'
    if (form.trailer_id) {
        if (!trailerAllowed) issues.trailer_id = 'Questa motrice non può avere un rimorchio.'
        else if (!trailer) issues.trailer_id = 'Seleziona un rimorchio valido.'
        if (!trailerCargo) issues.trailer_cargo_id = 'Seleziona il cassone del rimorchio.'
        else if (['Sponda','Furgone'].includes(trailerCargo.name)) issues.trailer_cargo_id = 'Cassone non compatibile con il rimorchio.'
        else if (cargo && truthy(cargo.is_long) === truthy(trailerCargo.is_long)) issues.trailer_cargo_id = 'Il convoglio richiede un cassone lungo e uno corto.'
    } else if (form.trailer_cargo_id) issues.trailer_cargo_id = 'Il cassone rimorchio richiede un rimorchio.'
    return {vehicle, cargo, trailer, trailerCargo, fixedName, trailerAllowed, issues, ready:Object.keys(issues).length === 0}
}
