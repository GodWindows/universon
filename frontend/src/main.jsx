import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { I18nProvider } from './i18n.jsx';
import { NotificationProvider } from './notifications.jsx';
import Landing from './pages/Landing.jsx';
import Login from './pages/Login.jsx';
import Dashboard from './pages/Dashboard.jsx';
import PublicProfile from './pages/PublicProfile.jsx';

createRoot(document.getElementById('root')).render(
    <StrictMode>
        <I18nProvider>
            <NotificationProvider>
                <BrowserRouter>
                    <Routes>
                        <Route path="/" element={<Landing />} />
                        <Route path="/login" element={<Login />} />
                        <Route path="/dashboard" element={<Dashboard />} />
                        {/* /@pseudo ; toute autre adresse rend l'écran « requête invalide » */}
                        <Route path="/:handle" element={<PublicProfile />} />
                        <Route path="*" element={<PublicProfile />} />
                    </Routes>
                </BrowserRouter>
            </NotificationProvider>
        </I18nProvider>
    </StrictMode>,
);
