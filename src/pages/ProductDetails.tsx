import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { getProductById, getProductBySlug, getProducts } from '../api/products';
import { Product } from '../types';
import ProductCard from '../components/ProductCard';
import { useCartStore } from '../store/useCartStore';
import { Plus, Minus, Heart, Maximize2, ShoppingBasket, ChevronUp, ChevronDown } from 'lucide-react';

const ProductDetails: React.FC = () => {
  const { id, slug } = useParams<{ id?: string, slug?: string }>();
  const navigate = useNavigate();
  const { items, addItem, updateQuantity, setCartOpen, setHighlightedProduct } = useCartStore();
  const [product, setProduct] = useState<Product | null>(null);
  const [relatedProducts, setRelatedProducts] = useState<Product[]>([]);
  const [selectedWeight, setSelectedWeight] = useState<string>('');
  const [selectedImage, setSelectedImage] = useState<string>('');
  const [loading, setLoading] = useState(true);
  const [thumbnailOffset, setThumbnailOffset] = useState(0);
  const [zoom, setZoom] = useState({ x: 0, y: 0, active: false });

  const handleMouseMove = (e: React.MouseEvent<HTMLDivElement>) => {
    const { left, top, width, height } = e.currentTarget.getBoundingClientRect();
    const x = ((e.pageX - left - window.scrollX) / width) * 100;
    const y = ((e.pageY - top - window.scrollY) / height) * 100;
    setZoom({ x, y, active: true });
  };

  const handleMouseLeave = () => {
    setZoom({ ...zoom, active: false });
  };

  const scrollThumbnails = (direction: 'up' | 'down') => {
    const step = 1;
    if (direction === 'up' && thumbnailOffset > 0) {
      setThumbnailOffset(prev => prev - step);
    } else if (direction === 'down' && product?.images && thumbnailOffset < product.images.length - 4) {
      setThumbnailOffset(prev => prev + step);
    }
  };

  const parsePrice = (val: any, fallback: number) => {
    if (val === undefined || val === null || val === '') return fallback;
    const parsed = parseFloat(String(val).replace(/[^\d.]/g, ''));
    return isNaN(parsed) ? fallback : parsed;
  };

  const weightPrices: Record<string, { current: string, original: string }> = product ? {
    '1 Kilogram': { 
      current: parsePrice(product.price_1kg, 250).toFixed(2), 
      original: (parsePrice(product.price_1kg, 250) * 1.2).toFixed(2) 
    },
    '1/2 Kg': { 
      current: parsePrice(product.price_500g, 125).toFixed(2), 
      original: (parsePrice(product.price_500g, 125) * 1.6).toFixed(2) 
    },
    '1/4 Kg': { 
      current: parsePrice(product.price_250g, 65).toFixed(2), 
      original: (parsePrice(product.price_250g, 65) * 1.5385).toFixed(2) 
    }
  } : {
    '1 Kilogram': { current: '250.00', original: '300.00' },
    '1/2 Kg': { current: '125.00', original: '200.00' },
    '1/4 Kg': { current: '65.00', original: '100.00' }
  };

  const isCombo = (product?.category || '').toLowerCase().includes('combo') || (product?.name || '').toLowerCase().includes('combo');
  
  // Auto-calculate range if it's missing or just a number for non-combos
  const getDisplayPriceRange = () => {
    if (!product) return '';
    if (isCombo) {
      const p = String(product.price).replace('₹', '').trim();
      const val = parseFloat(p);
      return isNaN(val) ? product.price : `₹${val.toFixed(2)}`;
    }
    
    let priceStr = String(product.price);
    // If it doesn't look like a range, construct it from weights
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
          {formatted}
          {i < arr.length - 1 && ' – '}
        </React.Fragment>
      );
    });
  };
  const [activeTab, setActiveTab] = useState<'additional' | 'reviews'>('additional');
  const [weightError, setWeightError] = useState(false);

  const cartItem = items.find(item => item.id === (product?.id || 0) && (!isCombo ? item.selectedWeight === selectedWeight : true));
  const cartQuantity = cartItem ? cartItem.quantity : 0;
  
  // Local state for quantity selector, defaults to 1 if not in cart
  const [displayQuantity, setDisplayQuantity] = useState(1);

  useEffect(() => {
    if (cartQuantity > 0) {
      setDisplayQuantity(cartQuantity);
    } else {
      setDisplayQuantity(1);
    }
  }, [cartQuantity, selectedWeight]);

  const handleAddToCart = () => {
    if (product) {
      if (!isCombo && !selectedWeight) {
        setWeightError(true);
        const element = document.getElementById('kilogram-selector');
        element?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      setWeightError(false);
      const currentPriceVal = !isCombo && selectedWeight 
        ? weightPrices[selectedWeight].current
        : parsePrice(String(product.price).split(/\s*[–-]\s*/)[0], 0).toFixed(2);

      const productWithWeight = !isCombo && selectedWeight 
        ? { ...product, price: `₹${currentPriceVal}`, selectedWeight } 
        : product;

      // Sync display quantity to cart
      if (cartQuantity === 0) {
        addItem(productWithWeight, displayQuantity);
      } else {
        updateQuantity(product.id, displayQuantity, selectedWeight);
      }
      
      setHighlightedProduct(product.id);
      setCartOpen(true); // Show total selection in cart
    }
  };

  const handleIncrement = () => {
    setDisplayQuantity(prev => prev + 1);
  };

  const handleDecrement = () => {
    if (displayQuantity > 1) {
      setDisplayQuantity(prev => prev - 1);
    } else if (displayQuantity === 1 && cartQuantity > 0) {
      // If user decrements to 0 and it was in cart, remove it
      updateQuantity(product!.id, 0, selectedWeight);
      setDisplayQuantity(1);
    }
  };

  useEffect(() => {
    const identifier = slug || id;
    if (identifier) {
      setLoading(true);
      const isActuallySlug = isNaN(Number(identifier));
      const fetchProduct = isActuallySlug ? getProductBySlug(identifier) : getProductById(Number(identifier));

      fetchProduct
        .then(p => {
          setProduct(p);
          setSelectedImage(p.image);
          // Fetch random related products
          getProducts().then(allProducts => {
            const others = allProducts.filter(item => item.id !== p.id);
            // Shuffle and pick 4
            const shuffled = others.sort(() => 0.5 - Math.random());
            setRelatedProducts(shuffled.slice(0, 4));
          });
        })
        .catch(console.error)
        .finally(() => setLoading(false));
    }
  }, [id, slug]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-yellow-500"></div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <h2 className="text-2xl font-bold mb-4">Product Not Found</h2>
          <button 
            onClick={() => navigate('/shop')}
            className="text-yellow-500 font-bold hover:underline"
          >
            Go back to Shop
          </button>
        </div>
      </div>
    );
  }

  return (
    <main className="py-10 sm:py-20 px-4 sm:px-8 bg-white min-h-[calc(100vh-80px)]">
      <div className="max-w-5xl mx-auto">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 sm:gap-12 mb-20">
          {/* Product Gallery */}
          <div className="flex flex-col sm:flex-row gap-4 sm:gap-6 items-center sm:items-start h-full order-1">
            {/* Horizontal/Vertical Thumbnail Strip */}
            <div className="flex flex-row sm:flex-col items-center gap-2 sm:gap-4 order-2 sm:order-1 w-full sm:w-auto overflow-x-auto sm:overflow-visible pb-4 sm:pb-0 scrollbar-hide">
              <div className="flex flex-row sm:flex-col gap-2 sm:gap-4 sm:max-h-[500px]">
                {product.images?.slice(thumbnailOffset, thumbnailOffset + 5).map((img, idx) => (
                  <button
                    key={idx}
                    onClick={() => setSelectedImage(img)}
                    className={`w-14 h-14 xs:w-16 xs:h-16 sm:w-24 sm:h-24 flex-shrink-0 rounded-xl sm:rounded-2xl overflow-hidden border-2 transition-all duration-300 p-1 bg-white active:scale-95 ${
                      selectedImage === img ? 'border-[#FFC222] shadow-md' : 'border-gray-100'
                    }`}
                  >
                    <div className="w-full h-full bg-[#F7F2E2] rounded-lg sm:rounded-xl overflow-hidden">
                      <img src={img} alt={`${product.name} thumbnail ${idx}`} className="w-full h-full object-contain" />
                    </div>
                  </button>
                ))}
              </div>

              <div className="hidden sm:flex flex-col gap-2">
                <button 
                  onClick={() => scrollThumbnails('up')}
                  className={`w-10 h-10 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-[#FFC222] hover:border-[#FFC222] hover:text-white transition-all ${thumbnailOffset === 0 ? 'opacity-20 cursor-not-allowed' : 'text-gray-400'}`}
                  disabled={thumbnailOffset === 0}
                >
                  <ChevronUp className="w-4 h-4" />
                </button>
                <button 
                  onClick={() => scrollThumbnails('down')}
                  className={`w-10 h-10 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-[#FFC222] hover:border-[#FFC222] hover:text-white transition-all ${!product.images || thumbnailOffset >= product.images.length - 4 ? 'opacity-20 cursor-not-allowed' : 'text-gray-400'}`}
                  disabled={!product.images || thumbnailOffset >= product.images.length - 4}
                >
                  <ChevronDown className="w-4 h-4" />
                </button>
              </div>
            </div>

            {/* Main Product Image with Zoom */}
            <div 
              className="relative w-full aspect-square sm:w-[450px] sm:h-[450px] bg-[#F7F2E2] rounded-[24px] sm:rounded-[40px] overflow-hidden group border-[6px] sm:border-[12px] border-white shadow-xl sm:shadow-2xl cursor-zoom-in flex-shrink-0 order-1 sm:order-2"
              onMouseMove={handleMouseMove}
              onMouseLeave={handleMouseLeave}
            >
              {/* Yellow Traveling Overlay */}
              <div className="absolute inset-0 z-0 bg-[#FFC222] translate-y-full group-hover:animate-sweep-up pointer-events-none"></div>

              <div className="absolute top-4 left-4 sm:top-6 sm:left-6 z-30 pointer-events-none">
                <span className="bg-[#1E1D23] text-white text-[10px] sm:text-[12px] font-black px-3 sm:px-4 py-1 sm:py-1.5 rounded-full uppercase tracking-widest shadow-xl">Sale!</span>
              </div>
              <button className="absolute top-4 right-4 sm:top-6 sm:right-6 z-30 bg-white p-2 sm:p-3 rounded-full shadow-xl hover:bg-[#FFC222] hover:text-white transition-all duration-300 transform hover:scale-110">
                <Maximize2 className="w-4 h-4 sm:w-5 sm:h-5" />
              </button>
              <img 
                src={selectedImage} 
                alt={product.name} 
                className={`w-full h-full object-contain relative z-10 transition-transform duration-200 pointer-events-none ${zoom.active ? '' : 'group-hover:scale-105'}`}
                style={{ 
                  transformOrigin: `${zoom.x}% ${zoom.y}%`, 
                  transform: zoom.active ? 'scale(2)' : 'scale(1)' 
                }}
              />
            </div>
          </div>

          {/* Product Info */}
          <div className="flex flex-col order-2">
            <h1 className="text-2xl xs:text-3xl sm:text-[40px] font-black text-[#111111] mb-4 sm:mb-6 leading-[1.1]">{product.name}</h1>
            
            {/* Product Meta (Description) */}
            <div className="mb-4 sm:mb-6">
              {product.description ? (
                <div 
                  className="text-[#666666] text-sm sm:text-[15px] description-content leading-relaxed"
                  dangerouslySetInnerHTML={{ __html: product.description }}
                />
              ) : (
                <ul className="space-y-2 text-[#666666] text-sm sm:text-[15px]">
                  <li className="flex items-start gap-3">
                    <span className="w-1 h-1 bg-[#666666] rounded-full mt-2 sm:mt-2.5 flex-shrink-0"></span>
                    <span>100 % Traditional <strong className="text-gray-900">Hand made</strong> Appalam, Papad / Papadum prepared from selected quality Urad Dhal from specific region of India.</span>
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="w-1 h-1 bg-[#666666] rounded-full mt-2 sm:mt-2.5 flex-shrink-0"></span>
                    <span>Crispy & Tasty, Hygienically made.</span>
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="w-1 h-1 bg-[#666666] rounded-full mt-2 sm:mt-2.5 flex-shrink-0"></span>
                    <span>No preservative and machinery used.</span>
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="w-1 h-1 bg-[#666666] rounded-full mt-2 sm:mt-2.5 flex-shrink-0"></span>
                    <span>100% quality tested food product.</span>
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="w-1 h-1 bg-[#666666] rounded-full mt-2 sm:mt-2.5 flex-shrink-0"></span>
                    <span>That's the authentic South Indian Papad.</span>
                  </li>
                </ul>
              )}
            </div>

            <p className="text-[#666666] mb-4 sm:mb-6 text-sm sm:text-[15px] leading-relaxed max-w-xl italic">
              Products will be provided based on customer requirements (Dimensions, weights, packing materials, boxes)
            </p>

            <div className="flex flex-col mb-8 pt-4 border-t border-gray-100">
              {/* Price Range / Selected Price */}
              <div className="flex flex-col mb-4">
                <div className="flex items-center gap-4">
                  {isCombo ? (
                    <>
                      <span className="text-[22px] sm:text-[32px] font-black text-[#FFC222] whitespace-nowrap">{String(product.price).startsWith('₹') ? product.price : `₹${product.price}`}</span>
                      <span className="text-xl text-gray-400 line-through font-bold whitespace-nowrap">₹{(parsePrice(String(product.price).split(/\s*[–-]\s*/)[0], 0) * 1.2).toFixed(2)}</span>
                    </>
                  ) : selectedWeight ? (
                    <>
                      <span className="text-[22px] sm:text-[32px] font-black text-[#FFC222] whitespace-nowrap">₹{weightPrices[selectedWeight].current}</span>
                      <span className="text-xl text-gray-400 line-through font-bold whitespace-nowrap">₹{weightPrices[selectedWeight].original}</span>
                    </>
                  ) : (
                    <span className="text-[22px] sm:text-[32px] font-black text-[#FFC222] whitespace-nowrap">
                      {getDisplayPriceRange()}
                    </span>
                  )}
                </div>
              </div>

              {!isCombo && (
                <div id="kilogram-selector" className="mb-0">
                  <label className="block text-[11px] font-bold text-gray-400 mb-3 uppercase tracking-widest">Kilogram</label>
                  <div className="flex flex-wrap gap-2">
                    {Object.keys(weightPrices).map((weight) => (
                      <button
                        key={weight}
                        onClick={() => {
                          setSelectedWeight(weight);
                          setWeightError(false);
                        }}
                        className={`px-5 py-2 rounded-lg border-2 font-black text-sm transition-all ${
                          selectedWeight === weight
                            ? 'border-[#FFC222] bg-white text-[#FFC222]'
                            : 'border-gray-50 text-gray-400 hover:border-[#FFC222]/50 bg-gray-50'
                        }`}
                      >
                        {weight}
                      </button>
                    ))}
                  </div>
                </div>
              )}
            </div>

            {/* Cart Actions */}
            <div className="space-y-4 mb-10 pt-4">
              {weightError && (
                <div className="bg-red-50 text-red-500 p-4 rounded-xl text-sm font-bold animate-shake">
                  Please select a weight before adding to cart
                </div>
              )}
              <div className="flex flex-wrap items-center gap-3 sm:gap-4">
                <div className="flex items-center bg-[#F5F5F5] rounded-lg px-2 py-1 h-[52px]">
                  <button 
                    onClick={handleDecrement} 
                    className="w-8 sm:w-10 h-full flex items-center justify-center text-gray-500 hover:text-black transition-colors"
                  >
                    <Minus className="w-3 h-3" />
                  </button>
                  <span className="w-8 sm:w-10 text-center font-bold text-lg text-[#1E1D23]">{displayQuantity}</span>
                  <button 
                    onClick={handleIncrement} 
                    className="w-8 sm:w-10 h-full flex items-center justify-center text-gray-500 hover:text-black transition-colors"
                  >
                    <Plus className="w-3 h-3" />
                  </button>
                </div>

                <button 
                  onClick={handleAddToCart}
                  className="flex-grow min-w-[140px] bg-[#FFC222] text-gray-900 font-black h-[52px] rounded-lg shadow-sm hover:bg-black hover:text-white transition-all flex items-center justify-center gap-2 sm:gap-3 uppercase tracking-tighter text-xs sm:text-sm"
                >
                  <ShoppingBasket className="w-4 h-4 sm:w-5 h-5" />
                  ADD TO CART
                </button>

                <button className="w-[52px] h-[52px] bg-[#F5F5F5] rounded-lg flex items-center justify-center text-gray-400 hover:text-red-500 transition-all group">
                  <Heart className="w-5 h-5 fill-current transition-colors" />
                </button>
              </div>
            </div>

          {/* Meta */}
          <div className="space-y-2 pt-8 text-sm mt-auto">
            <div className="flex gap-2">
              <span className="font-bold text-gray-900">SKU:</span>
              <span className="text-gray-500 uppercase">{product.sku || 'N/A'}</span>
            </div>
            <div className="flex gap-2">
              <span className="font-bold text-gray-900">Categories:</span>
              <span className="text-gray-500 uppercase">{product.category}</span>
            </div>
          </div>
        </div>
      </div>

        {/* Tabs Section */}
        <div className="mb-20">
          <div className="flex justify-center gap-6 sm:gap-12 border-b border-gray-100 mb-8 sm:mb-12">
            <button 
              onClick={() => setActiveTab('additional')}
              className={`pb-4 text-sm sm:text-2xl font-black transition-all relative ${
                activeTab === 'additional' ? 'text-[#FFC222]' : 'text-gray-400 hover:text-gray-600'
              }`}
            >
              Additional information
              {activeTab === 'additional' && <div className="absolute bottom-0 left-0 right-0 h-1 bg-[#FFC222] rounded-full"></div>}
            </button>
            <button 
              onClick={() => setActiveTab('reviews')}
              className={`pb-4 text-sm sm:text-2xl font-black transition-all relative ${
                activeTab === 'reviews' ? 'text-[#FFC222]' : 'text-gray-400 hover:text-gray-600'
              }`}
            >
              Reviews (0)
              {activeTab === 'reviews' && <div className="absolute bottom-0 left-0 right-0 h-1 bg-[#FFC222] rounded-full"></div>}
            </button>
          </div>

          <div className="bg-gray-50 rounded-[20px] sm:rounded-[32px] p-5 sm:p-12">
            {activeTab === 'additional' ? (
              <div className="space-y-4">
                <div className="flex flex-col sm:flex-row sm:items-center border-b border-gray-200 pb-4 last:border-0">
                  <span className="font-black text-[#1E1D23] uppercase tracking-widest text-[10px] sm:text-sm w-full sm:w-1/3 mb-1 sm:mb-0">Kilogram</span>
                  <span className="text-gray-600 font-medium text-xs sm:text-base">
                    {isCombo ? 'N/A' : Object.keys(weightPrices).join(', ')}
                  </span>
                </div>
              </div>
            ) : (
              <div className="text-center text-gray-500 py-8 sm:py-12 text-sm">
                There are no reviews yet.
              </div>
            )}
          </div>
        </div>

        {/* Related Products */}
        <div className="mt-20">
          <h2 className="text-3xl sm:text-4xl font-black text-center mb-8 sm:mb-12 uppercase">Related Products</h2>
          <div className="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-8">
            {relatedProducts.map(rp => (
              <ProductCard 
                key={rp.id}
                product={rp}
                onSelect={() => {
                  navigate(`/product/${rp.slug || rp.id}`);
                  window.scrollTo({ top: 0, behavior: 'smooth' });
                }}
              />
            ))}
          </div>
        </div>
      </div>
    </main>
  );
};

export default ProductDetails;
