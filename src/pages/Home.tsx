import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { getProducts } from '../api/products';
import { Product } from '../types';
import ProductCard from '../components/ProductCard';

const Home: React.FC = () => {
  const navigate = useNavigate();
  const [products, setProducts] = useState<Product[]>([]);
  const [activeTab, setActiveTab] = useState('ALL');
  const [loading, setLoading] = useState(true);

  const dynamicCategories = Array.from(new Set(products.map(p => p.category).filter(Boolean))).sort() as string[];
  const tabs = ['ALL', 'PAPADAM', ...dynamicCategories.filter(cat => cat && cat.toUpperCase() !== 'PAPADAM' && cat.toUpperCase() !== 'COMBO'), 'COMBO'];

  const categories = [
    { name: 'PAPADAM', image: '/assests/uploads/2021/07/Papadam.png', splash: '#FFC222' },
    { name: 'CHILI PEPPER', image: '/assests/uploads/2021/07/Black peper.png', splash: '#FFC222' },
    { name: 'RING', image: '/assests/uploads/2021/07/Ring.png', splash: '#FFC222' },
    { name: 'CUMIN', image: '/assests/uploads/2021/07/Cumin.png', splash: '#FFC222' },
    { name: 'BLACK PEPPER', image: '/assests/uploads/2021/07/Black peper.png', splash: '#FFC222' },
    { name: 'SOVI', image: '/assests/uploads/2021/07/Sovi.png', splash: '#FFC222' }
  ];

  useEffect(() => {
    getProducts()
      .then(setProducts)
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  const filteredProducts = products.filter(p => {
    if (activeTab === 'ALL') return true;
    
    const category = (p.category || '').toUpperCase();
    const tab = activeTab.toUpperCase();
    
    if (tab === 'PAPADAM') {
      return category === 'PAPADAM' || (category !== 'COMBO' && category !== '');
    }
    if (tab === 'COMBO') {
      return category === 'COMBO';
    }
    return category === tab;
  }).slice(0, 3);

  return (
    <main className="bg-white overflow-hidden">
      {/* Hero Section (Desktop) */}
      <section className="hidden lg:flex relative w-full h-[100dvh] min-h-[600px] items-center animate-fade-in">
        <div 
          className="absolute inset-0 bg-cover bg-center z-0 scale-105" 
          style={{ backgroundImage: "url('/assests/uploads/2021/07/HOMEPAGE.jpg')" }}
        />
        <div className="absolute inset-0 bg-black/20 z-10" />
        
        {/* Entrance text for Hero */}
        <div className="relative z-20 max-w-5xl mx-auto px-4 sm:px-8 w-full">
           <div className="animate-fade-in-up">

             <h1 className="text-white text-4xl sm:text-5xl md:text-8xl font-black leading-[1.1] uppercase drop-shadow-2xl">
              <br className="hidden sm:block" />
              <br className="hidden sm:block" />
              <br className="hidden sm:block" />
              <br className="hidden sm:block" />
             </h1>
             
           </div>
        </div>
      </section>

      {/* Mobile Banner Section (lg:hidden) */}
      <section className="lg:hidden bg-white pt-4">
        <div className="px-4">
          <div className="relative rounded-2xl overflow-hidden aspect-[16/9] sm:aspect-[2.5/1] shadow-lg">
            <img 
              src="/assests/uploads/2021/07/HOMEPAGE.jpg" 
              alt="Go Appalam Banner" 
              className="w-full h-full object-cover"
            />
          </div>
        </div>
      </section>

      {/* Categories Menu */}
      <section className="pt-8 pb-12 sm:py-20 bg-white">
        <div className="max-w-5xl mx-auto px-4 text-center">
          <div className="mb-8 sm:mb-16 animate-fade-in-up">
            <button 
              onClick={() => navigate('/menu')}
              className="bg-[#FFC222] text-gray-900 font-black px-8 sm:px-10 py-3 rounded-xl text-xs sm:text-sm uppercase tracking-widest hover:bg-gray-900 hover:text-white transition-all hover:scale-110 shadow-lg shadow-[#FFC222]/20"
            >
              MENU
            </button>
          </div>
          <div className="flex flex-wrap justify-center gap-3 sm:gap-6 md:gap-10 max-w-5xl mx-auto">
            {categories.map((cat, idx) => (
              <div 
                key={idx} 
                onClick={() => {
                  const targetTab = cat.name.toUpperCase();
                  if (tabs.includes(targetTab)) {
                    setActiveTab(targetTab);
                    document.getElementById('popular-dishes')?.scrollIntoView({ behavior: 'smooth' });
                  } else {
                    navigate(`/shop?category=${cat.name}`);
                  }
                }}
                className="flex flex-col items-center group cursor-pointer animate-fade-in-up w-[30%] sm:w-[15%] md:w-[12%] min-w-[70px]"
                style={{ animationDelay: `${idx * 100}ms` }}
              >
                <div className="relative w-14 h-14 sm:w-20 sm:h-20 md:w-28 md:h-28 mb-3 sm:mb-6 group-hover:scale-105 transition-transform duration-500 ease-out">
                  {/* Highly visible organic splash rays on hover */}
                  <div className="absolute inset-[-40%] pointer-events-none opacity-0 group-hover:opacity-100 transition-all duration-500 scale-75 group-hover:scale-100 z-0 hidden sm:block">
                    <svg viewBox="0 0 200 200" className="w-full h-full filter drop-shadow-[0_0_8px_rgba(0,0,0,0.1)]" style={{ fill: cat.splash || '#FFC222' }}>
                      {/* Left expressive rays */}
                      <path d="M55,60 Q30,45 5,50 Q25,65 55,75 Z" />
                      <path d="M45,100 Q10,100 0,110 Q10,120 45,120 Z" />
                      <path d="M55,140 Q30,155 5,170 Q25,160 55,150 Z" />
                      
                      {/* Right expressive rays */}
                      <path d="M145,60 Q170,45 195,50 Q175,65 145,75 Z" />
                      <path d="M155,100 Q190,100 200,110 Q190,120 155,120 Z" />
                      <path d="M145,140 Q170,155 195,170 Q175,160 145,150 Z" />
                    </svg>
                  </div>

                  <div className="relative w-full h-full rounded-full bg-white overflow-hidden border-[3px] sm:border-[6px] border-white group-hover:border-[#FFC222] transition-all duration-500 p-0 shadow-[0_5px_15px_rgba(0,0,0,0.15)] group-hover:shadow-2xl z-10">
                    <img src={cat.image} alt={cat.name} className="w-full h-full object-contain rounded-full bg-white" />
                  </div>
                </div>
                <p className="text-[10px] sm:text-[12px] font-black text-gray-800 tracking-tighter uppercase whitespace-nowrap group-hover:text-[#FFC222] transition-colors">{cat.name}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* About Our Food Section */}
      <section className="py-16 sm:py-24 bg-[#F9F7E8] relative overflow-hidden">
        <div className="max-w-5xl mx-auto px-6 sm:px-8 flex flex-col md:flex-row items-center gap-10 sm:gap-20">
          <div className="w-full md:w-1/2 relative">
            <div className="animate-fade-in">
              <img src="/assests/uploads/2021/07/About.png" alt="About Go Appalam" className="w-full max-w-[400px] mx-auto md:max-w-none object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-500" />
            </div>
          </div>
          <div className="w-full md:w-1/2 animate-fade-in-up text-center md:text-left">
            <h3 className="text-[#FFC222] font-cursive text-3xl sm:text-4xl mb-4 sm:mb-6 italic">About Our Food</h3>
            <h2 className="text-3xl sm:text-5xl md:text-6xl font-black text-gray-900 mb-6 sm:mb-8 leading-[1.1] uppercase tracking-tighter">From Taste with Crisp<br />and Crunch Love</h2>
            <p className="text-gray-500 mb-8 sm:mb-10 text-base sm:text-lg leading-relaxed max-w-xl font-medium mx-auto md:mx-0">
              What is more needed than traditional one? We are here to take back to a traditional food still prevailing with the same crisp crunch and taste. And we had added extra flavoring agents like pepper, cumin, chilli to make your food turn into treat of delight.
            </p>
            <button 
              onClick={() => navigate('/shop')}
              className="bg-[#FFC222] text-gray-900 font-black px-10 sm:px-12 py-4 sm:py-5 rounded-2xl hover:bg-gray-900 hover:text-white transition-all hover:scale-105 shadow-xl shadow-[#FFC222]/30 uppercase text-xs sm:text-sm tracking-widest"
            >
              ORDER NOW
            </button>
          </div>
        </div>
      </section>

      {/* Popular Dishes / Combo Tabs Section */}
      <section id="popular-dishes" className="py-16 sm:py-20 bg-white">
        <div className="max-w-5xl mx-auto px-4 text-center">
          <h2 className="text-3xl sm:text-4xl font-black mb-8 sm:mb-12 uppercase tracking-tight animate-fade-in-up">Popular Dishes</h2>
          <div className="flex flex-wrap justify-center gap-2 sm:gap-4 mb-10 sm:mb-16 animate-fade-in-up animate-delay-200">
            {tabs.map((tab) => (
              <button 
                key={tab}
                onClick={() => setActiveTab(tab)}
                className={`px-4 sm:px-8 py-2 sm:py-3 rounded-full text-[16px] font-black uppercase transition-all hover:scale-105 ${
                  activeTab === tab 
                    ? 'bg-[#FFC222] text-gray-900 shadow-lg shadow-[#FFC222]/30' 
                    : 'bg-gray-50 text-gray-500 border border-gray-100 hover:bg-white hover:shadow-md'
                }`}
              >
                {tab}
              </button>
            ))}
          </div>
          
          <div className="grid grid-cols-2 md:grid-cols-3 gap-4 sm:gap-10 min-h-[400px]">
            {loading ? (
              <div className="col-span-full flex justify-center py-20">
                <div className="animate-spin rounded-full h-12 w-12 border-4 border-[#FFC222] border-t-transparent"></div>
              </div>
            ) : filteredProducts.length > 0 ? (
              filteredProducts.map((product) => (
                <ProductCard 
                  key={product.id}
                  product={product}
                  onSelect={() => navigate(`/product/${product.slug || product.id}`)}
                />
              ))
            ) : (
              <div className="col-span-full py-20">
                <p className="text-gray-400 font-bold uppercase tracking-widest text-sm">No products found in this category.</p>
              </div>
            )}
          </div>
          
          <div className="mt-12 sm:mt-20">
            <button 
              onClick={() => navigate('/shop')}
              className="bg-[#1E1D23] text-white font-black px-10 sm:px-12 py-4 sm:py-5 rounded-2xl text-xs sm:text-sm uppercase tracking-widest hover:bg-[#FFC222] hover:text-[#1E1D23] transition-all hover:scale-105 shadow-xl shadow-black/10"
            >
              ALL PRODUCTS
            </button>
          </div>
        </div>
      </section>

      {/* Overall Banner */}
      <section className="w-full">
        <img 
          src="/assests/uploads/2021/Contact/Product banner.png" 
          alt="Go Appalam Banner" 
          className="w-full h-full object-cover" 
        />
      </section>

      {/* Testimonials Section */}
      <section className="relative py-24 overflow-hidden" style={{ backgroundColor: '#F7F2E2' }}>
        <div className="max-w-5xl mx-auto px-4 text-center relative z-20">
          
          <h2 
            className="text-gray-900 mb-16 animate-fade-in-up font-bold tracking-tight"
            style={{ fontFamily: "'Gilroy', sans-serif", fontSize: '36px' }}
          >
            What our client says
          </h2>
          <div className="relative overflow-hidden mt-8">
            <div className="flex animate-marquee pause-on-hover gap-8">
              {[
                { name: 'RANI', job: 'Teacher', text: 'Tried out the special cumin appalam which now became my favorite snack and an all-time side dish to my foods, a perfect flavor.' },
                { name: 'JEEVA', job: '', text: 'Your appalam takes back us to the tradition way which our grandmother prepares we are getting reminder of here every time.' },
                { name: 'PRAVIN MATHIALAGAN', job: 'Photographer', text: 'Experienced a best crunchy and crisp appalam from Go appalam the taste was authentic and was very much healthier.' }
              ].concat([
                { name: 'RANI', job: 'Teacher', text: 'Tried out the special cumin appalam which now became my favorite snack and an all-time side dish to my foods, a perfect flavor.' },
                { name: 'JEEVA', job: '', text: 'Your appalam takes back us to the tradition way which our grandmother prepares we are getting reminder of here every time.' },
                { name: 'PRAVIN MATHIALAGAN', job: 'Photographer', text: 'Experienced a best crunchy and crisp appalam from Go appalam the taste was authentic and was very much healthier.' }
              ]).map((t, idx) => (
                <div 
                  key={idx} 
                  className="bg-white p-6 sm:p-10 rounded-[30px] sm:rounded-[40px] shadow-sm border border-gray-100 relative group hover:shadow-2xl transition-all duration-500 w-[280px] sm:w-[400px] flex-shrink-0 cursor-pointer"
                >
                  <div className="flex items-center gap-4 sm:gap-6 mb-6 sm:mb-8">
                    <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 border-4 border-gray-50 group-hover:border-[#FFC222] transition-colors">
                      <img src={`/assests/uploads/2021/08/${(idx % 3) + 2}.jpg`} alt={t.name} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                    </div>
                    <div className="text-left">
                      <h4 className="font-black text-xs sm:text-sm tracking-widest uppercase">{t.name}</h4>
                      <p className="text-[10px] sm:text-xs text-gray-400 font-bold">{t.job}</p>
                      <div className="flex text-[#FFC222] mt-1 sm:mt-2 gap-0.5">
                        {[...Array(5)].map((_, i) => <i key={i} className="fa fa-star text-[8px] sm:text-[10px]">★</i>)}
                      </div>
                    </div>
                    <div className="absolute top-6 right-6 sm:top-12 sm:right-12 text-[#FFC222] opacity-10 group-hover:opacity-100 transition-all duration-500 transform group-hover:rotate-12">
                      <svg className="w-6 h-6 sm:w-10 sm:h-10 fill-current" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                    </div>
                  </div>
                  <p className="text-gray-500 leading-relaxed text-left italic font-medium text-sm sm:text-base">"{t.text}"</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Promotional Grid Section */}
      <section className="grid grid-cols-1 md:grid-cols-3">
        {[
          { label: 'Traditional food for', title: 'Meals', subtitle: 'SPICE CHILLI PAPADAM', color: '#E41D20', img: '/assests/uploads/2021/07/6flavour-chil.png' },
          { label: 'Home', title: 'Made', subtitle: 'SPICE CUMIN PAPADAM', color: '#9D6527', img: '/assests/uploads/2021/07/6flavour-cumin.png' },
          { label: 'Prepared', title: 'Freshly', subtitle: 'BLACK PEPPER PAPADAM', color: '#1E1D23', img: '/assests/uploads/2021/07/6flavour-pep.png' }
        ].map((item, idx) => (
          <div key={idx} className="relative p-8 sm:p-12 h-[500px] sm:h-[600px] flex flex-col justify-center overflow-hidden group cursor-pointer" style={{ backgroundColor: item.color }} onClick={() => navigate('/shop')}>
            
            {/* Background Decorative Circles */}
            <div className="absolute inset-0 pointer-events-none">
              {/* Outlined circles at top */}
              <div className="absolute top-[8%] right-[22%] w-14 h-14 border-2 border-white/30 rounded-full opacity-0 group-hover:opacity-100 group-hover:scale-110 transition-all duration-700"></div>
              <div className="absolute top-[12%] right-[12%] w-24 h-24 border-2 border-white/30 rounded-full opacity-0 group-hover:opacity-100 group-hover:scale-105 transition-all duration-1000"></div>
              
              {/* Solid white circle behind/near title as seen in screenshot */}
              <div className="absolute top-[18%] left-[18%] w-28 h-28 bg-white rounded-full opacity-0 group-hover:opacity-100 transition-all duration-700"></div>
              
              {/* Squiggly line */}
              <div className="absolute top-[38%] left-[32%] w-32 h-12 opacity-0 group-hover:opacity-80 transition-all duration-700">
                <svg className="w-full h-full text-white" viewBox="0 0 100 20" fill="none">
                  <path d="M0 10 C 10 0, 20 0, 30 10 C 40 20, 50 20, 60 10 C 70 0, 80 0, 90 10" stroke="currentColor" strokeWidth="3" fill="none" />
                </svg>
              </div>
            </div>

            <div className="relative z-20 group-hover:-translate-y-2 transition-transform duration-500">
              <h4 className="text-white text-2xl sm:text-4xl font-cursive mb-0 drop-shadow-md">{item.label}</h4>
              <h2 className="text-[12vw] sm:text-[10vw] md:text-[8vw] lg:text-[85px] font-black uppercase mb-8 leading-[0.8] tracking-tighter drop-shadow-xl text-[#FFC222] whitespace-nowrap overflow-hidden text-ellipsis" style={{ fontSize: 'clamp(2.5rem, 12vw, 85px)' }}>{item.title}</h2>
              <p className="text-white uppercase text-[11px] sm:text-[13px] font-bold tracking-[0.1em] mb-8 drop-shadow-sm max-w-[180px] sm:max-w-none">{item.subtitle}</p>
              <p className="text-[#FFC222] font-black text-3xl sm:text-4xl md:text-6xl mb-12 tracking-tighter drop-shadow-lg">₹250.00</p>
              <button 
                onClick={(e) => { e.stopPropagation(); navigate('/shop'); }}
                className="bg-white text-gray-900 font-bold px-6 sm:px-10 py-2.5 sm:py-4 rounded-xl text-[11px] sm:text-[13px] uppercase tracking-widest hover:bg-gray-900 hover:text-white transition-all shadow-2xl"
              >
                ORDER NOW
              </button>
            </div>
            <img 
              src={item.img} 
              alt="" 
              className="absolute top-[20%] right-[-40%] w-[90%] object-contain transition-all duration-1000 group-hover:scale-110 group-hover:-rotate-6 opacity-90 group-hover:opacity-100 z-10" 
            />
          </div>
        ))}
      </section>
    </main>
  );
};

export default Home;
