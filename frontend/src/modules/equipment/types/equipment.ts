export interface Category {
  id: string
  name: string
  slug: string
  equipments_count?: number
}

export interface Manufacturer {
  id: string
  name: string
  country?: string
  website?: string
}

export interface Supplier {
  id: string
  name: string
  cnpj?: string
  contact_name?: string
  contact_email?: string
  contact_phone?: string
}

export interface EquipmentPhoto {
  id: string
  path: string
  sort_order: number
}

export interface Equipment {
  id: string
  name: string
  patrimony_id?: string
  serial_number?: string
  category?: Category
  manufacturer?: Manufacturer
  supplier?: Supplier
  location?: string
  acquisition_date?: string
  warranty_end?: string
  status: 'active' | 'inactive' | 'maintenance' | 'retired'
  description?: string
  technical_specs?: string
  notes?: string
  photos?: EquipmentPhoto[]
  created_at: string
  updated_at: string
}

export interface EquipmentFormData {
  name: string
  patrimony_id?: string
  serial_number?: string
  category_id?: string
  manufacturer_id?: string
  supplier_id?: string
  location?: string
  acquisition_date?: string | Date
  warranty_end?: string | Date
  status: string
  description?: string
  technical_specs?: string
  notes?: string
}