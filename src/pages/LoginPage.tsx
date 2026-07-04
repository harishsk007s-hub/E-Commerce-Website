import React, { useState, useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Mail, Lock, User, Phone, ArrowRight, CheckCircle, ArrowLeft } from 'lucide-react';
import { useUserStore } from '../store/useUserStore';
import { login, sendMagicLink, createAccount, forgotPassword } from '../api/auth';

const LoginPage: React.FC = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');
  const setUser = useUserStore((state) => state.setUser);
  
  const [activeTab, setActiveTab] = useState<'login' | 'signup' | 'complete' | 'forgot'>('login');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Login Form
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');

  // Signup/Forgot Form
  const [email, setEmail] = useState('');

  // Complete Profile Form
  const [profileData, setProfileData] = useState({
    name: '',
    phone: '',
    regPassword: '',
    confirmPassword: ''
  });

  useEffect(() => {
    if (token) {
      setActiveTab('complete');
    }
  }, [token]);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const data = await login(username, password);
      if (data.status === 'success') {
        await setUser(data.user, data.token);
        const redirect = searchParams.get('redirect') || '/';
        navigate(redirect);
      } else {
        setError(data.error || 'Invalid credentials');
      }
    } catch (err: any) {
      setError(err.response?.data?.error || 'Login failed. Please check your credentials.');
    } finally {
      setLoading(false);
    }
  };

  const handleSendMagicLink = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);
    try {
      const data = await sendMagicLink(email);
      if (data.status === 'success') {
        if (data.token && data.user) {
          await setUser(data.user, data.token);
          const redirect = searchParams.get('redirect') || '/';
          navigate(redirect);
        } else {
          setSuccess('Check your email! We sent you a link to create your account.');
          setEmail('');
        }
      } else {
        setError(data.error || 'Failed to send magic link');
      }
    } catch (err: any) {
      setError(err.response?.data?.error || 'Failed to send magic link. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleForgotPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);
    try {
      const data = await forgotPassword(email);
      if (data.status === 'success') {
        setSuccess(data.message);
        setEmail('');
      } else {
        setError(data.error || 'Failed to send reset link');
      }
    } catch (err: any) {
      setError(err.response?.data?.error || 'An error occurred. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleCreateAccount = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (profileData.regPassword !== profileData.confirmPassword) {
      setError('Passwords do not match');
      return;
    }

    setLoading(true);
    try {
      const data = await createAccount({
        token: token!,
        name: profileData.name,
        phone: profileData.phone,
        password: profileData.regPassword
      });

      if (data.status === 'success') {
        await setUser(data.user, data.token);
        const redirect = searchParams.get('redirect') || '/';
        navigate(redirect);
      } else {
        setError(data.error || 'Failed to create account');
      }
    } catch (err: any) {
      setError(err.response?.data?.error || 'Failed to create account. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleProfileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setProfileData({ ...profileData, [e.target.name]: e.target.value });
  };

  return (
    <div className="min-h-[70vh] flex items-center justify-center p-4 sm:p-6 bg-gray-50">
      <div className="w-full max-w-md bg-white rounded-[32px] sm:rounded-[40px] shadow-2xl p-6 sm:p-10 relative overflow-hidden">
        {/* Background Accent */}
        <div className="absolute top-0 right-0 w-32 h-32 bg-[#FFC222]/10 rounded-full -mr-16 -mt-16"></div>
        
        <div className="mb-10 text-center relative z-10">
          <h2 className="text-4xl font-black text-[#1E1D23] mb-2 uppercase tracking-tight">
            {activeTab === 'login' ? 'Login' : activeTab === 'signup' ? 'Sign Up' : activeTab === 'forgot' ? 'Reset Password' : 'Complete Profile'}
          </h2>
          <p className="text-gray-400 font-bold uppercase text-[10px] tracking-widest">
            {activeTab === 'login' ? 'Welcome back to Goappalam' : activeTab === 'signup' ? 'Start your journey with us' : activeTab === 'forgot' ? 'We will send you a reset link' : 'Just a few more details'}
          </p>
        </div>

        {/* Tab Switcher - Only show if not completing profile or forgot password */}
        {activeTab !== 'complete' && activeTab !== 'forgot' && (
          <div className="flex bg-gray-100 p-1.5 rounded-2xl mb-8 relative z-10">
            <button 
              onClick={() => { setActiveTab('login'); setError(''); setSuccess(''); }}
              className={`flex-1 py-3 text-xs font-black uppercase tracking-widest rounded-xl transition-all ${activeTab === 'login' ? 'bg-white text-[#1E1D23] shadow-md' : 'text-gray-400 hover:text-gray-600'}`}
            >
              Login
            </button>
            <button 
              onClick={() => { setActiveTab('signup'); setError(''); setSuccess(''); }}
              className={`flex-1 py-3 text-xs font-black uppercase tracking-widest rounded-xl transition-all ${activeTab === 'signup' ? 'bg-white text-[#1E1D23] shadow-md' : 'text-gray-400 hover:text-gray-600'}`}
            >
              Sign Up
            </button>
          </div>
        )}

        {error && (
          <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-bold rounded-r-xl">
            {error}
          </div>
        )}

        {success && (
          <div className="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm font-bold rounded-r-xl flex items-center gap-3">
            <CheckCircle className="w-5 h-5 flex-shrink-0" />
            {success}
          </div>
        )}

        {activeTab === 'login' && (
          <form onSubmit={handleLogin} className="space-y-6 relative z-10">
            <div>
              <label htmlFor="login-username" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Username or Email</label>
              <div className="relative">
                <User className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                <input 
                  id="login-username"
                  name="username"
                  type="text" 
                  placeholder="Your username"
                  className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                  required
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                />
              </div>
            </div>

            <div>
              <label htmlFor="login-password" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Password</label>
              <div className="relative">
                <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                <input 
                  id="login-password"
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
              {loading ? 'Authenticating...' : 'Sign In'}
              <ArrowRight className="w-4 h-4" />
            </button>

            <div className="text-center">
              <button 
                type="button"
                onClick={() => { setActiveTab('forgot'); setError(''); setSuccess(''); }}
                className="text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-[#FFC222] transition-colors"
              >
                Forgot your password?
              </button>
            </div>
          </form>
        )}

        {activeTab === 'forgot' && (
          <form onSubmit={handleForgotPassword} className="space-y-6 relative z-10">
            <div>
              <label htmlFor="forgot-email" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Email address</label>
              <div className="relative">
                <Mail className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                <input 
                  id="forgot-email"
                  name="email"
                  type="email" 
                  placeholder="john@example.com"
                  className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                />
              </div>
            </div>

            <button 
              type="submit" 
              disabled={loading}
              className={`w-full bg-[#1E1D23] hover:bg-black text-white py-6 rounded-2xl font-black transition-all shadow-xl uppercase tracking-widest text-xs flex items-center justify-center gap-3 ${loading ? 'opacity-50 cursor-not-allowed' : ''}`}
            >
              {loading ? 'Sending link...' : 'Send Reset Link'}
              <ArrowRight className="w-4 h-4" />
            </button>

            <button 
              type="button"
              onClick={() => { setActiveTab('login'); setError(''); setSuccess(''); }}
              className="w-full text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-colors flex items-center justify-center gap-2"
            >
              <ArrowLeft className="w-3 h-3" /> Back to Login
            </button>
          </form>
        )}

        {activeTab === 'signup' && (
          <form onSubmit={handleSendMagicLink} className="space-y-6 relative z-10">
            <div>
              <label htmlFor="signup-email" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Email address</label>
              <div className="relative">
                <Mail className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                <input 
                  id="signup-email"
                  name="email"
                  type="email" 
                  placeholder="john@example.com"
                  className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                />
              </div>
            </div>

            <button 
              type="submit" 
              disabled={loading}
              className={`w-full bg-[#1E1D23] hover:bg-black text-white py-6 rounded-2xl font-black transition-all shadow-xl uppercase tracking-widest text-xs flex items-center justify-center gap-3 ${loading ? 'opacity-50 cursor-not-allowed' : ''}`}
            >
              {loading ? 'Sending link...' : 'Create Account'}
              <ArrowRight className="w-4 h-4" />
            </button>
          </form>
        )}

        {activeTab === 'complete' && (
          <form onSubmit={handleCreateAccount} className="space-y-6 relative z-10">
            <div>
              <label htmlFor="profile-name" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Full Name</label>
              <div className="relative">
                <User className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                <input 
                  id="profile-name"
                  type="text" 
                  name="name"
                  placeholder="John Doe"
                  className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                  required
                  value={profileData.name}
                  onChange={handleProfileChange}
                />
              </div>
            </div>

            <div>
              <label htmlFor="profile-phone" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Phone Number</label>
              <div className="relative">
                <Phone className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                <input 
                  id="profile-phone"
                  type="tel" 
                  name="phone"
                  placeholder="9876543210"
                  className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                  required
                  value={profileData.phone}
                  onChange={handleProfileChange}
                />
              </div>
            </div>

            <div>
              <label htmlFor="profile-password" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Password</label>
              <div className="relative">
                <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                <input 
                  id="profile-password"
                  type="password" 
                  name="regPassword"
                  placeholder="••••••••"
                  className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                  required
                  value={profileData.regPassword}
                  onChange={handleProfileChange}
                />
              </div>
            </div>

            <div>
              <label htmlFor="profile-confirm-password" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Confirm Password</label>
              <div className="relative">
                <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                <input 
                  id="profile-confirm-password"
                  type="password" 
                  name="confirmPassword"
                  placeholder="••••••••"
                  className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                  required
                  value={profileData.confirmPassword}
                  onChange={handleProfileChange}
                />
              </div>
            </div>

            <button 
              type="submit" 
              disabled={loading}
              className={`w-full bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white py-6 rounded-2xl font-black transition-all shadow-xl shadow-[#FFC222]/30 uppercase tracking-widest text-xs flex items-center justify-center gap-3 ${loading ? 'opacity-50 cursor-not-allowed' : ''}`}
            >
              {loading ? 'Creating Account...' : 'Finish Registration'}
              <ArrowRight className="w-4 h-4" />
            </button>
            
            <button 
              type="button"
              onClick={() => { setActiveTab('login'); navigate('/login', { replace: true }); }}
              className="w-full text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-colors"
            >
              Cancel and Return to Login
            </button>
          </form>
        )}
      </div>
    </div>
  );
};

export default LoginPage;
