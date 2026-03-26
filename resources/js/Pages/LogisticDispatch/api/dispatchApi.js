import axios from 'axios';

function extractApiMessage(error, fallback = 'Operazione non riuscita.') {
    const data = error?.response?.data;
    return data?.message ?? data?.error ?? fallback;
}

async function updateDispatchPlan(journeyId, payload) {
    const { data } = await axios.put(route('api.logistic-dispatch.update-plan', journeyId), payload);
    return data;
}

async function holdDispatch(journeyId, notes) {
    const { data } = await axios.post(route('api.logistic-dispatch.hold', journeyId), { notes });
    return data;
}

async function resumeDispatch(journeyId, notes) {
    const { data } = await axios.post(route('api.logistic-dispatch.resume', journeyId), { notes });
    return data;
}

async function fetchWorkspace(journeyId) {
    const { data } = await axios.get(route('api.logistic-dispatch.workspace', journeyId));
    return data;
}

async function saveJourneyCargos(journeyId, cargos) {
    const { data } = await axios.put(route('api.logistic-dispatch.cargos.save', journeyId), { cargos });
    return data;
}

async function saveWorkspace(journeyId, payload) {
    const { data } = await axios.put(route('api.logistic-dispatch.workspace.save', journeyId), payload);
    return data;
}

async function confirmWorkspace(journeyId, notes = null) {
    const { data } = await axios.post(route('api.logistic-dispatch.confirm', journeyId), { notes });
    return data;
}

async function appendDispatchEvent(journeyId, event, payload = {}) {
    const { data } = await axios.post(route('api.logistic-dispatch.events', journeyId), {
        event,
        payload,
    });
    return data;
}

async function approveTransshipment(transshipmentId, notes = null) {
    const { data } = await axios.post(route('api.logistic-transshipments.approve', transshipmentId), { notes });
    return data;
}

async function closeDispatchAudit(journeyId, notes = null) {
    const { data } = await axios.post(route('api.logistic-dispatch.close', journeyId), { notes });
    return data;
}

export {
    appendDispatchEvent,
    approveTransshipment,
    closeDispatchAudit,
    confirmWorkspace,
    extractApiMessage,
    fetchWorkspace,
    saveJourneyCargos,
    holdDispatch,
    resumeDispatch,
    saveWorkspace,
    updateDispatchPlan,
};
