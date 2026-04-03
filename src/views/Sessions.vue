<template>
  <div class="fade-in">
    <div class="mb-8">
      <p class="font-sans text-primary text-xs font-semibold tracking-wider mb-2">SESSION MANAGEMENT</p>
      <h1 class="text-3xl font-bold text-text-primary leading-tight">Attendance <span class="text-primary">Sessions</span></h1>
      <p class="text-text-secondary mt-2 max-w-lg text-sm">Create sessions and scan attendance for registered classes.</p>
    </div>

    <div v-if="message" :class="messageType === 'error' ? 'bg-red-50 border-red-200 text-danger' : 'bg-green-50 border-green-200 text-success'"
         class="border rounded-lg px-4 py-3 mb-4 text-sm flex justify-between items-center">
      {{ message }}
      <button @click="message = ''" class="ml-4 font-bold">×</button>
    </div>

    <!-- Create Session Form -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 card-shadow">
      <h2 class="font-sans text-lg font-semibold text-text-primary mb-4">Create New Session</h2>
      <form @submit.prevent="createSession" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Select Class</label>
          <select v-model="newSession.class_id" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">Choose a class</option>
            <option v-for="cls in store.classes" :key="cls.id" :value="cls.id">
              {{ cls.name }} ({{ cls.course_code }})
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Session Name</label>
          <input v-model="newSession.name" type="text" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="e.g., Week 1 Lecture" />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Date</label>
          <input v-model="newSession.date" type="date" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Start Time</label>
          <input v-model="newSession.start_time" type="time" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">End Time</label>
          <input v-model="newSession.end_time" type="time" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" />
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-1">Check-in Window (minutes)</label>
          <input v-model.number="newSession.checkin_window_minutes" type="number" min="5" max="60" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="15" />
        </div>
        <div class="md:col-span-2">
          <button type="submit" :disabled="loading"
            class="px-6 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors disabled:opacity-50">
            {{ loading ? 'Creating...' : 'Create Session' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Active Scanner -->
    <div v-if="activeSession" class="bg-white border border-gray-200 rounded-xl p-6 mb-8 card-shadow">
      <div class="flex justify-between items-center mb-4">
        <h2 class="font-sans text-lg font-semibold text-text-primary">
          Attendance Scanner — {{ activeSession.class_name }}
        </h2>
        <div class="flex items-center gap-3">
          <div class="w-2 h-2 rounded-full bg-success pulse-dot"></div>
          <span class="text-sm font-medium text-success">SCANNING ACTIVE</span>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-50 rounded-lg p-4">
          <p class="text-sm text-text-secondary mb-1">Session</p>
          <p class="text-lg font-semibold text-text-primary">{{ activeSession.name }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
          <p class="text-sm text-text-secondary mb-1">Time</p>
          <p class="text-lg font-semibold text-primary">{{ activeSession.start_time }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
          <p class="text-sm text-text-secondary mb-1">Detected</p>
          <p class="text-lg font-semibold text-success">{{ sessionAttendance.filter(r => r.status === 'present').length }} / {{ activeSession.total_students }}</p>
        </div>
      </div>

      <div class="mb-4">
        <h3 class="font-medium text-text-primary mb-3">Present Students</h3>
        <div v-if="sessionAttendance.filter(r => r.status === 'present').length === 0"
             class="text-text-secondary text-sm">No students detected yet...</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
          <div v-for="record in sessionAttendance.filter(r => r.status === 'present')" :key="record.id"
               class="flex justify-between items-center bg-success/10 border border-success/20 rounded-lg p-3">
            <div>
              <p class="font-medium text-text-primary">{{ record.student_name }}</p>
              <p class="text-sm text-text-secondary">{{ record.student_number }}</p>
            </div>
            <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/20 text-success">Present</span>
          </div>
        </div>
      </div>

      <div class="flex gap-3">

        <button @click="endSession(activeSession.id)"
          class="px-4 py-2 bg-danger text-white rounded-lg font-medium hover:bg-red-600 transition-colors text-sm">
          End Session
        </button>
      </div>
    </div>

    <!-- Sessions List -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 card-shadow">
      <div class="flex justify-between items-center mb-4">
        <h2 class="font-sans text-lg font-semibold text-text-primary">Session History</h2>
        <select v-model="filterStatus"
          class="px-3 py-1 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
          <option value="all">All Sessions</option>
          <option value="active">Active</option>
          <option value="upcoming">Upcoming</option>
          <option value="completed">Completed</option>
        </select>
      </div>

      <div v-if="filteredSessions.length === 0" class="text-text-secondary text-sm py-4 text-center">No sessions found.</div>
      <div class="space-y-3">
        <div v-for="session in filteredSessions" :key="session.id"
             class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
          <div class="flex justify-between items-start mb-3">
            <div>
              <h3 class="font-semibold text-text-primary">{{ session.class_name }} — {{ session.name }}</h3>
              <p class="text-sm text-text-secondary">{{ formatDate(session.date) }} at {{ session.start_time }}</p>
            </div>
            <span class="px-3 py-1 text-xs font-medium rounded-full" :class="statusClass(session.status)">
              {{ session.status }}
            </span>
          </div>

          <div class="grid grid-cols-3 gap-4 text-sm mb-3">
            <div><span class="text-text-secondary">Present:</span>
              <span class="ml-2 font-medium text-success">{{ session.present_count }}</span></div>
            <div><span class="text-text-secondary">Absent:</span>
              <span class="ml-2 font-medium text-danger">{{ session.total_students - session.present_count }}</span></div>
            <div><span class="text-text-secondary">Rate:</span>
              <span class="ml-2 font-medium text-primary">
                {{ session.total_students > 0 ? Math.round(session.present_count / session.total_students * 100) : 0 }}%
              </span></div>
          </div>

          <div class="flex gap-2">
            <button v-if="session.status === 'upcoming'" @click="startSession(session.id)"
              class="px-4 py-1 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
              Start Scan
            </button>
            <button v-if="session.status === 'active'" @click="setActiveView(session)"
              class="px-4 py-1 text-sm bg-success text-white rounded-lg hover:bg-green-600 transition-colors">
              View Scanner
            </button>
            <button @click="viewAttendance(session.id)"
              class="px-4 py-1 text-sm border border-gray-300 text-text-primary rounded-lg hover:bg-gray-50 transition-colors">
              View Details
            </button>
          </div>

          <!-- Attendance detail expand -->
          <div v-if="expandedSession === session.id && expandedAttendance.length > 0" class="mt-3 border-t pt-3">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
              <div v-for="r in expandedAttendance" :key="r.id"
                   class="flex items-center gap-2 text-sm p-2 rounded-lg"
                   :class="r.status === 'present' ? 'bg-success/10' : 'bg-red-50'">
                <span :class="r.status === 'present' ? 'text-success' : 'text-danger'" class="font-bold">●</span>
                <span class="text-text-primary">{{ r.student_name }}</span>
                <span class="text-text-secondary text-xs ml-auto">{{ r.student_number }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useAttendanceStore } from '../stores/attendance.js'
import { sessionsAPI } from '../services/api.js'

const store = useAttendanceStore()
const loading = ref(false)
const message = ref('')
const messageType = ref('success')
const filterStatus = ref('all')
const activeSession = ref(null)
const sessionAttendance = ref([])
const expandedSession = ref(null)
const expandedAttendance = ref([])
let scanInterval = null

const newSession = reactive({
  class_id: '', name: '', date: '', start_time: '', end_time: '', checkin_window_minutes: 15
})

const filteredSessions = computed(() => {
  if (filterStatus.value === 'all') return store.sessions
  return store.sessions.filter(s => s.status === filterStatus.value)
})

onMounted(async () => {
  await Promise.all([store.loadClasses(), store.loadSessions()])
  newSession.date = new Date().toISOString().split('T')[0]
  // Restore active session if any
  const active = store.sessions.find(s => s.status === 'active')
  if (active) setActiveView(active)
})

onUnmounted(() => clearInterval(scanInterval))

function showMsg(text, type = 'success') {
  message.value = text; messageType.value = type
  setTimeout(() => message.value = '', 4000)
}

async function createSession() {
  loading.value = true
  try {
    await store.createSession({ ...newSession })
    Object.assign(newSession, { class_id: '', name: '', start_time: '', end_time: '', checkin_window_minutes: 15 })
    newSession.date = new Date().toISOString().split('T')[0]
    showMsg('Session created')
  } catch (e) {
    showMsg(e.message, 'error')
  } finally {
    loading.value = false
  }
}

async function startSession(id) {
  try {
    await store.startSession(id)
    const session = store.sessions.find(s => s.id === id)
    setActiveView(session)
    showMsg('Session started — scanning active')
  } catch (e) {
    showMsg(e.message, 'error')
  }
}

function setActiveView(session) {
  activeSession.value = session
  refreshAttendance(session.id)
  clearInterval(scanInterval)
  scanInterval = setInterval(() => refreshAttendance(session.id), 5000)
}

async function refreshAttendance(id) {
  try {
    sessionAttendance.value = await sessionsAPI.getAttendance(id)
    // Update present_count in store
    const s = store.sessions.find(s => s.id === id)
    if (s) s.present_count = sessionAttendance.value.filter(r => r.status === 'present').length
  } catch {}
}

async function triggerSimulation() {
  if (!activeSession.value) return
  showMsg('Use the Arduino bridge to detect devices automatically.', 'error')
}

async function endSession(id) {
  if (!confirm('End this session? Absent students will be marked automatically.')) return
  try {
    await store.endSession(id)
    clearInterval(scanInterval)
    activeSession.value = null
    sessionAttendance.value = []
    showMsg('Session ended')
  } catch (e) {
    showMsg(e.message, 'error')
  }
}

async function viewAttendance(id) {
  if (expandedSession.value === id) { expandedSession.value = null; return }
  expandedSession.value = id
  try {
    expandedAttendance.value = await sessionsAPI.getAttendance(id)
  } catch {}
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function statusClass(status) {
  return { active: 'bg-primary/20 text-primary', upcoming: 'bg-warning/20 text-warning',
           completed: 'bg-success/20 text-success', cancelled: 'bg-gray-200 text-gray-600' }[status] || 'bg-gray-200 text-gray-600'
}
</script>
