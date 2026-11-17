import React from 'react';
import { X, Award, Briefcase, GraduationCap, MapPin, Calendar, Phone, Mail, FileText, CheckCircle2, ShieldAlert, Sparkles, UserCheck } from 'lucide-react';
import { Candidate, Position } from '../types';
import { SlidingCandidatePhoto } from './SlidingCandidatePhoto';

interface CandidateDetailModalProps {
  candidate: Candidate | null;
  position?: Position;
  onClose: () => void;
  onSelectCandidate?: (candidateId: number) => void;
  isSelected?: boolean;
}

export const CandidateDetailModal: React.FC<CandidateDetailModalProps> = ({
  candidate,
  position,
  onClose,
  onSelectCandidate,
  isSelected = false,
}) => {
  if (!candidate) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-950/80 backdrop-blur-md animate-fade-in overflow-y-auto">
      <div className="relative w-full max-w-2xl bg-slate-900 text-white rounded-3xl border border-sky-500/30 shadow-2xl overflow-hidden my-auto animate-scale-up">
        {/* Header Ribbon */}
        <div className="bg-gradient-to-r from-[#1A3A5C] via-slate-800 to-[#101e36] px-6 py-5 border-b border-white/10 flex items-center justify-between">
          <div>
            <span className="inline-flex items-center space-x-1.5 bg-sky-500/20 text-sky-300 border border-sky-400/30 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider mb-1">
              <Award className="w-3.5 h-3.5 text-sky-300" />
              <span>Contested Position: {position?.name || candidate.position_name || 'Executive Candidate'}</span>
            </span>
            <h2 className="text-xl sm:text-2xl font-black text-white font-display">
              {candidate.fullname}
            </h2>
            {candidate.preferred_name && candidate.preferred_name !== candidate.fullname && (
              <p className="text-xs text-sky-300 font-semibold">
                Preferred Name: "{candidate.preferred_name}"
              </p>
            )}
          </div>

          <button
            onClick={onClose}
            className="p-2.5 bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white rounded-2xl transition-all"
            title="Close Profile"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="p-6 space-y-6 max-h-[75vh] overflow-y-auto custom-scrollbar">
          {/* Photos & Primary Highlights */}
          <div className="flex flex-col sm:flex-row items-center sm:items-start gap-6 bg-slate-950/60 p-5 rounded-2xl border border-white/10">
            <SlidingCandidatePhoto
              universityPhoto={candidate.university_photo}
              recentPhoto={candidate.recent_photo}
              candidateName={candidate.fullname}
              positionName={position?.name || candidate.position_name}
              size="lg"
            />

            <div className="flex-1 space-y-3 text-center sm:text-left">
              <div>
                <h3 className="text-lg font-black text-white">{candidate.fullname}</h3>
                <p className="text-xs text-sky-300 font-bold flex items-center justify-center sm:justify-start space-x-1 mt-1">
                  <Briefcase className="w-3.5 h-3.5" />
                  <span>{candidate.occupation || 'Executive Leadership Candidate'}</span>
                </p>
              </div>

              <div className="grid grid-cols-1 gap-2 text-xs text-slate-300">
                {candidate.dept_grad_year && (
                  <div className="flex items-center space-x-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/5">
                    <GraduationCap className="w-4 h-4 text-amber-400 shrink-0" />
                    <span><strong>Dept / Grad Year:</strong> {candidate.dept_grad_year}</span>
                  </div>
                )}

                {candidate.location && (
                  <div className="flex items-center space-x-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/5">
                    <MapPin className="w-4 h-4 text-rose-400 shrink-0" />
                    <span><strong>Residence:</strong> {candidate.location}</span>
                  </div>
                )}

                {candidate.gender && (
                  <div className="flex items-center space-x-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/5">
                    <UserCheck className="w-4 h-4 text-emerald-400 shrink-0" />
                    <span><strong>Gender:</strong> {candidate.gender}</span>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Detailed Candidate Profile (Section C Field) */}
          <div className="space-y-2">
            <h4 className="text-xs font-black uppercase tracking-wider text-sky-300 flex items-center space-x-1.5">
              <Sparkles className="w-4 h-4 text-sky-400" />
              <span>Candidate Profile & Alumni Leadership History</span>
            </h4>
            <div className="bg-slate-950/80 p-4 rounded-2xl border border-white/10 text-xs text-slate-300 leading-relaxed font-sans whitespace-pre-line">
              {candidate.profile || 'No additional profile biography submitted.'}
            </div>
          </div>

          {/* Official Manifesto (Section C Field) */}
          <div className="space-y-2">
            <h4 className="text-xs font-black uppercase tracking-wider text-amber-300 flex items-center space-x-1.5">
              <FileText className="w-4 h-4 text-amber-400" />
              <span>Official Executive Manifesto</span>
            </h4>
            <div className="bg-amber-500/10 border-l-4 border-amber-400 p-4 rounded-r-2xl border border-white/10 text-xs text-amber-100 italic leading-relaxed whitespace-pre-line">
              "{candidate.manifesto || 'No manifesto text available.'}"
            </div>
          </div>

          {/* Supporting Verification & Declarations (Section D Fields) */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            {candidate.referee_letter && (
              <div className="bg-slate-950/60 p-3.5 rounded-2xl border border-white/10 space-y-1">
                <span className="font-bold text-slate-200 block text-[11px] uppercase tracking-wider">Referee Nomination</span>
                <p className="text-slate-300">{candidate.referee_letter}</p>
              </div>
            )}

            <div className="bg-slate-950/60 p-3.5 rounded-2xl border border-white/10 space-y-1">
              <span className="font-bold text-slate-200 block text-[11px] uppercase tracking-wider">Conflict of Interest Declaration</span>
              <p className={candidate.has_conflict ? 'text-amber-300 font-semibold' : 'text-emerald-300 font-semibold'}>
                {candidate.has_conflict ? 'Declared potential interest' : 'Confirmed No Conflicts of Interest'}
              </p>
            </div>
          </div>
        </div>

        {/* Footer Actions */}
        <div className="bg-slate-950/90 p-5 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div className="text-xs text-slate-400 flex items-center space-x-1">
            <ShieldAlert className="w-4 h-4 text-sky-400 shrink-0" />
            <span>Verified by BUI Electoral Committee</span>
          </div>

          <div className="flex items-center space-x-3 w-full sm:w-auto">
            <button
              onClick={onClose}
              className="w-1/2 sm:w-auto px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl border border-white/10"
            >
              Close
            </button>

            {onSelectCandidate && (
              <button
                onClick={() => {
                  onSelectCandidate(candidate.id);
                  onClose();
                }}
                className={`w-1/2 sm:w-auto px-6 py-2.5 font-extrabold text-xs rounded-xl shadow-lg transition-all flex items-center justify-center space-x-1.5 ${
                  isSelected
                    ? 'bg-emerald-500 text-slate-950 hover:bg-emerald-400'
                    : 'bg-[#1A3A5C] text-white hover:bg-sky-900 border border-sky-400/40'
                }`}
              >
                <CheckCircle2 className="w-4 h-4" />
                <span>{isSelected ? 'Candidate Selected' : `Vote for ${candidate.fullname.split(' ')[0]}`}</span>
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
