import React from 'react';
import { X, Mail, CheckCircle2, ShieldCheck, Copy, ExternalLink } from 'lucide-react';
import { OutboxMail } from '../types';

interface MailDrawerProps {
  isOpen: boolean;
  onClose: () => void;
  outbox: OutboxMail[];
  onUseToken: (email: string, token: string) => void;
}

export const MailDrawer: React.FC<MailDrawerProps> = ({
  isOpen,
  onClose,
  outbox,
  onUseToken,
}) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-md z-50 flex justify-end animate-fade-in">
      <div className="bg-slate-900/90 backdrop-blur-2xl w-full max-w-md h-full shadow-2xl flex flex-col border-l border-white/10 text-white">
        {/* Drawer Header */}
        <div className="bg-[#1A3A5C]/80 text-white p-5 flex items-center justify-between border-b border-white/10">
          <div className="flex items-center space-x-2">
            <Mail className="w-5 h-5 text-amber-400" />
            <div>
              <h3 className="font-extrabold text-base text-white">PHPMailer SMTP Outbox</h3>
              <p className="text-[10px] text-slate-300">Live Dispatched Voting Tokens</p>
            </div>
          </div>
          <button onClick={onClose} className="p-1.5 rounded-xl hover:bg-white/10 text-slate-300 hover:text-white transition-colors">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Drawer Body */}
        <div className="flex-1 overflow-y-auto p-4 space-y-4">
          <div className="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-3 text-xs text-amber-200 flex items-start space-x-2">
            <ShieldCheck className="w-4 h-4 text-amber-400 shrink-0 mt-0.5" />
            <span>
              In Hostinger production, PHPMailer dispatches these exact HTML emails to alumni via <code className="font-bold text-amber-300">smtp.hostinger.com:465</code>.
            </span>
          </div>

          {outbox.length === 0 ? (
            <div className="text-center py-12 text-slate-400 text-xs">
              <Mail className="w-8 h-8 mx-auto mb-2 opacity-40" />
              <p>No emails dispatched yet.</p>
              <p className="mt-1">Complete an alumni registration to trigger PHPMailer.</p>
            </div>
          ) : (
            outbox.map((mail) => (
              <div key={mail.id} className="border border-white/10 rounded-2xl p-4 bg-white/5 space-y-3 text-white shadow-xl">
                <div className="flex justify-between items-start text-xs border-b border-white/10 pb-2">
                  <div>
                    <span className="font-bold text-white">{mail.toName}</span>
                    <p className="text-slate-300 text-[11px]">{mail.toEmail}</p>
                  </div>
                  <span className="text-[10px] bg-emerald-500/20 text-emerald-300 font-bold px-2 py-0.5 rounded-full flex items-center space-x-1 border border-emerald-400/30">
                    <CheckCircle2 className="w-3 h-3" />
                    <span>Sent</span>
                  </span>
                </div>

                <div className="bg-slate-950/80 border border-white/10 text-white rounded-xl p-3 text-center">
                  <div className="text-[10px] text-slate-400 uppercase tracking-widest">Voting Token</div>
                  <div className="font-mono text-2xl font-black text-amber-400 tracking-widest my-1">
                    {mail.token}
                  </div>
                  <span className="text-[10px] text-slate-400">{mail.sentAt}</span>
                </div>

                <button
                  onClick={() => {
                    onUseToken(mail.toEmail, mail.token);
                    onClose();
                  }}
                  className="w-full py-2 bg-[#1A3A5C] hover:bg-sky-900 border border-sky-400/40 text-white rounded-xl font-bold text-xs flex items-center justify-center space-x-1.5 transition-all shadow"
                >
                  <span>Use This Token on Ballot Paper</span>
                  <ExternalLink className="w-3.5 h-3.5 text-sky-300" />
                </button>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
};
