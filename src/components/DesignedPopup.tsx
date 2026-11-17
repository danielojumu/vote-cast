import React from 'react';
import { CheckCircle2, AlertOctagon, AlertTriangle, Info, X } from 'lucide-react';
import { PopupData } from '../types';

interface DesignedPopupProps {
  data: PopupData | null;
  onClose: () => void;
}

export const DesignedPopup: React.FC<DesignedPopupProps> = ({ data, onClose }) => {
  if (!data || !data.isOpen) return null;

  const {
    type,
    title,
    message,
    confirmText = 'OK',
    cancelText,
    onConfirm,
    onCancel,
  } = data;

  const handleConfirm = () => {
    if (onConfirm) onConfirm();
    onClose();
  };

  const handleCancel = () => {
    if (onCancel) onCancel();
    onClose();
  };

  const getStyle = () => {
    switch (type) {
      case 'success':
        return {
          icon: <CheckCircle2 className="w-8 h-8 text-emerald-400" />,
          bgIcon: 'bg-emerald-500/20 border-emerald-500/40 text-emerald-400',
          badge: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
          badgeText: 'SUCCESS',
          buttonBg: 'bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black',
          border: 'border-emerald-500/40',
        };
      case 'error':
        return {
          icon: <AlertOctagon className="w-8 h-8 text-rose-400" />,
          bgIcon: 'bg-rose-500/20 border-rose-500/40 text-rose-400',
          badge: 'bg-rose-500/20 text-rose-300 border-rose-500/30',
          badgeText: 'ERROR',
          buttonBg: 'bg-rose-600 hover:bg-rose-700 text-white font-extrabold',
          border: 'border-rose-500/40',
        };
      case 'warning':
        return {
          icon: <AlertTriangle className="w-8 h-8 text-amber-400" />,
          bgIcon: 'bg-amber-500/20 border-amber-500/40 text-amber-400',
          badge: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
          badgeText: 'WARNING',
          buttonBg: 'bg-amber-500 hover:bg-amber-600 text-slate-950 font-black',
          border: 'border-amber-500/40',
        };
      case 'info':
      default:
        return {
          icon: <Info className="w-8 h-8 text-sky-400" />,
          bgIcon: 'bg-sky-500/20 border-sky-500/40 text-sky-400',
          badge: 'bg-sky-500/20 text-sky-300 border-sky-500/30',
          badgeText: 'NOTIFICATION',
          buttonBg: 'bg-[#1A3A5C] hover:bg-sky-900 text-white border border-sky-400/40 font-bold',
          border: 'border-sky-500/40',
        };
    }
  };

  const style = getStyle();

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in">
      <div
        className={`relative w-full max-w-md bg-slate-900 text-white rounded-3xl p-6 sm:p-8 border ${style.border} shadow-2xl space-y-5 animate-scale-up`}
      >
        {/* Close Button */}
        <button
          onClick={handleCancel}
          className="absolute top-4 right-4 p-2 text-slate-400 hover:text-white hover:bg-white/10 rounded-full transition-all"
        >
          <X className="w-5 h-5" />
        </button>

        {/* Top Header Icon & Badge */}
        <div className="flex items-center space-x-3">
          <div className={`p-3 rounded-2xl border ${style.bgIcon} shrink-0`}>
            {style.icon}
          </div>
          <div>
            <span className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider border mb-1 ${style.badge}`}>
              {style.badgeText}
            </span>
            <h3 className="text-xl font-extrabold text-white font-display leading-tight">{title}</h3>
          </div>
        </div>

        {/* Message Content */}
        <div className="text-sm text-slate-300 leading-relaxed font-sans bg-slate-950/50 p-4 rounded-2xl border border-white/5 max-h-60 overflow-y-auto whitespace-pre-line">
          {message}
        </div>

        {/* Buttons */}
        <div className="flex items-center justify-end space-x-3 pt-2">
          {cancelText && (
            <button
              type="button"
              onClick={handleCancel}
              className="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-xl border border-white/10 transition-all"
            >
              {cancelText}
            </button>
          )}

          <button
            type="button"
            onClick={handleConfirm}
            className={`px-6 py-2.5 text-xs rounded-xl shadow-lg transition-all ${style.buttonBg}`}
          >
            {confirmText}
          </button>
        </div>
      </div>
    </div>
  );
};
