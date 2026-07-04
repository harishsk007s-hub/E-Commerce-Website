import React from 'react';
import { X, ShoppingBag } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useCartStore } from '../store/useCartStore';

interface CartSidebarProps {
  isOpen: boolean;
  onClose: () => void;
}

const CartSidebar: React.FC<CartSidebarProps> = ({ isOpen, onClose }) => {
  const navigate = useNavigate();
  const { items, removeItem, updateQuantity, highlightedProductId, setHighlightedProduct } = useCartStore();
  
  React.useEffect(() => {
    if (isOpen && highlightedProductId) {
      const timer = setTimeout(() => {
        setHighlightedProduct(null);
      }, 2000);
      return () => clearTimeout(timer);
    }
  }, [isOpen, highlightedProductId, setHighlightedProduct]);

  const handleCheckout = () => {
    onClose();
    navigate('/checkout');
  };

  const totalPrice = items.reduce((acc, item) => {
    const priceStr = String(item.price).replace(/[^\d.-]/g, '').split('-')[0];
    const price = parseFloat(priceStr) || 0;
    return acc + (price * item.quantity);
  }, 0);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-[100] animate-in fade-in duration-300">
      {/* Overlay */}
      <div 
        className="absolute inset-0 bg-black/60 backdrop-blur-sm"
        onClick={onClose}
      ></div>

      {/* Sidebar */}
      <div 
        className="absolute top-0 right-0 h-full w-full max-w-md bg-white shadow-2xl animate-in slide-in-from-right duration-500 ease-in-out"
      >
        <div className="flex flex-col h-full">
          {/* Header */}
          <div className="flex items-center justify-between p-5 sm:p-8 border-b border-gray-100">
            <h2 className="text-lg sm:text-xl font-black text-[#1E1D23] uppercase tracking-wider">Shopping Cart</h2>
            <button 
              onClick={onClose}
              className="p-2 -mr-2 text-gray-400 hover:text-[#FFC222] transition-colors"
            >
              <X className="w-6 h-6 sm:w-5 sm:h-5" />
            </button>
          </div>

          {/* Content */}
          <div className="flex-grow overflow-y-auto p-4 sm:p-8 custom-scrollbar">
            {items.length === 0 ? (
              <div className="h-full flex flex-col items-center justify-center text-center">
                <div className="w-16 h-16 sm:w-20 sm:h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                  <ShoppingBag className="w-6 h-6 sm:w-8 sm:h-8 text-gray-200" />
                </div>
                <p className="text-gray-400 font-medium italic">No products in the cart.</p>
              </div>
            ) : (
              <div className="space-y-4 sm:space-y-8">
                {items.map((item, index) => (
                  <div 
                    key={`${item.id}-${item.selectedWeight || ''}-${index}`} 
                    className={`flex gap-4 sm:gap-6 group p-3 sm:p-4 rounded-xl sm:rounded-2xl transition-all duration-500 ${
                      item.id === highlightedProductId 
                        ? 'bg-[#FFC222]/10 border-2 border-[#FFC222] scale-[1.02] sm:scale-[1.05] shadow-lg' 
                        : 'border-2 border-transparent hover:bg-gray-50'
                    }`}
                  >
                    <div className="w-20 h-20 sm:w-24 sm:h-24 bg-gray-50 rounded-xl sm:rounded-2xl overflow-hidden flex-shrink-0 shadow-sm group-hover:shadow-md transition-shadow">
                      <img src={item.image} alt={item.name} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                    </div>
                    <div className="flex-grow pt-1">
                      <div className="flex justify-between items-start mb-1 sm:mb-2">
                        <h4 className="font-bold text-sm sm:text-base text-[#1E1D23] leading-tight hover:text-[#FFC222] cursor-pointer transition-colors">{item.name}</h4>
                        <button 
                          onClick={() => removeItem(item.id, item.selectedWeight)}
                          className="text-gray-300 hover:text-red-500 transition-colors ml-2"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      </div>
                      {item.selectedWeight && (
                        <p className="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{item.selectedWeight}</p>
                      )}
                      <p className="text-[#FFC222] font-black mb-2 sm:mb-4 text-sm sm:text-base">
                        {item.quantity} × <span className="text-gray-900">₹{(parseFloat(String(item.price).replace(/[^\d.-]/g, '').split('-')[0]) || 0).toFixed(2)}</span>
                      </p>
                      <div className="flex items-center gap-3">
                        <button 
                          onClick={() => updateQuantity(item.id, Math.max(1, item.quantity - 1), item.selectedWeight)}
                          className="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-gray-100 text-[#1E1D23] font-bold flex items-center justify-center hover:bg-[#FFC222] hover:text-white transition-all text-sm"
                        >
                          -
                        </button>
                        <span className="font-black text-xs sm:text-sm">{item.quantity}</span>
                        <button 
                          onClick={() => updateQuantity(item.id, item.quantity + 1, item.selectedWeight)}
                          className="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-gray-100 text-[#1E1D23] font-bold flex items-center justify-center hover:bg-[#FFC222] hover:text-white transition-all text-sm"
                        >
                          +
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Footer */}
          {items.length > 0 && (
            <div className="p-6 sm:p-8 border-t border-gray-100 space-y-4 pointer-events-auto">
              <div className="flex items-center justify-between mb-4">
                <span className="text-gray-400 font-bold uppercase text-[10px] sm:text-xs tracking-widest">Subtotal:</span>
                <span className="text-[#FFC222] text-xl sm:text-2xl font-black">
                  ₹{totalPrice.toFixed(2)}
                </span>
              </div>
              <button 
                onClick={handleCheckout}
                className="w-full bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white py-4 sm:py-5 rounded-xl sm:rounded-2xl font-black transition-all shadow-xl shadow-[#FFC222]/30 uppercase tracking-widest text-xs sm:text-sm flex items-center justify-center gap-2"
              >
                Checkout
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default CartSidebar;
