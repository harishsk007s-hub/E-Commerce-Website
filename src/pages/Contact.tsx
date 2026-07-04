import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Mail, Phone, MapPin, Loader2 } from 'lucide-react';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useToastStore } from '../store/useToastStore';
import { submitContactForm } from '../api/contact';

// Fix for default marker icons in react-leaflet using CDN links
const DefaultIcon = L.icon({
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

L.Marker.prototype.options.icon = DefaultIcon;

const Contact: React.FC = () => {
  const navigate = useNavigate();
  const position: [number, number] = [9.9135, 78.1182]; // Madurai coordinates
  
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    subject: '',
    comment: ''
  });

  const { addToast } = useToastStore();

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.name || !formData.email || !formData.comment) {
      addToast('Please fill in all required fields', 'error');
      return;
    }

    setLoading(true);
    try {
      const response = await submitContactForm(formData);
      if (response.status === 'success') {
        addToast(response.message || 'Message sent successfully!', 'success');
        setFormData({
          name: '',
          email: '',
          subject: '',
          comment: ''
        });
      } else {
        addToast(response.error || 'Failed to send message', 'error');
      }
    } catch (error: any) {
      addToast(error.message || 'An error occurred. Please try again.', 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="min-h-screen bg-white">
      {/* Banner */}
      <section className="relative h-[150px] sm:h-[250px] flex items-center justify-center overflow-hidden">
        <div 
          className="absolute inset-0 bg-cover bg-center" 
          style={{ backgroundImage: "url('/assests/uploads/2021/07/Banner-Overall-scaled.jpg')" }}
        />
        <div className="relative text-center z-10 px-4">
          <h1 className="text-4xl sm:text-6xl font-black text-[#1E1D23] mb-2 sm:mb-4">Contact Us</h1>
          <div className="flex items-center justify-center gap-2 text-xs sm:text-sm font-bold text-gray-500">
            <span 
              onClick={() => navigate('/')}
              className="hover:text-[#FFC222] cursor-pointer transition-colors"
            >
              Home
            </span>
            <span className="text-gray-400 font-normal mx-1">&gt;</span>
            <span className="text-gray-900">Contact us</span>
          </div>
        </div>
      </section>

      {/* Info Blocks Section */}
      <section className="py-12 sm:py-24 px-4 sm:px-8 max-w-5xl mx-auto animate-fade-in-up">
        <div className="text-center mb-10 sm:mb-16">
          <h2 className="text-3xl sm:text-4xl font-black text-[#1E1D23] mb-2 sm:mb-4">Call us or visit place</h2>
          <p className="text-gray-400 font-medium text-base sm:text-lg italic">Ready to make your food as feast with us?</p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 mb-16 sm:mb-24">
          <div className="text-center p-6 sm:p-12 bg-white rounded-[24px] sm:rounded-[32px] border-2 border-gray-50 hover:border-[#FFC222]/20 hover:shadow-2xl transition-all duration-300 group">
            <div className="w-12 h-12 sm:w-16 sm:h-16 bg-[#F7F2E2] rounded-2xl flex items-center justify-center mx-auto mb-6 sm:mb-8 group-hover:bg-[#FFC222] transition-colors">
              <Phone className="w-5 h-5 sm:w-6 sm:h-6 text-[#FFC222] group-hover:text-white" />
            </div>
            <h4 className="text-lg sm:text-xl font-black text-[#1E1D23] mb-3 sm:mb-4">Phone:</h4>
            <p className="text-gray-400 font-medium mb-1 text-sm sm:text-base">+91 9786 506 786</p>
            <p className="text-gray-400 font-medium text-sm sm:text-base">+91 99 43 79 1212</p>
          </div>

          <div className="text-center p-6 sm:p-12 bg-white rounded-[24px] sm:rounded-[32px] border-2 border-gray-50 hover:border-[#FFC222]/20 hover:shadow-2xl transition-all duration-300 group">
            <div className="w-12 h-12 sm:w-16 sm:h-16 bg-[#F7F2E2] rounded-2xl flex items-center justify-center mx-auto mb-6 sm:mb-8 group-hover:bg-[#FFC222] transition-colors">
              <MapPin className="w-5 h-5 sm:w-6 sm:h-6 text-[#FFC222] group-hover:text-white" />
            </div>
            <h4 className="text-lg sm:text-xl font-black text-[#1E1D23] mb-3 sm:mb-4">Address:</h4>
            <p className="text-gray-400 font-medium leading-relaxed text-sm sm:text-base">
              No : 06, Kavimani St, Thamarai Nagar,<br />
              Kamarajar Salai, Madurai, Tamilnadu – 625 001
            </p>
          </div>

          <div className="text-center p-6 sm:p-12 bg-white rounded-[24px] sm:rounded-[32px] border-2 border-gray-50 hover:border-[#FFC222]/20 hover:shadow-2xl transition-all duration-300 group">
            <div className="w-12 h-12 sm:w-16 sm:h-16 bg-[#F7F2E2] rounded-2xl flex items-center justify-center mx-auto mb-6 sm:mb-8 group-hover:bg-[#FFC222] transition-colors">
              <Mail className="w-5 h-5 sm:w-6 sm:h-6 text-[#FFC222] group-hover:text-white" />
            </div>
            <h4 className="text-lg sm:text-xl font-black text-[#1E1D23] mb-3 sm:mb-4">Email:</h4>
            <p className="text-gray-400 font-medium mb-1 text-sm sm:text-base">goappalam@gmail.com</p>
            <p className="text-gray-400 font-medium text-sm sm:text-base">contactgoofficial@gmail.com</p>
          </div>
        </div>

        {/* Map and Form Section */}
        <div className="flex flex-col lg:flex-row gap-8 sm:gap-12 bg-white rounded-[32px] sm:rounded-[48px] overflow-hidden border-2 border-gray-50 p-6 sm:p-12 shadow-sm">
          {/* Leaflet Map */}
          <div className="lg:w-1/2 h-[300px] sm:h-[500px] rounded-[24px] sm:rounded-[32px] overflow-hidden z-0">
            <MapContainer center={position} zoom={13} scrollWheelZoom={false} className="h-full w-full">
              <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
              />
              <Marker position={position}>
                <Popup>
                  Go Appalam Office <br /> Madurai, Tamil Nadu
                </Popup>
              </Marker>
            </MapContainer>
          </div>

          {/* Contact Form */}
          <div className="lg:w-1/2">
            <h2 className="text-3xl sm:text-4xl font-black text-[#1E1D23] mb-2">Send us a message</h2>
            <p className="text-gray-400 font-medium italic mb-8 sm:mb-10 text-sm sm:text-base">Easiest way to get connected ? start typing..</p>
            
            <form className="space-y-4 sm:space-y-6" onSubmit={handleSubmit}>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <input 
                  id="contact-name"
                  name="name"
                  type="text" 
                  placeholder="Your name" 
                  value={formData.name}
                  onChange={handleChange}
                  disabled={loading}
                  className="w-full bg-gray-50 border-2 border-transparent p-3 sm:p-4 rounded-xl focus:border-[#FFC222] focus:bg-white outline-none transition-all font-medium text-sm sm:text-base disabled:opacity-50" 
                  required
                />
                <input 
                  id="contact-email"
                  name="email"
                  type="email" 
                  placeholder="Email" 
                  value={formData.email}
                  onChange={handleChange}
                  disabled={loading}
                  className="w-full bg-gray-50 border-2 border-transparent p-3 sm:p-4 rounded-xl focus:border-[#FFC222] focus:bg-white outline-none transition-all font-medium text-sm sm:text-base disabled:opacity-50" 
                  required
                />
              </div>
              <input 
                id="contact-subject"
                name="subject"
                type="text" 
                placeholder="Subject" 
                value={formData.subject}
                onChange={handleChange}
                disabled={loading}
                className="w-full bg-gray-50 border-2 border-transparent p-3 sm:p-4 rounded-xl focus:border-[#FFC222] focus:bg-white outline-none transition-all font-medium text-sm sm:text-base disabled:opacity-50" 
              />
              <textarea 
                id="contact-comment"
                name="comment"
                placeholder="Comment" 
                value={formData.comment}
                onChange={handleChange}
                disabled={loading}
                className="w-full bg-gray-50 border-2 border-transparent p-3 sm:p-4 rounded-xl focus:border-[#FFC222] focus:bg-white outline-none transition-all font-medium h-24 sm:h-32 resize-none text-sm sm:text-base disabled:opacity-50"
                required
              ></textarea>
              
              <button 
                type="submit"
                disabled={loading}
                className="w-full sm:w-auto bg-[#FFC222] hover:bg-[#1E1D23] text-[#1E1D23] hover:text-white px-10 sm:px-12 py-3 sm:py-4 rounded-xl font-black transition-all shadow-xl shadow-[#FFC222]/30 uppercase tracking-widest text-xs sm:text-sm flex items-center justify-center gap-2 disabled:opacity-50"
              >
                {loading && <Loader2 className="w-4 h-4 animate-spin" />}
                {loading ? 'Submitting...' : 'Submit'}
              </button>
            </form>
          </div>
        </div>
      </section>
    </main>
  );
};

export default Contact;
