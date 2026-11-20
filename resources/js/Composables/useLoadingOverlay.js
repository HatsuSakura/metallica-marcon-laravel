import {ref} from 'vue'
export const isLoading = ref(false)
export function setupAxiosInterceptors(axiosInstance) {
    const pending = new Set()
    const finish = config => {if(config)pending.delete(config);isLoading.value=pending.size>0}
    axiosInstance.interceptors.request.use(config=>{pending.add(config);isLoading.value=true;return config},error=>Promise.reject(error))
    axiosInstance.interceptors.response.use(response=>{finish(response.config);return response},error=>{finish(error.config);return Promise.reject(error)})
}
