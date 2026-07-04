import React, { useState } from 'react';
import { Play, X } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

const AnimatedImage = ({ src, alt, className }: { src: string, alt: string, className?: string }) => {
  const [inView, setInView] = useState(false);
  const ref = React.useRef<HTMLImageElement>(null);

  React.useEffect(() => {
    const observer = new IntersectionObserver(([entry]) => {
      if (entry.isIntersecting) {
        setInView(true);
      }
    }, { threshold: 0.3 });
    if (ref.current) observer.observe(ref.current);
    return () => observer.disconnect();
  }, []);

  return (
    <img 
      ref={ref}
      src={src} 
      alt={alt} 
      className={`${className} transition-all duration-[1200ms] ease-out ${inView ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-125 translate-y-12'} hover:!scale-105 hover:!-translate-y-2 hover:shadow-2xl cursor-pointer`} 
    />
  );
};

const About: React.FC = () => {
  const navigate = useNavigate();
  const [showVideo, setShowVideo] = useState(false);
  return (
    <main className="min-h-screen bg-white">
      {/* Video Modal */}
      {showVideo && (
        <div 
          className="fixed inset-0 z-[100] bg-black/90 flex items-center justify-center p-4 sm:p-8 animate-in fade-in duration-300"
          onClick={() => setShowVideo(false)}
        >
          <div className="relative w-full max-w-4xl aspect-video bg-black rounded-2xl overflow-hidden shadow-2xl">
            <button 
              onClick={() => setShowVideo(false)}
              className="absolute top-4 right-4 z-10 p-2 bg-white/10 hover:bg-white/20 text-white rounded-full transition-colors"
            >
              <X className="w-6 h-6" />
            </button>
            <iframe 
              src="https://www.youtube.com/embed/toy_aEZlkPI?autoplay=1" 
              title="GO APPALAM" 
              className="w-full h-full border-none"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
              allowFullScreen
            ></iframe>
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
          <h1 className="text-4xl sm:text-6xl font-black text-[#1E1D23] mb-2 sm:mb-4">About Us</h1>
          <div className="flex items-center justify-center gap-2 text-xs sm:text-sm font-bold text-gray-500">
            <span 
              onClick={() => navigate('/')}
              className="hover:text-[#FFC222] cursor-pointer transition-colors"
            >
              Home
            </span>
            <span className="text-gray-400 font-normal mx-1">&gt;</span>
            <span className="text-gray-900">About us</span>
          </div>
        </div>
      </section>

      {/* Welcome Section */}
      <section className="py-12 sm:py-24 px-4 sm:px-8 max-w-5xl mx-auto animate-fade-in-up">
        <div className="flex flex-col md:flex-row items-center gap-10 sm:gap-16">
          <div className="w-full md:w-1/2 text-center md:text-left">
            <p className="text-[#FFC222] font-normal text-[32px] sm:text-[42px] mb-6 sm:mb-[42px]" style={{ fontFamily: 'Norican, sans-serif' }}>Welcome!</p>
            <h2 className="text-3xl sm:text-5xl font-black text-[#1E1D23] leading-tight mb-6 sm:mb-8">
              Best papadam with traditional touch and taste
            </h2>
            <p className="text-gray-500 text-base sm:text-lg leading-relaxed mb-6 sm:mb-10 font-medium">
              We are here providing a best pappadam with unique taste with best quality of spice added with it. And is purely handmade, we also believe firmly the quality and hygienic is maintained during the manufacture.
            </p>
            <p className="text-gray-500 text-base sm:text-lg leading-relaxed mb-8 sm:mb-10 font-medium">
              The taste of pappadam holds the authenticity in the taste of the origin in south Indian cuisine and it will act as both snacks and as well as a side dish.
            </p>
            <button 
              onClick={() => navigate('/contact')}
              className="bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white px-8 sm:px-10 py-3 sm:py-4 rounded-xl font-black transition-all shadow-xl uppercase tracking-wider text-sm sm:text-base"
            >
              Contact Now
            </button>
          </div>
          <div className="w-full md:w-1/2 flex flex-col items-center justify-center">
            <img 
              src="wp-content/uploads/2021/07/ICON-01-1-500x560.png"
              alt="Go Appalam Logo" 
              className="w-full max-w-[300px] sm:max-w-[500px] h-auto"
            />
          </div>
        </div>
      </section>  

      {/* Video Section */}
      <section className="relative min-h-[500px] sm:min-h-[650px] bg-cover bg-center flex items-center" style={{ backgroundImage: "url('/assests/uploads/2021/04/ROUND-APPALAM-L.jpg')" }}>
        <div className="absolute inset-0 bg-black/50" />
        <div className="relative z-10 w-full text-white text-center">
          <div className="max-w-4xl mx-auto px-4 sm:px-8">
            <div 
              onClick={() => setShowVideo(true)}
              className="relative w-20 h-20 sm:w-24 sm:h-24 bg-[#FFC222] rounded-full flex items-center justify-center mx-auto mb-8 sm:mb-12 cursor-pointer hover:scale-110 transition-all duration-300 shadow-[0_0_40px_rgba(255,194,34,0.3)] group"
            >
              {/* Outer Pulse Rings */}
              <div className="absolute inset-[-8px] sm:inset-[-12px] border-2 border-white/20 rounded-full group-hover:scale-110 transition-transform duration-500" />
              <div className="absolute inset-[-16px] sm:inset-[-24px] border border-white/10 rounded-full group-hover:scale-125 transition-transform duration-700" />
              
              <Play className="w-8 h-8 sm:w-10 sm:h-10 fill-white text-white translate-x-1" />
            </div>
            <h2 className="text-4xl sm:text-6xl font-black mb-6 tracking-tight leading-tight">Our Quality- Our Spicy Specials</h2>
            <p className="text-xl sm:text-2xl font-bold mb-10 opacity-90 italic">Our gratitude – We owe you</p>
            <button 
              onClick={() => navigate('/shop')}
              className="bg-[#FFC222] hover:bg-white text-[#1E1D23] px-12 py-4 rounded-xl font-black transition-all shadow-2xl shadow-[#FFC222]/20 uppercase tracking-[0.2em] text-sm"
            >
              Order Now
            </button>
          </div>
        </div>
      </section>

      {/* Specialties */}
      <section className="py-16 sm:py-24 px-4 sm:px-8 max-w-5xl mx-auto space-y-20 sm:space-y-32">
        {/* Chilli */}
        <div className="flex flex-col md:flex-row items-center gap-10 sm:gap-16">
          <div className="w-full md:w-1/2 order-2 md:order-1 text-center md:text-left">
            <p className="text-[#FFC222] font-normal text-[20px] sm:text-[25px] mb-4 sm:mb-[25px]" style={{ fontFamily: 'Norican, sans-serif' }}>Our Specalities</p>
            <h2 className="text-3xl sm:text-4xl font-black text-[#1E1D23] mb-6 sm:mb-8">Chilli Papadam</h2>
            <p className="text-gray-500 text-base sm:text-lg leading-relaxed mb-8 sm:mb-10 font-medium">
              Chilli popular in international markets, Indian chillies comes in variety of textures and spice levels we have induced it with our appalam to bring you out the best flavors to the crisp and crunchy snack you wish to take from us. It is only handmade pappadam made in the traditional method.
            </p>
            <button 
              onClick={() => navigate('/shop')}
              className="bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white px-8 sm:px-10 py-3 sm:py-4 rounded-xl font-black transition-all shadow-xl uppercase tracking-wider text-sm sm:text-base"
            >
              Order Now
            </button>
          </div>
          <div className="w-full md:w-1/2 order-1 md:order-2">
            <AnimatedImage src="wp-content/uploads/2021/08/aboutchill.png" alt="Chilli Papadam" className="w-full h-auto rounded-3xl" />
          </div>
        </div>

        {/* Pepper */}
        <div className="flex flex-col md:flex-row items-center gap-10 sm:gap-16">
          <div className="w-full md:w-1/2">
            <AnimatedImage src="wp-content/uploads/2021/08/pepper.png" alt="Pepper Papadam" className="w-full h-auto rounded-3xl" />
          </div>
          <div className="w-full md:w-1/2 text-center md:text-left">
            <p className="text-[#FFC222] font-normal text-[25px] mb-[25px]" style={{ fontFamily: 'Norican, sans-serif' }}>Our Specalities</p>
            <h2 className="text-3xl sm:text-4xl font-black text-[#1E1D23] mb-6 sm:mb-8">Pepper Papadam</h2>
            <p className="text-gray-500 text-base sm:text-lg leading-relaxed mb-8 sm:mb-10 font-medium">
              Pepper the “king of spices” with most beneficial value is used by us to make your appalam a different taste with touch of our south Indian tradition with the authenticity carried out. Don’t miss the crunch.
            </p>
            <div className="flex justify-center md:justify-start">
              <button 
                onClick={() => navigate('/shop')}
                className="bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white px-8 sm:px-10 py-3 sm:py-4 rounded-xl font-black transition-all shadow-xl uppercase tracking-wider text-sm sm:text-base"
              >
                Order Now
              </button>
            </div>
          </div>
        </div>

        {/* Cumin */}
        <div className="flex flex-col md:flex-row items-center gap-10 sm:gap-16">
          <div className="w-full md:w-1/2 order-2 md:order-1 text-center md:text-left">
            <p className="text-[#FFC222] font-normal text-[25px] mb-[25px]" style={{ fontFamily: 'Norican, sans-serif' }}>Our Specalities</p>
            <h2 className="text-3xl sm:text-4xl font-black text-[#1E1D23] mb-6 sm:mb-8">Cumin Papadam</h2>
            <p className="text-gray-500 text-base sm:text-lg leading-relaxed mb-8 sm:mb-10 font-medium">
              Cumin also named as jeera has more beneficial values in health and we had also carried out with our unique appalam with this flavored combined. And it is rich in antioxidant which helps to cure cold so it provides greater values to all ages with the perfect flavor.
            </p>
            <button 
              onClick={() => navigate('/shop')}
              className="bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white px-8 sm:px-10 py-3 sm:py-4 rounded-xl font-black transition-all shadow-xl uppercase tracking-wider text-sm sm:text-base"
            >
              Order Now
            </button>
          </div>
          <div className="w-full md:w-1/2 order-1 md:order-2">
            <AnimatedImage src="wp-content/uploads/2021/08/cumin.png" alt="Cumin Papadam" className="w-full h-auto rounded-3xl" />
          </div>
        </div>
      </section>

      {/* Delivery Section */}
      <section className="relative py-16 sm:py-24 px-4 sm:px-8 mt-12 sm:mt-24 bg-cover bg-center" style={{ backgroundImage: "url('/wp-content/uploads/2021/04/cumin-round-big-1.jpg')" }}>
        <div className="max-w-5xl mx-auto flex flex-col md:flex-row items-center gap-10 sm:gap-16">
          <div className="w-full md:w-1/2 text-center md:text-left relative z-10">
            <p className="text-[#FFC222] font-normal text-[32px] mb-[32px]" style={{ fontFamily: 'Norican, sans-serif' }}>We guarantee</p>
            <h2 className="text-4xl sm:text-6xl font-black text-white mb-6 sm:mb-8">Three Days Delivery!</h2>
            <p className="text-gray-400 text-lg sm:text-xl font-medium mb-8 sm:mb-12">Do not wait, ring us soon to get your favourite flavoured pappadam from us to be delivered.</p>
            <button 
              onClick={() => navigate('/shop')}
              className="bg-[#FFC222] hover:bg-white text-[#1E1D23] px-8 sm:px-10 py-3 sm:py-4 rounded-xl font-black transition-all shadow-xl uppercase tracking-wider text-sm sm:text-base"
            >
              Make An Order
            </button>
          </div>
          <div className="w-full md:w-1/2" />
        </div>
      </section>
    </main>
  );
};

export default About;
