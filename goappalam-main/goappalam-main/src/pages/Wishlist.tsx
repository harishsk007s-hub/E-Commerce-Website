import React from 'react';
import { useNavigate } from 'react-router-dom';

const Wishlist: React.FC = () => {
  const navigate = useNavigate();

  return (
    <main className="min-h-screen bg-white">
      {/* Banner */}
      <section className="relative h-[300px] flex items-center justify-center bg-[#F7F2E2] overflow-hidden">
        <div className="absolute inset-0 opacity-10">
          <img 
            src="wp-content/themes/poco/assets/images/background/bg-shop.png" 
            alt="" 
            className="w-full h-full object-cover"
            onError={(e) => {
              e.currentTarget.style.display = 'none';
            }}
          />
        </div>
        <div className="relative text-center z-10">
          <h1 className="text-5xl font-black text-[#1E1D23] mb-4">Wishlist</h1>
          <div className="flex items-center justify-center gap-2 text-sm font-bold uppercase tracking-wider text-gray-500">
            <span className="hover:text-[#FFC222] cursor-pointer" onClick={() => navigate('/')}>Home</span>
            <span>&gt;</span>
            <span className="text-gray-900">Wishlist</span>
          </div>
        </div>
      </section>

      <section className="py-24 px-8 max-w-5xl mx-auto text-center">
        <div className="py-20 border-2 border-dashed border-gray-100 rounded-[48px] bg-gray-50/50">
          <p className="text-gray-400 font-medium text-lg italic">There are no products on the Wishlist!</p>
        </div>
      </section>
    </main>
  );
};

export default Wishlist;
