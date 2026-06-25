import { useState, useEffect } from 'react';
import { Branding } from '../types';

import { NODE_API_URL } from '../config/api';

export const useBranding = () => {
  const [branding, setBranding] = useState<Branding | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchBranding = async () => {
      try {
        const response = await fetch(`${NODE_API_URL}branding`);
        if (!response.ok) {
          throw new Error('Branding fetch failed');
        }
        const result = await response.json();
        if (result && result.data) {
          setBranding(result.data);
          
          // Apply primary color as CSS variable
          if (result.data.primary_color) {
            document.documentElement.style.setProperty('--primary-color', result.data.primary_color);
          }
          
          // Apply font family if needed (optional)
          if (result.data.primary_font) {
            document.body.style.fontFamily = `${result.data.primary_font}, sans-serif`;
          }

          // Update favicon
          if (result.data.favicon) {
            let link = document.querySelector("link[rel~='icon']") as HTMLLinkElement;
            if (!link) {
              link = document.createElement('link');
              link.rel = 'icon';
              document.getElementsByTagName('head')[0].appendChild(link);
            }
            link.href = result.data.favicon;
          }

          // Update page title
          if (result.data.site_title) {
            document.title = result.data.site_title;
          }
        }
      } catch (error) {
        console.error('Error fetching branding:', error);
        // Set default branding if fetch fails
        setBranding({
          site_title: "Go Appalam",
          logo: "/assests/uploads/2021/Contact/Logo.png",
          primary_color: "#FFC222",
          primary_font: "Gilroy",
          favicon: "/assests/uploads/2021/07/ICON-01-1-500x560.png"
        });
      } finally {
        setLoading(false);
      }
    };

    fetchBranding();
  }, []);

  return { branding, loading };
};
