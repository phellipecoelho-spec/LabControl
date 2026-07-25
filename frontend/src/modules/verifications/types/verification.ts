export type VerificationResult = 'within_range' | 'outside_range' | 'not_measured'

export type VerificationFrequency = 'daily' | 'weekly' | 'shift'

export interface VerificationTemplate {
  id: string
  equipment_category_id: string
  parameter_name: string
  parameter_unit: string | null
  tolerance_min: number | null
  tolerance_max: number | null
  sort_order: number
}

export interface VerificationParam {
  id: string
  verification_id: string
  template_id: string
  value: string | null
  result: VerificationResult
  result_label: string
  notes: string | null
  template?: VerificationTemplate
}

export interface Verification {
  id: string
  equipment: { id: string; name: string; patrimony_id?: string } | null
  verified_at: string
  operator: { id: string; name: string } | null
  params: VerificationParam[]
  notes: string | null
  is_outside_range: boolean
  created_at: string
}

export interface PendingEquipment {
  id: string
  name: string
  patrimony_id: string | null
  serial_number: string | null
  category: { id: string; name: string } | null
  last_verification_at: string | null
  verification_frequency: VerificationFrequency
}

export interface VerificationFormData {
  equipment_id: string
  verified_at?: string
  notes?: string
  params: Record<string, string>  // template_id => value
}

export interface VerificationFilters {
  equipment_id?: string
  date_from?: string
  date_to?: string
  per_page?: number
}
