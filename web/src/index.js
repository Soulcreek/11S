// File: web/src/index.js
// Description: The entry point for the React web application.

import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css'; // Import our custom CSS with animations
import App from './App';

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);

// The web vitals logic is not needed for our MVP, so it's safe to remove.
