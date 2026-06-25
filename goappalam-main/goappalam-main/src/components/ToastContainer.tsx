import React from 'react';
import { useToastStore } from '../store/useToastStore';
import { CheckCircle2, XCircle, X } from 'lucide-react';

const ToastContainer: React.FC = () => {
  const { toasts, removeToast } = useToastStore();

  return (
    <div className="fixed top-4 sm:top-auto sm:bottom-8 left-1/2 -translate-x-1/2 sm:left-8 sm:translate-x-0 z-[9999] flex flex-col gap-4 pointer-events-none w-full max-w-[calc(100vw-32px)] sm:max-w-md">
      {toasts.map((toast) => (
        <div 
          key={toast.id}
          className="pointer-events-auto bg-[#1E1D23] text-white px-5 py-4 rounded-2xl shadow-2xl flex items-center gap-4 animate-in slide-in-from-top sm:slide-in-from-left duration-500 border border-white/10"
        >
          {toast.type === 'success' ? (
            <CheckCircle2 className="w-6 h-6 text-[#FFC222]" />
          ) : (
            <XCircle className="w-6 h-6 text-red-500" />
          )}
          <div className="flex-grow">
            <p className="text-xs font-black uppercase tracking-widest text-[#FFC222] mb-0.5">{toast.type}</p>
            <p className="text-sm font-bold">{toast.message}</p>
          </div>
          <button 
            onClick={() => removeToast(toast.id)}
            className="text-gray-500 hover:text-white transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      ))}
    </div>
  );
};

export default ToastContainer;
