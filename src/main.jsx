import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import './index.css'

// Initialize Teams SDK when available
const initTeams = () => {
  if (window.microsoftTeams) {
    window.microsoftTeams.app.initialize().then(() => {
      window.microsoftTeams.app.notifySuccess();
      console.log('✅ Teams SDK initialized');
    }).catch((error) => {
      console.warn('⚠️ Teams SDK initialization failed:', error);
    });
  }
};

// Try to initialize immediately if SDK is already loaded
if (window.microsoftTeams) {
  initTeams();
} else {
  // Wait for SDK to load
  window.addEventListener('load', () => {
    if (window.microsoftTeams) {
      initTeams();
    }
  });
}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
)
