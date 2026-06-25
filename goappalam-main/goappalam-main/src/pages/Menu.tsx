import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { getProducts } from '../api/products';
import { Product } from '../types';
import ProductCard from '../components/ProductCard';

const Menu: React.FC = () => {
  const navigate = useNavigate();
  const [products, setProducts] = useState<Product[]>([]);
  const [activeTab, setActiveTab] = useState('ALL');
  const [loading, setLoading] = useState(true);

  const dynamicCategories = Array.from(new Set(products.map(p => p.category).filter(Boolean))).sort() as string[];
  const tabs = ['ALL', 'PAPADAM', ...dynamicCategories.filter(cat => cat && cat.toUpperCase() !== 'PAPADAM' && cat.toUpperCase() !== 'COMBO'), 'COMBO'];

  useEffect(() => {
    getProducts()
      .then(setProducts)
      .catch((err) => {
        console.error("API Error:", err);
        setProducts([]);
      })
      .finally(() => setLoading(false));
  }, []);

  const filteredProducts = products.filter(p => {
    if (activeTab === 'ALL') return true;
    
    const category = (p.category || '').toUpperCase();
    const tab = activeTab.toUpperCase();
    
    if (tab === 'PAPADAM') {
      return category !== 'COMBO';
    }
    
    if (tab === 'COMBO') {
      return category === 'COMBO';
    }
    
    return category === tab;
  });

  return (
    <main className="min-h-screen bg-white">
      {/* Banner */}
      <section className="relative h-[150px] sm:h-[250px] flex items-center justify-center overflow-hidden">
        <div 
          className="absolute inset-0 bg-cover bg-center" 
          style={{ backgroundImage: "url('/assests/uploads/2021/07/Banner-Overall-scaled.jpg')" }}
        />
        <div className="relative text-center z-10 px-4">
          <h1 className="text-4xl sm:text-6xl font-black text-[#1E1D23] mb-2 sm:mb-4">Menu</h1>
          <div className="flex items-center justify-center gap-2 text-xs sm:text-sm font-bold text-gray-500">
            <span 
              onClick={() => navigate('/')}
              className="hover:text-[#FFC222] cursor-pointer transition-colors"
            >
              Home
            </span>
            <span className="text-gray-400 font-normal mx-1">&gt;</span>
            <span className="text-gray-900">Menu</span>
          </div>
        </div>
      </section>

      {/* Menu Sections */}
      <section className="py-20 px-8 max-w-5xl mx-auto animate-fade-in-up">
        <div className="text-center mb-16">
          <h2 className="text-3xl font-black text-[#1E1D23] mb-12 uppercase tracking-tight">Our Varieties Of Appalam</h2>
          
          {/* Tabs */}
          <div className="flex flex-wrap justify-center gap-4">
            {tabs.map((tab) => (
              <button
                key={tab}
                onClick={() => setActiveTab(tab)}
                className={`px-8 py-3 rounded-full font-black text-sm tracking-wider transition-all duration-300 flex items-center gap-2 ${
                  activeTab === tab 
                    ? 'bg-[#FFC222] text-white shadow-lg shadow-[#FFC222]/30' 
                    : 'bg-gray-50 text-gray-700 hover:bg-gray-100'
                }`}
              >
                {tab}
              </button>
            ))}
          </div>
        </div>

        {loading ? (
          <div className="flex justify-center py-20">
            <div className="animate-spin rounded-full h-12 w-12 border-4 border-[#FFC222] border-t-transparent"></div>
          </div>
        ) : (
          <div className="grid grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-8">
            {filteredProducts.map(product => (
              <ProductCard 
                key={product.id} 
                product={product} 
              />
            ))}
          </div>
        )}
      </section>

      {/* Delivery CTA Section (Optional, from images) */}
      <section className="relative h-[400px] sm:h-[600px] mt-12 sm:mt-20 bg-cover bg-no-repeat bg-center" style={{ backgroundImage: "url('/menu.jpg')" }}>
        <div className="absolute inset-0 flex items-center justify-center bg-black/30">
            <div className="text-center px-6 sm:px-8">
                <h2 
                  className="text-white mb-4 drop-shadow-lg font-black text-3xl sm:text-[52px] leading-tight uppercase"
                >
                  Explore the New Taste
                </h2>
                <p className="text-sm sm:text-xl font-medium text-white mb-8 max-w-2xl mx-auto drop-shadow-md">Enjoy our unique & traditional flavours of papadam</p>
                <button 
                    onClick={() => navigate('/shop')}
                    className="bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white px-8 sm:px-12 py-3 sm:py-4 rounded-xl font-black transition-all shadow-2xl uppercase tracking-widest text-xs sm:text-sm"
                >
                    ORDER NOW
                </button>
            </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-10 bg-white">
        <div className="max-w-6xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Box 1 - Scooter */}
          <div className="border border-dashed border-gray-200 rounded-xl p-5 flex items-center gap-4 hover:border-[#FFC222] transition-colors group">
            <div className="flex-shrink-0">
              <svg className="w-10 h-10 text-[#FFC222]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M19 17h2c.6 0 1-1.4 1-2a2 2 0 1 0-4 0 2 2 0 0 0 1 2z"/>
                <path d="M5 17h2a2 2 0 1 0-4 0 2 2 0 0 0 2 2z"/>
                <path d="M7 17h8a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H9"/>
                <path d="M13 17V9"/>
                <path d="M9 17V7A2 2 0 0 1 11 5h2"/>
                <rect x="3" y="11" width="4" height="4" rx="1"/>
              </svg>
            </div>
            <div>
              <h3 className="text-[15px] font-black text-[#1E1D23] uppercase tracking-tight leading-none mb-1">Free shipping</h3>
              <p className="text-gray-500 text-[12px] font-medium leading-tight">Sign up for updates and get free shipping</p>
            </div>
          </div>

          {/* Box 2 - Clock */}
          <div className="border border-dashed border-gray-200 rounded-xl p-5 flex items-center gap-4 hover:border-[#FFC222] transition-colors group">
            <div className="flex-shrink-0">
              <svg className="w-10 h-10 text-[#FFC222]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
            </div>
            <div>
              <h3 className="text-[15px] font-black text-[#1E1D23] uppercase tracking-tight leading-none mb-1">Delivery in 3 Days</h3>
              <p className="text-gray-500 text-[12px] font-medium leading-tight">Everything you order will quickly delivered to your door.</p>
            </div>
          </div>

          {/* Box 3 - Badge */}
          <div className="border border-dashed border-gray-200 rounded-xl p-5 flex items-center gap-4 hover:border-[#FFC222] transition-colors group">
            <div className="flex-shrink-0">
              <svg className="w-10 h-10 text-[#FFC222]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="m9 12 2 2 4-4"/>
              </svg>
            </div>
            <div>
              <h3 className="text-[15px] font-black text-[#1E1D23] uppercase tracking-tight leading-none mb-1">Best Quality Guarantee</h3>
              <p className="text-gray-500 text-[12px] font-medium leading-tight">Look through our new taste and enjoy with best quality.</p>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
};

export default Menu;
