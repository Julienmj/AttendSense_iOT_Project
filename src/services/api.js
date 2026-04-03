const API_BASE = import.meta.env.VITE_API_URL
  || (import.meta.env.DEV ? '/api' : '/attendsense/backend/api')

const request = async (endpoint, options = {}) => {
  const { query, ...fetchOptions } = options
  const [resource] = endpoint.replace(/^\//, '').split('/')
  const rest = endpoint.replace(/^\/[^/]+/, '')
  let url = `${API_BASE}/${resource}.php${rest}`
  if (query && Object.keys(query).length) {
    const qs = new URLSearchParams(Object.fromEntries(Object.entries(query).filter(([,v]) => v !== '' && v != null)))
    if (qs.toString()) url += '?' + qs.toString()
  }

  const res = await fetch(url, {
    headers: { 'Content-Type': 'application/json', ...fetchOptions.headers },
    ...fetchOptions,
  })
  const data = await res.json()
  if (!data.success) throw new Error(data.error || 'API error')
  return data.data
}

export const classesAPI = {
  getAll: () => request('/classes'),
  getById: (id) => request(`/classes/${id}`),
  create: (data) => request('/classes', { method: 'POST', body: JSON.stringify(data) }),
  update: (id, data) => request(`/classes/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  delete: (id) => request(`/classes/${id}`, { method: 'DELETE' }),
  getStudents: (id) => request(`/classes/${id}/students`),
  addStudent: (id, data) => request(`/classes/${id}/students`, { method: 'POST', body: JSON.stringify(data) }),
  updateStudent: (cid, sid, data) => request(`/classes/${cid}/students/${sid}`, { method: 'PUT', body: JSON.stringify(data) }),
  removeStudent: (cid, sid) => request(`/classes/${cid}/students/${sid}`, { method: 'DELETE' }),
}

export const sessionsAPI = {
  getAll: () => request('/sessions'),
  getById: (id) => request(`/sessions/${id}`),
  create: (data) => request('/sessions', { method: 'POST', body: JSON.stringify(data) }),
  update: (id, data) => request(`/sessions/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  start: (id) => request(`/sessions/${id}/start`, { method: 'PUT' }),
  end: (id) => request(`/sessions/${id}/end`, { method: 'PUT' }),
  getAttendance: (id) => request(`/sessions/${id}/attendance`),
  markAttendance: (id, data) => request(`/sessions/${id}/attendance`, { method: 'POST', body: JSON.stringify(data) }),
  processDetectedDevice: (id, data) => request(`/sessions/${id}/detect`, { method: 'POST', body: JSON.stringify(data) }),
  getActive: () => request('/sessions/active'),
  getToday: () => request('/sessions/today'),
}

export const reportsAPI = {
  getAttendance: (params = {}) => request('/reports/attendance', { query: params }),
  getClassPerformance: () => request('/reports/class-performance'),
  getStudentAttendance: (id) => request(`/reports/student-attendance/${id}`),
  getSummary: () => request('/reports/summary'),
  getDashboardStats: () => request('/reports/dashboard-stats'),
  exportSession: (id, format = 'csv') => {
    window.open(`${API_BASE}/reports.php/export/sessions/${id}?format=${format}`)
  },
  exportClass: (id, params = {}) => {
    const qs = new URLSearchParams({ format: 'csv', ...params }).toString()
    window.open(`${API_BASE}/reports.php/export/class/${id}?${qs}`)
  },
}

export default { classesAPI, sessionsAPI, reportsAPI }
