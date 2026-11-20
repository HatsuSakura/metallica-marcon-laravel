export const draftOperations = new Set(['reorder','add_stop','cancel_stop','update_journey','update_order','add_item','update_item','remove_item','restore_item','hide_removed_item','add_holder','update_holder','set_item_allocations','set_item_loaded','restore_original','restore_previous'])
export function draftInput(operation,input,references={}) {
    const result=JSON.parse(JSON.stringify(input))
    const fields={stop_id:'stop',stop_ids:'stop',execution_order_id:'order',item_id:'item'}
    if(operation==='update_holder') fields.holder_id='holder'
    for(const [field,type] of Object.entries(fields)) {
        if(!(field in result)) continue
        const replace=value=>references[type]?.[String(value)] || value
        result[field]=Array.isArray(result[field]) ? result[field].map(replace) : replace(result[field])
    }
    return result
}
