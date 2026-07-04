import React from 'react';
import { Product } from '../types';
import { ShoppingBasket, Heart } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

interface ProductCardProps {
  product: Product;
  onSelect?: (product: Product) => void;
  onAddToCart?: (product: Product) => void;
}

const ProductCard: React.FC<ProductCardProps> = ({ product, onSelect }) => {
  const navigate = useNavigate();
  
  const handleSelect = () => {
    if (onSelect) {
      onSelect(product);
    } else {
      const identifier = product.slug || product.id;
      navigate(`/product/${identifier}`);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const isCombo = (product.category || '').toLowerCase().includes('combo') || (product.name || '').toLowerCase().includes('combo');

  const stripHtml = (html: string) => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    return doc.body.textContent || "";
  };

  const getDisplayPriceRange = () => {
    if (isCombo) {
      const p = String(product.price).replace('₹', '').trim();
      const val = parseFloat(p);
      return isNaN(val) ? product.price : `₹${val.toFixed(2)}`;
    }
    
    let priceStr = String(product.price);
    if (!priceStr.includes('-') && !priceStr.includes('–')) {
      const min = product.price_250g || '65.00';
      const max = product.price_1kg || '250.00';
      priceStr = `${min} – ${max}`;
    }
    
    return priceStr.split(/\s*[–-]\s*/).map((part, i, arr) => {
      const p = part.trim().replace('₹', '').trim();
      const val = parseFloat(p);
      const formatted = isNaN(val) ? part.trim() : `₹${val.toFixed(2)}`;
      return (
        <React.Fragment key={i}>
          <span className="whitespace-nowrap">{formatted}</span>
          {i < arr.length - 1 && <span className="mx-1 opacity-50">–</span>}
        </React.Fragment>
      );
    });
  };

  return (
    <div className="group bg-white rounded-[24px] sm:rounded-[32px] p-3 sm:p-6 transition-all duration-500 hover:shadow-2xl border border-transparent hover:border-gray-100 animate-scale-in">
      {/* Image Container Wrapper */}
      <div className="relative aspect-square mb-3 sm:mb-6 flex items-center justify-center">
        {/* Base decorative background */}
        <div className="absolute inset-0 bg-[#FFF9E5] rounded-[24px] sm:rounded-[32px] scale-95 opacity-50 group-hover:scale-100 group-hover:opacity-100 transition-all duration-700"></div>
        
        {/* Main Image Container */}
        <div className="relative w-[85%] h-[85%] rounded-[16px] sm:rounded-[24px] overflow-hidden bg-[#8C715A] group-hover:w-full group-hover:h-full group-hover:rounded-[24px] sm:group-hover:rounded-[32px] transition-all duration-700 ease-in-out shadow-lg group-hover:shadow-xl">
          {/* Yellow Traveling Overlay */}
          <div className="absolute inset-0 z-0 bg-[#FFC222] translate-y-full group-hover:animate-sweep-up pointer-events-none"></div>
          
          {/* Sale Badge */}
          <div className="absolute top-2 left-2 sm:top-3 sm:left-3 z-30 transition-transform duration-500 group-hover:scale-110">
            <span className="bg-[#1E1D23] text-white text-[8px] sm:text-[9px] font-black px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-full uppercase tracking-widest">
              Sale!
            </span>
          </div>
          
          <img 
            src={product.image} 
            alt={product.name} 
            className="w-full h-full object-cover relative z-10 transition-transform duration-700 group-hover:scale-110 cursor-pointer"
            onClick={handleSelect}
          />
        </div>

        {/* Wishlist Icon */}
        <button className="absolute top-2 right-2 z-40 text-gray-300 hover:text-red-500 hover:scale-125 transition-all duration-300 transform">
          <Heart className="w-4 h-4 sm:w-5 sm:h-5 fill-current" />
        </button>
      </div>

      {/* Content */}
      <div className="space-y-1 sm:space-y-3 transition-transform duration-500 group-hover:-translate-y-1">
        <h3 
          className="text-sm sm:text-[17px] font-black text-[#1E1D23] hover:text-[#FFC222] cursor-pointer transition-colors line-clamp-2 leading-tight"
          onClick={handleSelect}
        >
          {product.name}
        </h3>
        
        <p className="text-[11px] sm:text-[13px] text-gray-600 font-medium line-clamp-2 leading-relaxed opacity-90 group-hover:opacity-100 transition-opacity">
          {product.description ? stripHtml(product.description) : "100 % Traditional Hand made Appalam, Papad / Papadum prepared from selected quality..."}
        </p>

        <div className="flex flex-row items-center justify-between pt-1 sm:pt-2 gap-1">
          <div className="flex flex-row items-center min-w-0">
            <span className="text-[#FFC222] font-black text-[10px] xs:text-[13px] sm:text-[20px] leading-tight truncate">
              {getDisplayPriceRange()}
            </span>
          </div>
          
          <div className="flex-shrink-0">
            <button 
              onClick={handleSelect}
              className="w-7 h-7 xs:w-8 xs:h-8 sm:w-12 sm:h-12 bg-[#FFC222] text-gray-900 rounded-full shadow-lg hover:bg-black hover:text-white transition-all duration-300 flex items-center justify-center transform active:scale-95"
            >
              <ShoppingBasket className="w-3 h-3 xs:w-3.5 xs:h-3.5 sm:w-5 sm:h-5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProductCard;
