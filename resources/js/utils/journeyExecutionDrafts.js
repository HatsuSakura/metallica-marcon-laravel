export const savedValueEquals = (left, right) => {
    if (left === right) return true
    if ([null,undefined,''].includes(left) && [null,undefined,''].includes(right)) return true
    const numeric = value => (typeof value === 'number' || (typeof value === 'string' && value.trim() !== '')) && Number.isFinite(Number(value))
    return numeric(left) && numeric(right) && Number(left) === Number(right)
}

export const mergeSavedDraft = (draft = {}, previous = {}, next = {}) => {
    const result = {...draft}
    for (const [key,value] of Object.entries(next)) {
        if (!(key in previous) || !(key in draft) || savedValueEquals(draft[key], previous[key])) result[key] = value
    }
    return result
}

export const hasUnsavedDraft = (draft = {}, saved = {}) => Object.keys(saved).some(key => !savedValueEquals(draft[key], saved[key]))

export const changedDraftValues = (draft = {}, saved = {}) => Object.fromEntries(Object.entries(draft).filter(([key,value]) => !savedValueEquals(value, saved[key])))
