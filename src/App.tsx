import React, { useState } from 'react';
import { Header } from './components/Header';
import { RegistrationForm } from './components/RegistrationForm';
import { VotingSection } from './components/VotingSection';
import { AdminPortal } from './components/AdminPortal';
import { MailDrawer } from './components/MailDrawer';
import { PhpSourceInspector } from './components/PhpSourceInspector';
import { PublicTurnout } from './components/PublicTurnout';

import {
  INITIAL_POSITIONS,
  INITIAL_CANDIDATES,
  INITIAL_REGISTRATIONS,
  INITIAL_VOTERS,
  INITIAL_AUDIT_LOGS,
  INITIAL_ELIGIBLE_VOTERS,
} from './data/initialData';

import { Registration, Voter, Candidate, Position, VoteRecord, AuditLogItem, OutboxMail, EligibleVoter } from './types';

export default function App() {
  const [activeTab, setActiveTab] = useState<'register' | 'vote' | 'turnout' | 'admin' | 'php-code'>('register');

  const [eligibleVoters, setEligibleVoters] = useState<EligibleVoter[]>(INITIAL_ELIGIBLE_VOTERS);
  const [positions, setPositions] = useState<Position[]>(INITIAL_POSITIONS);
  const [candidates, setCandidates] = useState<Candidate[]>(INITIAL_CANDIDATES);
  const [registrations, setRegistrations] = useState<Registration[]>(INITIAL_REGISTRATIONS);
  const [voters, setVoters] = useState<Voter[]>(INITIAL_VOTERS);
  const [votes, setVotes] = useState<VoteRecord[]>([
    { id: 1, voter_id: 1, candidate_id: 101, position_id: 1, created_at: '2026-07-21 10:00:00' },
    { id: 2, voter_id: 1, candidate_id: 202, position_id: 2, created_at: '2026-07-21 10:00:00' },
    { id: 3, voter_id: 1, candidate_id: 301, position_id: 3, created_at: '2026-07-21 10:00:00' },
    { id: 4, voter_id: 1, candidate_id: 401, position_id: 4, created_at: '2026-07-21 10:00:00' },
  ]);
  const [auditLogs, setAuditLogs] = useState<AuditLogItem[]>(INITIAL_AUDIT_LOGS);

  // Outbox Mail Dispatcher
  const [outbox, setOutbox] = useState<OutboxMail[]>([
    {
      id: 'm1',
      toEmail: 'blessing.o@example.com',
      toName: 'Blessing Okonjo',
      token: 'M4P9X7WR',
      sentAt: '2026-07-21 11:30:05',
    },
    {
      id: 'm2',
      toEmail: 'kelechi.a@example.com',
      toName: 'Kelechi Amadi',
      token: 'K3T8V2NY',
      sentAt: '2026-07-22 14:20:05',
    },
  ]);
  const [isMailOpen, setIsMailOpen] = useState(false);

  // Prefilled ballot verification fields
  const [prefilledEmail, setPrefilledEmail] = useState('');
  const [prefilledToken, setPrefilledToken] = useState('');

  // Handle Registration Success
  const handleRegisterSuccess = (newReg: Registration, newVoter: Voter, token: string) => {
    setRegistrations((prev) => [newReg, ...prev]);
    setVoters((prev) => [newVoter, ...prev]);

    // Add to PHPMailer Outbox
    const newMail: OutboxMail = {
      id: `m_${Date.now()}`,
      toEmail: newReg.email,
      toName: newReg.fullname,
      token,
      sentAt: new Date().toLocaleString(),
    };
    setOutbox((prev) => [newMail, ...prev]);

    // Audit Log
    const newAudit: AuditLogItem = {
      id: auditLogs.length + 1,
      user: 'Registration System',
      action: `Alumni registered: ${newReg.fullname} (${newReg.email}) - Token ${token}`,
      ip_address: '197.210.226.4',
      created_at: new Date().toLocaleString(),
    };
    setAuditLogs((prev) => [newAudit, ...prev]);
  };

  // Handle Vote Submission
  const handleSubmitVote = (voterId: number, selections: Record<number, number>) => {
    const nowStr = new Date().toLocaleString();

    // Mark voter as voted
    setVoters((prev) =>
      prev.map((v) => (v.id === voterId ? { ...v, has_voted: true, voted_at: nowStr } : v))
    );

    // Record votes
    const newVoteRecords: VoteRecord[] = Object.entries(selections).map(([posId, candId], idx) => ({
      id: votes.length + idx + 1,
      voter_id: voterId,
      candidate_id: candId,
      position_id: parseInt(posId, 10),
      created_at: nowStr,
    }));

    setVotes((prev) => [...prev, ...newVoteRecords]);

    const voterObj = voters.find((v) => v.id === voterId);
    const newAudit: AuditLogItem = {
      id: auditLogs.length + 1,
      user: 'Voting System',
      action: `Vote submitted successfully by voter ID ${voterId} (${voterObj?.email || 'Voter'})`,
      ip_address: '197.210.226.4',
      created_at: nowStr,
    };
    setAuditLogs((prev) => [newAudit, ...prev]);
  };

  // Candidate Actions
  const handleAddCandidate = (candData: Omit<Candidate, 'id'>) => {
    const newCand: Candidate = {
      ...candData,
      id: Date.now(),
    };
    setCandidates((prev) => [...prev, newCand]);

    setAuditLogs((prev) => [
      {
        id: prev.length + 1,
        user: 'Electoral Commission',
        action: `Added new candidate: ${newCand.fullname}`,
        ip_address: '127.0.0.1',
        created_at: new Date().toLocaleString(),
      },
      ...prev,
    ]);
  };

  const handleUpdateCandidate = (updated: Candidate) => {
    setCandidates((prev) => prev.map((c) => (c.id === updated.id ? updated : c)));
  };

  const handleDeleteCandidate = (id: number) => {
    setCandidates((prev) => prev.filter((c) => c.id !== id));
  };

  const handleToggleCandidateActive = (id: number) => {
    setCandidates((prev) =>
      prev.map((c) => (c.id === id ? { ...c, active: !c.active } : c))
    );
  };

  const handleAddPosition = (posName: string) => {
    const newPos: Position = {
      id: Date.now(),
      name: posName.trim(),
      display_order: positions.length + 1,
      active: true,
    };
    setPositions((prev) => [...prev, newPos]);

    setAuditLogs((prev) => [
      {
        id: prev.length + 1,
        user: 'Electoral Commission',
        action: `Created new custom executive position: ${newPos.name}`,
        ip_address: '127.0.0.1',
        created_at: new Date().toLocaleString(),
      },
      ...prev,
    ]);
    return newPos;
  };

  const handleResendToken = (voterId: number) => {
    const voter = voters.find((v) => v.id === voterId);
    if (!voter) return;

    const newMail: OutboxMail = {
      id: `m_resend_${Date.now()}`,
      toEmail: voter.email,
      toName: voter.fullname || 'Alumni Member',
      token: voter.voting_token,
      sentAt: new Date().toLocaleString(),
    };
    setOutbox((prev) => [newMail, ...prev]);

    setAuditLogs((prev) => [
      {
        id: prev.length + 1,
        user: 'Admin Portal',
        action: `Resent voting token to ${voter.email}`,
        ip_address: '127.0.0.1',
        created_at: new Date().toLocaleString(),
      },
      ...prev,
    ]);

    alert(`Voting token (${voter.voting_token}) resent to ${voter.email}. Check the SMTP Outbox drawer.`);
  };

  const handleResetDatabase = () => {
    setVoters([]);
    setRegistrations([]);
    setVotes([]);
    setOutbox([]);
    setAuditLogs([
      {
        id: Date.now(),
        user: 'Super Admin',
        action: 'RESET ELECTION DATABASE: Cleared all votes, voters, registrations, and SMTP outbox queue.',
        ip_address: '127.0.0.1',
        created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
      },
    ]);
  };

  return (
    <div className="min-h-screen bg-[#0F172A] text-slate-100 font-sans flex flex-col antialiased relative overflow-x-hidden selection:bg-sky-500 selection:text-white">
      {/* Ambient Frosted Background Orbs */}
      <div className="fixed top-[-10%] right-[-10%] w-[600px] h-[600px] bg-[#1A3A5C] rounded-full blur-[140px] opacity-40 pointer-events-none z-0" />
      <div className="fixed bottom-[-10%] left-[-10%] w-[500px] h-[500px] bg-sky-900/60 rounded-full blur-[120px] opacity-30 pointer-events-none z-0" />
      <div className="fixed top-[40%] left-[30%] w-[350px] h-[350px] bg-blue-900/30 rounded-full blur-[100px] opacity-25 pointer-events-none z-0" />

      <Header
        activeTab={activeTab}
        setActiveTab={setActiveTab}
        outboxCount={outbox.length}
        onOpenOutbox={() => setIsMailOpen(true)}
      />

      <main className="flex-1 relative z-10">
        {activeTab === 'register' && (
          <RegistrationForm
            registrations={registrations}
            voters={voters}
            eligibleVoters={eligibleVoters}
            onRegisterSuccess={handleRegisterSuccess}
            onNavigateToVote={(email, token) => {
              setPrefilledEmail(email);
              setPrefilledToken(token);
              setActiveTab('vote');
            }}
          />
        )}

        {activeTab === 'vote' && (
          <VotingSection
            voters={voters}
            positions={positions}
            candidates={candidates}
            prefilledEmail={prefilledEmail}
            prefilledToken={prefilledToken}
            onSubmitVote={handleSubmitVote}
          />
        )}

        {activeTab === 'turnout' && (
          <PublicTurnout
            positions={positions}
            candidates={candidates}
            voters={voters}
            votes={votes}
          />
        )}

        {activeTab === 'admin' && (
          <AdminPortal
            candidates={candidates}
            positions={positions}
            registrations={registrations}
            voters={voters}
            votes={votes}
            auditLogs={auditLogs}
            onAddCandidate={handleAddCandidate}
            onUpdateCandidate={handleUpdateCandidate}
            onDeleteCandidate={handleDeleteCandidate}
            onToggleCandidateActive={handleToggleCandidateActive}
            onAddPosition={handleAddPosition}
            onResendToken={handleResendToken}
            onResetDatabase={handleResetDatabase}
          />
        )}

        {activeTab === 'php-code' && <PhpSourceInspector />}
      </main>

      {/* Mail Drawer */}
      <MailDrawer
        isOpen={isMailOpen}
        onClose={() => setIsMailOpen(false)}
        outbox={outbox}
        onUseToken={(email, token) => {
          setPrefilledEmail(email);
          setPrefilledToken(token);
          setActiveTab('vote');
        }}
      />

      {/* Footer */}
      <footer className="relative z-10 bg-white/5 backdrop-blur-xl text-slate-400 py-8 border-t border-white/10 text-xs mt-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
          <div>
            <span className="font-extrabold text-white">VCAST - BUI Beta Set Alumni Election</span>
            <p className="mt-1 text-slate-300">Official Hostinger Web Hosting Release | Target: <a href="https://vote.buibetaset.org" className="text-sky-400 hover:underline">https://vote.buibetaset.org</a></p>
          </div>
          <div className="text-center md:text-right">
            <p>&copy; {new Date().getFullYear()} BUI Beta Set Alumni Electoral Committee. All rights reserved.</p>
            <p className="mt-1 text-[11px] text-slate-400">Built with PHP 8.2+, MySQL 8+, PHPMailer & Tailwind CSS (Frosted Glass)</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
