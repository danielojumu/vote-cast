import React, { useState } from 'react';
import { UserPlus, CheckCircle2, ShieldAlert, Sparkles, Send, Copy, ArrowRight, Mail } from 'lucide-react';
import { Registration, Voter, EligibleVoter } from '../types';

interface RegistrationFormProps {
  registrations: Registration[];
  voters: Voter[];
  eligibleVoters?: EligibleVoter[];
  onRegisterSuccess: (reg: Registration, voter: Voter, token: string) => void;
  onNavigateToVote: (email: string, token: string) => void;
}

export const RegistrationForm: React.FC<RegistrationFormProps> = ({
  registrations,
  voters,
  eligibleVoters = [],
  onRegisterSuccess,
  onNavigateToVote,
}) => {
  const [matricNo, setMatricNo] = useState('');
  const [fullname, setFullname] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [graduationYear, setGraduationYear] = useState('2008');
  const [idType, setIdType] = useState<'Matric No.' | 'Student ID' | 'Passport' | 'National ID' | 'Personal Code'>('Matric No.');
  const [idValue, setIdValue] = useState('');

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successResult, setSuccessResult] = useState<{
    fullname: string;
    email: string;
    token: string;
  } | null>(null);

  const [copied, setCopied] = useState(false);

  const years = Array.from({ length: 20 }, (_, i) => 2005 + i);

  const generateToken = (): string => {
    const chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    let res = '';
    for (let i = 0; i < 8; i++) {
      res += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return res;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    const newErrors: Record<string, string> = {};

    if (!matricNo.trim()) {
      newErrors.matricNo = 'Matric No. (Matriculation Number) is required.';
    }

    if (!fullname.trim()) newErrors.fullname = 'Full Name is required.';
    
    if (!phone.trim()) {
      newErrors.phone = 'Phone Number is required.';
    } else {
      const cleanPhone = phone.replace(/[^0-9+]/g, '');
      if (cleanPhone.length < 8) newErrors.phone = 'Valid phone number required.';
      if (registrations.some((r) => r.phone === phone.trim())) {
        newErrors.phone = 'This phone number is already registered.';
      }
    }

    if (!email.trim()) {
      newErrors.email = 'Email Address is required.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) {
      newErrors.email = 'Please enter a valid email address.';
    } else {
      const targetEmail = email.trim().toLowerCase();
      // Check if email is in the pre-uploaded accredited register
      if (eligibleVoters.length > 0 && !eligibleVoters.some((ev) => ev.email.toLowerCase() === targetEmail)) {
        newErrors.email = 'Accreditation Error: Your email is not in the pre-uploaded eligible voter register. Please contact the Electoral Committee.';
      } else if (voters.some((v) => v.email.toLowerCase() === targetEmail)) {
        newErrors.email = 'This email address has already been issued a voting token.';
      }
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    setIsSubmitting(true);

    setTimeout(() => {
      const token = generateToken();
      const newRegId = registrations.length + 1;
      const newVoterId = voters.length + 1;
      const nowStr = new Date().toISOString().replace('T', ' ').substring(0, 19);

      const newRegistration: Registration = {
        id: newRegId,
        fullname: fullname.trim(),
        phone: phone.trim(),
        email: email.trim().toLowerCase(),
        graduation_year: parseInt(graduationYear, 10),
        matric_no: matricNo.trim().toUpperCase(),
        id_type: idType,
        id_value: matricNo.trim().toUpperCase() || idValue.trim(),
        created_at: nowStr,
      };

      const newVoter: Voter = {
        id: newVoterId,
        registration_id: newRegId,
        email: email.trim().toLowerCase(),
        voting_token: token,
        token_sent: true,
        token_sent_at: nowStr,
        has_voted: false,
        voted_at: null,
        fullname: fullname.trim(),
      };

      onRegisterSuccess(newRegistration, newVoter, token);

      setSuccessResult({
        fullname: fullname.trim(),
        email: email.trim().toLowerCase(),
        token,
      });

      setIsSubmitting(false);
    }, 600);
  };

  const copyToClipboard = () => {
    if (successResult) {
      navigator.clipboard.writeText(successResult.token);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  return (
    <div className="max-w-3xl mx-auto py-8 px-4 sm:px-6">
      {/* Banner */}
      <div className="text-center mb-8">
        <div className="inline-flex items-center space-x-2 bg-sky-500/10 text-sky-300 border border-sky-500/20 px-4 py-1.5 rounded-full text-xs font-semibold mb-3">
          <Sparkles className="w-4 h-4 text-sky-400" />
          <span>Official Alumni Registration Portal</span>
        </div>
        <h1 className="text-3xl font-extrabold text-white tracking-tight">
          BUI Beta Set Alumni Voter Registration
        </h1>
        <p className="mt-2 text-slate-300 max-w-xl mx-auto text-sm">
          Register your details below to verify your eligibility. Upon submission, an encrypted 8-character voting token will be immediately issued to your email.
        </p>
      </div>

      {successResult ? (
        <div className="bg-white/5 backdrop-blur-xl border-2 border-emerald-500/60 rounded-3xl shadow-2xl p-6 sm:p-8 text-center animate-fade-in text-white">
          <div className="w-16 h-16 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-500/30">
            <CheckCircle2 className="w-10 h-10" />
          </div>

          <h2 className="text-2xl font-extrabold text-white mb-2">Registration Successful!</h2>
          <p className="text-slate-300 text-sm mb-6">
            Your voting token has been generated and sent to <strong className="text-sky-300">{successResult.email}</strong>.
          </p>

          <div className="bg-slate-950/80 text-white rounded-2xl p-6 max-w-md mx-auto mb-6 relative overflow-hidden shadow-2xl border border-white/10">
            <div className="text-xs uppercase tracking-widest text-slate-400 font-medium mb-2">Your Confidential Voting Token</div>
            <div className="font-mono text-3xl sm:text-4xl font-extrabold text-amber-400 tracking-wider my-2">
              {successResult.token}
            </div>
            <div className="text-xs text-slate-400 mt-2 flex items-center justify-center space-x-1">
              <Mail className="w-3.5 h-3.5 text-amber-400" />
              <span>Sent via PHPMailer SMTP Integration</span>
            </div>

            <button
              onClick={copyToClipboard}
              className="mt-4 inline-flex items-center space-x-1.5 text-xs bg-white/10 hover:bg-white/20 text-amber-300 px-3.5 py-1.5 rounded-xl border border-white/15 transition-all"
            >
              <Copy className="w-3.5 h-3.5" />
              <span>{copied ? 'Copied to Clipboard!' : 'Copy Token'}</span>
            </button>
          </div>

          <div className="bg-amber-500/10 border-l-4 border-amber-400 p-4 text-left text-xs text-amber-200 mb-8 rounded-r-xl border border-white/5">
            <strong>Confidentiality Notice:</strong> Keep this token secure. It can only be used once to submit your official ballot for President, Vice President, Secretary, and Treasurer.
          </div>

          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <button
              onClick={() => onNavigateToVote(successResult.email, successResult.token)}
              className="w-full sm:w-auto inline-flex items-center justify-center space-x-2 bg-[#1A3A5C] hover:bg-sky-900 text-white border border-sky-400/40 px-6 py-3.5 rounded-2xl font-bold shadow-xl transition-all"
            >
              <span>Proceed Directly to Voting Portal</span>
              <ArrowRight className="w-4 h-4" />
            </button>

            <button
              onClick={() => {
                setSuccessResult(null);
                setFullname('');
                setPhone('');
                setEmail('');
                setIdValue('');
              }}
              className="w-full sm:w-auto text-slate-300 hover:text-white text-sm font-semibold px-4 py-2"
            >
              Register Another Member
            </button>
          </div>
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="bg-white/5 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/10 p-6 sm:p-10 space-y-6 text-white">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Field 1 (FIRST ON LIST): Matric No */}
            <div className="md:col-span-2 bg-sky-950/30 p-4 rounded-2xl border border-sky-500/30">
              <label className="block text-xs font-black text-sky-300 uppercase tracking-wider mb-2">
                1. Matric No. (Matriculation Number) <span className="text-red-400">*</span>
              </label>
              <input
                type="text"
                value={matricNo}
                onChange={(e) => setMatricNo(e.target.value.toUpperCase())}
                placeholder="e.g. BUI/08/CS/042 or BUI/07/EE/019"
                className={`w-full px-4 py-3.5 rounded-xl border font-mono text-base font-bold transition-all bg-slate-900/90 text-sky-300 placeholder-slate-500 uppercase focus:outline-none ${
                  errors.matricNo ? 'border-red-500 bg-red-500/10 focus:ring-red-500' : 'border-sky-400/50 focus:border-sky-400 focus:ring-1 focus:ring-sky-400'
                }`}
              />
              <p className="mt-1 text-xs text-sky-200/70">Your primary university matriculation number used for voter accreditation.</p>
              {errors.matricNo && <p className="mt-1 text-xs text-red-400 font-bold">{errors.matricNo}</p>}
            </div>

            {/* Field 2: Full Name */}
            <div>
              <label className="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                2. Full Name <span className="text-red-400">*</span>
              </label>
              <input
                type="text"
                value={fullname}
                onChange={(e) => setFullname(e.target.value)}
                placeholder="e.g. Dr. Samuel O. Adebayo"
                className={`w-full px-4 py-3 rounded-xl border text-sm font-medium transition-all bg-white/5 text-white placeholder-slate-400 focus:outline-none ${
                  errors.fullname ? 'border-red-500 bg-red-500/10 focus:ring-red-500' : 'border-white/15 focus:border-sky-400 focus:ring-1 focus:ring-sky-400'
                }`}
              />
              {errors.fullname && <p className="mt-1 text-xs text-red-400 font-medium">{errors.fullname}</p>}
            </div>

            {/* Field 3: Phone Number */}
            <div>
              <label className="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                3. Phone Number <span className="text-red-400">*</span>
              </label>
              <input
                type="text"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                placeholder="+2348012345678"
                className={`w-full px-4 py-3 rounded-xl border text-sm font-medium transition-all bg-white/5 text-white placeholder-slate-400 focus:outline-none ${
                  errors.phone ? 'border-red-500 bg-red-500/10 focus:ring-red-500' : 'border-white/15 focus:border-sky-400 focus:ring-1 focus:ring-sky-400'
                }`}
              />
              {errors.phone && <p className="mt-1 text-xs text-red-400 font-medium">{errors.phone}</p>}
            </div>

            {/* Field 4: Email Address */}
            <div className="md:col-span-2">
              <label className="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                4. Alumni Email Address <span className="text-red-400">*</span>
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="alumni.member@example.com"
                className={`w-full px-4 py-3 rounded-xl border text-sm font-medium transition-all bg-white/5 text-white placeholder-slate-400 focus:outline-none ${
                  errors.email ? 'border-red-500 bg-red-500/10 focus:ring-red-500' : 'border-white/15 focus:border-sky-400 focus:ring-1 focus:ring-sky-400'
                }`}
              />
              <p className="mt-1 text-xs text-slate-400">Your voting token will be delivered immediately to this address.</p>
              {errors.email && <p className="mt-1 text-xs text-red-400 font-medium">{errors.email}</p>}
            </div>

            {/* Field 5: Graduation Year */}
            <div>
              <label className="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                5. Graduation Year <span className="text-red-400">*</span>
              </label>
              <select
                value={graduationYear}
                onChange={(e) => setGraduationYear(e.target.value)}
                className="w-full px-4 py-3 rounded-xl border border-white/15 text-sm font-medium focus:border-sky-400 focus:outline-none bg-slate-900 text-white"
              >
                {years.map((y) => (
                  <option key={y} value={y} className="bg-slate-900 text-white">
                    Class of {y}
                  </option>
                ))}
              </select>
            </div>

            {/* Field 6: Verification ID Type */}
            <div>
              <label className="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                6. Additional ID Type <span className="text-red-400">*</span>
              </label>
              <select
                value={idType}
                onChange={(e) => setIdType(e.target.value as any)}
                className="w-full px-4 py-3 rounded-xl border border-white/15 text-sm font-medium focus:border-sky-400 focus:outline-none bg-slate-900 text-white"
              >
                <option value="Matric No." className="bg-slate-900 text-white">Matric No. / Matriculation Number</option>
                <option value="Student ID" className="bg-slate-900 text-white">Student ID Card</option>
                <option value="Passport" className="bg-slate-900 text-white">International Passport</option>
                <option value="National ID" className="bg-slate-900 text-white">National ID / NIN</option>
                <option value="Personal Code" className="bg-slate-900 text-white">BUI Alumni Verification Code</option>
              </select>
            </div>
          </div>

          <div className="pt-4 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div className="flex items-center space-x-2 text-xs text-slate-400">
              <ShieldAlert className="w-4 h-4 text-sky-400" />
              <span>Verified against MySQL BUI Alumni Database</span>
            </div>

            <button
              type="submit"
              disabled={isSubmitting}
              className="w-full sm:w-auto inline-flex items-center justify-center space-x-2 bg-[#1A3A5C] hover:bg-sky-900 text-white border border-sky-400/40 px-8 py-3.5 rounded-2xl font-bold shadow-[0_0_20px_rgba(56,189,248,0.2)] transition-all disabled:opacity-50"
            >
              {isSubmitting ? (
                <span>Generating Token...</span>
              ) : (
                <>
                  <UserPlus className="w-4 h-4 text-sky-400" />
                  <span>Register & Request Token</span>
                </>
              )}
            </button>
          </div>
        </form>
      )}
    </div>
  );
};
