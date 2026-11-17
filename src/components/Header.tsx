import React from 'react';
import { UserCheck, Vote, BarChart3, ShieldCheck, Mail, Code2, ExternalLink } from 'lucide-react';

interface HeaderProps {
  activeTab: 'register' | 'vote' | 'turnout' | 'admin' | 'php-code';
  setActiveTab: (tab: 'register' | 'vote' | 'turnout' | 'admin' | 'php-code') => void;
  outboxCount: number;
  onOpenOutbox: () => void;
}

export const Header: React.FC<HeaderProps> = ({
  activeTab,
  setActiveTab,
  outboxCount,
  onOpenOutbox,
}) => {
  return (
    <header className="bg-white/5 backdrop-blur-xl border-b border-white/10 text-white shadow-2xl sticky top-0 z-40">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-20">
          {/* Brand Logo & Name */}
          <div className="flex items-center space-x-3 cursor-pointer" onClick={() => setActiveTab('register')}>
            <div className="w-11 h-11 rounded-xl bg-[#1A3A5C] flex items-center justify-center text-white font-black text-xl shadow-inner border border-white/20">
              B
            </div>
            <div>
              <div className="flex items-center space-x-2">
                <span className="font-extrabold text-xl tracking-tight text-white">VCAST</span>
                <span className="bg-sky-500/10 text-sky-300 text-xs px-2.5 py-0.5 rounded-full font-semibold border border-sky-500/20">
                  Alumni Voting
                </span>
              </div>
              <p className="text-[10px] text-slate-400 uppercase tracking-widest">BUI Beta Set Association</p>
            </div>
          </div>

          {/* Navigation Links */}
          <nav className="hidden md:flex items-center space-x-1 lg:space-x-2">
            <button
              onClick={() => setActiveTab('register')}
              className={`flex items-center space-x-2 px-3.5 py-2 rounded-xl font-medium text-sm transition-all ${
                activeTab === 'register'
                  ? 'bg-[#1A3A5C] text-white font-semibold border border-sky-400/30 shadow-[0_0_15px_rgba(56,189,248,0.15)]'
                  : 'text-slate-300 hover:text-white hover:bg-white/5'
              }`}
            >
              <UserCheck className="w-4 h-4 text-sky-400" />
              <span>Register / Token</span>
            </button>

            <button
              onClick={() => setActiveTab('vote')}
              className={`flex items-center space-x-2 px-3.5 py-2 rounded-xl font-medium text-sm transition-all ${
                activeTab === 'vote'
                  ? 'bg-[#1A3A5C] text-white font-semibold border border-sky-400/30 shadow-[0_0_15px_rgba(56,189,248,0.15)]'
                  : 'text-slate-300 hover:text-white hover:bg-white/5'
              }`}
            >
              <Vote className="w-4 h-4 text-sky-400" />
              <span>Vote Now</span>
            </button>

            <button
              onClick={() => setActiveTab('turnout')}
              className={`flex items-center space-x-2 px-3.5 py-2 rounded-xl font-medium text-sm transition-all ${
                activeTab === 'turnout'
                  ? 'bg-[#1A3A5C] text-white font-semibold border border-sky-400/30 shadow-[0_0_15px_rgba(56,189,248,0.15)]'
                  : 'text-slate-300 hover:text-white hover:bg-white/5'
              }`}
            >
              <BarChart3 className="w-4 h-4 text-sky-400" />
              <span>Turnout & Results</span>
            </button>

            <button
              onClick={() => setActiveTab('admin')}
              className={`flex items-center space-x-2 px-3.5 py-2 rounded-xl font-medium text-sm transition-all ${
                activeTab === 'admin'
                  ? 'bg-[#1A3A5C] text-white font-semibold border border-sky-400/30 shadow-[0_0_15px_rgba(56,189,248,0.15)]'
                  : 'text-slate-300 hover:text-white hover:bg-white/5'
              }`}
            >
              <ShieldCheck className="w-4 h-4 text-sky-400" />
              <span>Admin Portal</span>
            </button>

            <button
              onClick={() => setActiveTab('php-code')}
              className={`flex items-center space-x-2 px-3.5 py-2 rounded-xl font-medium text-sm transition-all border ${
                activeTab === 'php-code'
                  ? 'bg-emerald-500/20 text-emerald-300 border-emerald-400/50 font-semibold shadow'
                  : 'text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/10'
              }`}
            >
              <Code2 className="w-4 h-4 text-emerald-400" />
              <span>PHP MVC Source</span>
            </button>
          </nav>

          {/* Action Buttons: Mail Simulator & Target Hostinger Tag */}
          <div className="flex items-center space-x-3">
            <button
              onClick={onOpenOutbox}
              className="relative p-2.5 bg-white/5 hover:bg-white/10 rounded-xl border border-white/10 text-sky-300 transition-all flex items-center space-x-2 shadow"
              title="PHPMailer SMTP Dispatch Simulator"
            >
              <Mail className="w-5 h-5 text-sky-400" />
              <span className="hidden sm:inline text-xs font-semibold text-slate-200">SMTP Outbox</span>
              {outboxCount > 0 && (
                <span className="absolute -top-1 -right-1 bg-amber-500 text-slate-950 font-bold text-xs w-5 h-5 rounded-full flex items-center justify-center animate-pulse">
                  {outboxCount}
                </span>
              )}
            </button>

            <div className="hidden xl:flex items-center space-x-1.5 text-xs bg-white/5 border border-white/10 px-3 py-1.5 rounded-xl text-slate-300">
              <span className="w-2 h-2 rounded-full bg-sky-400 animate-ping" />
              <span className="font-mono text-sky-300">vote.buibetaset.org</span>
              <ExternalLink className="w-3.0 h-3.0 opacity-60" />
            </div>
          </div>
        </div>

        {/* Mobile Navigation Tabs */}
        <div className="md:hidden flex items-center justify-between pb-3 overflow-x-auto space-x-1 pt-1">
          <button
            onClick={() => setActiveTab('register')}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap ${
              activeTab === 'register' ? 'bg-[#1A3A5C] text-sky-300 border border-sky-400/40 font-bold' : 'text-slate-300 bg-white/5'
            }`}
          >
            Register
          </button>
          <button
            onClick={() => setActiveTab('vote')}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap ${
              activeTab === 'vote' ? 'bg-[#1A3A5C] text-sky-300 border border-sky-400/40 font-bold' : 'text-slate-300 bg-white/5'
            }`}
          >
            Vote Ballot
          </button>
          <button
            onClick={() => setActiveTab('turnout')}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap ${
              activeTab === 'turnout' ? 'bg-[#1A3A5C] text-sky-300 border border-sky-400/40 font-bold' : 'text-slate-300 bg-white/5'
            }`}
          >
            Turnout
          </button>
          <button
            onClick={() => setActiveTab('admin')}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap ${
              activeTab === 'admin' ? 'bg-[#1A3A5C] text-sky-300 border border-sky-400/40 font-bold' : 'text-slate-300 bg-white/5'
            }`}
          >
            Admin
          </button>
          <button
            onClick={() => setActiveTab('php-code')}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap ${
              activeTab === 'php-code' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 font-bold' : 'text-emerald-300 bg-white/5'
            }`}
          >
            PHP Source
          </button>
        </div>
      </div>
    </header>
  );
};
