import React, { useState } from 'react';
import {
  ShieldCheck,
  Users,
  Send,
  Vote,
  Clock,
  Percent,
  UserCheck,
  Plus,
  Edit,
  Trash2,
  Download,
  FileSpreadsheet,
  FileText,
  Search,
  RefreshCw,
  Eye,
  LogOut,
  Lock,
  Building,
  Activity,
  CheckCircle,
  XCircle,
  UploadCloud,
  FileUp,
  Sparkles,
} from 'lucide-react';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { Candidate, Position, Registration, Voter, AuditLogItem, VoteRecord, PopupData } from '../types';
import { DesignedPopup } from './DesignedPopup';

interface AdminPortalProps {
  candidates: Candidate[];
  positions: Position[];
  registrations: Registration[];
  voters: Voter[];
  votes: VoteRecord[];
  auditLogs: AuditLogItem[];
  onAddCandidate: (cand: Omit<Candidate, 'id'>) => void;
  onUpdateCandidate: (cand: Candidate) => void;
  onDeleteCandidate: (id: number) => void;
  onToggleCandidateActive: (id: number) => void;
  onAddPosition?: (posName: string) => Position;
  onResendToken: (voterId: number) => void;
  onResetDatabase?: () => void;
}

export const AdminPortal: React.FC<AdminPortalProps> = ({
  candidates,
  positions,
  registrations,
  voters,
  votes,
  auditLogs,
  onAddCandidate,
  onUpdateCandidate,
  onDeleteCandidate,
  onToggleCandidateActive,
  onAddPosition,
  onResendToken,
  onResetDatabase,
}) => {
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [loginEmail, setLoginEmail] = useState('admin@vote.buibetaset.org');
  const [loginPassword, setLoginPassword] = useState('Admin@2026!');
  const [loginError, setLoginError] = useState('');

  const [activeTab, setActiveTab] = useState<'dashboard' | 'candidates' | 'voters' | 'results' | 'audit'>('dashboard');
  const [searchTerm, setSearchTerm] = useState('');

  // Designed Popup state
  const [popup, setPopup] = useState<PopupData | null>(null);

  const showPopup = (type: 'success' | 'error' | 'warning' | 'info', title: string, message: string) => {
    setPopup({
      isOpen: true,
      type,
      title,
      message,
    });
  };

  // Candidate Modal Extended State
  const [showCandModal, setShowCandModal] = useState(false);
  const [editingCandidate, setEditingCandidate] = useState<Candidate | null>(null);

  const [candFullname, setCandFullname] = useState('');
  const [candPreferredName, setCandPreferredName] = useState('');
  const [candGender, setCandGender] = useState<'Male' | 'Female' | 'Other'>('Male');
  const [candDob, setCandDob] = useState('');
  const [candPhone, setCandPhone] = useState('');
  const [candEmail, setCandEmail] = useState('');
  const [candLocation, setCandLocation] = useState('');
  const [candDeptGradYear, setCandDeptGradYear] = useState('');
  const [candOccupation, setCandOccupation] = useState('');
  const [candPosId, setCandPosId] = useState(1);
  const [candManifesto, setCandManifesto] = useState('');
  const [candProfile, setCandProfile] = useState('');
  const [candUniPhoto, setCandUniPhoto] = useState('');
  const [candRecentPhoto, setCandRecentPhoto] = useState('');
  const [candReferee, setCandReferee] = useState('');
  const [candHasConflict, setCandHasConflict] = useState(false);
  const [candOrder, setCandOrder] = useState(1);
  const [candActive, setCandActive] = useState(true);

  // Custom Position Creation Modal State
  const [showAddPosModal, setShowAddPosModal] = useState(false);
  const [newPositionName, setNewPositionName] = useState('');

  // Candidate CSV Bulk Upload State
  const [showCsvModal, setShowCsvModal] = useState(false);
  const [csvContent, setCsvContent] = useState('');

  // Metrics
  const totalRegistrations = registrations.length;
  const tokensSent = voters.length;
  const votesCast = voters.filter((v) => v.has_voted).length;
  const remainingVoters = tokensSent - votesCast;
  const turnout = tokensSent > 0 ? ((votesCast / tokensSent) * 100).toFixed(1) : '0.0';

  // Reset Modal State
  const [showResetModal, setShowResetModal] = useState(false);
  const [resetConfirmText, setResetConfirmText] = useState('');

  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();
    setLoginError('');

    if ((loginEmail === 'admin@vote.buibetaset.org' || loginEmail === 'admin@buibetaset.org') && loginPassword === 'Admin@2026!') {
      setIsLoggedIn(true);
    } else {
      setLoginError('Invalid email or password credentials.');
    }
  };

  const handleConfirmResetDatabase = () => {
    if (resetConfirmText.trim().toUpperCase() === 'RESET') {
      if (onResetDatabase) {
        onResetDatabase();
      }
      showPopup('success', 'Database Reset Complete', 'All votes, voters, registrations, and SMTP queues have been reset afresh.');
      setShowResetModal(false);
      setResetConfirmText('');
    }
  };

  const handleCreateNewPosition = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newPositionName.trim()) return;

    if (onAddPosition) {
      const created = onAddPosition(newPositionName.trim());
      setCandPosId(created.id);
      showPopup('success', 'Executive Position Created', `Successfully created new position: "${created.name}"`);
    }

    setNewPositionName('');
    setShowAddPosModal(false);
  };

  const handleOpenCandidateModal = (cand?: Candidate) => {
    if (cand) {
      setEditingCandidate(cand);
      setCandFullname(cand.fullname);
      setCandPreferredName(cand.preferred_name || '');
      setCandGender(cand.gender || 'Male');
      setCandDob(cand.date_of_birth || '');
      setCandPhone(cand.phone || '');
      setCandEmail(cand.email || '');
      setCandLocation(cand.location || '');
      setCandDeptGradYear(cand.dept_grad_year || '');
      setCandOccupation(cand.occupation || '');
      setCandPosId(cand.position_id);
      setCandManifesto(cand.manifesto);
      setCandProfile(cand.profile);
      setCandUniPhoto(cand.university_photo);
      setCandRecentPhoto(cand.recent_photo);
      setCandReferee(cand.referee_letter || '');
      setCandHasConflict(cand.has_conflict || false);
      setCandOrder(cand.display_order);
      setCandActive(cand.active);
    } else {
      setEditingCandidate(null);
      setCandFullname('');
      setCandPreferredName('');
      setCandGender('Male');
      setCandDob('');
      setCandPhone('');
      setCandEmail('');
      setCandLocation('');
      setCandDeptGradYear('');
      setCandOccupation('');
      setCandPosId(positions[0]?.id || 1);
      setCandManifesto('');
      setCandProfile('');
      setCandUniPhoto('https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=400');
      setCandRecentPhoto('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=400');
      setCandReferee('');
      setCandHasConflict(false);
      setCandOrder(1);
      setCandActive(true);
    }
    setShowCandModal(true);
  };

  const handleSaveCandidate = (e: React.FormEvent) => {
    e.preventDefault();
    if (!candFullname || !candManifesto) {
      showPopup('warning', 'Required Candidate Fields', 'Candidate Full Name and Manifesto are mandatory.');
      return;
    }

    const payload = {
      fullname: candFullname.trim(),
      preferred_name: candPreferredName.trim() || undefined,
      gender: candGender,
      date_of_birth: candDob || undefined,
      phone: candPhone.trim() || undefined,
      email: candEmail.trim().toLowerCase() || undefined,
      location: candLocation.trim() || undefined,
      dept_grad_year: candDeptGradYear.trim() || undefined,
      occupation: candOccupation.trim() || undefined,
      position_id: candPosId,
      manifesto: candManifesto.trim(),
      profile: candProfile.trim(),
      university_photo: candUniPhoto.trim() || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=400',
      recent_photo: candRecentPhoto.trim() || 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=400',
      referee_letter: candReferee.trim() || undefined,
      has_conflict: candHasConflict,
      display_order: candOrder,
      active: candActive,
    };

    if (editingCandidate) {
      onUpdateCandidate({
        ...editingCandidate,
        ...payload,
      });
      showPopup('success', 'Candidate Saved', `Updated candidate details for ${candFullname}.`);
    } else {
      onAddCandidate(payload);
      showPopup('success', 'Candidate Added', `Successfully added candidate ${candFullname}.`);
    }

    setShowCandModal(false);
  };

  // CSV Candidate File Import Logic
  const handleDownloadCandidateCsvTemplate = () => {
    const headers = [
      'position_name',
      'fullname',
      'preferred_name',
      'gender',
      'date_of_birth',
      'email',
      'phone',
      'location',
      'dept_grad_year',
      'occupation',
      'profile',
      'manifesto',
      'university_photo',
      'recent_photo',
      'referee_letter',
    ];

    const sampleRow = [
      'Gen Secretary',
      'Dr. Adewale B. Phillips',
      'Wale Phillips',
      'Male',
      '1986-05-14',
      'adewale.p@buibetaset.org',
      '+2348031122334',
      'Lagos State Nigeria',
      'Mass Communication (2007)',
      'Chief Media Strategist',
      'Pioneer Student Leader & Media Executive with 15 years communications experience.',
      'To build an automated, open-access secretarial document repository for all Beta Set members.',
      'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=400',
      'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&q=80&w=400',
      'Nominated by Executive Patron',
    ];

    const csvStr = `${headers.join(',')}\n${sampleRow.map((col) => `"${col.replace(/"/g, '""')}"`).join(',')}\n`;

    const blob = new Blob([csvStr], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'BUI_Beta_Set_Candidate_Import_Template.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const handleProcessCsvCandidateImport = () => {
    if (!csvContent.trim()) {
      showPopup('warning', 'Empty CSV Content', 'Please paste CSV content or upload a valid CSV file.');
      return;
    }

    const lines = csvContent.trim().split('\n').map((l) => l.trim()).filter((l) => l.length > 0);
    if (lines.length < 2) {
      showPopup('warning', 'Invalid CSV Format', 'CSV must contain at least a header row and one candidate row.');
      return;
    }

    let addedCount = 0;
    const headerRow = lines[0].toLowerCase();

    for (let i = 1; i < lines.length; i++) {
      // Basic CSV splitter respecting quotes
      const row = lines[i].match(/(".*?"|[^",\s]+)(?=\s*,|\s*$)/g)?.map((c) => c.replace(/^"|"$/g, '').trim()) || lines[i].split(',');

      if (row.length >= 3) {
        const posName = row[0] || 'Gen Secretary';
        const fullname = row[1] || 'Candidate';
        const preferred_name = row[2] || '';
        const gender = (row[3] || 'Male') as any;
        const date_of_birth = row[4] || '';
        const email = row[5] || '';
        const phone = row[6] || '';
        const location = row[7] || '';
        const dept_grad_year = row[8] || '';
        const occupation = row[9] || '';
        const profile = row[10] || 'Candidate biography';
        const manifesto = row[11] || 'Candidate manifesto text';
        const university_photo = row[12] || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=400';
        const recent_photo = row[13] || 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=400';
        const referee_letter = row[14] || '';

        // Match or create position
        let matchedPos = positions.find((p) => p.name.toLowerCase() === posName.toLowerCase());
        if (!matchedPos && onAddPosition) {
          matchedPos = onAddPosition(posName);
        }

        const posId = matchedPos ? matchedPos.id : (positions[0]?.id || 1);

        onAddCandidate({
          fullname,
          preferred_name: preferred_name || undefined,
          gender: gender === 'Female' ? 'Female' : 'Male',
          date_of_birth: date_of_birth || undefined,
          email: email || undefined,
          phone: phone || undefined,
          location: location || undefined,
          dept_grad_year: dept_grad_year || undefined,
          occupation: occupation || undefined,
          position_id: posId,
          profile,
          manifesto,
          university_photo,
          recent_photo,
          referee_letter: referee_letter || undefined,
          has_conflict: false,
          display_order: i,
          active: true,
        });

        addedCount++;
      }
    }

    showPopup('success', 'CSV Import Successful', `Successfully imported ${addedCount} candidates from CSV file.`);
    setCsvContent('');
    setShowCsvModal(false);
  };

  // Export handlers
  const exportCSV = () => {
    let csv = 'BUI Beta Set Alumni Election Official Results\n';
    csv += `Generated At:,${new Date().toLocaleString()}\n\n`;
    csv += 'Position,Candidate Name,Votes Received,Percentage (%)\n';

    positions.forEach((pos) => {
      const posCandidates = candidates.filter((c) => c.position_id === pos.id);
      const posVotesCount = votes.filter((v) => v.position_id === pos.id).length;

      posCandidates.forEach((c) => {
        const cVotes = votes.filter((v) => v.candidate_id === c.id).length;
        const pct = posVotesCount > 0 ? ((cVotes / posVotesCount) * 100).toFixed(1) : '0.0';
        csv += `"${pos.name}","${c.fullname}",${cVotes},${pct}%\n`;
      });
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `BUI_Beta_Set_Election_Results_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const exportPDF = () => {
    const doc = new jsPDF();
    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(18);
    doc.setTextColor(26, 58, 92); // #1A3A5C
    doc.text('BUI BETA SET ALUMNI ASSOCIATION', 14, 20);

    doc.setFontSize(11);
    doc.setFont('Helvetica', 'normal');
    doc.setTextColor(100);
    doc.text('Official Executive Election Results Report | https://vote.buibetaset.org', 14, 26);
    doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 32);

    const tableRows: any[] = [];

    positions.forEach((pos) => {
      const posCandidates = candidates.filter((c) => c.position_id === pos.id);
      const posVotesCount = votes.filter((v) => v.position_id === pos.id).length;

      tableRows.push([`POSITION: ${pos.name.toUpperCase()} (Total Votes: ${posVotesCount})`, '', '', '']);

      posCandidates.forEach((c) => {
        const cVotes = votes.filter((v) => v.candidate_id === c.id).length;
        const pct = posVotesCount > 0 ? ((cVotes / posVotesCount) * 100).toFixed(1) : '0.0';
        tableRows.push([c.fullname, pos.name, cVotes.toString(), `${pct}%`]);
      });
    });

    autoTable(doc, {
      startY: 40,
      head: [['Candidate Name', 'Position', 'Votes Received', 'Percentage']],
      body: tableRows,
      headStyles: { fillColor: [26, 58, 92] },
      didParseCell: (data) => {
        if (data.row.raw && data.row.raw[0] && String(data.row.raw[0]).startsWith('POSITION:')) {
          data.cell.styles.fontStyle = 'bold';
          data.cell.styles.fillColor = [240, 244, 248];
          data.cell.styles.textColor = [26, 58, 92];
        }
      },
    });

    doc.save(`BUI_Beta_Set_Election_Results_${new Date().toISOString().slice(0, 10)}.pdf`);
  };

  if (!isLoggedIn) {
    return (
      <div className="max-w-md mx-auto my-12 p-6 sm:p-8 bg-white border border-slate-200 rounded-3xl shadow-lg text-slate-900">
        <div className="text-center mb-6">
          <div className="w-14 h-14 bg-[#1b3252] text-white rounded-2xl flex items-center justify-center mx-auto mb-3 shadow">
            <Lock className="w-7 h-7" />
          </div>
          <h2 className="text-2xl font-extrabold text-[#101e36]">Admin Portal Login</h2>
          <p className="text-xs text-slate-500 mt-1">BUI Beta Set Electoral Commission</p>
        </div>

        {loginError && (
          <div className="mb-4 p-3 bg-red-50 border-l-4 border-red-500 text-xs text-red-800 font-medium rounded-r-md border border-red-200">
            {loginError}
          </div>
        )}

        <form onSubmit={handleLogin} className="space-y-4">
          <div>
            <label className="block text-xs font-bold text-slate-700 uppercase mb-1">Email Address</label>
            <input
              type="email"
              value={loginEmail}
              onChange={(e) => setLoginEmail(e.target.value)}
              className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 text-sm font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
              required
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-slate-700 uppercase mb-1">Password</label>
            <input
              type="password"
              value={loginPassword}
              onChange={(e) => setLoginPassword(e.target.value)}
              className="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 text-sm font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
              required
            />
          </div>

          <button
            type="submit"
            className="w-full bg-[#1b3252] hover:bg-[#152742] text-white py-3 rounded-2xl font-bold shadow-md transition-all text-sm mt-2"
          >
            Authenticate & Access Dashboard
          </button>
        </form>

        <div className="mt-6 p-3 bg-amber-50 rounded-xl text-[11px] text-amber-900 border border-amber-200">
          <strong>Demo Credentials:</strong> Email: <code className="font-bold text-amber-950">admin@vote.buibetaset.org</code> | Password:{' '}
          <code className="font-bold text-amber-950">Admin@2026!</code>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8 text-slate-900">
      {/* Admin Top Header Bar */}
      <div className="bg-white border border-slate-200 text-slate-900 p-6 rounded-3xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center space-x-2">
            <span className="bg-slate-100 text-slate-800 text-xs px-2.5 py-0.5 rounded-full font-bold border border-slate-200">
              Super Admin Mode
            </span>
            <span className="text-xs text-slate-500">Session Secure</span>
          </div>
          <h1 className="text-2xl font-black text-[#101e36] mt-1 font-display">Electoral Commission Control Panel</h1>
        </div>

        <div className="flex flex-wrap items-center gap-2.5">
          <button
            onClick={exportCSV}
            className="inline-flex items-center space-x-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-sm"
          >
            <FileSpreadsheet className="w-4 h-4 text-[#1b3252]" />
            <span>CSV Report</span>
          </button>

          <button
            onClick={exportPDF}
            className="inline-flex items-center space-x-1.5 bg-[#1b3252] hover:bg-[#152742] text-white px-3.5 py-2 rounded-xl text-xs font-extrabold shadow-sm transition-all"
          >
            <FileText className="w-4 h-4" />
            <span>Printable PDF</span>
          </button>

          <button
            onClick={() => setShowResetModal(true)}
            className="inline-flex items-center space-x-1.5 bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-2 rounded-xl text-xs font-extrabold shadow-sm transition-all"
            title="Clear election database afresh"
          >
            <RefreshCw className="w-4 h-4" />
            <span>Reset Database</span>
          </button>

          <button
            onClick={() => setIsLoggedIn(false)}
            className="p-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl transition-all shadow-sm"
            title="Logout Admin"
          >
            <LogOut className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Admin Sub-Tabs */}
      <div className="flex items-center space-x-2 border-b border-slate-200 pb-2 overflow-x-auto">
        <button
          onClick={() => setActiveTab('dashboard')}
          className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 ${
            activeTab === 'dashboard' ? 'bg-[#1b3252] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'
          }`}
        >
          <Activity className="w-4 h-4" />
          <span>Dashboard Overview</span>
        </button>

        <button
          onClick={() => setActiveTab('candidates')}
          className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 ${
            activeTab === 'candidates' ? 'bg-[#1b3252] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'
          }`}
        >
          <Users className="w-4 h-4" />
          <span>Candidate Management ({candidates.length})</span>
        </button>

        <button
          onClick={() => setActiveTab('voters')}
          className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 ${
            activeTab === 'voters' ? 'bg-[#1b3252] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'
          }`}
        >
          <UserCheck className="w-4 h-4" />
          <span>Voter Roster ({voters.length})</span>
        </button>

        <button
          onClick={() => setActiveTab('results')}
          className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 ${
            activeTab === 'results' ? 'bg-[#1b3252] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'
          }`}
        >
          <Vote className="w-4 h-4" />
          <span>Live Tally & Breakdown</span>
        </button>

        <button
          onClick={() => setActiveTab('audit')}
          className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 ${
            activeTab === 'audit' ? 'bg-[#1b3252] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'
          }`}
        >
          <Clock className="w-4 h-4" />
          <span>Audit Logs ({auditLogs.length})</span>
        </button>
      </div>

      {/* TAB 1: DASHBOARD OVERVIEW */}
      {activeTab === 'dashboard' && (
        <div className="space-y-8">
          {/* 6 KPI Cards */}
          <div className="grid grid-cols-2 lg:grid-cols-6 gap-4">
            <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm text-slate-900">
              <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Registrations</div>
              <div className="text-2xl font-black text-[#101e36]">{totalRegistrations}</div>
              <span className="text-[10px] text-emerald-700 font-extrabold">100% Verified</span>
            </div>

            <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm text-slate-900">
              <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Tokens Sent</div>
              <div className="text-2xl font-black text-[#101e36]">{tokensSent}</div>
              <span className="text-[10px] text-emerald-700 font-extrabold">SMTP Delivered</span>
            </div>

            <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm text-slate-900">
              <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Votes Cast</div>
              <div className="text-2xl font-black text-emerald-700">{votesCast}</div>
              <span className="text-[10px] text-slate-500 font-semibold">Recorded Ballots</span>
            </div>

            <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm text-slate-900">
              <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Remaining</div>
              <div className="text-2xl font-black text-amber-700">{remainingVoters}</div>
              <span className="text-[10px] text-slate-500 font-semibold">Pending Tokens</span>
            </div>

            <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm text-slate-900">
              <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Turnout %</div>
              <div className="text-2xl font-black text-[#1b3252]">{turnout}%</div>
              <span className="text-[10px] text-emerald-700 font-extrabold">Participation</span>
            </div>

            <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm text-slate-900">
              <div className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Candidates</div>
              <div className="text-2xl font-black text-[#101e36]">{candidates.length}</div>
              <span className="text-[10px] text-slate-500 font-semibold">Across 4 Positions</span>
            </div>
          </div>

          {/* Quick Action Bar & Live Results Summary */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div className="lg:col-span-2 space-y-6">
              <div className="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 text-slate-900">
                <h3 className="font-extrabold text-[#101e36] text-lg mb-4">Executive Positions Vote Standings</h3>
                <div className="space-y-6">
                  {positions.map((pos) => {
                    const posCandidates = candidates.filter((c) => c.position_id === pos.id);
                    const posVotesCount = votes.filter((v) => v.position_id === pos.id).length;

                    return (
                      <div key={pos.id} className="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                        <div className="flex justify-between items-center mb-2">
                          <span className="font-bold text-[#101e36] text-sm">{pos.name}</span>
                          <span className="text-xs text-slate-500 font-medium">{posVotesCount} votes cast</span>
                        </div>
                        <div className="space-y-2">
                          {posCandidates.map((cand) => {
                            const cVotes = votes.filter((v) => v.candidate_id === cand.id).length;
                            const pct = posVotesCount > 0 ? ((cVotes / posVotesCount) * 100).toFixed(1) : '0.0';

                            return (
                              <div key={cand.id}>
                                <div className="flex justify-between text-xs mb-1">
                                  <span className="font-semibold text-slate-700">{cand.fullname}</span>
                                  <span className="font-bold text-[#1b3252]">
                                    {cVotes} ({pct}%)
                                  </span>
                                </div>
                                <div className="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden border border-slate-200">
                                  <div
                                    className="bg-[#1b3252] h-2.5 rounded-full transition-all duration-500"
                                    style={{ width: `${pct}%` }}
                                  />
                                </div>
                              </div>
                            );
                          })}
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>

            {/* Recent Audit Stream */}
            <div className="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 text-slate-900">
              <h3 className="font-extrabold text-[#101e36] text-lg mb-4">Real-Time Audit Trail</h3>
              <div className="space-y-3">
                {auditLogs.slice(0, 6).map((log) => (
                  <div key={log.id} className="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-900">
                    <div className="flex justify-between items-center text-[10px] text-slate-500 mb-1">
                      <span className="font-bold text-[#1b3252]">{log.user}</span>
                      <span>{log.created_at}</span>
                    </div>
                    <p className="text-slate-700 font-medium">{log.action}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* TAB 2: CANDIDATE MANAGEMENT */}
      {activeTab === 'candidates' && (
        <div className="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 space-y-6 text-slate-900">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h2 className="text-xl font-extrabold text-[#101e36]">Contestant & Position Management</h2>
              <p className="text-xs text-slate-500">Add candidates, create executive positions, or import contestant lists via CSV file upload.</p>
            </div>

            <div className="flex flex-wrap items-center gap-2">
              <button
                onClick={() => setShowAddPosModal(true)}
                className="inline-flex items-center space-x-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 px-3.5 py-2 rounded-2xl font-bold text-xs transition-all"
              >
                <Plus className="w-4 h-4 text-slate-700" />
                <span>+ Create Position</span>
              </button>

              <button
                onClick={() => setShowCsvModal(true)}
                className="inline-flex items-center space-x-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 px-3.5 py-2 rounded-2xl font-bold text-xs transition-all"
              >
                <UploadCloud className="w-4 h-4 text-emerald-700" />
                <span>CSV Candidates Upload</span>
              </button>

              <button
                onClick={() => handleOpenCandidateModal()}
                className="inline-flex items-center space-x-2 bg-[#1b3252] hover:bg-[#152742] text-white px-4 py-2 rounded-2xl font-bold text-xs shadow-sm transition-all"
              >
                <Plus className="w-4 h-4 text-white" />
                <span>Add Single Candidate</span>
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {candidates.map((cand) => {
              const posName = positions.find((p) => p.id === cand.position_id)?.name || 'Executive Position';

              return (
                <div key={cand.id} className="border border-slate-200 rounded-2xl p-5 shadow-sm bg-slate-50 space-y-4 text-slate-900 hover:border-slate-300 transition-all">
                  <div className="flex items-center space-x-3">
                    <img
                      src={cand.recent_photo}
                      alt={cand.fullname}
                      className="w-14 h-14 rounded-2xl object-cover border-2 border-slate-300 shadow-sm"
                    />
                    <div>
                      <span className="text-[10px] font-bold uppercase tracking-wider bg-slate-200/80 text-slate-800 px-2 py-0.5 rounded-md border border-slate-300">
                        {posName}
                      </span>
                      <h3 className="font-extrabold text-[#101e36] text-sm mt-1">{cand.fullname}</h3>
                      {cand.dept_grad_year && (
                        <p className="text-[11px] text-slate-500 font-medium">{cand.dept_grad_year}</p>
                      )}
                    </div>
                  </div>

                  <p className="text-xs text-slate-600 line-clamp-2 italic">"{cand.manifesto}"</p>

                  <div className="flex items-center justify-between pt-3 border-t border-slate-200 text-xs">
                    <button
                      onClick={() => onToggleCandidateActive(cand.id)}
                      className={`inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg font-bold text-[11px] ${
                        cand.active ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'
                      }`}
                    >
                      {cand.active ? <CheckCircle className="w-3.5 h-3.5" /> : <XCircle className="w-3.5 h-3.5" />}
                      <span>{cand.active ? 'Active' : 'Disabled'}</span>
                    </button>

                    <div className="flex items-center space-x-2">
                      <button
                        onClick={() => handleOpenCandidateModal(cand)}
                        className="p-1.5 text-slate-700 hover:text-slate-900 bg-white hover:bg-slate-100 rounded-lg transition-colors border border-slate-200 shadow-sm"
                        title="Edit Candidate"
                      >
                        <Edit className="w-4 h-4" />
                      </button>

                      <button
                        onClick={() => onDeleteCandidate(cand.id)}
                        className="p-1.5 text-red-700 hover:text-red-900 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200 shadow-sm"
                        title="Delete Candidate"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* TAB 3: VOTER ROSTER */}
      {activeTab === 'voters' && (
        <div className="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 space-y-6 text-slate-900">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h2 className="text-xl font-extrabold text-[#101e36]">Registered Voters & Token Status</h2>
              <p className="text-xs text-slate-500">Monitor token delivery and ballot participation status.</p>
            </div>

            <div className="relative">
              <Search className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
              <input
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Search by name, email or token..."
                className="pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-xs font-medium w-full sm:w-64 bg-slate-50 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-slate-800 focus:outline-none"
              />
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs border-collapse">
              <thead>
                <tr className="bg-slate-50 text-slate-700 border-b border-slate-200">
                  <th className="p-3 rounded-tl-xl font-extrabold uppercase text-[11px]">Voter Name</th>
                  <th className="p-3 font-extrabold uppercase text-[11px]">Email</th>
                  <th className="p-3 font-extrabold uppercase text-[11px]">Voting Token</th>
                  <th className="p-3 font-extrabold uppercase text-[11px]">Voting Status</th>
                  <th className="p-3 font-extrabold uppercase text-[11px]">Sent Timestamp</th>
                  <th className="p-3 rounded-tr-xl font-extrabold uppercase text-[11px] text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 text-slate-700">
                {voters
                  .filter(
                    (v) =>
                      v.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                      v.voting_token.toLowerCase().includes(searchTerm.toLowerCase()) ||
                      (v.fullname && v.fullname.toLowerCase().includes(searchTerm.toLowerCase()))
                  )
                  .map((v) => (
                    <tr key={v.id} className="hover:bg-slate-50 transition-colors">
                      <td className="p-3 font-bold text-[#101e36]">{v.fullname || 'Alumni Member'}</td>
                      <td className="p-3 text-slate-600 font-medium">{v.email}</td>
                      <td className="p-3 font-mono font-extrabold text-amber-700 tracking-wider">{v.voting_token}</td>
                      <td className="p-3">
                        {v.has_voted ? (
                          <span className="bg-emerald-50 text-emerald-800 font-bold px-2.5 py-1 rounded-full text-[10px] border border-emerald-200">
                            Voted ({v.voted_at})
                          </span>
                        ) : (
                          <span className="bg-amber-50 text-amber-800 font-bold px-2.5 py-1 rounded-full text-[10px] border border-amber-200">
                            Token Unused
                          </span>
                        )}
                      </td>
                      <td className="p-3 text-slate-500 text-[11px] font-mono">{v.token_sent_at}</td>
                      <td className="p-3 text-right">
                        <button
                          onClick={() => onResendToken(v.id)}
                          className="inline-flex items-center space-x-1 bg-[#1b3252] hover:bg-[#152742] text-white px-2.5 py-1.5 rounded-lg font-bold text-[11px] transition-all shadow-sm"
                        >
                          <RefreshCw className="w-3 h-3 text-white" />
                          <span>Resend Token</span>
                        </button>
                      </td>
                    </tr>
                  ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* TAB 4: LIVE RESULTS */}
      {activeTab === 'results' && (
        <div className="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 space-y-6 text-slate-900">
          <div className="flex justify-between items-center border-b border-slate-100 pb-4">
            <div>
              <h2 className="text-xl font-extrabold text-[#101e36]">Detailed Election Results</h2>
              <p className="text-xs text-slate-500">Live breakdown per position with vote counts and percentage share.</p>
            </div>

            <div className="flex space-x-2">
              <button onClick={exportCSV} className="bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 px-3 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm">
                Export CSV
              </button>
              <button onClick={exportPDF} className="bg-[#1b3252] hover:bg-[#152742] text-white px-3 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm">
                Export PDF
              </button>
            </div>
          </div>

          <div className="space-y-8">
            {positions.map((pos) => {
              const posCandidates = candidates.filter((c) => c.position_id === pos.id);
              const posVotesCount = votes.filter((v) => v.position_id === pos.id).length;

              return (
                <div key={pos.id} className="border border-slate-200 rounded-3xl p-6 bg-slate-50 text-slate-900">
                  <div className="flex justify-between items-center mb-4 border-b border-slate-200 pb-3">
                    <h3 className="text-lg font-bold text-[#101e36]">{pos.name}</h3>
                    <span className="text-xs bg-[#1b3252] text-white px-3 py-1 rounded-full font-bold shadow-sm">
                      {posVotesCount} Total Votes
                    </span>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {posCandidates.map((cand) => {
                      const cVotes = votes.filter((v) => v.candidate_id === cand.id).length;
                      const pct = posVotesCount > 0 ? ((cVotes / posVotesCount) * 100).toFixed(1) : '0.0';

                      return (
                        <div key={cand.id} className="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
                          <img
                            src={cand.recent_photo}
                            alt={cand.fullname}
                            className="w-14 h-14 rounded-2xl object-cover border-2 border-slate-300"
                          />
                          <div className="flex-1">
                            <h4 className="font-bold text-[#101e36] text-sm">{cand.fullname}</h4>
                            <div className="flex justify-between items-center text-xs text-slate-500 mt-1 mb-1">
                              <span>Votes: <strong className="text-slate-900">{cVotes}</strong></span>
                              <span className="font-bold text-[#1b3252]">{pct}%</span>
                            </div>
                            <div className="w-full bg-slate-100 rounded-full h-2.5 border border-slate-200 overflow-hidden">
                              <div
                                className="bg-[#1b3252] h-2.5 rounded-full"
                                style={{ width: `${pct}%` }}
                              />
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* TAB 5: AUDIT LOGS */}
      {activeTab === 'audit' && (
        <div className="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 space-y-6 text-slate-900">
          <div>
            <h2 className="text-xl font-extrabold text-[#101e36]">System Audit Logs</h2>
            <p className="text-xs text-slate-500">Immutable record of all registrations, tokens, and votes.</p>
          </div>

          <div className="space-y-3">
            {auditLogs.map((log) => (
              <div key={log.id} className="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex justify-between items-center text-xs text-slate-900">
                <div>
                  <div className="flex items-center space-x-2">
                    <span className="font-bold text-[#1b3252]">{log.user}</span>
                    <span className="text-slate-400">•</span>
                    <span className="font-mono text-[11px] text-slate-500">IP: {log.ip_address}</span>
                  </div>
                  <p className="text-slate-700 font-medium mt-1">{log.action}</p>
                </div>
                <span className="text-[11px] text-slate-500 font-mono whitespace-nowrap">{log.created_at}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* EXTENDED CANDIDATE MODAL */}
      {showCandModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-2xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto text-slate-900">
            <div className="flex justify-between items-center border-b border-slate-100 pb-3">
              <div>
                <h3 className="text-xl font-black text-[#101e36]">
                  {editingCandidate ? 'Edit Contestant Profile' : 'Add Executive Contestant'}
                </h3>
                <p className="text-xs text-slate-500">Configure candidate bio, position, sliding photos, and manifesto.</p>
              </div>
              <button
                type="button"
                onClick={() => setShowCandModal(false)}
                className="text-slate-400 hover:text-slate-600 font-bold p-1 text-lg"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleSaveCandidate} className="space-y-4 text-xs">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Full Contestant Name *</label>
                  <input
                    type="text"
                    value={candFullname}
                    onChange={(e) => setCandFullname(e.target.value)}
                    placeholder="e.g. Dr. Adewale B. Phillips"
                    className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                    required
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Preferred / Alias Name</label>
                  <input
                    type="text"
                    value={candPreferredName}
                    onChange={(e) => setCandPreferredName(e.target.value)}
                    placeholder="e.g. Wale Phillips"
                    className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Gender</label>
                  <select
                    value={candGender}
                    onChange={(e) => setCandGender(e.target.value as any)}
                    className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                  >
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Date of Birth</label>
                  <input
                    type="date"
                    value={candDob}
                    onChange={(e) => setCandDob(e.target.value)}
                    className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Department & Grad Year</label>
                  <input
                    type="text"
                    value={candDeptGradYear}
                    onChange={(e) => setCandDeptGradYear(e.target.value)}
                    placeholder="e.g. Mass Comm (2007)"
                    className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Executive Position *</label>
                  <div className="flex space-x-2">
                    <select
                      value={candPosId}
                      onChange={(e) => setCandPosId(parseInt(e.target.value, 10))}
                      className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                    >
                      {positions.map((p) => (
                        <option key={p.id} value={p.id} className="bg-white text-slate-900">
                          {p.name}
                        </option>
                      ))}
                    </select>
                    <button
                      type="button"
                      onClick={() => setShowAddPosModal(true)}
                      className="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl font-bold border border-slate-200 text-xs whitespace-nowrap"
                    >
                      + Custom
                    </button>
                  </div>
                </div>

                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Occupation / Title</label>
                  <input
                    type="text"
                    value={candOccupation}
                    onChange={(e) => setCandOccupation(e.target.value)}
                    placeholder="e.g. Chief Media Officer"
                    className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Email</label>
                  <input
                    type="email"
                    value={candEmail}
                    onChange={(e) => setCandEmail(e.target.value)}
                    placeholder="contestant@example.com"
                    className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Phone Number</label>
                  <input
                    type="text"
                    value={candPhone}
                    onChange={(e) => setCandPhone(e.target.value)}
                    placeholder="+234..."
                    className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Current Location</label>
                  <input
                    type="text"
                    value={candLocation}
                    onChange={(e) => setCandLocation(e.target.value)}
                    placeholder="City, Country"
                    className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 uppercase mb-1">Executive Manifesto Summary *</label>
                <textarea
                  value={candManifesto}
                  onChange={(e) => setCandManifesto(e.target.value)}
                  rows={3}
                  className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                  required
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 uppercase mb-1">Full Biography / Candidate Profile</label>
                <textarea
                  value={candProfile}
                  onChange={(e) => setCandProfile(e.target.value)}
                  rows={2}
                  className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">Old / University Photo URL</label>
                  <input
                    type="text"
                    value={candUniPhoto}
                    onChange={(e) => setCandUniPhoto(e.target.value)}
                    className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 uppercase mb-1">New / Recent Photo URL</label>
                  <input
                    type="text"
                    value={candRecentPhoto}
                    onChange={(e) => setCandRecentPhoto(e.target.value)}
                    className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 uppercase mb-1">Referee / Nomination Note</label>
                <input
                  type="text"
                  value={candReferee}
                  onChange={(e) => setCandReferee(e.target.value)}
                  placeholder="e.g. Approved by Electoral Steering Committee"
                  className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-medium focus:bg-white focus:border-slate-800 focus:outline-none"
                />
              </div>

              <div className="flex justify-end space-x-2 pt-4 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowCandModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl font-bold border border-slate-200"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-[#1b3252] hover:bg-[#152742] text-white rounded-xl font-bold shadow-sm"
                >
                  Save Contestant Profile
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* CREATE CUSTOM EXECUTIVE POSITION MODAL */}
      {showAddPosModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-md w-full p-6 space-y-4 text-slate-900">
            <div className="flex items-center space-x-3 text-[#101e36]">
              <div className="p-3 bg-sky-100 rounded-2xl">
                <Plus className="w-6 h-6 text-[#1b3252]" />
              </div>
              <div>
                <h3 className="text-lg font-black font-display">Create Custom Executive Position</h3>
                <p className="text-xs text-slate-500">Define a new elective position for the BUI Beta Set ballot.</p>
              </div>
            </div>

            <form onSubmit={handleCreateNewPosition} className="space-y-4 text-xs">
              <div>
                <label className="block font-bold text-slate-700 uppercase mb-1">Position Title *</label>
                <input
                  type="text"
                  value={newPositionName}
                  onChange={(e) => setNewPositionName(e.target.value)}
                  placeholder="e.g. Director of Socials & Welfare"
                  className="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-xl font-bold focus:bg-white focus:border-slate-800 focus:outline-none"
                  required
                />
              </div>

              <div className="flex justify-end space-x-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowAddPosModal(false)}
                  className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2.5 bg-[#1b3252] hover:bg-[#152742] text-white font-extrabold text-xs rounded-xl shadow-md transition-all"
                >
                  Create Executive Position
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* CSV CANDIDATES BULK UPLOAD MODAL */}
      {showCsvModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-xl w-full p-6 space-y-4 text-slate-900">
            <div className="flex justify-between items-center border-b border-slate-100 pb-3">
              <div className="flex items-center space-x-3">
                <div className="p-3 bg-emerald-100 rounded-2xl">
                  <FileSpreadsheet className="w-6 h-6 text-emerald-700" />
                </div>
                <div>
                  <h3 className="text-lg font-black font-display text-[#101e36]">Bulk Upload Candidates via CSV</h3>
                  <p className="text-xs text-slate-500">Import contestant records with positions, bios, and photo URLs.</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setShowCsvModal(false)}
                className="text-slate-400 hover:text-slate-600 font-bold p-1 text-lg"
              >
                ✕
              </button>
            </div>

            <div className="flex justify-between items-center bg-slate-50 p-3 rounded-2xl border border-slate-200 text-xs">
              <span className="font-semibold text-slate-700">Need the standard candidate CSV format?</span>
              <button
                type="button"
                onClick={handleDownloadCandidateCsvTemplate}
                className="inline-flex items-center space-x-1 bg-white hover:bg-slate-100 border border-slate-300 px-3 py-1.5 rounded-xl font-bold text-slate-800 text-[11px] transition-all shadow-sm"
              >
                <Download className="w-3.5 h-3.5 text-slate-700" />
                <span>Download Sample CSV Template</span>
              </button>
            </div>

            <div>
              <label className="block font-bold text-slate-700 uppercase mb-1 text-xs">
                Upload CSV File or Paste Raw CSV Lines
              </label>

              {/* Local File Selector */}
              <div className="mb-3">
                <input
                  type="file"
                  accept=".csv,text/csv"
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (file) {
                      const reader = new FileReader();
                      reader.onload = (event) => {
                        const text = event.target?.result as string;
                        if (text) setCsvContent(text);
                      };
                      reader.readAsText(file);
                    }
                  }}
                  className="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#1b3252] file:text-white hover:file:bg-[#152742] cursor-pointer"
                />
              </div>

              <textarea
                value={csvContent}
                onChange={(e) => setCsvContent(e.target.value)}
                rows={6}
                placeholder={`position_name,fullname,preferred_name,gender,date_of_birth,email,phone,location,dept_grad_year,occupation,profile,manifesto,university_photo,recent_photo\n"Gen Secretary","Dr. Adewale Phillips","Wale","Male","1986-05-14","wale@example.com","+234803...","Lagos","Mass Comm 2007","Media Chief","Pioneer leader","To automate secretarial archives","http://...","http://..."`}
                className="w-full p-3.5 border border-slate-200 bg-slate-50 text-slate-900 rounded-2xl font-mono text-[11px] focus:bg-white focus:border-slate-800 focus:outline-none"
              />
            </div>

            <div className="flex justify-end space-x-2 pt-2 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setShowCsvModal(false)}
                className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleProcessCsvCandidateImport}
                className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all inline-flex items-center space-x-1.5"
              >
                <FileUp className="w-4 h-4" />
                <span>Process CSV Import</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* RESET ELECTION DATABASE MODAL */}
      {showResetModal && (
        <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl p-6 max-w-md w-full border border-slate-200 shadow-2xl space-y-4">
            <div className="flex items-center space-x-3 text-rose-600">
              <div className="p-3 bg-rose-100 rounded-2xl">
                <Trash2 className="w-6 h-6 text-rose-600" />
              </div>
              <div>
                <h3 className="text-lg font-black text-slate-900 font-display">Reset Election Database Afresh</h3>
                <p className="text-xs text-rose-600 font-bold">Irreversible Administrative Action</p>
              </div>
            </div>

            <p className="text-xs text-slate-600 leading-relaxed">
              This action will permanently wipe all <strong className="text-slate-900 font-bold">ballots cast ({votes.length})</strong>, <strong className="text-slate-900 font-bold">voter voting tokens ({voters.length})</strong>, <strong className="text-slate-900 font-bold">alumni registrations ({registrations.length})</strong>, and <strong className="text-slate-900 font-bold">outbox email queues</strong> so you can conduct a completely fresh election.
            </p>

            <div className="bg-rose-50 border border-rose-200 p-3.5 rounded-2xl text-xs text-rose-900 space-y-2">
              <p className="font-bold">To confirm database wipe, type <code className="bg-rose-200 px-1.5 py-0.5 rounded text-rose-950 font-black">RESET</code> below:</p>
              <input
                type="text"
                value={resetConfirmText}
                onChange={(e) => setResetConfirmText(e.target.value)}
                placeholder="Type RESET in capital letters"
                className="w-full px-3.5 py-2 border border-rose-300 rounded-xl bg-white text-slate-900 text-xs font-mono font-bold focus:outline-none focus:border-rose-600"
              />
            </div>

            <div className="flex items-center justify-end space-x-2 pt-2">
              <button
                type="button"
                onClick={() => {
                  setShowResetModal(false);
                  setResetConfirmText('');
                }}
                className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleConfirmResetDatabase}
                disabled={resetConfirmText.trim().toUpperCase() !== 'RESET'}
                className="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center space-x-1.5"
              >
                <Trash2 className="w-4 h-4" />
                <span>Clear Database Afresh</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* DESIGNED POPUP MODAL */}
      <DesignedPopup data={popup} onClose={() => setPopup(null)} />
    </div>
  );
};
