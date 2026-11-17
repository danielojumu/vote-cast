export interface EligibleVoter {
  id: number;
  fullname: string;
  email: string;
  graduation_year?: number;
  created_at?: string;
}

export interface Position {
  id: number;
  name: string;
  display_order: number;
  active: boolean;
}

export interface Candidate {
  id: number;
  position_id: number;
  fullname: string;
  preferred_name?: string;
  gender?: 'Male' | 'Female' | 'Other';
  date_of_birth?: string;
  phone?: string;
  email?: string;
  location?: string; // Current City, State & Country of Residence
  dept_grad_year?: string; // Graduation Year & Programme/Department
  occupation?: string; // Current Occupation
  manifesto: string; // Candidate Manifesto
  profile: string; // Candidate Profile (leadership, involvement, skills)
  university_photo: string; // Photo from university days (~20 yrs ago)
  recent_photo: string; // Recent photograph (taken within last 12 months)
  referee_letter?: string; // Referee details
  has_conflict?: boolean; // Conflict of interest declaration
  display_order: number;
  active: boolean;
  position_name?: string;
  show_public_fields?: string[]; // Field names marked to display directly on voting card
}

export interface Registration {
  id: number;
  fullname: string;
  phone: string;
  email: string;
  graduation_year: number;
  matric_no: string; // Student Matriculation Number (1st field)
  id_type: 'Matric No.' | 'Student ID' | 'Passport' | 'National ID' | 'Personal Code';
  id_value: string;
  created_at: string;
}

export interface Voter {
  id: number;
  registration_id: number;
  email: string;
  voting_token: string;
  token_sent: boolean;
  token_sent_at: string;
  has_voted: boolean;
  voted_at: string | null;
  fullname?: string;
}

export interface VoteRecord {
  id: number;
  voter_id: number;
  candidate_id: number;
  position_id: number;
  created_at: string;
}

export interface AuditLogItem {
  id: number;
  user: string;
  action: string;
  ip_address: string;
  created_at: string;
}

export interface OutboxMail {
  id: string;
  toEmail: string;
  toName: string;
  token: string;
  sentAt: string;
}

export type PopupType = 'success' | 'error' | 'warning' | 'info';

export interface PopupData {
  isOpen: boolean;
  type: PopupType;
  title: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  onConfirm?: () => void;
  onCancel?: () => void;
}

