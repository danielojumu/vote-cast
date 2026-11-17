import React, { useState, useEffect } from 'react';
import { Sparkles, Calendar, GraduationCap, UserCheck, Play, Pause, ChevronLeft, ChevronRight } from 'lucide-react';
import { PositionAvatar } from './PositionAvatar';

interface SlidingCandidatePhotoProps {
  universityPhoto?: string;
  recentPhoto?: string;
  candidateName: string;
  positionName?: string;
  autoSlideInterval?: number; // ms, default 3500
  size?: 'sm' | 'md' | 'lg';
}

export const SlidingCandidatePhoto: React.FC<SlidingCandidatePhotoProps> = ({
  universityPhoto,
  recentPhoto,
  candidateName,
  positionName = '',
  autoSlideInterval = 3500,
  size = 'md',
}) => {
  // 0 = university photo, 1 = recent photo
  const [activeTab, setActiveTab] = useState<0 | 1>(0);
  const [isPaused, setIsPaused] = useState(false);
  const [hasImageError, setHasImageError] = useState<{ 0: boolean; 1: boolean }>({ 0: false, 1: false });

  useEffect(() => {
    if (isPaused) return;

    const timer = setInterval(() => {
      setActiveTab((prev) => (prev === 0 ? 1 : 0));
    }, autoSlideInterval);

    return () => clearInterval(timer);
  }, [isPaused, autoSlideInterval]);

  const toggleTab = (tab: 0 | 1) => {
    setActiveTab(tab);
  };

  const handleNext = (e: React.MouseEvent) => {
    e.stopPropagation();
    setActiveTab((prev) => (prev === 0 ? 1 : 0));
  };

  const currentPhoto = activeTab === 0 ? universityPhoto : recentPhoto;
  const isError = hasImageError[activeTab] || !currentPhoto;

  const containerSizes = {
    sm: 'w-24 h-24 rounded-xl',
    md: 'w-36 h-36 rounded-2xl',
    lg: 'w-48 h-48 sm:w-56 sm:h-56 rounded-3xl',
  }[size];

  return (
    <div
      className="relative flex flex-col items-center group shrink-0"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
    >
      {/* Sliding Image Container */}
      <div className={`relative ${containerSizes} overflow-hidden border-2 shadow-xl bg-slate-950 transition-all ${
        activeTab === 0 ? 'border-amber-400/80 shadow-amber-500/10' : 'border-sky-400/80 shadow-sky-500/10'
      }`}>
        {!isError ? (
          <img
            src={currentPhoto}
            alt={`${candidateName} - ${activeTab === 0 ? 'University Days' : 'Present Day'}`}
            onError={() => setHasImageError((prev) => ({ ...prev, [activeTab]: true }))}
            className="w-full h-full object-cover transition-opacity duration-500 ease-in-out"
          />
        ) : (
          <PositionAvatar
            positionName={positionName}
            candidateName={candidateName}
            size={size === 'lg' ? 'xl' : size === 'md' ? 'lg' : 'sm'}
            variant={activeTab === 0 ? 'university' : 'recent'}
          />
        )}

        {/* Dynamic Badge Overlay */}
        <div className="absolute top-2 left-2 z-10 flex items-center space-x-1">
          <span
            className={`text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-full border backdrop-blur-md shadow-md flex items-center space-x-1 ${
              activeTab === 0
                ? 'bg-amber-950/90 text-amber-300 border-amber-400/50'
                : 'bg-sky-950/90 text-sky-300 border-sky-400/50'
            }`}
          >
            {activeTab === 0 ? (
              <>
                <GraduationCap className="w-3 h-3 text-amber-300 shrink-0" />
                <span>Uni Days (~20 Yrs)</span>
              </>
            ) : (
              <>
                <UserCheck className="w-3 h-3 text-sky-300 shrink-0" />
                <span>Present Day</span>
              </>
            )}
          </span>
        </div>

        {/* Auto-Slide Play/Pause indicator on hover */}
        <button
          onClick={(e) => {
            e.stopPropagation();
            setIsPaused((prev) => !prev);
          }}
          className="absolute top-2 right-2 z-10 p-1 rounded-full bg-slate-950/70 border border-white/20 text-white/80 hover:text-white opacity-0 group-hover:opacity-100 transition-opacity"
          title={isPaused ? 'Resume Auto Slide' : 'Pause Auto Slide'}
        >
          {isPaused ? <Play className="w-3 h-3" /> : <Pause className="w-3 h-3" />}
        </button>

        {/* Left/Right Slide Arrows */}
        <button
          onClick={handleNext}
          className="absolute left-1 top-1/2 -translate-y-1/2 z-10 p-1 bg-slate-950/80 rounded-full border border-white/20 text-white opacity-0 group-hover:opacity-100 transition-opacity hover:bg-slate-900"
        >
          <ChevronLeft className="w-3.5 h-3.5" />
        </button>
        <button
          onClick={handleNext}
          className="absolute right-1 top-1/2 -translate-y-1/2 z-10 p-1 bg-slate-950/80 rounded-full border border-white/20 text-white opacity-0 group-hover:opacity-100 transition-opacity hover:bg-slate-900"
        >
          <ChevronRight className="w-3.5 h-3.5" />
        </button>
      </div>

      {/* Slide Dots / Toggle Bar */}
      <div className="flex items-center space-x-1.5 mt-2 bg-slate-950/80 px-2.5 py-1 rounded-full border border-white/10 text-[10px]">
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            toggleTab(0);
          }}
          className={`px-2 py-0.5 rounded-md font-bold transition-all ${
            activeTab === 0
              ? 'bg-amber-400 text-slate-950 font-black shadow'
              : 'text-slate-400 hover:text-slate-200'
          }`}
        >
          Uni Days
        </button>
        <span className="text-slate-600">|</span>
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            toggleTab(1);
          }}
          className={`px-2 py-0.5 rounded-md font-bold transition-all ${
            activeTab === 1
              ? 'bg-sky-400 text-slate-950 font-black shadow'
              : 'text-slate-400 hover:text-slate-200'
          }`}
        >
          Present
        </button>
      </div>
    </div>
  );
};
