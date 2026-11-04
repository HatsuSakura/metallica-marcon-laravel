export const restoreFields = [
    {value:'holder_quantity',label:'Contenitori da ritirare'},
    {value:'weight_declared',label:'Peso dichiarato'},
    {value:'description',label:'Descrizione'},
    {value:'cer_code_id',label:'CER'},
    {value:'holder_id',label:'Tipo contenitore'},
    {value:'is_bulk',label:'Materiale sfuso'},
    {value:'actual_containers',label:'Contenitori ritirati'},
    {value:'actual_weight_kg',label:'Peso effettivo'},
    {value:'cargo_location',label:'Zona di carico'},
]

export const restoreActions = options => {
    if (!options) return []
    const actions = []
    if (options.previous?.available) actions.push({operation:'restore_previous',label:'Ripristina valore precedente'})
    if (options.original?.available && !options.same_targets) actions.push({operation:'restore_original',label:'Ripristina originale alla partenza'})
    return actions
}
