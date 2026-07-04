import React, { useState } from 'react';
import { X, User, Lock, ArrowRight } from 'lucide-react';
import { useUserStore } from '../store/useUserStore';
import { login } from '../api/auth';
import { useNavigate } from 'react-router-dom';

interface LoginModalProps {
  isOpen: boolean;
  onClose: () => void;
}

const LoginModal: React.FC<LoginModalProps> = ({ isOpen, onClose }) => {
  const navigate = useNavigate();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const setUser = useUserStore((state) => state.setUser);

  // Reset state when modal opens
  React.useEffect(() => {
    if (isOpen) {
      setUsername('');
      setPassword('');
      setError('');
      setLoading(false);
    }
  }, [isOpen]);

  if (!isOpen) return null;

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const data = await login(username, password);
      if (data.status === 'success') {
        await setUser(data.user, data.token);
        onClose();
        // Check if we are on a page that needs redirect, or just stay
        if (window.location.pathname === '/cart') {
           navigate('/checkout');
        }
      } else {
        setError(data.error || 'Invalid credentials');
      }
    } catch (err: any) {
      setError(err.response?.data?.error || 'Login failed. Please check your credentials.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={onClose}></div>

      <div className="relative bg-white w-full max-w-md rounded-[32px] sm:rounded-[48px] p-6 sm:p-10 shadow-2xl animate-in fade-in zoom-in duration-300">
        <button onClick={onClose} className="absolute top-6 right-6 sm:top-8 sm:right-8 text-gray-400 hover:text-[#FFC222] transition-colors">
          <X className="w-5 h-5 sm:w-6 sm:h-6" />
        </button>

        <div className="mb-10 text-center">
          <h2 className="text-3xl font-black text-[#1E1D23] mb-2 uppercase tracking-tight">
            Welcome Back
          </h2>
          <p className="text-gray-400 font-bold uppercase text-[10px] tracking-widest">
            Login to your Goappalam account
          </p>
        </div>

        <form className="space-y-6" onSubmit={handleLogin}>
          {error && <div className="p-4 rounded-xl text-sm font-bold bg-red-50 text-red-600">{error}</div>}
          
          <div>
            <label htmlFor="modal-username" className="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Username or Email</label>
            <div className="relative">
              <User className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
              <input 
                id="modal-username"
                name="username"
                type="text" 
                placeholder="Username"
                className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                required
                value={username}
                onChange={(e) => setUsername(e.target.value)}
              />
            </div>
          </div>

          <div>
            <label htmlFor="modal-password" className="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Password</label>
            <div className="relative">
              <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
              <input 
                id="modal-password"
                name="password"
                type="password" 
                placeholder="••••••••"
                className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>
          </div>

          <button 
            type="submit"
            disabled={loading} 
            className={`w-full bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white py-6 rounded-2xl font-black transition-all shadow-xl shadow-[#FFC222]/30 uppercase tracking-widest text-xs flex items-center justify-center gap-3 ${loading ? 'opacity-50 cursor-not-allowed' : ''}`}
          >
            {loading ? 'Logging in...' : 'Sign In'}
            <ArrowRight className="w-4 h-4" />
          </button>

          <div className="text-center mt-6">
            <p className="text-gray-400 text-xs font-bold mb-4">Don't have an account?</p>
            <button 
              type="button" 
              onClick={() => { onClose(); navigate('/login'); }} 
              className="text-[#FFC222] font-black uppercase text-[10px] tracking-widest hover:underline"
            >
              Create Account
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default LoginModal;
