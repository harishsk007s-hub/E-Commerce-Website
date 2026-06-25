import React, { useState } from 'react';
import { Link, NavLink, useNavigate } from 'react-router-dom';
import { ShoppingBasket, Search, Menu, User, Phone, MapPin, Facebook, Twitter, Instagram, Heart, X, LogOut, ChevronDown, UserCircle } from 'lucide-react';
import { useCartStore } from '../store/useCartStore';
import { useUserStore } from '../store/useUserStore';
import CartSidebar from './CartSidebar';

const Navbar: React.FC = () => {
  const navigate = useNavigate();
  const totalItems = useCartStore((state) => state.totalItems());
  const { isCartOpen, setCartOpen } = useCartStore();
  const { isLoggedIn, user, logout } = useUserStore();
  const [isSearchOpen, setIsSearchOpen] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);

  React.useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20);
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  return (
    <header className={`fixed top-0 left-0 right-0 z-[60] bg-white transition-all duration-300 ${isScrolled ? 'shadow-lg border-none' : 'shadow-none border-b border-transparent'}`}>
      {/* Top Bar */}
      <div className={`bg-[#1E1D23] text-white px-4 sm:px-8 transition-all duration-300 overflow-hidden hidden md:block ${isScrolled ? 'h-0 opacity-0' : 'h-[40px] py-2 opacity-100'}`}>
        <div className="max-w-5xl mx-auto w-full flex justify-between items-center text-[13px] font-medium whitespace-nowrap">
          <div className="flex items-center space-x-8">
            <a href="tel:+919786506786" className="flex items-center gap-2 hover:text-[#FFC222] transition-colors">
              <Phone className="w-3.5 h-3.5 text-[#FFC222]" />
              <span className="font-bold tracking-tight">CALL US: +91 9786 506 786</span>
            </a>
            <div className="flex items-center gap-2">
              <MapPin className="w-3.5 h-3.5 text-[#FFC222]" />
              <span className="font-bold tracking-tight">Madurai-625 001</span>
            </div>
          </div>
          <div className="flex items-center space-x-6">
            <a href="https://www.facebook.com/goappalam" target="_blank" rel="noopener noreferrer">
              <Facebook className="w-4 h-4 hover:text-[#FFC222] cursor-pointer" />
            </a>
            <a href="https://twitter.com/goappalam" target="_blank" rel="noopener noreferrer">
              <Twitter className="w-4 h-4 hover:text-[#FFC222] cursor-pointer" />
            </a>
            <a href="https://www.instagram.com/goappalam/" target="_blank" rel="noopener noreferrer">
              <Instagram className="w-4 h-4 hover:text-[#FFC222] cursor-pointer" />
            </a>
          </div>
        </div>
      </div>

      {/* Main Navbar */}
      <nav className="bg-white py-4 px-4 sm:px-8 transition-all duration-300">
        <div className="max-w-5xl mx-auto w-full">
          {/* Mobile Layout (lg:hidden) */}
          <div className="flex lg:hidden justify-between items-center w-full">
            <div className="flex items-center">
              <button 
                onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                className="p-2 text-[#1E1D23] hover:text-[#FFC222] transition-colors mr-1"
              >
                {isMobileMenuOpen ? <X className="w-5 h-5 sm:w-6 sm:h-6" /> : <Menu className="w-5 h-5 sm:w-6 sm:h-6" />}
              </button>
              <button 
                onClick={() => setIsSearchOpen(true)}
                className="p-2 text-[#1E1D23] hover:text-[#FFC222] transition-colors"
              >
                <Search className="w-5 h-5 sm:w-6 sm:h-6" />
              </button>
            </div>
            
            <Link to="/" className="flex items-center absolute left-1/2 -translate-x-1/2">
              <img 
                src="/assests/uploads/2021/Contact/Logo.png"
                alt="Go Appalam" 
                style={{ width: '85px', height: '48px' }}
                className="xs:w-[100px] xs:h-[56px] object-contain transition-all duration-300" 
              />
            </Link>

            <div className="flex items-center">
              <a href="tel:+919786506786" className="p-2 text-[#1E1D23] hover:text-[#FFC222] transition-colors hidden sm:block">
                <Phone className="w-5 h-5 sm:w-6 sm:h-6" />
              </a>
              <button 
                onClick={() => setCartOpen(true)}
                className="relative p-2 text-[#1E1D23] hover:text-[#FFC222] transition-colors"
              >
                <ShoppingBasket className="w-5 h-5 sm:w-6 sm:h-6" />
                <span className="absolute top-0 right-0 bg-[#FFC222] text-gray-900 text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center border border-white shadow-sm">{totalItems}</span>
              </button>
            </div>
          </div>

          {/* Desktop Layout (hidden lg:flex) */}
          <div className="hidden lg:flex justify-between items-center w-full">
            {/* Logo */}
            <Link to="/" className="flex items-center">
              <img 
                src="/assests/uploads/2021/Contact/Logo.png"
                alt="Go Appalam" 
                style={{ width: '140px', height: '90.02px' }}
                className="object-contain transition-all duration-300 ml-1" 
              />
            </Link>
            
            {/* Navigation */}
            <div className="flex space-x-8 items-center ml-0">
              <NavLink 
                to="/" 
                className={({ isActive }) => 
                  `font-bold text-[16px] transition-all ${isActive ? 'text-[#FFC222]' : 'text-gray-900 hover:text-[#FFC222]'}`
                }
              >
                Home
              </NavLink>
              <NavLink 
                to="/shop" 
                className={({ isActive }) => 
                  `font-bold text-[16px] transition-all ${isActive ? 'text-[#FFC222]' : 'text-gray-900 hover:text-[#FFC222]'}`
                }
              >
                Shop
              </NavLink>
              <NavLink 
                to="/about" 
                className={({ isActive }) => 
                  `font-bold text-[16px] transition-all ${isActive ? 'text-[#FFC222]' : 'text-gray-900 hover:text-[#FFC222]'}`
                }
              >
                About
              </NavLink>
              <NavLink 
                to="/menu" 
                className={({ isActive }) => 
                  `font-bold text-[16px] transition-all ${isActive ? 'text-[#FFC222]' : 'text-gray-900 hover:text-[#FFC222]'}`
                }
              >
                Menu
              </NavLink>
              <NavLink 
                to="/contact" 
                className={({ isActive }) => 
                  `font-bold text-[16px] transition-all ${isActive ? 'text-[#FFC222]' : 'text-gray-900 hover:text-[#FFC222]'}`
                }
              >
                Contact us
              </NavLink>
            </div>

            {/* Call & Icons */}
            <div className="flex items-center space-x-6">
              <a href="tel:+919786506786" className="hidden xl:flex items-center gap-4 hover:opacity-80 transition-opacity">
                <img src="/assests/uploads/2021/Contact/ContactLogo.png" alt="" className="w-14 h-14 object-contain" />
                <div>
                  <p className="text-[13px] text-gray-400 font-medium">Call and Order In</p>
                  <p className="text-[20px] font-black text-[#FFC222] tracking-tight">+91 9786 506 786</p>
                </div>
              </a>

              <div className="flex items-center space-x-3">
                {isLoggedIn ? (
                  <div className="relative group">
                    <button 
                      onClick={() => setIsUserMenuOpen(!isUserMenuOpen)}
                      className="flex items-center gap-2 p-1 bg-white border border-gray-100 rounded-full hover:bg-[#FFC222] transition-all shadow-sm"
                    >
                      <div className="w-8 h-8 rounded-full bg-white flex items-center justify-center text-[#1E1D23]">
                        <User className="w-4 h-4" />
                      </div>
                      <ChevronDown className={`w-3 h-3 mr-2 transition-transform text-gray-500 group-hover:text-white ${isUserMenuOpen ? 'rotate-180' : ''}`} />
                    </button>
                    
                    {isUserMenuOpen && (
                      <div className="absolute right-0 mt-3 w-48 bg-white rounded-2xl shadow-2xl py-3 animate-in fade-in slide-in-from-top-2 duration-200 z-[100]">
                        <div className="px-5 py-2 border-b border-gray-50 mb-2">
                          <p className="text-[10px] text-gray-400 font-black uppercase tracking-widest">Account</p>
                          <p className="text-sm font-black text-gray-800 truncate">{user?.name}</p>
                        </div>
                        <Link 
                          to="/profile" 
                          className="flex items-center gap-3 px-5 py-3 text-sm font-bold text-gray-600 hover:bg-gray-50 hover:text-[#FFC222] transition-colors"
                          onClick={() => setIsUserMenuOpen(false)}
                        >
                          <UserCircle className="w-4 h-4" />
                          My Profile
                        </Link>
                        <button 
                          onClick={() => {
                            logout();
                            setIsUserMenuOpen(false);
                            navigate('/');
                          }}
                          className="w-full flex items-center gap-3 px-5 py-3 text-sm font-bold text-red-500 hover:bg-red-50 transition-colors mt-1"
                        >
                          <LogOut className="w-4 h-4" />
                          Logout
                        </button>
                      </div>
                    )}
                  </div>
                ) : (
                  <Link 
                    to="/login"
                    className="p-3 bg-white border border-gray-100 rounded-full text-gray-700 hover:bg-[#FFC222] hover:text-white transition-all shadow-sm"
                  >
                    <User className="w-5 h-5" />
                  </Link>
                )}
                <Link to="/wishlist" className="relative p-3 bg-white border border-gray-100 rounded-full text-gray-700 hover:bg-[#FFC222] hover:text-white transition-all group shadow-sm">
                  <Heart className="w-5 h-5" />
                  <span className="absolute -top-1 -right-1 bg-[#FFC222] text-gray-900 text-[10px] font-bold rounded-full h-5 w-5 flex items-center justify-center group-hover:bg-gray-900 group-hover:text-white border-2 border-white shadow-sm">0</span>
                </Link>
                <button 
                  onClick={() => setCartOpen(true)}
                  className="relative p-3 bg-white border border-gray-100 rounded-full text-gray-700 hover:bg-[#FFC222] hover:text-white transition-all group shadow-sm"
                >
                  <ShoppingBasket className="w-5 h-5" />
                  <span className="absolute -top-1 -right-1 bg-[#FFC222] text-gray-900 text-[10px] font-bold rounded-full h-5 w-5 flex items-center justify-center group-hover:bg-gray-900 group-hover:text-white border-2 border-white shadow-sm">{totalItems}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </nav>

      <CartSidebar isOpen={isCartOpen} onClose={() => setCartOpen(false)} />

      {/* Search Overlay */}
      {isSearchOpen && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-300">
          <button 
            onClick={() => setIsSearchOpen(false)}
            className="absolute top-8 right-8 text-white hover:text-[#FFC222] transition-colors"
          >
            <X className="w-8 h-8" />
          </button>
          <div className="w-full max-w-2xl relative">
            <input 
              type="text" 
              placeholder="Search products..." 
              className="w-full bg-transparent border-b-2 sm:border-b-4 border-white/20 focus:border-[#FFC222] py-4 sm:py-6 text-2xl sm:text-4xl font-black text-white outline-none transition-all placeholder:text-white/20"
              autoFocus
              onKeyDown={(e) => {
                if (e.key === 'Enter') setIsSearchOpen(false);
              }}
            />
            <button className="absolute right-0 top-1/2 -translate-y-1/2 text-white hover:text-[#FFC222] transition-colors">
              <Search className="w-6 h-6 sm:w-8 sm:h-8" />
            </button>
          </div>
        </div>
      )}

      {/* Mobile Menu */}
      {isMobileMenuOpen && (
        <div className="fixed inset-0 z-[100] lg:hidden animate-in fade-in duration-300">
          <div 
            className="absolute inset-0 bg-black/60 backdrop-blur-sm" 
            onClick={() => setIsMobileMenuOpen(false)}
          ></div>
          <div className="absolute top-0 left-0 h-full w-[280px] sm:w-[320px] bg-white shadow-2xl flex flex-col animate-in slide-in-from-left duration-300">
            <div className="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
              <Link to="/" onClick={() => setIsMobileMenuOpen(false)}>
                <img 
                  src="/assests/uploads/2021/Contact/Logo.png"
                  alt="Go Appalam" 
                  style={{ width: '80px', height: '45px' }}
                  className="object-contain" 
                />
              </Link>
              <button 
                onClick={() => setIsMobileMenuOpen(false)}
                className="text-gray-400 hover:text-[#FFC222] transition-colors"
              >
                <X className="w-6 h-6" />
              </button>
            </div>

            <div className="p-6 border-b border-gray-100">
              {isLoggedIn ? (
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 rounded-full bg-[#FFC222] flex items-center justify-center text-white">
                    <User className="w-6 h-6" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-xs text-gray-400 font-bold uppercase tracking-widest">Welcome back</p>
                    <p className="text-base font-black text-gray-800 truncate">{user?.name}</p>
                  </div>
                </div>
              ) : (
                <Link 
                  to="/login"
                  onClick={() => setIsMobileMenuOpen(false)}
                  className="flex items-center gap-4 p-3 bg-gray-50 rounded-xl hover:bg-[#FFC222]/10 transition-colors border border-gray-100 group"
                >
                  <div className="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-400 group-hover:text-[#FFC222] shadow-sm">
                    <User className="w-5 h-5" />
                  </div>
                  <div>
                    <p className="text-sm font-black text-gray-800">Login / Register</p>
                    <p className="text-[10px] text-gray-400 font-bold">Manage your orders</p>
                  </div>
                </Link>
              )}
            </div>

            <div className="flex-1 overflow-y-auto py-6 px-6">
              <div className="flex flex-col space-y-1">
                <NavLink 
                  to="/" 
                  onClick={() => setIsMobileMenuOpen(false)}
                  className={({ isActive }) => `flex items-center gap-4 py-3 px-4 rounded-xl font-bold text-lg transition-colors ${isActive ? 'bg-[#FFC222]/10 text-[#FFC222]' : 'text-gray-900 hover:bg-gray-50'}`}
                >
                  Home
                </NavLink>
                <NavLink 
                  to="/shop" 
                  onClick={() => setIsMobileMenuOpen(false)}
                  className={({ isActive }) => `flex items-center gap-4 py-3 px-4 rounded-xl font-bold text-lg transition-colors ${isActive ? 'bg-[#FFC222]/10 text-[#FFC222]' : 'text-gray-900 hover:bg-gray-50'}`}
                >
                  Shop
                </NavLink>
                <NavLink 
                  to="/wishlist" 
                  onClick={() => setIsMobileMenuOpen(false)}
                  className={({ isActive }) => `flex items-center gap-4 py-3 px-4 rounded-xl font-bold text-lg transition-colors ${isActive ? 'bg-[#FFC222]/10 text-[#FFC222]' : 'text-gray-900 hover:bg-gray-50'}`}
                >
                  Wishlist
                </NavLink>
                <NavLink 
                  to="/menu" 
                  onClick={() => setIsMobileMenuOpen(false)}
                  className={({ isActive }) => `flex items-center gap-4 py-3 px-4 rounded-xl font-bold text-lg transition-colors ${isActive ? 'bg-[#FFC222]/10 text-[#FFC222]' : 'text-gray-900 hover:bg-gray-50'}`}
                >
                  Menu
                </NavLink>
                <NavLink 
                  to="/about" 
                  onClick={() => setIsMobileMenuOpen(false)}
                  className={({ isActive }) => `flex items-center gap-4 py-3 px-4 rounded-xl font-bold text-lg transition-colors ${isActive ? 'bg-[#FFC222]/10 text-[#FFC222]' : 'text-gray-900 hover:bg-gray-50'}`}
                >
                  About
                </NavLink>
                <NavLink 
                  to="/contact" 
                  onClick={() => setIsMobileMenuOpen(false)}
                  className={({ isActive }) => `flex items-center gap-4 py-3 px-4 rounded-xl font-bold text-lg transition-colors ${isActive ? 'bg-[#FFC222]/10 text-[#FFC222]' : 'text-gray-900 hover:bg-gray-50'}`}
                >
                  Contact us
                </NavLink>

                {isLoggedIn && (
                  <>
                    <div className="h-px bg-gray-100 my-4" />
                    <NavLink 
                      to="/profile" 
                      onClick={() => setIsMobileMenuOpen(false)}
                      className={({ isActive }) => `flex items-center gap-4 py-3 px-4 rounded-xl font-bold text-lg transition-colors ${isActive ? 'bg-[#FFC222]/10 text-[#FFC222]' : 'text-gray-900 hover:bg-gray-50'}`}
                    >
                      <UserCircle className="w-5 h-5" />
                      My Profile
                    </NavLink>
                    <button 
                      onClick={() => {
                        logout();
                        setIsMobileMenuOpen(false);
                        navigate('/');
                      }}
                      className="flex items-center gap-4 py-3 px-4 rounded-xl font-bold text-lg text-red-500 hover:bg-red-50 transition-colors"
                    >
                      <LogOut className="w-5 h-5" />
                      Logout
                    </button>
                  </>
                )}
              </div>
            </div>

            <div className="p-6 bg-gray-50 border-t border-gray-100">
               <p className="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-2">Need help? Call us</p>
               <a href="tel:+919786506786" className="text-xl font-black text-[#FFC222] hover:opacity-80 transition-opacity flex items-center gap-2">
                 <Phone className="w-5 h-5" />
                 +91 9786 506 786
               </a>
            </div>
          </div>
        </div>
      )}
    </header>
  );
};

export default Navbar;
