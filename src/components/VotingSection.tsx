import React, { useState, useEffect } from 'react';
import { KeyRound, CheckCircle2, ShieldX, User, Sparkles, Award, Lock, FileText, Check, Info, ExternalLink } from 'lucide-react';
import confetti from 'canvas-confetti';
import { Voter, Position, Candidate, PopupData } from '../types';
import { SlidingCandidatePhoto } from './SlidingCandidatePhoto';
import { CandidateDetailModal } from './CandidateDetailModal';
import { DesignedPopup } from './DesignedPopup';

interface VotingSectionProps {
  voters: Voter[];
  positions: Position[];
  candidates: Candidate[];
  prefilledEmail?: string;
  prefilledToken?: string;
  onSubmitVote: (voterId: number, selections: Record<number, number>) => void;
}

export const VotingSection: React.FC<VotingSectionProps> = ({
  voters,
  positions,
  candidates,
  prefilledEmail = '',
  prefilledToken = '',
  onSubmitVote,
}) => {
  const [email, setEmail] = useState(prefilledEmail);
  const [token, setToken] = useState(prefilledToken);
  const [errorMsg, setErrorMsg] = useState('');
  
  const [verifiedVoter, setVerifiedVoter] = useState<Voter | null>(null);
  const [selectedCandidates, setSelectedCandidates] = useState<Record<number, number>>({});
  
  const [voteSubmitted, setVoteSubmitted] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Modal & Popup States
  const [detailCandidate, setDetailCandidate] = useState<{ candidate: Candidate; position: Position } | null>(null);
  const [popup, setPopup] = useState<PopupData | null>(null);

  useEffect(() => {
    if (prefilledEmail) setEmail(prefilledEmail);
    if (prefilledToken) setToken(prefilledToken);
  }, [prefilledEmail, prefilledToken]);

  const showPopup = (type: 'success' | 'error' | 'warning' | 'info', title: string, message: string) => {
    setPopup({
      isOpen: true,
      type,
      title,
      message,
    });
  };

  const handleVerify = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg('');

    const cleanEmail = email.trim().toLowerCase();
    const cleanToken = token.trim().toUpperCase();

    if (!cleanEmail || !cleanToken) {
      showPopup('warning', 'Missing Details', 'Please enter both your email address and voting token.');
      return;
    }

    const matchedVoter = voters.find(
      (v) => v.email.toLowerCase() === cleanEmail && v.voting_token.toUpperCase() === cleanToken
    );

    if (!matchedVoter) {
      showPopup('error', 'Authentication Failed', 'Invalid email or voting token. Please verify your credentials and try again.');
      return;
    }

    if (matchedVoter.has_voted) {
      showPopup('warning', 'Token Already Used', 'This voting token has already been used to cast an official ballot. Duplicate voting is strictly prohibited.');
      return;
    }

    setVerifiedVoter(matchedVoter);
  };

  const handleSelectCandidate = (positionId: number, candidateId: number) => {
    setSelectedCandidates((prev) => ({
      ...prev,
      [positionId]: candidateId,
    }));
  };

  const handleSubmitBallot = (e: React.FormEvent) => {
    e.preventDefault();
    if (!verifiedVoter) return;

    const activePositions = positions.filter((p) => p.active);
    const missingPositions = activePositions.filter((p) => !selectedCandidates[p.id]);

    if (missingPositions.length > 0) {
      showPopup(
        'warning',
        'Incomplete Ballot Paper',
        `Please select a candidate for all active executive positions:\n- ${missingPositions.map((p) => p.name).join('\n- ')}`
      );
      return;
    }

    setIsSubmitting(true);

    setTimeout(() => {
      onSubmitVote(verifiedVoter.id, selectedCandidates);
      setIsSubmitting(false);
      setVoteSubmitted(true);

      confetti({
        particleCount: 120,
        spread: 80,
        origin: { y: 0.6 },
      });
    }, 800);
  };

  if (voteSubmitted) {
    return (
      <div className="max-w-2xl mx-auto my-12 p-8 bg-white/5 backdrop-blur-xl border-2 border-emerald-500/60 rounded-3xl shadow-2xl text-center animate-fade-in text-white">
        <div className="w-20 h-20 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-500/30">
          <CheckCircle2 className="w-12 h-12" />
        </div>

        <h1 className="text-3xl font-extrabold text-white mb-2">Thank You!</h1>
        <p className="text-lg font-semibold text-emerald-300 mb-4">
          Your vote has been recorded successfully.
        </p>

        <p className="text-slate-300 text-sm max-w-md mx-auto mb-6">
          Your ballot has been securely counted in the BUI Beta Set Alumni election database. Your voting token has been invalidated to prevent duplicate submissions.
        </p>

        <div className="p-4 bg-slate-950/80 border border-white/10 rounded-2xl text-xs text-slate-400 max-w-sm mx-auto mb-6">
          <strong className="text-slate-200">Transaction Timestamp:</strong> {new Date().toLocaleString()}
          <br />
          <strong className="text-slate-200">Status:</strong> Encrypted & Audited
        </div>

        <button
          onClick={() => {
            setVoteSubmitted(false);
            setVerifiedVoter(null);
            setEmail('');
            setToken('');
            setSelectedCandidates({});
          }}
          className="inline-flex items-center space-x-2 bg-[#1A3A5C] hover:bg-sky-900 text-white border border-sky-400/40 px-6 py-3 rounded-2xl font-bold shadow-xl transition-all text-sm"
        >
          <span>Return to Voting Main Page</span>
        </button>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto py-8 px-4 sm:px-6">
      {/* Banner */}
      <div className="text-center mb-8">
        <div className="inline-flex items-center space-x-2 bg-sky-500/10 text-sky-300 border border-sky-500/20 px-4 py-1.5 rounded-full text-xs font-semibold mb-3">
          <KeyRound className="w-4 h-4 text-sky-400" />
          <span>Official Ballot Verification & Voting</span>
        </div>
        <h1 className="text-3xl font-extrabold text-white tracking-tight">
          BUI Beta Set Alumni Election Ballot
        </h1>
        <p className="mt-2 text-slate-300 text-sm max-w-xl mx-auto">
          Enter your registered email address and 8-character voting token to authenticate and unlock your ballot paper.
        </p>
      </div>

      {!verifiedVoter ? (
        /* STEP 1: TOKEN VERIFICATION FORM */
        <div className="max-w-md mx-auto bg-white/5 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/10 p-6 sm:p-8 text-white">
          {errorMsg && (
            <div className="mb-6 p-4 bg-red-500/10 border-l-4 border-red-500 rounded-r-xl flex items-start space-x-3 text-xs text-red-200 border border-white/5">
              <ShieldX className="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
              <div>
                <strong className="block font-bold text-red-300">Verification Error</strong>
                <span>{errorMsg}</span>
              </div>
            </div>
          )}

          <form onSubmit={handleVerify} className="space-y-5">
            <div>
              <label className="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                Registered Email Address
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="e.g. blessing.o@example.com"
                className="w-full px-4 py-3 rounded-xl border border-white/15 bg-white/5 text-white placeholder-slate-400 text-sm font-medium focus:border-sky-400 focus:outline-none"
                required
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                Voting Token (8 Characters)
              </label>
              <input
                type="text"
                value={token}
                onChange={(e) => setToken(e.target.value.toUpperCase())}
                placeholder="e.g. M4P9X7WR"
                maxLength={8}
                className="w-full px-4 py-3 rounded-xl border border-white/20 bg-white/5 font-mono text-center tracking-widest text-lg font-bold text-sky-300 uppercase focus:border-sky-400 focus:outline-none"
                required
              />
            </div>

            <button
              type="submit"
              className="w-full inline-flex items-center justify-center space-x-2 bg-[#1A3A5C] hover:bg-sky-900 text-white border border-sky-400/40 py-3.5 px-4 rounded-2xl font-bold shadow-[0_0_20px_rgba(56,189,248,0.2)] transition-all text-sm"
            >
              <Lock className="w-4 h-4 text-sky-400" />
              <span>Verify & Unlock Official Ballot</span>
            </button>
          </form>

          {/* Quick Demo Helper */}
          <div className="mt-8 pt-6 border-t border-white/10 text-xs text-slate-400">
            <span className="font-semibold text-slate-300">Quick Test Credentials:</span>
            <div className="mt-2 space-y-1.5 font-mono text-[11px] bg-slate-950/60 p-3 rounded-xl border border-white/10 text-slate-300">
              <div
                className="cursor-pointer hover:text-sky-300 flex justify-between transition-colors"
                onClick={() => {
                  setEmail('blessing.o@example.com');
                  setToken('M4P9X7WR');
                }}
              >
                <span>blessing.o@example.com</span>
                <span className="font-bold text-sky-400">M4P9X7WR</span>
              </div>
              <div
                className="cursor-pointer hover:text-sky-300 flex justify-between transition-colors"
                onClick={() => {
                  setEmail('kelechi.a@example.com');
                  setToken('K3T8V2NY');
                }}
              >
                <span>kelechi.a@example.com</span>
                <span className="font-bold text-sky-400">K3T8V2NY</span>
              </div>
            </div>
          </div>
        </div>
      ) : (
        /* STEP 2: OFFICIAL BALLOT PAPER */
        <form onSubmit={handleSubmitBallot} className="space-y-8">
          {/* Verified Header */}
          <div className="bg-[#1A3A5C]/80 border border-sky-400/40 text-white p-6 rounded-3xl shadow-2xl backdrop-blur-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <span className="text-xs uppercase tracking-widest text-sky-300 font-bold">Authenticated Voter</span>
              <h2 className="text-xl font-bold">{verifiedVoter.fullname || verifiedVoter.email}</h2>
              <p className="text-xs text-slate-300 mt-0.5">Token: <span className="font-mono text-amber-300">{verifiedVoter.voting_token}</span></p>
            </div>
            <div className="bg-sky-500/20 text-sky-300 border border-sky-400/30 px-4 py-2 rounded-xl text-xs font-semibold flex items-center space-x-2 self-start md:self-auto">
              <Sparkles className="w-4 h-4 text-sky-300" />
              <span>1 Ballot Per Member Enforced</span>
            </div>
          </div>

          {/* Positions */}
          {positions
            .filter((p) => p.active)
            .sort((a, b) => a.display_order - b.display_order)
            .map((pos) => {
              const posCandidates = candidates.filter((c) => c.position_id === pos.id && c.active);

              return (
                <div key={pos.id} className="bg-white/5 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/10 overflow-hidden">
                  <div className="bg-white/10 border-b border-white/10 px-6 py-4 flex items-center justify-between">
                    <div>
                      <h3 className="text-lg font-bold text-white">Position: {pos.name}</h3>
                      <p className="text-xs text-slate-300">Select ONE candidate for {pos.name}</p>
                    </div>
                    {selectedCandidates[pos.id] && (
                      <span className="bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-xs px-3 py-1 rounded-full font-bold flex items-center space-x-1">
                        <Check className="w-3.5 h-3.5" />
                        <span>Selection Saved</span>
                      </span>
                    )}
                  </div>

                  <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    {posCandidates.map((cand) => {
                      const isSelected = selectedCandidates[pos.id] === cand.id;

                      return (
                        <div
                          key={cand.id}
                          onClick={() => handleSelectCandidate(pos.id, cand.id)}
                          className={`relative cursor-pointer rounded-2xl border-2 p-5 transition-all flex flex-col justify-between ${
                            isSelected
                              ? 'border-sky-400 bg-[#1A3A5C]/70 shadow-[0_0_25px_rgba(56,189,248,0.3)]'
                              : 'border-white/10 hover:border-white/25 bg-white/5 text-white'
                          }`}
                        >
                          <div>
                            {/* Candidate Header with Sliding Photo */}
                            <div className="flex flex-col sm:flex-row items-center sm:items-start space-y-3 sm:space-y-0 sm:space-x-4 mb-4">
                              <SlidingCandidatePhoto
                                universityPhoto={cand.university_photo}
                                recentPhoto={cand.recent_photo}
                                candidateName={cand.fullname}
                                positionName={pos.name}
                                size="md"
                              />

                              <div className="flex-1 min-w-0 text-center sm:text-left">
                                <h4 className="font-extrabold text-white text-base leading-snug">{cand.fullname}</h4>
                                {cand.preferred_name && (
                                  <p className="text-[11px] text-sky-300 font-semibold italic">"{cand.preferred_name}"</p>
                                )}
                                {cand.dept_grad_year && (
                                  <p className="text-xs text-amber-300/90 font-medium mt-0.5">{cand.dept_grad_year}</p>
                                )}
                                {cand.occupation && (
                                  <p className="text-xs text-slate-300 mt-0.5 line-clamp-1">{cand.occupation}</p>
                                )}

                                {/* Read More Popup Button */}
                                <button
                                  type="button"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    setDetailCandidate({ candidate: cand, position: pos });
                                  }}
                                  className="mt-2.5 inline-flex items-center space-x-1.5 text-xs bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 px-3 py-1.5 rounded-xl border border-sky-400/40 font-bold transition-all shadow-sm"
                                >
                                  <Info className="w-3.5 h-3.5 text-sky-400" />
                                  <span>Read More & Manifesto</span>
                                  <ExternalLink className="w-3 h-3 text-sky-300" />
                                </button>
                              </div>
                            </div>

                            {/* Manifesto Brief */}
                            <div className="bg-slate-950/60 rounded-xl p-3 text-xs text-slate-300 border border-white/10 mb-4">
                              <span className="font-bold text-slate-200 block mb-1">Executive Manifesto Snippet:</span>
                              <p className="italic text-slate-300 line-clamp-3">"{cand.manifesto}"</p>
                            </div>
                          </div>

                          {/* Select Action Button */}
                          <button
                            type="button"
                            onClick={(e) => {
                              e.stopPropagation();
                              handleSelectCandidate(pos.id, cand.id);
                            }}
                            className={`w-full py-3 px-4 rounded-xl font-extrabold text-xs flex items-center justify-center space-x-2 transition-all ${
                              isSelected
                                ? 'bg-sky-400 text-slate-950 shadow-lg'
                                : 'bg-white/10 text-white hover:bg-white/20 border border-white/10'
                            }`}
                          >
                            {isSelected ? (
                              <>
                                <CheckCircle2 className="w-4 h-4 text-slate-950" />
                                <span>Selected for {pos.name}</span>
                              </>
                            ) : (
                              <span>Select {cand.fullname.split(' ')[0]}</span>
                            )}
                          </button>
                        </div>
                      );
                    })}
                  </div>
                </div>
              );
            })}

          {/* Submit Ballot Button */}
          <div className="bg-white/5 backdrop-blur-xl p-6 rounded-3xl shadow-2xl border border-white/10 text-center">
            <button
              type="submit"
              disabled={isSubmitting}
              className="w-full sm:w-auto inline-flex items-center justify-center space-x-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 px-10 py-4 rounded-2xl font-extrabold text-base shadow-xl transition-all disabled:opacity-50"
            >
              <Award className="w-5 h-5" />
              <span>{isSubmitting ? 'Recording Vote...' : 'Submit Official Ballot'}</span>
            </button>
            <p className="text-xs text-slate-400 mt-2">
              By clicking Submit, your vote will be atomically written to MySQL and your token marked used.
            </p>
          </div>
        </form>
      )}

      {/* Candidate Read More Detail Modal */}
      {detailCandidate && (
        <CandidateDetailModal
          candidate={detailCandidate.candidate}
          position={detailCandidate.position}
          onClose={() => setDetailCandidate(null)}
          onSelectCandidate={(candId) => handleSelectCandidate(detailCandidate.position.id, candId)}
          isSelected={selectedCandidates[detailCandidate.position.id] === detailCandidate.candidate.id}
        />
      )}

      {/* Designed Popup Modal for Warnings/Errors */}
      <DesignedPopup data={popup} onClose={() => setPopup(null)} />
    </div>
  );
};
