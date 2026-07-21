export type LoanStatus = 'reserved' | 'active' | 'returned' | 'cancelled'

// EquipmentLoan pivot
export interface EquipmentLoanPivot {
  id: string
  loan_id: string
  equipment_id: string
  returned_at: string | null
  notes: string | null
  is_returned: boolean
}

// Equipment with pivot data (para exibição na lista e detalhe)
export interface LoanedEquipment {
  id: string
  name: string
  patrimony_id?: string
  serial_number?: string
  pivot: EquipmentLoanPivot
}

// User summary (borrower/approver)
export interface UserSummary {
  id: string
  name: string
  email?: string
}

// Loan interface
export interface Loan {
  id: string
  status: LoanStatus
  borrower: UserSummary
  borrowed_at: string | null
  expected_return_at: string | null
  returned_at: string | null
  reason: string | null
  destination: string | null
  contact: string | null
  notes: string | null
  approved_by: UserSummary | null
  created_by: UserSummary | null
  equipment: LoanedEquipment[]
  is_overdue: boolean
  items_count: number
  returned_items_count: number
  progress: number
  created_at: string
  updated_at: string
}

// Form data for creating a loan
export interface LoanFormData {
  borrower_id: string
  equipment_ids: string[]
  borrowed_at: string
  expected_return_at: string
  reason?: string
  destination?: string
  contact?: string
  notes?: string
  approved_by?: string
}

// Form data for returning an item
export interface ReturnItemFormData {
  equipment_id: string
  returned_at?: string
  notes?: string
}

// Loan status options for dropdowns/filters
export const LOAN_STATUS_OPTIONS: { label: string; value: LoanStatus }[] = [
  { label: 'Reservado', value: 'reserved' },
  { label: 'Ativo', value: 'active' },
  { label: 'Devolvido', value: 'returned' },
  { label: 'Cancelado', value: 'cancelled' },
]
