import { describe, it, expect } from 'vitest'

import api, { unwrapResponse, normalizeError } from '../api'

describe('api', () => {
  it('targets the versioned API path', () => {
    expect(api.defaults.baseURL).toBe('/api/v1')
  })

  it('unwraps the data field of the envelope', () => {
    const response = { data: { message: 'Ok.', status: 200, data: { id: 1 } } }

    expect(unwrapResponse(response)).toEqual({ id: 1 })
  })

  it('returns null when the envelope has no data (204)', () => {
    expect(unwrapResponse({ data: '' })).toBeNull()
  })

  it('normalizes API errors to status, message and errors', async () => {
    const error = {
      response: {
        status: 422,
        data: { message: 'Os dados enviados são inválidos.', errors: { title: ['Obrigatório.'] } },
      },
    }

    await expect(normalizeError(error)).rejects.toEqual({
      status: 422,
      message: 'Os dados enviados são inválidos.',
      errors: { title: ['Obrigatório.'] },
    })
  })

  it('normalizes network failures', async () => {
    await expect(normalizeError(new Error('Network Error'))).rejects.toEqual({
      status: 0,
      message: 'Não foi possível conectar ao servidor.',
      errors: {},
    })
  })
})
