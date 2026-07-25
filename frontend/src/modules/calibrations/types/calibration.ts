export type CalibrationStatus = 'scheduled' | 'completed' | 'cancelled'

export interface CalibrationCertificate {
  id: string
  calibration_id: string
  filename: string
  filepath: string
  mime_type: string
  size_bytes: number
  certificate_number: string | null
  issuer: string | null
  issued_at: string | null
  validity_start: string | null
  validity_end: string | null
  notes: string | null
  created_at: string
}

export interface Calibration {
  id: string
  equipment: { id: string; name: string; patrimony_id?: string }
  part_name: string | null
  status: CalibrationStatus
  scheduled_date: string
  completed_at: string | null
  next_due_at: string | null
  interval_value: number
  interval_unit: 'months' | 'days' | 'hours'
  responsible: string | null
  laboratory: string | null
  certificate_number: string | null
  notes: string | null
  created_by: { id: string; name: string } | null
  certificates: CalibrationCertificate[]
  is_due: boolean
  is_due_soon: boolean
  created_at: string
  updated_at: string
}

export interface CalibrationFormData {
  equipment_id: string
  part_name?: string
  scheduled_date: string
  interval_value: number
  interval_unit: 'months' | 'days' | 'hours'
  responsible?: string
  laboratory?: string
  notes?: string
}

export interface CompleteCalibrationFormData {
  completed_at?: string
  certificate_number?: string
  responsible?: string
  laboratory?: string
  notes?: string
}

export const CALIBRATION_STATUS_OPTIONS = [
  { label: 'Agendada', value: 'scheduled' },
  { label: 'Concluída', value: 'completed' },
  { label: 'Cancelada', value: 'cancelled' },
]

export const INTERVAL_UNIT_OPTIONS = [
  { label: 'Meses', value: 'months' },
  { label: 'Dias', value: 'days' },
  { label: 'Horas', value: 'hours' },
]
