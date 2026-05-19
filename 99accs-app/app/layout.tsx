import type { Metadata } from 'next';
import './globals.css';
import ScrollTop from '@/components/layout/ScrollTop';
import AuthModal from '@/components/modals/AuthModal';
import VendorScripts from '@/components/layout/VendorScripts';
import AuthHydrator from '@/components/layout/AuthHydrator';
import TopProgressBar from '@/components/ui/TopProgressBar';

export const metadata: Metadata = {
  title: '99Accs — Your Trusted Marketplace for Game Accounts',
  description: 'Buy and sell Valorant, Fortnite, and League of Legends accounts safely on 99Accs.',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="/bootstrap-grid.rtl.min.css" />
        <link rel="stylesheet" href="/fontawesome-all.min.css" />
        <link rel="stylesheet" href="/default-icons.css" />
        <link rel="stylesheet" href="/default.css" />
        <link rel="stylesheet" href="/animate.min.css" />
        <link rel="stylesheet" href="/magnific-popup.css" />
        <link rel="stylesheet" href="/swiper-bundle.min.css" />
        <link rel="stylesheet" href="/main.css" />
        <link rel="shortcut icon" href="/img/favicon.png" />
      </head>
      <body>
        <TopProgressBar />
        <ScrollTop />
        {children}
        <AuthModal />
        <VendorScripts />
        <AuthHydrator />
      </body>
    </html>
  );
}
