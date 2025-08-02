// src/composables/useLoadingOverlay.js
import { ref } from 'vue'

export const isLoading = ref(false)

export function setupAxiosInterceptors(axiosInstance) {
  let pending = 0

  axiosInstance.interceptors.request.use(cfg => {
    pending++
    isLoading.value = true
    return cfg
  }, err => Promise.reject(err))

  axiosInstance.interceptors.response.use(
    res => {
      pending = Math.max(0, pending - 1)
      if (pending === 0) isLoading.value = false
      return res
    },
    err => {
      pending = Math.max(0, pending - 1)
      if (pending === 0) isLoading.value = false
      return Promise.reject(err)
    }
  )
}
