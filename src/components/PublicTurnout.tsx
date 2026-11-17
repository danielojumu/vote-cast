import React from 'react';
import { BarChart3, Users, Vote, CheckCircle2, Trophy, Clock, Award } from 'lucide-react';
import { Candidate, Position, Voter, VoteRecord } from '../types';

interface PublicTurnoutProps {
  positions: Position[];
  candidates: Candidate[];
  voters: Voter[];
  votes: VoteRecord[];
}

export const PublicTurnout: React.FC<PublicTurnoutProps> = ({
  positions,
  candidates,
  voters,
  votes,
}) => {
  const totalTokens = voters.length;
  const totalVotesCast = voters.filter((v) => v.has_voted).length;
  const turnoutPercent = totalTokens > 0 ? ((totalVotesCast / totalTokens) * 100).toFixed(1) : '0.0';

  return (
    <div className="max-w-6xl mx-auto py-8 px-4 sm:px-6 space-y-8">
      {/* Banner */}
      <div className="text-center max-w-2xl mx-auto">
        <div className="inline-flex items-center space-x-2 bg-sky-500/10 text-sky-300 border border-sky-500/20 px-4 py-1.5 rounded-full text-xs font-semibold mb-3">
          <BarChart3 className="w-4 h-4 text-sky-400" />
          <span>Public Election Dashboard</span>
        </div>
        <h1 className="text-3xl font-extrabold text-white tracking-tight">
          BUI Beta Set Live Turnout & Results
        </h1>
        <p className="mt-2 text-slate-300 text-sm">
          Real-time audited voting statistics and official standings for all alumni positions.
        </p>
      </div>

      {/* KPI Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white/5 backdrop-blur-xl p-6 rounded-3xl border border-white/10 shadow-2xl flex items-center space-x-4 text-white">
          <div className="w-12 h-12 bg-sky-500/20 text-sky-300 border border-sky-400/30 rounded-2xl flex items-center justify-center shrink-0">
            <Users className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-bold text-slate-400 uppercase tracking-wider">Registered Voters</div>
            <div className="text-2xl font-extrabold text-white mt-0.5">{totalTokens}</div>
          </div>
        </div>

        <div className="bg-white/5 backdrop-blur-xl p-6 rounded-3xl border border-white/10 shadow-2xl flex items-center space-x-4 text-white">
          <div className="w-12 h-12 bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 rounded-2xl flex items-center justify-center shrink-0">
            <Vote className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Ballots Cast</div>
            <div className="text-2xl font-extrabold text-emerald-400 mt-0.5">{totalVotesCast}</div>
          </div>
        </div>

        <div className="bg-white/5 backdrop-blur-xl p-6 rounded-3xl border border-white/10 shadow-2xl flex items-center space-x-4 text-white">
          <div className="w-12 h-12 bg-amber-500/20 text-amber-300 border border-amber-400/30 rounded-2xl flex items-center justify-center shrink-0">
            <Trophy className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-bold text-slate-400 uppercase tracking-wider">Participation Rate</div>
            <div className="text-2xl font-extrabold text-amber-400 mt-0.5">{turnoutPercent}%</div>
          </div>
        </div>
      </div>

      {/* Standings by Position */}
      <div className="space-y-8">
        {positions
          .filter((p) => p.active)
          .sort((a, b) => a.display_order - b.display_order)
          .map((pos) => {
            const posCandidates = candidates.filter((c) => c.position_id === pos.id && c.active);
            const posVotesCount = votes.filter((v) => v.position_id === pos.id).length;

            return (
              <div key={pos.id} className="bg-white/5 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/10 overflow-hidden">
                <div className="bg-[#1A3A5C]/80 backdrop-blur-md text-white px-6 py-4 flex items-center justify-between border-b border-white/10">
                  <div className="flex items-center space-x-2">
                    <Award className="w-5 h-5 text-amber-400" />
                    <h3 className="text-lg font-bold">Position: {pos.name}</h3>
                  </div>
                  <span className="bg-amber-400 text-slate-950 font-extrabold text-xs px-3 py-1 rounded-full border border-amber-300/40">
                    {posVotesCount} Votes Recorded
                  </span>
                </div>

                <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                  {posCandidates.map((cand) => {
                    const cVotes = votes.filter((v) => v.candidate_id === cand.id).length;
                    const pct = posVotesCount > 0 ? ((cVotes / posVotesCount) * 100).toFixed(1) : '0.0';

                    return (
                      <div key={cand.id} className="border border-white/10 rounded-2xl p-5 bg-white/5 flex space-x-4 text-white">
                        <img
                          src={cand.recent_photo}
                          alt={cand.fullname}
                          className="w-16 h-16 rounded-2xl object-cover border-2 border-sky-400 shrink-0 shadow"
                        />
                        <div className="flex-1">
                          <h4 className="font-extrabold text-white text-base">{cand.fullname}</h4>
                          <p className="text-xs text-slate-300 line-clamp-1 mt-0.5">{cand.profile}</p>

                          <div className="flex justify-between items-center text-xs font-bold text-slate-300 mt-3 mb-1">
                            <span>{cVotes} Votes</span>
                            <span className="text-sky-300">{pct}%</span>
                          </div>

                          <div className="w-full bg-slate-950/80 rounded-full h-3 border border-white/10 overflow-hidden">
                            <div
                              className="bg-gradient-to-r from-[#1A3A5C] to-sky-400 h-3 rounded-full transition-all duration-700"
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
  );
};
