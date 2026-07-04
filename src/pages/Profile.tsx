import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useUserStore } from '../store/useUserStore';
import { getUserOrders } from '../api/order';
import { updateProfile } from '../api/auth';
import { User, Package, Clock, MapPin, ShoppingBag, Settings, Lock, Edit2, CheckCircle2 } from 'lucide-react';

const Profile: React.FC = () => {
  const navigate = useNavigate();
  const { user, isLoggedIn, setUser, token } = useUserStore();
  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'orders' | 'settings'>('orders');
  const [updating, setUpdating] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');

  // Form states
  const [name, setName] = useState(user?.name || '');
  const [phone, setPhone] = useState(user?.phone || '');
  const [addressLine1, setAddressLine1] = useState(user?.addresses?.shipping?.line1 || '');
  const [addressLine2, setAddressLine2] = useState(user?.addresses?.shipping?.line2 || '');
  const [addressLine3, setAddressLine3] = useState(user?.addresses?.shipping?.line3 || '');
  const [pincode, setPincode] = useState(user?.addresses?.shipping?.pincode || '');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  useEffect(() => {
    if (user) {
      setName(user.name || '');
      setPhone(user.phone || '');
      setAddressLine1(user.addresses?.shipping?.line1 || '');
      setAddressLine2(user.addresses?.shipping?.line2 || '');
      setAddressLine3(user.addresses?.shipping?.line3 || '');
      setPincode(user.addresses?.shipping?.pincode || '');
    }
  }, [user]);

  useEffect(() => {
    if (!isLoggedIn) {
      navigate('/');
      return;
    }

    if (user?.id) {
      getUserOrders(user.id)
        .then((data) => {
          if (data.status === 'success') {
            setOrders(data.orders);
          }
        })
        .catch(console.error)
        .finally(() => setLoading(false));
    }
  }, [isLoggedIn, user, navigate]);

  const getEstimatedDelivery = (createdAt: string) => {
    const date = new Date(createdAt);
    date.setDate(date.getDate() + 5);
    return date.toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' });
  };

  const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
      case 'completed': return 'bg-green-100 text-green-700';
      case 'pending': return 'bg-yellow-100 text-yellow-700';
      case 'processing': return 'bg-blue-100 text-blue-700';
      case 'shipped': return 'bg-purple-100 text-purple-700';
      case 'cancelled': return 'bg-red-100 text-red-700';
      default: return 'bg-gray-100 text-gray-700';
    }
  };

  const handleUpdateProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess(false);

    if (password && password !== confirmPassword) {
      setError('Passwords do not match');
      return;
    }

    setUpdating(true);
    try {
      const result = await updateProfile({
        name,
        phone,
        address_line1: addressLine1,
        address_line2: addressLine2,
        address_line3: addressLine3,
        pincode: pincode,
        ...(password ? { password } : {})
      });

      if (result.status === 'success') {
        setSuccess(true);
        if (user) {
          setUser({ 
            ...user, 
            name, 
            phone,
            addresses: {
              ...user.addresses,
              shipping: {
                line1: addressLine1,
                line2: addressLine2,
                line3: addressLine3,
                pincode: pincode
              }
            }
          }, token);
        }
        setPassword('');
        setConfirmPassword('');
        setTimeout(() => setSuccess(false), 3000);
      } else {
        setError(result.error || 'Failed to update profile');
      }
    } catch (err: any) {
      setError(err.response?.data?.error || 'An error occurred while updating profile');
    } finally {
      setUpdating(false);
    }
  };

  if (!isLoggedIn) return null;

  return (
    <main className="min-h-screen bg-gray-50 py-20 px-4 sm:px-8">
      <div className="max-w-5xl mx-auto">
        <h1 className="text-4xl font-black text-gray-900 mb-12 uppercase tracking-tight animate-fade-in">My Profile</h1>
        
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* User Info Card */}
          <div className="lg:col-span-1">
            <div className="bg-white p-6 sm:p-8 rounded-[32px] sm:rounded-[40px] shadow-sm border border-gray-100 lg:sticky lg:top-28 animate-fade-in-up">
              <div className="flex flex-col items-center text-center">
                <div className="w-24 h-24 bg-[#FFC222]/10 rounded-full flex items-center justify-center mb-6 border-4 border-[#FFC222]/20">
                  <User className="w-10 h-10 text-[#FFC222]" />
                </div>
                <h2 className="text-2xl font-black text-gray-900 mb-1">{user?.name}</h2>
                <p className="text-sm font-bold text-gray-400 mb-8">{user?.email}</p>
                
                <div className="w-full space-y-4 pt-6 border-t border-gray-50">
                  <button 
                    onClick={() => setActiveTab('orders')}
                    className={`w-full flex items-center gap-4 text-left p-4 rounded-2xl transition-all ${activeTab === 'orders' ? 'bg-[#FFC222]/10 border border-[#FFC222]/20' : 'bg-gray-50 hover:bg-gray-100'}`}
                  >
                    <Package className={`w-5 h-5 ${activeTab === 'orders' ? 'text-[#FFC222]' : 'text-gray-400'}`} />
                    <div>
                      <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Orders</p>
                      <p className="text-sm font-black text-gray-900">{orders.length} Total</p>
                    </div>
                  </button>
                  
                  <button 
                    onClick={() => setActiveTab('settings')}
                    className={`w-full flex items-center gap-4 text-left p-4 rounded-2xl transition-all ${activeTab === 'settings' ? 'bg-[#FFC222]/10 border border-[#FFC222]/20' : 'bg-gray-50 hover:bg-gray-100'}`}
                  >
                    <Settings className={`w-5 h-5 ${activeTab === 'settings' ? 'text-[#FFC222]' : 'text-gray-400'}`} />
                    <div>
                      <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Settings</p>
                      <p className="text-sm font-black text-gray-900">Account Management</p>
                    </div>
                  </button>

                  <div className="flex items-center gap-4 text-left p-4 bg-gray-50 rounded-2xl opacity-60">
                    <Clock className="w-5 h-5 text-gray-400" />
                    <div>
                      <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Member Since</p>
                      <p className="text-sm font-black text-gray-900">January 2026</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Main Content Area */}
          <div className="lg:col-span-2">
            <div className="bg-white p-6 sm:p-10 rounded-[32px] sm:rounded-[40px] shadow-sm border border-gray-100 min-h-[600px] animate-fade-in-up animate-delay-200">
              
              {activeTab === 'orders' ? (
                <>
                  <div className="flex items-center justify-between mb-10">
                    <h3 className="text-2xl font-black text-gray-900 uppercase tracking-tight">Track Your Orders</h3>
                    <span className="bg-gray-900 text-white text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest">
                      Recent Activity
                    </span>
                  </div>

                  {loading ? (
                    <div className="flex justify-center py-20">
                      <div className="animate-spin rounded-full h-10 w-10 border-4 border-[#FFC222] border-t-transparent"></div>
                    </div>
                  ) : orders.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-20 text-center">
                      <div className="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                        <ShoppingBag className="w-8 h-8 text-gray-200" />
                      </div>
                      <p className="text-gray-400 font-bold italic mb-6">You haven't placed any orders yet.</p>
                      <button 
                        onClick={() => navigate('/shop')}
                        className="bg-[#FFC222] text-gray-900 font-black px-10 py-4 rounded-2xl hover:bg-gray-900 hover:text-white transition-all shadow-lg shadow-[#FFC222]/20 uppercase text-xs tracking-widest"
                      >
                        Start Shopping
                      </button>
                    </div>
                  ) : (
                    <div className="space-y-8">
                      {orders.map((order) => (
                        <div key={order.id} className="group bg-gray-50/50 rounded-[32px] p-8 border border-transparent hover:border-[#FFC222]/30 hover:bg-white hover:shadow-xl transition-all duration-500">
                          <div className="flex flex-wrap justify-between items-start gap-4 mb-6">
                            <div>
                              <div className="flex items-center gap-3 mb-2">
                                <span className="text-lg font-black text-gray-900">Order #{order.id}</span>
                                <span className={`text-[9px] font-black px-3 py-1 rounded-full uppercase tracking-widest ${getStatusColor(order.status)}`}>
                                  {order.status}
                                </span>
                              </div>
                              <p className="text-xs text-gray-400 font-bold">Placed on {new Date(order.created_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}</p>
                            </div>
                            <div className="text-right">
                              <p className="text-2xl font-black text-[#FFC222] tracking-tighter">₹{parseFloat(order.total).toFixed(2)}</p>
                              <p className="text-[10px] text-gray-400 font-black uppercase tracking-widest">{order.payment_method}</p>
                            </div>
                          </div>

                          <div className="space-y-4 mb-8">
                            {order.items.map((item: any, idx: number) => (
                              <div key={idx} className="flex items-center gap-4 bg-white/50 p-3 rounded-xl border border-gray-100">
                                <div className="w-12 h-12 bg-gray-50 rounded-lg overflow-hidden flex-shrink-0">
                                  {item.image ? (
                                    <img src={item.image} alt={item.name} className="w-full h-full object-cover" />
                                  ) : (
                                    <div className="w-full h-full flex items-center justify-center text-[10px] font-black text-gray-300 uppercase">
                                      {item.name?.charAt(0)}
                                    </div>
                                  )}
                                </div>
                                <div className="flex-grow">
                                  <p className="text-sm font-bold text-gray-800 leading-tight">{item.name}</p>
                                  <div className="flex items-center gap-2">
                                    <p className="text-[10px] text-gray-400 font-black uppercase tracking-widest">Qty: {item.quantity}</p>
                                    {item.selectedWeight && <p className="text-[10px] text-[#FFC222] font-black uppercase tracking-widest">{item.selectedWeight}</p>}
                                  </div>
                                </div>
                                <p className="text-sm font-black text-gray-900">₹{(parseFloat(String(item.price).replace(/[^\d.-]/g, '')) * item.quantity).toFixed(2)}</p>
                              </div>
                            ))}
                          </div>

                          <div className="flex flex-wrap items-center justify-between gap-6 pt-6 border-t border-gray-100">
                            <div className="flex items-center gap-4">
                              <div className="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center">
                                <Clock className="w-5 h-5 text-green-500" />
                              </div>
                              <div>
                                <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Estimated Delivery</p>
                                <p className="text-sm font-black text-green-600">{getEstimatedDelivery(order.created_at)}</p>
                              </div>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </>
              ) : (
                <div className="max-w-2xl">
                  <div className="flex items-center justify-between mb-10">
                    <h3 className="text-2xl font-black text-gray-900 uppercase tracking-tight">Account Settings</h3>
                    <span className="bg-[#FFC222] text-gray-900 text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest">
                      Manage Profile
                    </span>
                  </div>

                  {success && (
                    <div className="mb-8 flex items-center gap-3 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 animate-in fade-in slide-in-from-top-4 duration-300">
                      <CheckCircle2 className="w-5 h-5" />
                      <p className="text-sm font-bold">Profile updated successfully!</p>
                    </div>
                  )}

                  {error && (
                    <div className="mb-8 p-4 bg-red-50 text-red-600 rounded-2xl border border-red-100 text-sm font-bold">
                      {error}
                    </div>
                  )}

                  <form onSubmit={handleUpdateProfile} className="space-y-8">
                    {/* Basic Info */}
                    <div className="space-y-6">
                      <div className="flex items-center gap-3 text-gray-400 mb-2">
                        <Edit2 className="w-4 h-4" />
                        <span className="text-[10px] font-black uppercase tracking-widest">Basic Information</span>
                      </div>
                      
                      <div>
                        <label className="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3 ml-1">Full Name</label>
                        <input 
                          type="text" 
                          value={name}
                          onChange={(e) => setName(e.target.value)}
                          className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white p-4 rounded-2xl outline-none transition-all font-bold text-gray-900"
                          placeholder="Your full name"
                          required
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3 ml-1">Phone Number</label>
                        <input 
                          type="tel" 
                          value={phone}
                          onChange={(e) => setPhone(e.target.value)}
                          className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white p-4 rounded-2xl outline-none transition-all font-bold text-gray-900"
                          placeholder="Your phone number"
                        />
                      </div>

                      <div>
                        <label className="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3 ml-1">Email Address</label>
                        <input 
                          type="email" 
                          value={user?.email}
                          disabled
                          className="w-full bg-gray-100 border-2 border-transparent p-4 rounded-2xl text-gray-400 font-bold cursor-not-allowed"
                        />
                        <p className="mt-2 text-[10px] text-gray-400 font-bold italic ml-1">Email cannot be changed as it is used for login.</p>
                      </div>
                    </div>

                    {/* Shipping Address */}
                    <div className="pt-8 border-t border-gray-50 space-y-6">
                      <div className="flex items-center gap-3 text-gray-400 mb-2">
                        <MapPin className="w-4 h-4" />
                        <span className="text-[10px] font-black uppercase tracking-widest">Shipping Address</span>
                      </div>

                      <div className="space-y-4">
                        <div>
                          <label className="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Address Line 1</label>
                          <input 
                            type="text" 
                            value={addressLine1}
                            onChange={(e) => setAddressLine1(e.target.value)}
                            className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white p-4 rounded-2xl outline-none transition-all font-bold text-gray-900"
                            placeholder="Door No / Street Name"
                          />
                        </div>
                        <div>
                          <label className="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Address Line 2</label>
                          <input 
                            type="text" 
                            value={addressLine2}
                            onChange={(e) => setAddressLine2(e.target.value)}
                            className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white p-4 rounded-2xl outline-none transition-all font-bold text-gray-900"
                            placeholder="Area / Locality"
                          />
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                          <div>
                            <label className="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Address Line 3 (City/State)</label>
                            <input 
                              type="text" 
                              value={addressLine3}
                              onChange={(e) => setAddressLine3(e.target.value)}
                              className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white p-4 rounded-2xl outline-none transition-all font-bold text-gray-900"
                              placeholder="City, State"
                            />
                          </div>
                          <div>
                            <label className="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Pincode</label>
                            <input 
                              type="text" 
                              value={pincode}
                              onChange={(e) => setPincode(e.target.value)}
                              className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white p-4 rounded-2xl outline-none transition-all font-bold text-gray-900"
                              placeholder="6-digit Pincode"
                            />
                          </div>
                        </div>
                      </div>
                    </div>

                    {/* Password Change */}
                    <div className="pt-8 border-t border-gray-50 space-y-6">
                      <div className="flex items-center gap-3 text-gray-400 mb-2">
                        <Lock className="w-4 h-4" />
                        <span className="text-[10px] font-black uppercase tracking-widest">Security & Password</span>
                      </div>

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                          <label className="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3 ml-1">New Password</label>
                          <input 
                            type="password" 
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white p-4 rounded-2xl outline-none transition-all font-bold text-gray-900"
                            placeholder="••••••••"
                          />
                        </div>
                        <div>
                          <label className="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3 ml-1">Confirm New Password</label>
                          <input 
                            type="password" 
                            value={confirmPassword}
                            onChange={(e) => setConfirmPassword(e.target.value)}
                            className="w-full bg-gray-50 border-2 border-transparent focus:border-[#FFC222] focus:bg-white p-4 rounded-2xl outline-none transition-all font-bold text-gray-900"
                            placeholder="••••••••"
                          />
                        </div>
                      </div>
                      <p className="text-[10px] text-gray-400 font-bold italic ml-1">Leave blank if you don't want to change your password.</p>
                    </div>

                    <div className="pt-8">
                      <button 
                        type="submit"
                        disabled={updating}
                        className="w-full md:w-auto bg-[#FFC222] text-gray-900 font-black px-12 py-5 rounded-2xl hover:bg-gray-900 hover:text-white transition-all shadow-xl shadow-[#FFC222]/20 uppercase text-sm tracking-widest disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        {updating ? 'Saving Changes...' : 'Update Profile'}
                      </button>
                    </div>
                  </form>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </main>
  );
};

export default Profile;
