import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  headers: { Accept: 'application/json' },
})

export function unwrapResponse(response) {
  return response.data?.data ?? null
}

export function normalizeError(error) {
  const body = error.response?.data ?? {}

  return Promise.reject({
    status: error.response?.status ?? 0,
    message: body.message ?? 'Não foi possível conectar ao servidor.',
    errors: body.errors ?? {},
  })
}

api.interceptors.response.use(unwrapResponse, normalizeError)

export default api
