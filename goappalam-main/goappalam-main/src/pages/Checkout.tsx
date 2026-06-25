import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useCartStore } from '../store/useCartStore';
import { useUserStore } from '../store/useUserStore';
import { processCheckout, verifyPayment } from '../api/order';
import { login, sendMagicLink, createAccount, forgotPassword } from '../api/auth';
import { getAppSettings, PaymentMethod } from '../api/settings';
import { CheckCircle2, CreditCard, ArrowLeft, Loader2, Download, Mail, Lock, User, Phone, CheckCircle, ArrowRight } from 'lucide-react';

const INDIAN_STATES = [
  "Andaman and Nicobar Islands", "Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar",
  "Chandigarh", "Chhattisgarh", "Dadra and Nagar Haveli and Daman and Diu", "Delhi", "Goa",
  "Gujarat", "Haryana", "Himachal Pradesh", "Jammu and Kashmir", "Jharkhand", "Karnataka",
  "Kerala", "Ladakh", "Lakshadweep", "Madhya Pradesh", "Maharashtra", "Manipur", "Meghalaya",
  "Mizoram", "Nagaland", "Odisha", "Puducherry", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu",
  "Telangana", "Tripura", "Uttar Pradesh", "Uttarakhand", "West Bengal"
];

const Checkout: React.FC = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');
  const { items, totalPrice, clearCart, sessionId, appliedCoupon, applyCoupon, removeCoupon, discountAmount, finalTotal, shipping, tax, loadCart } = useCartStore();
  const { user, setUser, isLoggedIn, _hasHydrated } = useUserStore();

  const [couponCode, setCouponCode] = useState('');
  const [couponLoading, setCouponLoading] = useState(false);
  const [couponError, setCouponError] = useState('');

  const [activeTab, setActiveTab] = useState<'login' | 'signup' | 'complete' | 'forgot' | 'none'>(isLoggedIn ? 'none' : 'login');
  const [authLoading, setAuthLoading] = useState(false);
  const [authError, setAuthError] = useState('');
  const [authSuccess, setAuthSuccess] = useState('');

  // Auth Forms
  const [username, setUsername] = useState('');
  const [authPassword, setAuthPassword] = useState('');
  const [email, setEmail] = useState('');
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

  useEffect(() => {
    if (isLoggedIn) {
      setActiveTab('none');
    } else if (activeTab === 'none') {
      setActiveTab('login');
    }
  }, [isLoggedIn]);

  const [loading, setLoading] = useState(false);
  const [orderSuccess, setOrderSuccess] = useState(false);
  const [showPopup, setShowPopup] = useState(false);
  const [orderId, setOrderId] = useState<number | null>(null);
  const [orderTotal, setOrderTotal] = useState<number>(0);
  const [availablePaymentMethods, setAvailablePaymentMethods] = useState<PaymentMethod[]>([]);
  const [paymentMethod, setPaymentMethod] = useState<'razorpay'>('razorpay');

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const settings = await getAppSettings();
        if (settings && settings.payment_methods) {
          // Filter out Cash on Delivery (cod)
          const filteredMethods = settings.payment_methods.filter(m => m.code !== 'cod');
          setAvailablePaymentMethods(filteredMethods);

          // Set default to first available online method
          if (filteredMethods.length > 0) {
            setPaymentMethod(filteredMethods[0].code as any);
          } else {
            setPaymentMethod('razorpay');
          }
        }
      } catch (error) {
        console.error('Failed to fetch app settings:', error);
      }
    };
    fetchSettings();
  }, []);

  const [formData, setFormData] = useState({
    name: user?.name || '',
    phone: user?.phone || '',
    alternativePhone: '',
    address: '',
    city: 'Madurai',
    state: 'Tamil Nadu',
    pincode: '',
    country: 'India'
  });

  useEffect(() => {
    if (user) {
      setFormData(prev => {
        const newData = {
          ...prev,
          name: user.name || prev.name,
          phone: user.phone || prev.phone
        };

        // Pre-fill from saved addresses if available
        if (user.addresses) {
          const addr = user.addresses as any;
          if (addr.address) {
            newData.address = addr.address;
          } else if (addr.address1 || addr.address2 || addr.address3) {
            newData.address = [addr.address1, addr.address2, addr.address3].filter(Boolean).join(', ');
          }
          if (addr.city) newData.city = addr.city;
          if (addr.state) newData.state = addr.state;
          if (addr.pincode) newData.pincode = addr.pincode;
          if (addr.country) newData.country = addr.country;
        }

        return newData;
      });
    }
  }, [user]);

  useEffect(() => {
    if (items.length === 0 && !orderSuccess) {
      navigate('/shop');
    }
  }, [items, navigate, orderSuccess]);

  const [isStateDropdownOpen, setIsStateDropdownOpen] = useState(false);
  const stateDropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (stateDropdownRef.current && !stateDropdownRef.current.contains(event.target as Node)) {
        setIsStateDropdownOpen(false);
      }
    };

    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setIsStateDropdownOpen(false);
    };

    if (isStateDropdownOpen) {
      document.addEventListener('mousedown', handleClickOutside);
      document.addEventListener('keydown', handleEscape);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleEscape);
    };
  }, [isStateDropdownOpen]);

  const selectState = (val: string) => {
    setFormData(prev => ({ ...prev, state: val }));
    setIsStateDropdownOpen(false);
  };

  useEffect(() => {
    const timer = setTimeout(() => {
      loadCart({
        country: formData.country,
        state: formData.state,
        city: formData.city
      });
    }, 500);
    return () => clearTimeout(timer);
  }, [formData.state, formData.city, formData.country, loadCart]);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setAuthError('');
    setAuthLoading(true);
    try {
      const data = await login(username, authPassword);
      if (data.status === 'success') {
        await setUser(data.user, data.token);
      } else {
        setAuthError(data.error || 'Invalid credentials');
      }
    } catch (err: any) {
      setAuthError(err.response?.data?.error || 'Login failed. Please check your credentials.');
    } finally {
      setAuthLoading(false);
    }
  };

  const handleSendMagicLink = async (e: React.FormEvent) => {
    e.preventDefault();
    setAuthError('');
    setAuthSuccess('');
    setAuthLoading(true);
    try {
      const data = await sendMagicLink(email);
      if (data.status === 'success') {
        if (data.user && data.token) {
          await setUser(data.user, data.token);
          setAuthSuccess(data.is_new ? 'Welcome! Your account has been created.' : 'Welcome back!');
        } else {
          setAuthSuccess('Check your email! We sent you a link to create your account.');
        }
        setEmail('');
      } else {
        setAuthError(data.error || 'Failed to send magic link');
      }
    } catch (err: any) {
      setAuthError(err.response?.data?.error || 'Failed to send magic link. Please try again.');
    } finally {
      setAuthLoading(false);
    }
  };

  const handleForgotPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setAuthError('');
    setAuthSuccess('');
    setAuthLoading(true);
    try {
      const data = await forgotPassword(email);
      if (data.status === 'success') {
        setAuthSuccess(data.message);
        setEmail('');
      } else {
        setAuthError(data.error || 'Failed to send reset link');
      }
    } catch (err: any) {
      setAuthError(err.response?.data?.error || 'An error occurred. Please try again.');
    } finally {
      setAuthLoading(false);
    }
  };

  const handleCreateAccount = async (e: React.FormEvent) => {
    e.preventDefault();
    setAuthError('');

    if (profileData.regPassword !== profileData.confirmPassword) {
      setAuthError('Passwords do not match');
      return;
    }

    setAuthLoading(true);
    try {
      const data = await createAccount({
        token: token!,
        name: profileData.name,
        phone: profileData.phone,
        password: profileData.regPassword
      });

      if (data.status === 'success') {
        await setUser(data.user, data.token);
      } else {
        setAuthError(data.error || 'Failed to create account');
      }
    } catch (err: any) {
      setAuthError(err.response?.data?.error || 'Failed to create account. Please try again.');
    } finally {
      setAuthLoading(false);
    }
  };

  const handleProfileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setProfileData({ ...profileData, [e.target.name]: e.target.value });
  };

  const handleApplyCoupon = async () => {
    if (!couponCode) return;
    setCouponLoading(true);
    setCouponError('');
    const error = await applyCoupon(couponCode);
    if (error) {
      setCouponError(error);
    } else {
      setCouponCode('');
    }
    setCouponLoading(false);
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const [confirmedItems, setConfirmedItems] = useState<any[]>([]);

  const finishCheckout = (id: number, total: number) => {
    setOrderId(id);
    setOrderTotal(total);
    setConfirmedItems([...items]); // Save items before clearing cart
    setShowPopup(true);
    setTimeout(() => {
      setOrderSuccess(true);
      setShowPopup(false);
      clearCart();
    }, 3000);
  };

  const handlePayment = async () => {
    if (!formData.name || !formData.phone || !formData.address || !formData.pincode || !formData.state) {
      alert('Please fill in all required fields (Name, Phone, Full Address, Pincode, State)');
      return;
    }

    setLoading(true);

    try {
      // 1. Create Order First
      const checkoutData = {
        session_id: sessionId,
        user_id: user?.id,
        customer_id: user?.id,
        name: formData.name,
        phone: formData.phone,
        alternative_phone: formData.alternativePhone,
        email: user?.email,
        shipping_address: {
          country: formData.country,
          state: formData.state,
          city: formData.city,
          address: `${formData.address}${formData.pincode ? ', Pincode: ' + formData.pincode : ''}`,
          name: formData.name,
          phone: formData.phone,
          address1: formData.address,
          address2: '',
          address3: '',
          pincode: formData.pincode
        },
        payment_method: paymentMethod,
        coupon_code: appliedCoupon?.code,
        discount_amount: discountAmount()
      };

      const result = await processCheckout(checkoutData);

      if (result.status === 'success') {
        const newOrderId = result.order_id;

        if (paymentMethod === 'razorpay') {
          // 2. Open Razorpay Checkout
          const options = {
            key: result.razorpay_key_id,
            amount: Math.round(result.total * 100),
            currency: 'INR',
            name: 'Goappalam',
            description: `Order #${newOrderId}`,
            order_id: result.razorpay_order_id,
            handler: async (response: any) => {
              try {
                setLoading(true);
                await verifyPayment({
                  order_id: newOrderId,
                  session_id: sessionId,
                  razorpay_payment_id: response.razorpay_payment_id,
                  razorpay_order_id: response.razorpay_order_id,
                  razorpay_signature: response.razorpay_signature
                });
                finishCheckout(newOrderId, result.total);
              } catch (error) {
                console.error('Verification failed:', error);
                alert('Payment verification failed. Your order is placed but payment status is pending.');
                finishCheckout(newOrderId, result.total); // Still show success but maybe with warning
              } finally {
                setLoading(false);
              }
            },
            prefill: {
              name: formData.name,
              contact: formData.phone,
              email: user?.email || ''
            },
            theme: {
              color: '#FFC222'
            },
            modal: {
              ondismiss: function () {
                setLoading(false);
              }
            }
          };

          const rzp = new (window as any).Razorpay(options);
          rzp.open();
        } else {
          finishCheckout(newOrderId, result.total);
        }
      } else {
        alert(result.error || 'Failed to place order');
      }
    } catch (error: any) {
      console.error('Checkout error:', error);
      alert(error.response?.data?.error || 'An error occurred during checkout');
    } finally {
      // For Razorpay, we keep loading until handler completes or modal closes
      if (paymentMethod !== 'razorpay') {
        setLoading(false);
      }
    }
  };

  if (orderSuccess) {
    return (
      <div className="min-h-screen bg-white flex flex-col items-center justify-center p-6 sm:p-8 animate-in fade-in duration-700 text-center">
        <div className="bg-green-50 p-6 sm:p-10 rounded-full mb-6 sm:mb-10 scale-100 sm:scale-110">
          <CheckCircle2 className="w-16 h-16 sm:w-24 sm:h-24 text-green-500 animate-bounce" />
        </div>
        <h1 className="text-3xl sm:text-5xl font-black text-gray-900 mb-4 sm:mb-6 uppercase tracking-tighter">Order Confirmed!</h1>
        <p className="text-lg sm:text-xl text-gray-500 mb-8 sm:mb-10 font-medium">Your Order ID is <span className="text-gray-900 font-black">#{orderId}</span></p>

        {/* Order Items Summary */}
        <div className="w-full max-w-xl bg-white p-6 sm:p-10 rounded-[32px] sm:rounded-[40px] shadow-sm border border-gray-100 mb-10 text-left">
          <h2 className="text-xl font-black mb-6 uppercase tracking-tight border-b pb-4">Your Order</h2>
          <div className="space-y-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
            {confirmedItems.map((item, idx) => (
              <div key={idx} className="flex gap-4 items-center">
                <div className="w-14 h-14 bg-gray-50 rounded-xl overflow-hidden flex-shrink-0">
                  <img src={item.image} alt={item.name} className="w-full h-full object-cover" />
                </div>
                <div className="flex-grow min-w-0">
                  <p className="font-bold text-gray-900 text-xs sm:text-sm truncate">{item.name}</p>
                  <p className="text-[10px] font-black text-[#FFC222] uppercase tracking-widest">Qty: {item.quantity} {item.selectedWeight && `| ${item.selectedWeight}`}</p>
                </div>
                <p className="font-black text-xs sm:text-sm text-gray-900">₹{(parseFloat(String(item.price).replace(/[^\d.-]/g, '')) * item.quantity).toFixed(2)}</p>
              </div>
            ))}
          </div>
          <div className="mt-6 pt-6 border-t border-dashed flex justify-between items-center">
            <span className="text-sm font-black uppercase tracking-widest text-gray-400">Paid Amount</span>
            <span className="text-2xl font-black text-[#FFC222]">₹{orderTotal.toFixed(2)}</span>
          </div>
        </div>

        <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 mb-8 sm:mb-12 w-full max-w-md sm:max-w-none">
          <button
            onClick={() => navigate('/shop')}
            className="w-full sm:w-auto bg-gray-900 text-white font-black px-8 sm:px-12 py-4 sm:py-5 rounded-2xl hover:bg-black transition-all hover:scale-105 shadow-xl uppercase text-[10px] sm:text-xs tracking-widest"
          >
            Continue Shopping
          </button>
          <a
            href={`/backend/public/download-invoice.php?order_id=${orderId}`}
            target="_blank"
            className="w-full sm:w-auto bg-white border-2 border-gray-100 text-gray-900 font-black px-8 sm:px-12 py-4 sm:py-5 rounded-2xl hover:border-[#FFC222] transition-all hover:scale-105 shadow-sm flex items-center justify-center gap-2 uppercase text-[10px] sm:text-xs tracking-widest"
          >
            <Download className="w-4 h-4" />
            Download Invoice
          </a>
        </div>

        <p className="text-gray-400 text-xs italic">A confirmation email with your invoice has been sent.</p>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-10 sm:py-20 px-4 sm:px-8">
      <div className="max-w-5xl mx-auto">
        <button
          onClick={() => navigate('/shop')}
          className="flex items-center gap-2 text-gray-400 hover:text-gray-900 font-bold mb-8 sm:mb-10 transition-colors group"
        >
          <ArrowLeft className="w-4 h-4 sm:w-5 sm:h-5 group-hover:-translate-x-1 transition-transform" />
          Back to Shop
        </button>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 sm:gap-12">
          <div className="lg:col-span-2 space-y-6 sm:space-y-8">
            {/* Authentication Section */}
            {_hasHydrated && !isLoggedIn && activeTab !== 'none' && (
              <div className="bg-white p-6 sm:p-10 rounded-[32px] sm:rounded-[40px] shadow-sm border border-gray-100 mb-8">
                <div className="mb-10 text-center relative z-10">
                  <h2 className="text-3xl font-black text-[#1E1D23] mb-2 uppercase tracking-tight">
                    {activeTab === 'login' ? 'Login' : activeTab === 'signup' ? 'Sign Up' : activeTab === 'forgot' ? 'Reset Password' : 'Complete Profile'}
                  </h2>
                  <p className="text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                    {activeTab === 'login' ? 'Welcome back to Goappalam' : activeTab === 'signup' ? 'Start your journey with us' : activeTab === 'forgot' ? 'We will send you a reset link' : 'Just a few more details'}
                  </p>
                </div>

                {/* Tab Switcher */}
                {activeTab !== 'complete' && activeTab !== 'forgot' && (
                  <div className="flex bg-gray-100 p-1.5 rounded-2xl mb-8 relative z-10">
                    <button
                      onClick={() => { setActiveTab('login'); setAuthError(''); setAuthSuccess(''); }}
                      className={`flex-1 py-3 text-xs font-black uppercase tracking-widest rounded-xl transition-all ${activeTab === 'login' ? 'bg-white text-[#1E1D23] shadow-md' : 'text-gray-400 hover:text-gray-600'}`}
                    >
                      Login
                    </button>
                    <button
                      onClick={() => { setActiveTab('signup'); setAuthError(''); setAuthSuccess(''); }}
                      className={`flex-1 py-3 text-xs font-black uppercase tracking-widest rounded-xl transition-all ${activeTab === 'signup' ? 'bg-white text-[#1E1D23] shadow-md' : 'text-gray-400 hover:text-gray-600'}`}
                    >
                      Sign Up
                    </button>
                  </div>
                )}

                {authError && (
                  <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-bold rounded-r-xl">
                    {authError}
                  </div>
                )}

                {authSuccess && (
                  <div className="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm font-bold rounded-r-xl flex items-center gap-3">
                    <CheckCircle className="w-5 h-5 flex-shrink-0" />
                    {authSuccess}
                  </div>
                )}

                {activeTab === 'login' && (
                  <form onSubmit={handleLogin} className="space-y-6 relative z-10">
                    <div>
                      <label htmlFor="checkout-login-username" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Username or Email</label>
                      <div className="relative">
                        <User className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                        <input
                          id="checkout-login-username"
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
                      <label htmlFor="checkout-login-password" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Password</label>
                      <div className="relative">
                        <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                        <input
                          id="checkout-login-password"
                          type="password"
                          placeholder="••••••••"
                          className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white rounded-2xl py-4 pl-14 pr-6 outline-none transition-all font-medium"
                          required
                          value={authPassword}
                          onChange={(e) => setAuthPassword(e.target.value)}
                        />
                      </div>
                    </div>

                    <button
                      type="submit"
                      disabled={authLoading}
                      className={`w-full bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white py-6 rounded-2xl font-black transition-all shadow-xl shadow-[#FFC222]/30 uppercase tracking-widest text-xs flex items-center justify-center gap-3 ${authLoading ? 'opacity-50 cursor-not-allowed' : ''}`}
                    >
                      {authLoading ? 'Authenticating...' : 'Sign In'}
                      <ArrowRight className="w-4 h-4" />
                    </button>

                    <div className="text-center">
                      <button
                        type="button"
                        onClick={() => { setActiveTab('forgot'); setAuthError(''); setAuthSuccess(''); }}
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
                      <label htmlFor="checkout-forgot-email" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Email address</label>
                      <div className="relative">
                        <Mail className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                        <input
                          id="checkout-forgot-email"
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
                      disabled={authLoading}
                      className={`w-full bg-[#1E1D23] hover:bg-black text-white py-6 rounded-2xl font-black transition-all shadow-xl uppercase tracking-widest text-xs flex items-center justify-center gap-3 ${authLoading ? 'opacity-50 cursor-not-allowed' : ''}`}
                    >
                      {authLoading ? 'Sending link...' : 'Send Reset Link'}
                      <ArrowRight className="w-4 h-4" />
                    </button>

                    <button
                      type="button"
                      onClick={() => { setActiveTab('login'); setAuthError(''); setAuthSuccess(''); }}
                      className="w-full text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-colors flex items-center justify-center gap-2"
                    >
                      <ArrowLeft className="w-3 h-3" /> Back to Login
                    </button>
                  </form>
                )}

                {activeTab === 'signup' && (
                  <form onSubmit={handleSendMagicLink} className="space-y-6 relative z-10">
                    <div>
                      <label htmlFor="checkout-signup-email" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Email address</label>
                      <div className="relative">
                        <Mail className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                        <input
                          id="checkout-signup-email"
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
                      disabled={authLoading}
                      className={`w-full bg-[#1E1D23] hover:bg-black text-white py-6 rounded-2xl font-black transition-all shadow-xl uppercase tracking-widest text-xs flex items-center justify-center gap-3 ${authLoading ? 'opacity-50 cursor-not-allowed' : ''}`}
                    >
                      {authLoading ? 'Sending link...' : 'Create Account'}
                      <ArrowRight className="w-4 h-4" />
                    </button>
                  </form>
                )}

                {activeTab === 'complete' && (
                  <form onSubmit={handleCreateAccount} className="space-y-6 relative z-10">
                    <div>
                      <label htmlFor="checkout-profile-name" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Full Name</label>
                      <div className="relative">
                        <User className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                        <input
                          id="checkout-profile-name"
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
                      <label htmlFor="checkout-profile-phone" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Phone Number</label>
                      <div className="relative">
                        <Phone className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                        <input
                          id="checkout-profile-phone"
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
                      <label htmlFor="checkout-profile-password" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Password</label>
                      <div className="relative">
                        <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                        <input
                          id="checkout-profile-password"
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
                      <label htmlFor="checkout-profile-confirm-password" className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Confirm Password</label>
                      <div className="relative">
                        <Lock className="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300" />
                        <input
                          id="checkout-profile-confirm-password"
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
                      disabled={authLoading}
                      className={`w-full bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white py-6 rounded-2xl font-black transition-all shadow-xl shadow-[#FFC222]/30 uppercase tracking-widest text-xs flex items-center justify-center gap-3 ${authLoading ? 'opacity-50 cursor-not-allowed' : ''}`}
                    >
                      {authLoading ? 'Completing Profile...' : 'Complete Registration'}
                      <ArrowRight className="w-4 h-4" />
                    </button>
                  </form>
                )}
              </div>
            )}

            <div className="bg-white p-6 sm:p-10 rounded-[32px] sm:rounded-[40px] shadow-sm border border-gray-100 mb-8 sm:mb-12">
              <h2 className="text-2xl sm:text-3xl font-black mb-6 sm:mb-10 uppercase tracking-tight text-slate-700">Shipping Details</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                {/* Recipient's Name */}
                <div className="md:col-span-2">
                  <label htmlFor="checkout-name" className="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 sm:mb-3">Recipient's Name</label>
                  <input
                    id="checkout-name"
                    type="text"
                    name="name"
                    value={formData.name}
                    onChange={handleInputChange}
                    className="w-full bg-white border-2 border-slate-100 focus:border-[#FFC222] rounded-xl sm:rounded-2xl py-3 sm:py-4 px-4 sm:px-6 outline-none transition-all font-medium text-sm sm:text-base shadow-sm"
                    placeholder="Full Name"
                  />
                </div>

                {/* Recipient's Phone */}
                <div className="md:col-span-2">
                  <label htmlFor="checkout-phone" className="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 sm:mb-3">Recipient's Phone</label>
                  <div className="relative group">
                    <div className="absolute left-4 top-1/2 -translate-y-1/2 flex items-center gap-2 pr-4 border-r-2 border-slate-100 h-6">
                      <img 
                        src="https://flagcdn.com/w20/in.png" 
                        srcSet="https://flagcdn.com/w40/in.png 2x"
                        width="20" 
                        alt="India" 
                        className="rounded-sm"
                      />
                      <span className="text-xs font-black text-slate-700">(+91)</span>
                      <svg className="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                    <input
                      id="checkout-phone"
                      type="tel"
                      name="phone"
                      value={formData.phone}
                      onChange={handleInputChange}
                      className="w-full bg-white border-2 border-slate-100 focus:border-[#FFC222] rounded-xl sm:rounded-2xl py-3 sm:py-4 pl-32 pr-6 outline-none transition-all font-medium text-sm sm:text-base shadow-sm"
                      placeholder="Phone Number"
                    />
                  </div>
                </div>

                {/* PIN Code and State */}
                <div className="col-span-1">
                  <label htmlFor="checkout-pincode" className="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 sm:mb-3">PIN CODE</label>
                  <input
                    id="checkout-pincode"
                    type="text"
                    name="pincode"
                    value={formData.pincode}
                    onChange={handleInputChange}
                    className="w-full bg-white border-2 border-slate-100 focus:border-[#FFC222] rounded-xl sm:rounded-2xl py-3 sm:py-4 px-4 sm:px-6 outline-none transition-all font-medium text-sm sm:text-base shadow-sm"
                    placeholder="PIN CODE"
                  />
                </div>
                <div className="col-span-1">
                  <label htmlFor="checkout-state" className="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 sm:mb-3">STATE</label>
                  <div className="relative" ref={stateDropdownRef}>
                    <div 
                      onClick={() => setIsStateDropdownOpen(!isStateDropdownOpen)}
                      className={`w-full bg-white border-2 cursor-pointer rounded-xl sm:rounded-2xl py-4 pr-10 pl-6 outline-none transition-all font-medium text-sm sm:text-base shadow-sm flex items-center justify-between ${isStateDropdownOpen ? 'border-[#FFC222] ring-4 ring-[#FFC222]/10' : 'border-slate-100 hover:border-slate-200'}`}
                    >
                      <span className={formData.state ? 'text-slate-900' : 'text-slate-400'}>
                        {formData.state || 'Select State'}
                      </span>
                      <svg className={`w-4 h-4 text-slate-400 transition-transform duration-300 ${isStateDropdownOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </div>

                    {isStateDropdownOpen && (
                      <div className="absolute z-[100] left-0 right-0 mt-2 bg-white border border-slate-100 rounded-2xl shadow-2xl overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                        <div className="max-h-[300px] overflow-y-auto custom-scrollbar">
                          {INDIAN_STATES.map(st => (
                            <div
                              key={st}
                              onClick={() => selectState(st)}
                              className={`px-6 py-3.5 text-sm font-medium transition-all cursor-pointer hover:bg-slate-50 flex items-center justify-between group ${formData.state === st ? 'bg-[#FFC222]/5 text-[#FFC222]' : 'text-slate-600'}`}
                            >
                              <span>{st}</span>
                              {formData.state === st && <div className="w-1.5 h-1.5 rounded-full bg-[#FFC222]" />}
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </div>

                {/* City / Town / District */}
                <div className="md:col-span-2">
                  <label htmlFor="checkout-city" className="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 sm:mb-3">CITY / TOWN / DISTRICT</label>
                  <input
                    id="checkout-city"
                    type="text"
                    name="city"
                    value={formData.city}
                    onChange={handleInputChange}
                    className="w-full bg-white border-2 border-slate-100 focus:border-[#FFC222] rounded-xl sm:rounded-2xl py-3 sm:py-4 px-4 sm:px-6 outline-none transition-all font-medium text-sm sm:text-base shadow-sm"
                    placeholder="City / Town / District"
                  />
                </div>

                {/* Full Address */}
                <div className="md:col-span-2">
                  <label htmlFor="checkout-address" className="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 sm:mb-3">FULL ADDRESS</label>
                  <textarea 
                    id="checkout-address"
                    name="address"
                    value={formData.address}
                    onChange={handleInputChange}
                    rows={3}
                    className="w-full bg-white border-2 border-slate-100 focus:border-[#FFC222] rounded-xl sm:rounded-2xl py-4 sm:py-5 px-6 sm:px-8 outline-none transition-all font-medium text-sm sm:text-base resize-none shadow-sm placeholder:text-slate-300"
                    placeholder="Flat no., Building/Apartment, House no.&#10;Street, Area, Nearby Landmark"
                    required
                  />
                </div>
              </div>
            </div>

            <div className="bg-white p-6 sm:p-10 rounded-[32px] sm:rounded-[40px] shadow-sm border border-gray-100">
              <h2 className="text-2xl sm:text-3xl font-black mb-6 sm:mb-10 uppercase tracking-tight">Payment Method</h2>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                {availablePaymentMethods.length > 0 ? (
                  availablePaymentMethods.map((method) => (
                    <button
                      key={method.code}
                      onClick={() => setPaymentMethod(method.code as any)}
                      className={`p-6 sm:p-8 rounded-[24px] sm:rounded-[32px] border-2 transition-all flex flex-col items-center gap-3 sm:gap-4 ${paymentMethod === method.code ? 'border-[#FFC222] bg-[#FFC222]/5 shadow-lg' : 'border-gray-100 hover:border-gray-200 bg-white'}`}
                    >
                      <CreditCard className={`w-6 h-6 sm:w-8 sm:h-8 ${paymentMethod === method.code ? 'text-[#FFC222]' : 'text-gray-400'}`} />
                      <span className={`font-black uppercase text-[10px] sm:text-xs tracking-widest ${paymentMethod === method.code ? 'text-gray-900' : 'text-gray-400'}`}>
                        {method.name || 'Online Payment'}
                      </span>
                    </button>
                  ))
                ) : (
                  <button
                    onClick={() => setPaymentMethod('razorpay')}
                    className={`p-6 sm:p-8 rounded-[24px] sm:rounded-[32px] border-2 transition-all flex flex-col items-center gap-3 sm:gap-4 ${paymentMethod === 'razorpay' ? 'border-[#FFC222] bg-[#FFC222]/5 shadow-lg' : 'border-gray-100 hover:border-gray-200 bg-white'}`}
                  >
                    <CreditCard className={`w-6 h-6 sm:w-8 sm:h-8 ${paymentMethod === 'razorpay' ? 'text-[#FFC222]' : 'text-gray-400'}`} />
                    <span className={`font-black uppercase text-[10px] sm:text-xs tracking-widest ${paymentMethod === 'razorpay' ? 'text-gray-900' : 'text-gray-400'}`}>Razorpay / Online</span>
                  </button>
                )}
              </div>
            </div>
          </div>

          {/* Order Summary */}
          <div className="lg:col-span-1">
            <div className="bg-white p-6 sm:p-10 rounded-[32px] sm:rounded-[40px] shadow-xl border border-gray-100 lg:sticky lg:top-28">
              <h2 className="text-xl sm:text-2xl font-black mb-6 sm:mb-8 border-b pb-4 sm:pb-6 uppercase tracking-tight">Your Order</h2>
              <div className="space-y-4 mb-6 sm:mb-8 max-h-[400px] lg:max-h-none overflow-y-auto pr-2 custom-scrollbar">
                {items.map(item => (
                  <div key={`${item.id}-${item.selectedWeight || ''}`} className="flex gap-4 items-center group">
                    <div className="w-16 h-16 bg-gray-50 rounded-xl overflow-hidden flex-shrink-0 shadow-sm">
                      <img src={item.image} alt={item.name} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                    </div>
                    <div className="flex-grow min-w-0">
                      <div className="flex justify-between items-start mb-1">
                        <p className="font-bold text-gray-900 text-xs sm:text-sm leading-tight truncate pr-2">{item.name}</p>
                        <span className="font-black text-xs sm:text-sm text-gray-900 flex-shrink-0">
                          ₹{(parseFloat(String(item.price).replace(/[^\d.-]/g, '').split('-')[0]) * item.quantity).toFixed(2)}
                        </span>
                      </div>
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <span className="text-[10px] font-black text-[#FFC222] bg-[#FFC222]/10 px-2 py-0.5 rounded-full uppercase tracking-widest">Qty: {item.quantity}</span>
                          {item.selectedWeight && <span className="text-[9px] font-black text-gray-400 uppercase tracking-widest">{item.selectedWeight}</span>}
                        </div>
                        <span className="text-[10px] text-gray-400 font-bold uppercase tracking-widest">₹{(parseFloat(String(item.price).replace(/[^\d.-]/g, '').split('-')[0]) || 0).toFixed(2)} / unit</span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {/* Coupon Section */}
              <div className="mb-8 p-5 sm:p-6 bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Apply Coupon</label>
                {!appliedCoupon ? (
                  <div className="relative flex flex-col xs:flex-row gap-2 transition-all">
                    <div className="relative flex-grow">
                      <input
                        type="text"
                        value={couponCode}
                        onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                        placeholder="COUPON CODE"
                        className="w-full bg-white border-2 border-gray-100 focus:border-[#FFC222] rounded-xl sm:rounded-2xl px-4 py-3 sm:py-4 outline-none font-bold text-xs sm:text-sm uppercase transition-all shadow-sm placeholder:text-gray-300 pr-4 sm:pr-24"
                      />
                      <button
                        onClick={handleApplyCoupon}
                        disabled={couponLoading || !couponCode}
                        className="hidden sm:flex absolute right-1.5 top-1.5 bottom-1.5 bg-[#1E1D23] text-white font-black px-6 rounded-xl text-[10px] uppercase tracking-widest hover:bg-black disabled:bg-gray-200 transition-all items-center justify-center"
                      >
                        {couponLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Apply'}
                      </button>
                    </div>
                    <button
                      onClick={handleApplyCoupon}
                      disabled={couponLoading || !couponCode}
                      className="sm:hidden w-full bg-[#1E1D23] text-white font-black py-4 rounded-xl text-[10px] uppercase tracking-widest hover:bg-black disabled:bg-gray-200 transition-all flex items-center justify-center mt-2 xs:mt-0 xs:w-auto xs:px-8"
                    >
                      {couponLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Apply'}
                    </button>
                  </div>
                ) : (
                  <div className="flex items-center justify-between bg-white p-4 sm:p-5 rounded-2xl border-2 border-[#FFC222]/20">
                    <div className="flex items-center gap-3">
                      <div className="bg-[#FFC222]/10 p-2.5 rounded-xl">
                        <CheckCircle2 className="w-5 h-5 text-[#FFC222]" />
                      </div>
                      <div>
                        <p className="text-sm font-black text-gray-900 tracking-tight">{appliedCoupon.code}</p>
                        <p className="text-[10px] font-bold text-green-500 uppercase tracking-wide">Applied Successfully</p>
                      </div>
                    </div>
                    <button
                      onClick={removeCoupon}
                      className="text-[10px] font-black text-red-500 uppercase tracking-widest hover:text-red-700 p-2 hover:bg-red-50 rounded-lg transition-all"
                    >
                      Remove
                    </button>
                  </div>
                )}
                {couponError && <p className="mt-2 text-[10px] font-bold text-red-500 uppercase tracking-wide px-1">{couponError}</p>}
              </div>

              <div className="space-y-4 mb-8 border-t pt-6">
                <div className="flex justify-between text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                  <span>Subtotal</span>
                  <span className="text-gray-900">₹{totalPrice().toFixed(2)}</span>
                </div>
                {appliedCoupon && (
                  <div className="flex justify-between text-[#FFC222] font-bold uppercase text-[10px] tracking-widest">
                    <span>Discount ({appliedCoupon.discount_type === 'percentage' ? `${appliedCoupon.discount_value}%` : 'Fixed'})</span>
                    <span>-₹{discountAmount().toFixed(2)}</span>
                  </div>
                )}
                <div className="flex justify-between text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                  <span>Shipping</span>
                  <span className={shipping > 0 ? "text-gray-900" : "text-green-500"}>
                    {shipping > 0 ? `₹${shipping.toFixed(2)}` : 'FREE'}
                  </span>
                </div>
                {tax > 0 && (
                  <div className="flex justify-between text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                    <span>Tax (GST)</span>
                    <span className="text-gray-900">₹{tax.toFixed(2)}</span>
                  </div>
                )}
              </div>

              <div className="border-t border-dashed pt-6 mb-8 sm:mb-10">
                <div className="flex justify-between items-end gap-2">
                  <span className="text-[10px] xs:text-sm sm:text-lg font-black uppercase tracking-widest mb-1 sm:mb-2 flex-shrink-0">Total</span>
                  <span className="text-2xl xs:text-3xl sm:text-4xl font-black text-[#FFC222] tracking-tighter leading-none">₹{finalTotal().toFixed(2)}</span>
                </div>
              </div>

              <button
                onClick={handlePayment}
                disabled={loading}
                className={`w-full ${loading ? 'bg-gray-400' : 'bg-gray-900 hover:bg-black'} text-white font-black py-6 rounded-2xl transition-all shadow-xl flex items-center justify-center gap-3 uppercase text-sm tracking-widest active:scale-95`}
              >
                {loading ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin" />
                    Placing Order...
                  </>
                ) : (
                  <>
                    Place Order
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      </div>
      {showPopup && <OrderSuccessPopup />}
    </div>
  );
};

const OrderSuccessPopup: React.FC = () => {
  return (
    <div className="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300"></div>
      <div className="relative bg-white w-full max-w-md rounded-[48px] p-10 shadow-2xl animate-in zoom-in duration-500 text-center">
        <div className="bg-green-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
          <CheckCircle2 className="w-10 h-10 text-green-500" />
        </div>
        <h2 className="text-3xl font-black text-gray-900 mb-2 uppercase tracking-tight">Order Placed!</h2>
        <p className="text-gray-500 font-medium mb-0">Your order has been placed successfully.</p>
        <div className="mt-8 flex justify-center">
          <div className="w-12 h-1 bg-[#FFC222] rounded-full animate-pulse"></div>
        </div>
      </div>
    </div>
  );
};

export default Checkout;
