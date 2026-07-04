import React, { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Lock, ArrowRight, CheckCircle, ArrowLeft } from 'lucide-react';
import { resetPassword } from '../api/auth';

const ResetPassword: React.FC = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');
  
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (newPassword !== confirmPassword) {
      setError('Passwords do not match');
      return;
    }

    if (!token) {
      setError('Invalid or missing reset token');
      return;
    }

    setLoading(true);
    try {
      const data = await resetPassword({
        token,
        new_password: newPassword
      });

      if (data.status === 'success') {
        setSuccess(data.message);
        setTimeout(() => {
          navigate('/login');
        }, 3000);
      } else {
        setError(data.error || 'Failed to reset password');
      }
    } catch (err: any) {
      setError(err.response?.data?.error || 'An error occurred. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-[70vh] flex items-center justify-center p-6 bg-gray-50">
      <div className="w-full max-w-md bg-white rounded-[40px] shadow-2xl p-10 relative overflow-hidden">
        <div className="absolute top-0 right-0 w-32 h-32 bg-[#FFC222]/10 rounded-full -mr-16 -mt-16"></div>
        
        <div className="mb-10 text-center relative z-10">
          <h2 className="text-4xl font-black text-[#1E1D23] mb-2 uppercase tracking-tight">
            New Password
          </h2>
          <p className="text-gray-400 font-bold uppercase text-[10px] tracking-widest">
            Set your new account password
          </p>
        </div>

        {error && (
          <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-bold rounded-r-xl">
            {error}
          </div>
        )}

        {success && (
          <div className="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm font-bold rounded-r-xl flex items-center gap-3">
            <CheckCircle className="w-5 h-5 flex-shrink-0" />
            <div>
              {success}
              <p className="text-xs mt-1 font-medium">Redirecting to login...</p>
            </div>
          </div>
        )}

        {!success && (
          <form onSubmit={handleSubmit} className="space-y-6 relative z-10">
            <div>
              <label htmlFor="new-password" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">New Password</label>
              <div className="relative">
                <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                <input 
                  id="new-password"
                  type="password" 
                  placeholder="••••••••"
                  className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                  required
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                />
              </div>
            </div>

            <div>
              <label htmlFor="confirm-password" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Confirm Password</label>
              <div className="relative">
                <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                <input 
                  id="confirm-password"
                  type="password" 
                  placeholder="••••••••"
                  className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                  required
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                />
              </div>
            </div>

            <button 
              type="submit" 
              disabled={loading}
              className={`w-full bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white py-6 rounded-2xl font-black transition-all shadow-xl shadow-[#FFC222]/30 uppercase tracking-widest text-xs flex items-center justify-center gap-3 ${loading ? 'opacity-50 cursor-not-allowed' : ''}`}
            >
              {loading ? 'Updating...' : 'Set New Password'}
              <ArrowRight className="w-4 h-4" />
            </button>

            <button 
              type="button"
              onClick={() => navigate('/login')}
              className="w-full text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-colors flex items-center justify-center gap-2"
            >
              <ArrowLeft className="w-3 h-3" /> Back to Login
            </button>
          </form>
        )}
      </div>
    </div>
  );
};

export default ResetPassword;
