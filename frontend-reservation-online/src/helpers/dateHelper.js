/**
 * Utilitaires pour le traitement et le formatage des dates sans décalage de fuseau horaire
 */

/**
 * Analyse une chaîne de date/heure (SQL 'YYYY-MM-DD HH:mm:ss' ou ISO 'YYYY-MM-DDTHH:mm:ss.sssZ')
 * et renvoie un objet Date fidèle à l'heure locale spécifiée sans décalage UTC intempestif.
 *
 * @param {string|Date} dateInput
 * @returns {Date|null}
 */
export function parseLocalDate(dateInput) {
  if (!dateInput) return null
  if (dateInput instanceof Date) {
    if (isNaN(dateInput.getTime())) return null
    return dateInput
  }

  const str = String(dateInput).trim()
  if (!str) return null

  // Format standard YYYY-MM-DD ou YYYY-MM-DD[ T]HH:mm(:ss)?
  const match = str.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2})(?::(\d{2}))?)?/)
  if (match) {
    const year = parseInt(match[1], 10)
    const month = parseInt(match[2], 10) - 1
    const day = parseInt(match[3], 10)
    const hours = match[4] !== undefined ? parseInt(match[4], 10) : 0
    const minutes = match[5] !== undefined ? parseInt(match[5], 10) : 0
    const seconds = match[6] !== undefined ? parseInt(match[6], 10) : 0

    return new Date(year, month, day, hours, minutes, seconds)
  }

  const d = new Date(str)
  return isNaN(d.getTime()) ? null : d
}

/**
 * Convertit une date API (SQL ou ISO) en format compatible input datetime-local (YYYY-MM-DDTHH:mm)
 */
export function toInputDateTime(dtStr) {
  const d = parseLocalDate(dtStr)
  if (!d) return ''
  try {
    const yyyy = d.getFullYear()
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    const dd = String(d.getDate()).padStart(2, '0')
    const hh = String(d.getHours()).padStart(2, '0')
    const min = String(d.getMinutes()).padStart(2, '0')
    return `${yyyy}-${mm}-${dd}T${hh}:${min}`
  } catch {
    return ''
  }
}

/**
 * Convertit une valeur d'input datetime-local (YYYY-MM-DDTHH:mm) en format API (YYYY-MM-DD HH:mm:ss)
 */
export function toApiDateTime(dtLocal) {
  if (!dtLocal) return ''
  return dtLocal.replace('T', ' ') + (dtLocal.length === 16 ? ':00' : '')
}

/**
 * Formate une date et heure : '14 sept. 2026 à 10:00' ou '14 sept. 2026, 10:00'
 */
export function formatDateTime(dateInput, withTime = true) {
  const d = parseLocalDate(dateInput)
  if (!d) return 'N/A'

  try {
    if (withTime) {
      return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      }).format(d)
    }
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(d)
  } catch {
    return String(dateInput)
  }
}

/**
 * Formate uniquement la date : '14 septembre 2026' ou '14/09/2026'
 */
export function formatDate(dateInput, format = 'long') {
  const d = parseLocalDate(dateInput)
  if (!d) return 'N/A'

  try {
    if (format === 'short') {
      return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      }).format(d)
    }
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    }).format(d)
  } catch {
    return String(dateInput)
  }
}

/**
 * Formate uniquement l'heure : '10:00'
 */
export function formatTime(dateInput) {
  const d = parseLocalDate(dateInput)
  if (!d) return ''

  try {
    return new Intl.DateTimeFormat('fr-FR', {
      hour: '2-digit',
      minute: '2-digit',
    }).format(d)
  } catch {
    return ''
  }
}

/**
 * Formate une plage horaire : '10:00 - 12:30'
 */
export function formatTimeRange(debutInput, finInput) {
  if (!debutInput || !finInput) return ''
  const tDebut = formatTime(debutInput)
  const tFin = formatTime(finInput)
  if (!tDebut || !tFin) return ''
  return `${tDebut} - ${tFin}`
}
