import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getProducts } from '../api/products';
import { Product } from '../types';
import ProductCard from '../components/ProductCard';
import { LayoutGrid, List, Search, Settings2, X, ChevronDown } from 'lucide-react';

const Shop: React.FC = () => {
  const navigate = useNavigate();
  const [products, setProducts] = useState<Product[]>([]);
  const [filteredProducts, setFilteredProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [sortBy, setSortBy] = useState('default');
  const [maxPrice, setMaxPrice] = useState(500);
  const [priceRange, setPriceRange] = useState([0, 500]);
  const [isMobileFilterOpen, setIsMobileFilterOpen] = useState(false);

  const dynamicCategories = Array.from(new Set(products.map(p => p.category).filter(Boolean))).sort();
  const categories = [
    { name: 'PAPADAM' },
    ...dynamicCategories.filter(cat => cat && cat.toUpperCase() !== 'PAPADAM' && cat.toUpperCase() !== 'COMBO').map(cat => ({
      name: cat
    })),
    { name: 'COMBO' }
  ];

  useEffect(() => {
    getProducts()
      .then((data) => {
        setProducts(data);
        if (data.length > 0) {
          const prices = data.map(p => {
            const match = String(p.price).match(/\d+(\.\d+)?/);
            return match ? parseFloat(match[0]) : 0;
          });
          const maxP = Math.max(...prices, 500);
          setMaxPrice(maxP);
          setPriceRange([0, maxP]);
        }
      })
      .catch((err) => {
        console.error("API Error:", err);
        setProducts([]);
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    let result = [...products];
    if (selectedCategory) {
      const selected = selectedCategory.toUpperCase();
      result = result.filter(p => {
        const category = (p.category || '').toUpperCase();
        
        if (selected === 'PAPADAM') {
          return category === 'PAPADAM' || (category !== 'COMBO' && category !== '');
        }
        if (selected === 'COMBO') {
          return category === 'COMBO';
        }
        return category === selected;
      });
    }
    if (searchQuery) {
      result = result.filter(p => p.name.toLowerCase().includes(searchQuery.toLowerCase()));
    }
    
    // Price filter
    result = result.filter(p => {
      const match = String(p.price).match(/\d+(\.\d+)?/);
      const price = match ? parseFloat(match[0]) : 0;
      return price >= priceRange[0] && price <= priceRange[1];
    });

    // Sorting
    if (sortBy === 'price-low') {
      result.sort((a, b) => {
        const ma = String(a.price).match(/\d+(\.\d+)?/);
        const mb = String(b.price).match(/\d+(\.\d+)?/);
        return (ma ? parseFloat(ma[0]) : 0) - (mb ? parseFloat(mb[0]) : 0);
      });
    } else if (sortBy === 'price-high') {
      result.sort((a, b) => {
        const ma = String(a.price).match(/\d+(\.\d+)?/);
        const mb = String(b.price).match(/\d+(\.\d+)?/);
        return (mb ? parseFloat(mb[0]) : 0) - (ma ? parseFloat(ma[0]) : 0);
      });
    } else if (sortBy === 'name') {
      result.sort((a, b) => a.name.localeCompare(b.name));
    }

    setFilteredProducts(result);
  }, [selectedCategory, searchQuery, products, sortBy, priceRange]);

  const FilterContent = ({ isMobile = false }: { isMobile?: boolean }) => (
    <div className="space-y-6 sm:space-y-10">
      {/* Categories */}
      <div className={`bg-[#FFF9E5] rounded-[24px] sm:rounded-[32px] ${isMobile ? 'p-5 sm:p-8' : 'p-8'} border border-gray-100/50`}>
        <h3 className="text-lg sm:text-xl font-black text-[#1E1D23] mb-6 sm:mb-8 border-b border-[#FFC222]/20 pb-4">Categories</h3>
        <ul className="space-y-4 sm:space-y-5">
          <li 
            onClick={() => {
              setSelectedCategory(null);
              setIsMobileFilterOpen(false);
            }}
            className="flex items-center justify-between group cursor-pointer border-b border-gray-200/30 pb-3"
          >
            <span className={`font-bold text-[14px] sm:text-[15px] transition-colors ${!selectedCategory ? 'text-[#FFC222]' : 'text-gray-700 group-hover:text-[#FFC222]'}`}>All Products</span>
          </li>
          {categories.map((cat) => (
            <li 
              key={cat.name} 
              onClick={() => {
                setSelectedCategory(cat.name);
                setIsMobileFilterOpen(false);
              }}
              className="flex items-center justify-between group cursor-pointer border-b border-gray-200/30 pb-3 last:border-0 last:pb-0"
            >
              <span className={`font-bold text-[14px] sm:text-[15px] transition-colors ${selectedCategory === cat.name ? 'text-[#FFC222]' : 'text-gray-700 group-hover:text-[#FFC222]'}`}>
                {cat.name}
              </span>
            </li>
          ))}
        </ul>
      </div>

      {/* Search */}
      <div className="relative group">
        <input 
          type="text" 
          placeholder="Search products..." 
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="w-full bg-white border-2 border-[#FFF9E5] rounded-xl sm:rounded-2xl py-3 sm:py-4 pl-4 sm:pl-6 pr-12 sm:pr-14 font-medium outline-none focus:border-[#FFC222] transition-all shadow-sm text-sm sm:text-base"
        />
        <button className="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 bg-[#1E1D23] text-white p-2 rounded-lg sm:rounded-xl group-focus-within:bg-[#FFC222] transition-colors shadow-lg">
          <Search className="w-4 h-4 sm:w-5 h-5" />
        </button>
      </div>

      {/* Filter by Price */}
      <div className={`bg-white border-2 border-[#FFF9E5] rounded-[24px] sm:rounded-[32px] ${isMobile ? 'p-5 sm:p-8' : 'p-8'} shadow-sm`}>
        <h3 className="text-lg sm:text-xl font-black text-[#1E1D23] mb-6 sm:mb-8 border-b border-[#FFC222]/20 pb-4">Filter by price</h3>
        <div className="space-y-6 sm:space-y-8">
          <div className="h-1.5 bg-gray-100 rounded-full relative mx-2">
            <div 
              className="absolute inset-y-0 bg-[#FFC222] rounded-full"
              style={{ 
                left: `${(priceRange[0] / maxPrice) * 100}%`, 
                right: `${100 - (priceRange[1] / maxPrice) * 100}%` 
              }}
            ></div>
            
            {/* Min Slider */}
            <input 
              type="range" 
              min="0" 
              max={maxPrice} 
              value={priceRange[0]}
              onChange={(e) => {
                const val = Math.min(parseInt(e.target.value), priceRange[1] - 10);
                setPriceRange([val, priceRange[1]]);
              }}
              className="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20 pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto"
            />
            
            {/* Max Slider */}
            <input 
              type="range" 
              min="0" 
              max={maxPrice} 
              value={priceRange[1]}
              onChange={(e) => {
                const val = Math.max(parseInt(e.target.value), priceRange[0] + 10);
                setPriceRange([priceRange[0], val]);
              }}
              className="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20 pointer-events-none [&::-webkit-slider-thumb]:pointer-events-auto"
            />

            <div 
              className="absolute -top-1.5 w-4 h-4 bg-[#FFC222] rounded-full shadow-md z-10 border-2 border-white"
              style={{ left: `${(priceRange[0] / maxPrice) * 100}%`, transform: 'translateX(-50%)' }}
            ></div>
            <div 
              className="absolute -top-1.5 w-4 h-4 bg-[#FFC222] rounded-full shadow-md z-10 border-2 border-white"
              style={{ left: `${(priceRange[1] / maxPrice) * 100}%`, transform: 'translateX(-50%)' }}
            ></div>
          </div>
          <div className="flex flex-col gap-4">
            <p className="text-xs sm:text-sm font-bold text-gray-400">Price: <span className="text-gray-900 ml-1">₹{priceRange[0]} – ₹{priceRange[1]}</span></p>
            <button 
              onClick={() => {
                setPriceRange([priceRange[0], priceRange[1]]);
                setIsMobileFilterOpen(false);
              }}
              className="w-full bg-[#FFC222] text-[#1E1D23] px-6 py-3 rounded-xl font-black text-[11px] sm:text-xs uppercase tracking-widest hover:bg-[#1E1D23] hover:text-white transition-all shadow-lg shadow-[#FFC222]/20"
            >
              Filter
            </button>
          </div>
        </div>
      </div>

      {/* Best Deals */}
      <div className="space-y-8">
        <h3 className="text-xl font-black text-[#1E1D23] border-b border-[#FFC222]/20 pb-4">Best Deals</h3>
        <div className="space-y-6">
          <div 
            onClick={() => {
              navigate('/product/chilli-pepper-cumin-papad-1kg');
              setIsMobileFilterOpen(false);
            }}
            className="flex gap-4 group cursor-pointer bg-gray-50 p-4 rounded-2xl hover:bg-white hover:shadow-xl transition-all duration-300 border border-transparent hover:border-gray-100"
          >
            <div className="w-16 h-16 bg-white rounded-xl overflow-hidden flex-shrink-0">
              <img src="/assests/uploads/2021/12/C3-450x450.jpg" alt="" className="w-full h-full object-cover group-hover:scale-110 transition-transform" />
            </div>
            <div>
              <h4 className="font-bold text-[#1E1D23] group-hover:text-[#FFC222] transition-colors text-sm leading-tight mb-2">Chilli Pepper and Cumin Papad 1Kg</h4>
              <p className="text-[#FFC222] font-black text-sm">₹300.00</p>
            </div>
          </div>
          <div 
            onClick={() => {
              navigate('/product/black-pepper-cumin-papad-1kg');
              setIsMobileFilterOpen(false);
            }}
            className="flex gap-4 group cursor-pointer bg-gray-50 p-4 rounded-2xl hover:bg-white hover:shadow-xl transition-all duration-300 border border-transparent hover:border-gray-100"
          >
            <div className="w-16 h-16 bg-white rounded-xl overflow-hidden flex-shrink-0">
              <img src="/assests/uploads/2021/12/C2-450x450.jpg" alt="" className="w-full h-full object-cover group-hover:scale-110 transition-transform" />
            </div>
            <div>
              <h4 className="font-bold text-[#1E1D23] group-hover:text-[#FFC222] transition-colors text-sm leading-tight mb-2">Black Pepper and Cumin Papad 1Kg</h4>
              <p className="text-[#FFC222] font-black text-sm">₹300.00</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  return (
    <main className="min-h-screen bg-white">
      {/* Mobile Filter Drawer */}
      {isMobileFilterOpen && (
        <div className="fixed inset-0 z-[100] lg:hidden animate-in fade-in duration-300">
          <div 
            className="absolute inset-0 bg-black/60 backdrop-blur-sm" 
            onClick={() => setIsMobileFilterOpen(false)}
          ></div>
          <div className="absolute top-0 right-0 h-full w-[280px] sm:w-[350px] bg-white shadow-2xl p-6 overflow-y-auto animate-in slide-in-from-right duration-300">
            <div className="flex justify-between items-center mb-8">
              <h2 className="text-xl font-black uppercase tracking-tight">Filter Options</h2>
              <button 
                onClick={() => setIsMobileFilterOpen(false)}
                className="p-2 text-gray-400 hover:text-[#FFC222] transition-colors"
              >
                <X className="w-6 h-6" />
              </button>
            </div>
            <FilterContent isMobile={true} />
          </div>
        </div>
      )}
      {/* Banner */}
      <section className="relative h-[150px] sm:h-[250px] flex items-center justify-center overflow-hidden">
        <div 
          className="absolute inset-0 bg-cover bg-center" 
          style={{ backgroundImage: "url('/assests/uploads/2021/07/Banner-Overall-scaled.jpg')" }}
        />
        <div className="relative text-center z-10">
          <h1 className="text-4xl sm:text-6xl font-black text-[#1E1D23] mb-2 sm:mb-4">Shop</h1>
          <div className="flex items-center justify-center gap-2 text-xs sm:text-sm font-bold text-gray-500">
            <span className="hover:text-[#FFC222] cursor-pointer transition-colors" onClick={() => navigate('/')}>Home</span>
            <span className="text-gray-400 font-normal mx-1">&gt;</span>
            <span className="text-gray-900">Shop</span>
          </div>
        </div>
      </section>

      <div className="max-w-5xl mx-auto px-4 sm:px-8 py-10 sm:py-20 flex flex-col lg:flex-row gap-8 sm:gap-12 animate-fade-in-up">
        {/* Main Content */}
        <div className="flex-grow order-2 lg:order-1">
          {/* Controls */}
          <div className="flex flex-row justify-between items-center mb-6 sm:mb-10 gap-2 sm:gap-6 w-full">
            <button 
              onClick={() => setIsMobileFilterOpen(true)}
              className="lg:hidden flex items-center gap-2 font-black text-[13px] uppercase tracking-widest text-[#1E1D23] hover:text-[#FFC222] transition-colors"
            >
              <Settings2 className="w-4 h-4" />
              Filter
            </button>

            <p className="hidden sm:block text-gray-400 font-medium text-sm sm:text-base">Showing all {filteredProducts.length} results</p>
            
            <div className="flex items-center gap-2 sm:gap-6">
              <div className="hidden sm:flex items-center gap-2 border-r border-gray-100 pr-6">
                <LayoutGrid className="w-5 h-5 text-[#1E1D23] cursor-pointer" />
                <List className="w-5 h-5 text-gray-300 hover:text-[#1E1D23] cursor-pointer" />
              </div>
              <div className="relative group min-w-[160px] sm:min-w-[200px]">
                <select 
                  value={sortBy}
                  onChange={(e) => setSortBy(e.target.value)}
                  className="appearance-none w-full bg-[#FFF9E5] border-none rounded-xl px-4 sm:px-6 py-2.5 sm:py-3.5 font-bold text-[13px] sm:text-sm text-gray-700 outline-none cursor-pointer transition-all pr-10 hover:bg-[#FFF2CC]"
                >
                  <option value="default">Default sorting</option>
                  <option value="popularity">Sort by popularity</option>
                  <option value="rating">Sort by average rating</option>
                  <option value="latest">Sort by latest</option>
                  <option value="price-low">Sort by price: low to high</option>
                  <option value="price-high">Sort by price: high to low</option>
                  <option value="name">Sort by name</option>
                </select>
                <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                  <ChevronDown className="w-4 h-4 text-gray-400 group-hover:text-gray-900 transition-colors" />
                </div>
              </div>
            </div>
          </div>

          {/* Grid */}
          {loading ? (
            <div className="flex justify-center py-20">
              <div className="animate-spin rounded-full h-12 w-12 border-4 border-[#FFC222] border-t-transparent"></div>
            </div>
          ) : (
            <div className="grid grid-cols-2 md:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-8">
              {filteredProducts.map(product => (
                <ProductCard 
                  key={product.id} 
                  product={product} 
                  onSelect={() => navigate(`/product/${product.slug || product.id}`)}
                />
              ))}
            </div>
          )}
        </div>

        {/* Sidebar */}
        <aside className="lg:w-[350px] flex-shrink-0 hidden lg:block order-1 lg:order-2">
          <FilterContent />
        </aside>
      </div>
    </main>
  );
};

export default Shop;
