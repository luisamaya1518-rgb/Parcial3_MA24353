import { defineStore } from 'pinia'
import api from '@/services/api'
import type { LoginResponse, Role, User, VerifyResponse } from '@/types'

interface AuthState {
  token: string | null
  user: User | null
  mustChangePassword: boolean
  tempToken: string | null
  qr: string | null
}

export interface ChangePasswordPayload {
  current_password: string
  password: string
  password_confirmation: string
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    token: localStorage.getItem('token'),
    user: JSON.parse(localStorage.getItem('user') || 'null') as User | null,
    mustChangePassword: localStorage.getItem('mcp') === '1',
    tempToken: null,
    qr: null,
  }),

  getters: {
    isAuth: (s): boolean => !!s.token,
    role: (s): Role | undefined => s.user?.role,
  },

  actions: {
    // Siempre devuelve un token temporal; el QR solo viene la primera vez
    async login(email: string, password: string): Promise<void> {
      const { data } = await api.post<LoginResponse>('/login', { email, password })
      this.tempToken = data.token
      this.qr = data.requires_qr ? (data.qr ?? null) : null
    },

    async verify(code: string): Promise<void> {
      const { data } = await api.post<VerifyResponse>('/2fa/verify', { code })
      this.tempToken = null
      this.qr = null
      this.token = data.token
      this.user = data.user
      this.mustChangePassword = data.must_change_password
      localStorage.setItem('token', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
      localStorage.setItem('mcp', data.must_change_password ? '1' : '0')
    },

    async changePassword(payload: ChangePasswordPayload): Promise<void> {
      await api.post('/change-password', payload)
      this.mustChangePassword = false
      localStorage.setItem('mcp', '0')
    },

    async logout(): Promise<void> {
      try {
        await api.post('/logout')
      } catch {
        /* la sesión ya había vencido */
      }
      this.clear()
    },

    clear(): void {
      this.token = null
      this.user = null
      this.tempToken = null
      this.qr = null
      this.mustChangePassword = false
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('mcp')
    },
  },
})