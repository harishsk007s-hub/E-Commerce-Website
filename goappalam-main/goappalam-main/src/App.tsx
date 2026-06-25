import React, { useEffect } from 'react';
import { Routes, Route, Link, useLocation } from 'react-router-dom';
import { Facebook, Twitter, Instagram, ArrowUp, ShoppingBag, ArrowRight } from 'lucide-react';
import Navbar from './components/Navbar';
import Home from './pages/Home';
import Shop from './pages/Shop';
import ProductDetails from './pages/ProductDetails';
import Checkout from './pages/Checkout';
import About from './pages/About';
import Contact from './pages/Contact';
import Menu from './pages/Menu';
import Wishlist from './pages/Wishlist';
import Profile from './pages/Profile';
import LoginPage from './pages/LoginPage';
import ResetPassword from './pages/ResetPassword';
import PrivacyPolicy from './pages/PrivacyPolicy';
import TermsAndConditions from './pages/TermsAndConditions';
import CancelReturnPolicy from './pages/CancelReturnPolicy';
import ToastContainer from './components/ToastContainer';
import LoginModal from './components/LoginModal';
import { useUserStore } from './store/useUserStore';
import { useCartStore } from './store/useCartStore';

const ScrollToTop = () => {
  const { pathname } = useLocation();
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [pathname]);
  return null;
};

const App: React.FC = () => {
  const { loadUser, isLoginModalOpen, setLoginModalOpen, _hasHydrated } = useUserStore();
  const { items, totalPrice, totalItems, setCartOpen, loadCart } = useCartStore();
  const location = useLocation();

  useEffect(() => {
    // Load cart from server/localStorage on startup
    loadCart();

    // Load user from token on startup once hydrated
    if (_hasHydrated) {
      loadUser();
    }
  }, [_hasHydrated, loadUser]);

  const showFloatingCart = items.length > 0 && location.pathname !== '/checkout';

  return (
    <div className="min-h-screen bg-white font-sans text-gray-900 flex flex-col">
      <ScrollToTop />
      <Navbar />
      <div className="flex-grow pt-[90px] sm:pt-[120px] md:pt-[140px]">
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/shop" element={<Shop />} />
          <Route path="/product/:id" element={<ProductDetails />} />
          <Route path="/checkout" element={<Checkout />} />
          <Route path="/about" element={<About />} />
          <Route path="/contact" element={<Contact />} />
          <Route path="/menu" element={<Menu />} />
          <Route path="/wishlist" element={<Wishlist />} />
          <Route path="/profile" element={<Profile />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/reset-password" element={<ResetPassword />} />
          <Route path="/privacy-policy" element={<PrivacyPolicy />} />
          <Route path="/terms-and-condition" element={<TermsAndConditions />} />
          <Route path="/cancel-return-policy" element={<CancelReturnPolicy />} />
          <Route path="/admin" element={<div dangerouslySetInnerHTML={{ __html: '<script>window.location.href="/admin/";</script>' }} />} />
          <Route path="/developer" element={<div dangerouslySetInnerHTML={{ __html: '<script>window.location.href="/developer/";</script>' }} />} />
        </Routes>
      </div>
      <ToastContainer />
      <LoginModal isOpen={isLoginModalOpen} onClose={() => setLoginModalOpen(false)} />

      {/* Floating Cart Bar (Swiggy Style) */}
      {showFloatingCart && (
        <div className="fixed bottom-0 left-0 right-0 z-[50] p-3 sm:p-4 pointer-events-none animate-in slide-in-from-bottom duration-500">
          <div className="max-w-4xl mx-auto w-full pointer-events-auto">
            <button 
              onClick={() => setCartOpen(true)}
              className="w-full bg-[#1E1D23] text-white p-3 sm:p-4 rounded-xl sm:rounded-2xl shadow-2xl flex items-center justify-between border-2 border-[#FFC222]/20 hover:scale-[1.02] transition-all group"
            >
              <div className="flex items-center gap-3 sm:gap-4">
                <div className="bg-[#FFC222] p-1.5 sm:p-2 rounded-lg sm:rounded-xl">
                  <ShoppingBag className="w-4 h-4 sm:w-5 sm:h-5 text-[#1E1D23]" />
                </div>
                <div className="text-left">
                  <p className="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[#FFC222]">{totalItems()} Items</p>
                  <p className="text-xs sm:text-sm font-black">₹{totalPrice().toFixed(2)} | plus taxes</p>
                </div>
              </div>
              <div className="flex items-center gap-1 sm:gap-2 font-black text-xs sm:text-sm uppercase tracking-widest group-hover:text-[#FFC222] transition-colors">
                View Cart <ArrowRight className="w-3 h-3 sm:w-4 sm:h-4 group-hover:translate-x-1 transition-transform" />
              </div>
            </button>
          </div>
        </div>
      )}

      {/* Footer */}
      <footer className="bg-[#0e0e0e] text-white pt-8 relative overflow-hidden">
        <div className="absolute inset-0 opacity-5 pointer-events-none" style={{ backgroundImage: "url('/assests/uploads/2020/09/shape_dot.png')", backgroundRepeat: 'repeat' }} />
        
        <div className="max-w-5xl mx-auto px-8 pb-8 relative z-10">
          <div className="flex flex-col items-center mb-8">
            <img 
              src="/assests/uploads/2020/09/LOGOMARK-03-1.png"
              alt="Go Appalam" 
              style={{ width: '140px', height: '90.02px' }}
              className="object-contain" 
            />
            <div className="w-full h-[1px] bg-white/10 mt-6" />
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-4 gap-12 text-center">
            <div>
              <h4 className="text-white font-bold text-lg mb-3 uppercase tracking-widest">Address</h4>
              <p className="text-gray-400 leading-relaxed text-[15px]">
                8/A, Nalla Muthu Pillai St,<br />
                Keeraithurai, Madurai-01.<br />
                Tamil Nadu.
              </p>
            </div>
            <div>
              <h4 className="text-white font-bold text-lg mb-3 uppercase tracking-widest">Ring Us</h4>
              <p className="text-gray-400 mb-6 text-[15px]">Ring us whenever and <br className="hidden md:block"></br>wherever you need us</p>
              <p className="text-[#FFC222] font-black text-xl">+91 9786 506 786</p>
            </div>
            <div>
              <h4 className="text-white font-bold text-lg mb-3 uppercase tracking-widest">Opening Hours</h4>
              <p className="text-gray-400 leading-relaxed text-[15px] mb-8">
                Monday – Friday: <span className="text-white">8am – 4pm</span><br />
                Saturday: <span className="text-white">9am – 5pm</span>
              </p>
              <div className="flex justify-center gap-4">
                <a href="https://www.facebook.com/goappalam" target="_blank" rel="noopener noreferrer" className="bg-white p-3 rounded-full hover:bg-[#FFC222] transition-colors cursor-pointer group">
                  <Facebook className="w-4 h-4 text-[#1E1D23] fill-current" />
                </a>
                <a href="https://twitter.com/goappalam" target="_blank" rel="noopener noreferrer" className="bg-white p-3 rounded-full hover:bg-[#FFC222] transition-colors cursor-pointer group">
                  <Twitter className="w-4 h-4 text-[#1E1D23] fill-current" />
                </a>
                <a href="https://www.instagram.com/goappalam/" target="_blank" rel="noopener noreferrer" className="bg-white p-3 rounded-full hover:bg-[#FFC222] transition-colors cursor-pointer group">
                  <Instagram className="w-4 h-4 text-[#1E1D23]" />
                </a>
              </div>
            </div>
            <div>
              <h4 className="text-white font-bold text-lg mb-3 uppercase tracking-widest">Quick Links</h4>
              <ul className="text-gray-400 text-[15px]">
                <li className="hover:text-white transition-colors">
                  <Link to="/privacy-policy">Privacy Policy</Link>
                </li>
                <li className="hover:text-white transition-colors">
                  <Link to="/terms-and-condition">Terms and Condition</Link>
                </li>
                <li className="hover:text-white transition-colors">
                  <Link to="/cancel-return-policy">Cancel/Return policy</Link>
                </li>
              </ul>
            </div>
          </div>
        </div>
        
        <div className="bg-[#7C0303] py-6 sm:py-8 relative z-10 border-t border-white/5">
          <div className="max-w-5xl mx-auto px-4 sm:px-8 flex flex-col md:flex-row justify-between items-center gap-6 text-center md:text-left">
            <p className="text-gray-200 text-[13px] sm:text-sm font-medium leading-relaxed max-w-[280px] sm:max-w-none mx-auto md:mx-0">
              Copyright © 2021 <span className="text-[#FFC222] font-black uppercase tracking-widest">goappalam</span>. All Rights Reserved.
            </p>
            <div className="flex items-center justify-center gap-6">
              <img 
                src="/assests/uploads/2020/09/MODE.png" 
                alt="Payment Methods" 
                className="h-6 sm:h-7 opacity-90 hover:opacity-100 transition-all filter brightness-110" 
              />
            </div>
          </div>
        </div>
      </footer>

      {/* Floating Buttons */}
      <div className={`fixed transition-all duration-500 right-6 sm:right-8 z-[9999] flex flex-col gap-3 sm:gap-4 ${showFloatingCart ? 'bottom-24 sm:bottom-28' : 'bottom-6 sm:bottom-8'}`}>
        <button 
          onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
          className="hidden sm:flex bg-[#1E1D23] text-white p-3 sm:p-4 rounded-full shadow-2xl hover:bg-[#FFC222] hover:text-[#1E1D23] transition-all hover:scale-110 active:scale-95 group border-2 border-[#FFC222]"
          title="Back to Top"
        >
          <ArrowUp className="w-5 h-5 sm:w-6 sm:h-6 group-hover:-translate-y-1 transition-transform" />
        </button>
        <a 
          href="https://wa.me/919786506786" 
          target="_blank" 
          rel="noopener noreferrer"
          className="bg-[#25D366] text-white p-3 sm:p-4 rounded-full shadow-2xl hover:scale-110 transition-all active:scale-95 group flex items-center justify-center border-2 border-white"
          title="WhatsApp Chat"
        >
          <svg className="w-6 h-6 sm:w-8 sm:h-8 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.588-5.946 0-6.556 5.332-11.888 11.888-11.888 3.176 0 6.161 1.237 8.404 3.48s3.481 5.229 3.481 8.406c0 6.556-5.332 11.888-11.888 11.888-2.01 0-3.988-.511-5.741-1.482l-6.143 1.705zm6.349-4.321l.455.27c1.42.844 3.061 1.29 4.747 1.29 5.063 0 9.182-4.119 9.182-9.182 0-2.454-.955-4.761-2.69-6.496-1.735-1.735-4.041-2.69-6.492-2.69-5.064 0-9.184 4.12-9.184 9.184 0 1.724.482 3.407 1.393 4.885l.304.492-1.01 3.693 3.795-.946zm11.161-6.176c-.302-.151-1.785-.881-2.062-.982-.277-.101-.48-.151-.681.151-.202.302-.782.982-.958 1.183-.176.202-.352.227-.654.076-.302-.151-1.276-.47-2.431-1.5-.899-.801-1.504-1.791-1.681-2.092-.177-.302-.019-.465.132-.615.136-.135.302-.352.453-.528.151-.177.202-.302.302-.504.101-.202.051-.378-.026-.528-.076-.151-.681-1.641-.934-2.253-.247-.599-.497-.517-.681-.527-.176-.01-.378-.013-.58-.013-.202 0-.528.076-.806.378-.277.302-1.057 1.032-1.057 2.52s1.083 2.922 1.233 3.123c.151.202 2.132 3.256 5.166 4.565.721.312 1.284.499 1.723.638.724.23 1.382.197 1.902.12.58-.088 1.785-.731 2.037-1.438.252-.706.252-1.31.176-1.438-.076-.126-.277-.202-.58-.352z"/></svg>
        </a>
      </div>
    </div>
  );
};

export default App;
