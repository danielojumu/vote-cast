import React from 'react';
import { Award, Shield, FileText, Code, DollarSign, Megaphone, Heart, Smile, Trophy, Scale, Lock, Crown } from 'lucide-react';

interface PositionAvatarProps {
  positionName?: string;
  candidateName: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  variant?: 'university' | 'recent';
}

export const PositionAvatar: React.FC<PositionAvatarProps> = ({
  positionName = '',
  candidateName,
  size = 'md',
  variant = 'recent',
}) => {
  const normPos = positionName.toLowerCase();

  const getPositionIcon = () => {
    if (normPos.includes('chairman') || normPos.includes('president')) return <Crown className="w-1/2 h-1/2 text-amber-300" />;
    if (normPos.includes('secretary')) return <FileText className="w-1/2 h-1/2 text-sky-300" />;
    if (normPos.includes('treasurer') || normPos.includes('financial') || normPos.includes('auditor')) return <DollarSign className="w-1/2 h-1/2 text-emerald-300" />;
    if (normPos.includes('technical') || normPos.includes('tech')) return <Code className="w-1/2 h-1/2 text-cyan-300" />;
    if (normPos.includes('publicity')) return <Megaphone className="w-1/2 h-1/2 text-purple-300" />;
    if (normPos.includes('welfare')) return <Heart className="w-1/2 h-1/2 text-rose-300" />;
    if (normPos.includes('social')) return <Smile className="w-1/2 h-1/2 text-pink-300" />;
    if (normPos.includes('sports')) return <Trophy className="w-1/2 h-1/2 text-yellow-300" />;
    if (normPos.includes('marshal')) return <Shield className="w-1/2 h-1/2 text-orange-300" />;
    return <Award className="w-1/2 h-1/2 text-indigo-300" />;
  };

  const initials = candidateName
    ? candidateName
        .split(' ')
        .filter((n) => !['Dr.', 'Engr.', 'Prof.', 'Captain', 'Mr.', 'Mrs.', 'Ms.'].includes(n))
        .map((n) => n[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()
    : 'CA';

  const dimensions = {
    sm: 'w-12 h-12 text-xs',
    md: 'w-16 h-16 text-sm',
    lg: 'w-24 h-24 text-base',
    xl: 'w-32 h-32 text-xl',
  }[size];

  const gradient = variant === 'university'
    ? 'from-amber-900/80 via-slate-900 to-amber-950 border-amber-500/40'
    : 'from-[#1A3A5C] via-slate-900 to-sky-950 border-sky-400/40';

  return (
    <div
      className={`relative ${dimensions} rounded-2xl bg-gradient-to-br ${gradient} border-2 flex flex-col items-center justify-center shadow-lg overflow-hidden shrink-0 group`}
    >
      <div className="opacity-70 transform group-hover:scale-110 transition-transform">
        {getPositionIcon()}
      </div>
      <span className="font-extrabold text-white tracking-wider mt-0.5 drop-shadow">
        {initials}
      </span>
      <span className="absolute bottom-0 inset-x-0 bg-slate-950/80 text-[8px] font-extrabold uppercase text-center text-slate-300 py-0.5 border-t border-white/10 truncate px-1">
        {variant === 'university' ? 'Campus Days' : 'Present'}
      </span>
    </div>
  );
};
